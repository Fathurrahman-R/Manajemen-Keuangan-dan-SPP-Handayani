<?php

namespace App\Services\ImportExport;

use App\DTOs\ImportExport\ImportPreviewDTO;
use App\DTOs\ImportExport\ImportValidationResult;
use App\Exceptions\ImportHasInvalidRowsException;
use App\Imports\Normalizers\TagihanRowNormalizer;
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
        $result = $this->validateRows($rows, $branchId);

        $previewId = Str::uuid()->toString();

        // Cache all rows (not just the valid ones) so the file can never be
        // partially committed and so patchRow() can revise a specific row
        // without requiring the user to re-upload.
        Cache::put("import_preview:{$previewId}", [
            'importType' => 'tagihan',
            'branchId' => $branchId,
            'rows' => $result->rows,
            'validData' => $result->validData,
            'totalRows' => count($rows),
            'validRows' => $result->validCount,
            'errorRows' => $result->errorCount,
            'fileName' => $file->getClientOriginalName(),
        ], self::CACHE_TTL);

        return new ImportPreviewDTO(
            previewId: $previewId,
            totalRows: count($rows),
            validRows: $result->validCount,
            errorRows: $result->errorCount,
            errors: $result->errors,
            validData: $result->validData,
            requiresQueue: count($rows) > self::QUEUE_THRESHOLD,
            invalidRows: $result->invalidRows,
            summary: $result->summary,
        );
    }

    /**
     * Revise a single row within an existing preview session and revalidate
     * the whole file — a fix to one row can resolve or introduce duplicate
     * errors on other rows, so the whole file must be rechecked.
     */
    public function patchRow(string $previewId, int $rowIndex, array $data, int $branchId): ImportPreviewDTO
    {
        $cached = Cache::get("import_preview:{$previewId}");

        if (! $cached) {
            throw new \InvalidArgumentException('Sesi preview telah kedaluwarsa. Silakan upload ulang file.');
        }

        if (($cached['branchId'] ?? null) !== $branchId || ($cached['importType'] ?? null) !== 'tagihan') {
            throw new \InvalidArgumentException('Sesi preview tidak valid untuk cabang ini.');
        }

        $rows = $cached['rows'];

        if (! array_key_exists($rowIndex, $rows)) {
            throw new \InvalidArgumentException('Baris tidak ditemukan dalam sesi preview.');
        }

        $rows[$rowIndex] = TagihanRowNormalizer::normalize(array_merge($rows[$rowIndex], $data));

        $result = $this->validateRows($rows, $branchId);

        Cache::put("import_preview:{$previewId}", [
            'importType' => 'tagihan',
            'branchId' => $branchId,
            'rows' => $result->rows,
            'validData' => $result->validData,
            'totalRows' => count($rows),
            'validRows' => $result->validCount,
            'errorRows' => $result->errorCount,
            'fileName' => $cached['fileName'],
        ], self::CACHE_TTL);

        return new ImportPreviewDTO(
            previewId: $previewId,
            totalRows: count($rows),
            validRows: $result->validCount,
            errorRows: $result->errorCount,
            errors: $result->errors,
            validData: $result->validData,
            requiresQueue: count($rows) > self::QUEUE_THRESHOLD,
            invalidRows: $result->invalidRows,
            summary: $result->summary,
        );
    }

    /**
     * Validate a full set of rows and report errors grouped per row.
     * Shared by validate(), patchRow(), and confirm()'s revalidation gate.
     *
     * Periode aktif belum diatur is treated as a file-level error (thrown),
     * not a per-row one — previously every row repeated the same message,
     * misleadingly attributed to the `nis` column.
     */
    public function validateRows(array $rows, int $branchId): ImportValidationResult
    {
        $periodeAktif = TahunAjaran::getAktif($branchId);
        if (! $periodeAktif) {
            throw new \InvalidArgumentException('Periode aktif belum diatur untuk cabang ini.');
        }
        $tahunAjaranId = $periodeAktif->id;

        // Get existing siswa NIS values in this branch
        $existingSiswa = Siswa::where('branch_id', $branchId)
            ->pluck('nis')
            ->toArray();

        // Get existing JenisTagihan for this branch and tahun_ajaran
        $jenisTagihanRecords = JenisTagihan::where('branch_id', $branchId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->pluck('nama')
            ->toArray();

        // Get existing tagihan combinations (NIS + jenis_tagihan) for this tahun_ajaran
        // Kolom branch_id dan tahun_ajaran_id ada di kedua tabel, jadi wajib
        // diprefiks — tanpa itu MariaDB menolak query dengan "Column
        // 'branch_id' in WHERE is ambiguous" dan seluruh import gagal.
        $existingTagihan = Tagihan::where('tagihans.branch_id', $branchId)
            ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
            ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
            ->select('tagihans.nis', 'jenis_tagihans.nama as jenis_tagihan_nama')
            ->get()
            ->map(fn ($t) => strtolower($t->nis.'|'.$t->jenis_tagihan_nama))
            ->toArray();

        $validRows = [];
        $report = new ImportErrorReport('tagihan');

        // Registered for every row (valid or not) so a duplicate combination
        // is still caught even when its first occurrence errored for an
        // unrelated reason.
        $processedCombinations = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is header
            $rowErrors = $this->validateRow(
                $row,
                $rowNumber,
                $existingSiswa,
                $jenisTagihanRecords,
                $existingTagihan,
                $processedCombinations
            );

            if (! empty($row['nis']) && ! empty($row['jenis_tagihan'])) {
                $processedCombinations[] = strtolower($row['nis'].'|'.$row['jenis_tagihan']);
            }

            if (empty($rowErrors)) {
                $validRows[] = $row;
            } else {
                $report->add($index, $rowNumber, $row, $rowErrors);
            }
        }

        $totalRows = count($rows);
        $validCount = count($validRows);
        $errorCount = $totalRows - $validCount;

        return new ImportValidationResult(
            rows: $rows,
            validData: $validRows,
            errors: $report->flatErrors(),
            invalidRows: $report->invalidRows(),
            summary: $report->summary($totalRows),
            validCount: $validCount,
            errorCount: $errorCount,
        );
    }

    /**
     * Confirm and process the import (synchronous for ≤500 rows).
     * All-or-nothing: the whole file is revalidated here and rejected if any
     * row is still invalid, regardless of what the cached preview counts say.
     */
    public function confirm(string $previewId, int $branchId, int $userId): ImportBatch
    {
        $cached = Cache::get("import_preview:{$previewId}");

        if (! $cached) {
            throw new \InvalidArgumentException('Sesi preview telah kedaluwarsa. Silakan upload ulang file.');
        }

        if (($cached['branchId'] ?? null) !== $branchId) {
            throw new \InvalidArgumentException('Sesi preview tidak valid untuk cabang ini.');
        }

        // validateRows() throws if Periode Aktif is missing.
        $result = $this->validateRows($cached['rows'], $branchId);

        if ($result->errorCount > 0) {
            throw new ImportHasInvalidRowsException($result->summary, $result->invalidRows, $result->errors);
        }

        $periodeAktif = TahunAjaran::getAktif($branchId);

        $validData = $result->validData;
        $totalRows = count($cached['rows']);

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
            'error_count' => 0,
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

        if (! $cached) {
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

                if (! $jenisTagihan) {
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
        $import = new \App\Imports\TagihanImportValidator;
        Excel::import($import, $file);

        return $import->getRows();
    }

    /**
     * Validate a single row and return errors (empty array if valid).
     */
    private function validateRow(
        array $row,
        int $rowNumber,
        array $existingSiswa,
        array $jenisTagihanRecords,
        array $existingTagihan,
        array $processedCombinations
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
        if (! empty($row['nis']) && ! in_array((string) $row['nis'], $existingSiswa)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => "Siswa dengan NIS '{$row['nis']}' tidak ditemukan"];
        }

        // Jenis tagihan exists
        if (! empty($row['jenis_tagihan']) && ! in_array($row['jenis_tagihan'], $jenisTagihanRecords)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenis_tagihan', 'message' => "Jenis tagihan '{$row['jenis_tagihan']}' tidak ditemukan untuk periode aktif"];
        }

        // Duplicate check (existing in DB)
        if (! empty($row['nis']) && ! empty($row['jenis_tagihan'])) {
            $combination = strtolower($row['nis'].'|'.$row['jenis_tagihan']);

            if (in_array($combination, $existingTagihan)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => "Tagihan untuk NIS '{$row['nis']}' dengan jenis '{$row['jenis_tagihan']}' sudah ada"];
            }

            // Intra-file duplicate check
            if (in_array($combination, $processedCombinations)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => "Duplikat dalam file: NIS '{$row['nis']}' dengan jenis '{$row['jenis_tagihan']}'"];
            }
        }

        return $errors;
    }
}
