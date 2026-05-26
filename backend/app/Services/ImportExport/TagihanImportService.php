<?php

namespace App\Services\ImportExport;

use App\DTOs\ImportExport\ImportPreviewDTO;
use App\Models\ImportBatch;
use App\Models\JenisTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Services\GenerateKodeTagihan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class TagihanImportService
{
    /**
     * Queue threshold: files with more than 500 rows are processed via queue.
     */
    private const QUEUE_THRESHOLD = 500;

    /**
     * Cache TTL for preview data (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Validate the uploaded import file and return a preview.
     */
    public function validate(UploadedFile $file, int $branchId): ImportPreviewDTO
    {
        $rows = $this->parseFile($file);

        // Get Periode Aktif for validation
        $periodeAktif = TahunAjaran::getAktif($branchId);
        $tahunAjaranId = $periodeAktif?->id;

        // Get existing siswa NIS values in this branch
        $existingSiswa = Siswa::where('branch_id', $branchId)
            ->pluck('nis')
            ->toArray();

        // Get existing JenisTagihan for this branch and tahun_ajaran
        $jenisTagihanRecords = [];
        if ($tahunAjaranId) {
            $jenisTagihanRecords = JenisTagihan::where('branch_id', $branchId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->pluck('nama')
                ->toArray();
        }

        // Get existing tagihan combinations (NIS + jenis_tagihan) for this tahun_ajaran
        $existingTagihan = [];
        if ($tahunAjaranId) {
            $existingTagihan = Tagihan::where('branch_id', $branchId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
                ->select('tagihans.nis', 'jenis_tagihans.nama as jenis_tagihan_nama')
                ->get()
                ->map(fn($t) => strtolower($t->nis . '|' . $t->jenis_tagihan_nama))
                ->toArray();
        }

        $validRows = [];
        $errors = [];
        $processedCombinations = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is header
            $rowErrors = $this->validateRow(
                $row,
                $rowNumber,
                $branchId,
                $existingSiswa,
                $jenisTagihanRecords,
                $existingTagihan,
                $processedCombinations,
                $tahunAjaranId
            );

            if (empty($rowErrors)) {
                $validRows[] = $row;
                // Track this combination to detect intra-file duplicates
                if (!empty($row['nis']) && !empty($row['jenis_tagihan'])) {
                    $processedCombinations[] = strtolower($row['nis'] . '|' . $row['jenis_tagihan']);
                }
            } else {
                foreach ($rowErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        $totalRows = count($rows);
        $validCount = count($validRows);
        $errorCount = $totalRows - $validCount;
        $requiresQueue = $totalRows > self::QUEUE_THRESHOLD;

        $previewId = Str::uuid()->toString();

        // Cache valid data for later confirmation
        Cache::put("import_preview:{$previewId}", [
            'validData' => $validRows,
            'totalRows' => $totalRows,
            'validRows' => $validCount,
            'errorRows' => $errorCount,
            'fileName' => $file->getClientOriginalName(),
        ], self::CACHE_TTL);

        return new ImportPreviewDTO(
            previewId: $previewId,
            totalRows: $totalRows,
            validRows: $validCount,
            errorRows: $errorCount,
            errors: $errors,
            validData: $validRows,
            requiresQueue: $requiresQueue,
        );
    }

    /**
     * Confirm and process the import (synchronous for ≤500 rows).
     */
    public function confirm(string $previewId, int $branchId, int $userId): ImportBatch
    {
        $cached = Cache::get("import_preview:{$previewId}");

        if (!$cached) {
            throw new \InvalidArgumentException('Sesi preview telah kedaluwarsa. Silakan upload ulang file.');
        }

        // Check Periode_Aktif exists
        $periodeAktif = TahunAjaran::getAktif($branchId);
        if (!$periodeAktif) {
            throw new \InvalidArgumentException('Periode aktif belum diatur untuk cabang ini.');
        }

        $validData = $cached['validData'];
        $totalRows = $cached['totalRows'];
        $errorRows = $cached['errorRows'];

        // If requires queue, dispatch job instead
        if (count($validData) > self::QUEUE_THRESHOLD) {
            return $this->processInBackground($previewId, $branchId, $userId);
        }

        $batchReference = Str::uuid()->toString();

        // Create ImportBatch record
        $batch = ImportBatch::create([
            'batch_reference' => $batchReference,
            'user_id' => $userId,
            'import_type' => 'tagihan',
            'file_name' => $cached['fileName'],
            'total_rows' => $totalRows,
            'success_count' => 0,
            'error_count' => $errorRows,
            'status' => 'processing',
            'branch_id' => $branchId,
        ]);

        try {
            $successCount = $this->processRows($validData, $branchId, $batchReference, $periodeAktif->id);

            $batch->update([
                'success_count' => $successCount,
                'status' => 'completed',
            ]);
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Clear the cache
        Cache::forget("import_preview:{$previewId}");

        return $batch->fresh();
    }

    /**
     * Dispatch background processing for large imports.
     */
    public function processInBackground(string $previewId, int $branchId, int $userId): ImportBatch
    {
        $cached = Cache::get("import_preview:{$previewId}");

        if (!$cached) {
            throw new \InvalidArgumentException('Sesi preview telah kedaluwarsa. Silakan upload ulang file.');
        }

        $batchReference = Str::uuid()->toString();

        $batch = ImportBatch::create([
            'batch_reference' => $batchReference,
            'user_id' => $userId,
            'import_type' => 'tagihan',
            'file_name' => $cached['fileName'],
            'total_rows' => $cached['totalRows'],
            'success_count' => 0,
            'error_count' => $cached['errorRows'],
            'status' => 'processing',
            'branch_id' => $branchId,
        ]);

        dispatch(new \App\Jobs\ProcessImportJob(
            previewId: $previewId,
            importType: 'tagihan',
            branchId: $branchId,
            userId: $userId,
            batchId: $batch->batch_reference,
        ));

        return $batch;
    }

    /**
     * Process valid rows and insert tagihan records into database.
     * Used by both sync confirm and queue job.
     */
    public function processRows(array $validData, int $branchId, string $batchReference, int $tahunAjaranId): int
    {
        $successCount = 0;

        DB::transaction(function () use ($validData, $branchId, $batchReference, $tahunAjaranId, &$successCount) {
            foreach ($validData as $row) {
                // Resolve jenis_tagihan_id
                $jenisTagihan = JenisTagihan::where('branch_id', $branchId)
                    ->where('tahun_ajaran_id', $tahunAjaranId)
                    ->where('nama', $row['jenis_tagihan'])
                    ->first();

                if (!$jenisTagihan) {
                    continue; // Should not happen if validation passed
                }

                // Generate kode_tagihan
                $kodeTagihan = GenerateKodeTagihan::generate();

                // Create Tagihan record
                Tagihan::create([
                    'kode_tagihan' => $kodeTagihan,
                    'jenis_tagihan_id' => $jenisTagihan->id,
                    'nis' => $row['nis'],
                    'tmp' => 0,
                    'status' => 'Belum Lunas',
                    'branch_id' => $branchId,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'batch_reference' => $batchReference,
                ]);

                $successCount++;
            }
        });

        return $successCount;
    }

    /**
     * Parse the uploaded file into an array of rows.
     */
    private function parseFile(UploadedFile $file): array
    {
        $import = new \App\Imports\TagihanImportValidator();
        Excel::import($import, $file);

        return $import->getRows();
    }

    /**
     * Validate a single row and return errors (empty array if valid).
     */
    private function validateRow(
        array $row,
        int $rowNumber,
        int $branchId,
        array $existingSiswa,
        array $jenisTagihanRecords,
        array $existingTagihan,
        array $processedCombinations,
        ?int $tahunAjaranId
    ): array {
        $errors = [];

        // Required fields
        if (empty($row['nis'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS wajib diisi'];
        }
        if (empty($row['jenis_tagihan'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenis_tagihan', 'message' => 'Jenis tagihan wajib diisi'];
        }

        // NIS exists in siswa table
        if (!empty($row['nis']) && !in_array((string) $row['nis'], $existingSiswa)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => "Siswa dengan NIS '{$row['nis']}' tidak ditemukan"];
        }

        // Jenis tagihan exists
        if (!empty($row['jenis_tagihan']) && !in_array($row['jenis_tagihan'], $jenisTagihanRecords)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenis_tagihan', 'message' => "Jenis tagihan '{$row['jenis_tagihan']}' tidak ditemukan untuk periode aktif"];
        }

        // Duplicate check (existing in DB)
        if (!empty($row['nis']) && !empty($row['jenis_tagihan'])) {
            $combination = strtolower($row['nis'] . '|' . $row['jenis_tagihan']);

            if (in_array($combination, $existingTagihan)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => "Tagihan untuk NIS '{$row['nis']}' dengan jenis '{$row['jenis_tagihan']}' sudah ada"];
            }

            // Intra-file duplicate check
            if (in_array($combination, $processedCombinations)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => "Duplikat dalam file: NIS '{$row['nis']}' dengan jenis '{$row['jenis_tagihan']}'"];
            }
        }

        // Check if Periode Aktif exists (early validation)
        if (!$tahunAjaranId) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'Periode aktif belum diatur'];
        }

        return $errors;
    }
}
