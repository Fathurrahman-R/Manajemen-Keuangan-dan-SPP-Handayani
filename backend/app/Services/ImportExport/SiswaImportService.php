<?php

namespace App\Services\ImportExport;

use App\DTOs\ImportExport\ImportPreviewDTO;
use App\Models\Ayah;
use App\Models\Ibu;
use App\Models\ImportBatch;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Wali;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SiswaImportService
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
     * Allowed agama values.
     */
    private const ALLOWED_AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

    /**
     * Validate the uploaded import file and return a preview.
     */
    public function validate(UploadedFile $file, int $branchId): ImportPreviewDTO
    {
        $rows = $this->parseFile($file);

        $validRows = [];
        $errors = [];

        // Get existing NIS values in this branch for duplicate checking
        $existingNis = Siswa::where('branch_id', $branchId)
            ->pluck('nis')
            ->toArray();

        // Get existing Kelas records for this branch
        $kelasRecords = Kelas::where('branch_id', $branchId)
            ->get()
            ->keyBy(function ($kelas) {
                return strtolower($kelas->nama . '|' . $kelas->jenjang);
            });

        // Track NIS within the file to detect intra-file duplicates
        $nisInFile = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is header, data starts at row 2
            $rowErrors = $this->validateRow($row, $rowNumber, $branchId, $existingNis, $kelasRecords, $nisInFile);

            if (empty($rowErrors)) {
                $validRows[] = $row;
                $nisInFile[] = $row['nis'] ?? null;
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
            'import_type' => 'siswa',
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
            'import_type' => 'siswa',
            'file_name' => $cached['fileName'],
            'total_rows' => $cached['totalRows'],
            'success_count' => 0,
            'error_count' => $cached['errorRows'],
            'status' => 'processing',
            'branch_id' => $branchId,
        ]);

        dispatch(new \App\Jobs\ProcessImportJob(
            previewId: $previewId,
            importType: 'siswa',
            branchId: $branchId,
            userId: $userId,
            batchId: $batch->batch_reference,
        ));

        return $batch;
    }

    /**
     * Process valid rows and insert into database.
     * Used by both sync confirm and queue job.
     */
    public function processRows(array $validData, int $branchId, string $batchReference, int $tahunAjaranId): int
    {
        $successCount = 0;

        DB::transaction(function () use ($validData, $branchId, $batchReference, $tahunAjaranId, &$successCount) {
            foreach ($validData as $row) {
                // Create Ayah record if parent data present
                $ayahId = null;
                if (!empty($row['nama_ayah'])) {
                    $ayah = Ayah::create([
                        'nama' => $row['nama_ayah'],
                        'pendidikan_terakhir' => $row['pendidikan_terakhir_ayah'] ?? null,
                        'pekerjaan' => $row['pekerjaan_ayah'] ?? null,
                        'email' => $row['email_ayah'] ?? null,
                    ]);
                    $ayahId = $ayah->id;
                }

                // Create Ibu record if parent data present
                $ibuId = null;
                if (!empty($row['nama_ibu'])) {
                    $ibu = Ibu::create([
                        'nama' => $row['nama_ibu'],
                        'pendidikan_terakhir' => $row['pendidikan_terakhir_ibu'] ?? null,
                        'pekerjaan' => $row['pekerjaan_ibu'] ?? null,
                        'email' => $row['email_ibu'] ?? null,
                    ]);
                    $ibuId = $ibu->id;
                }

                // Create Wali record if wali data present
                $waliId = null;
                if (!empty($row['nama_wali'])) {
                    $wali = Wali::create([
                        'nama' => $row['nama_wali'],
                        'pekerjaan' => $row['pekerjaan_wali'] ?? null,
                        'no_hp' => $row['no_hp_wali'] ?? null,
                        'alamat' => $row['alamat_wali'] ?? null,
                        'keterangan' => $row['keterangan_wali'] ?? null,
                        'email' => $row['email_wali'] ?? null,
                    ]);
                    $waliId = $wali->id;
                }

                // Resolve kelas_id
                $kelasId = null;
                if (!empty($row['kelas'])) {
                    $jenjang = $row['jenjang'] ?? null;
                    $kelas = Kelas::where('branch_id', $branchId)
                        ->where('nama', $row['kelas'])
                        ->when($jenjang, fn($q) => $q->where('jenjang', $jenjang))
                        ->first();
                    $kelasId = $kelas?->id;
                }

                // Resolve kategori_id
                $kategoriId = null;
                if (!empty($row['kategori'])) {
                    $kategori = \App\Models\Kategori::where('nama', $row['kategori'])->first();
                    $kategoriId = $kategori?->id;
                }

                // Create Siswa record
                $siswa = Siswa::create([
                    'nis' => $row['nis'],
                    'nisn' => $row['nisn'] ?? null,
                    'nama' => $row['nama'],
                    'jenis_kelamin' => $row['jenis_kelamin'],
                    'tempat_lahir' => $row['tempat_lahir'] ?? null,
                    'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
                    'agama' => $row['agama'] ?? null,
                    'alamat' => $row['alamat'] ?? null,
                    'jenjang' => $row['jenjang'],
                    'kelas_id' => $kelasId,
                    'kategori_id' => $kategoriId,
                    'asal_sekolah' => $row['asal_sekolah'] ?? null,
                    'kelas_diterima' => $row['kelas_diterima'] ?? null,
                    'tahun_diterima' => $row['tahun_diterima'] ?? null,
                    'status' => $row['status'] ?? 'Aktif',
                    'keterangan' => $row['keterangan_siswa'] ?? null,
                    'ayah_id' => $ayahId,
                    'ibu_id' => $ibuId,
                    'wali_id' => $waliId,
                    'branch_id' => $branchId,
                    'batch_reference' => $batchReference,
                ]);

                // Create SiswaKelas record if kelas resolved
                if ($kelasId) {
                    SiswaKelas::create([
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $kelasId,
                        'tahun_ajaran_id' => $tahunAjaranId,
                    ]);
                }

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
        $import = new \App\Imports\SiswaImportValidator();
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
        array $existingNis,
        $kelasRecords,
        array $nisInFile
    ): array {
        $errors = [];

        // Required fields
        if (empty($row['nis'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS wajib diisi'];
        }
        if (empty($row['nama'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nama', 'message' => 'Nama wajib diisi'];
        }
        if (empty($row['jenis_kelamin'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenis_kelamin', 'message' => 'Jenis kelamin wajib diisi'];
        }
        if (empty($row['jenjang'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenjang', 'message' => 'Jenjang wajib diisi'];
        }

        // NIS format: numeric, max 20 chars
        if (!empty($row['nis'])) {
            $nis = (string) $row['nis'];
            if (!ctype_digit($nis)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS harus berupa angka'];
            } elseif (strlen($nis) > 20) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS maksimal 20 karakter'];
            }
        }

        // NISN format: numeric, exactly 10 digits (if provided)
        if (!empty($row['nisn'])) {
            $nisn = (string) $row['nisn'];
            if (!ctype_digit($nisn) || strlen($nisn) !== 10) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nisn', 'message' => 'NISN harus berupa 10 digit angka'];
            }
        }

        // Jenis kelamin validation
        if (!empty($row['jenis_kelamin']) && !in_array($row['jenis_kelamin'], ['Laki-laki', 'Perempuan'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenis_kelamin', 'message' => 'Jenis kelamin harus Laki-laki atau Perempuan'];
        }

        // Jenjang validation
        if (!empty($row['jenjang']) && !in_array($row['jenjang'], ['TK', 'MI', 'KB'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenjang', 'message' => 'Jenjang harus TK, MI, atau KB'];
        }

        // Tanggal lahir format
        if (!empty($row['tanggal_lahir'])) {
            $date = $row['tanggal_lahir'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'tanggal_lahir', 'message' => 'Format tanggal lahir harus YYYY-MM-DD'];
            }
        }

        // Agama validation
        if (!empty($row['agama']) && !in_array($row['agama'], self::ALLOWED_AGAMA)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'agama', 'message' => 'Agama tidak valid. Pilihan: ' . implode(', ', self::ALLOWED_AGAMA)];
        }

        // Duplicate NIS check (existing in DB)
        if (!empty($row['nis']) && in_array((string) $row['nis'], $existingNis)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS sudah terdaftar di sistem'];
        }

        // Duplicate NIS check (within file)
        if (!empty($row['nis']) && in_array((string) $row['nis'], $nisInFile)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS duplikat dalam file'];
        }

        // Kelas validation (if provided)
        if (!empty($row['kelas']) && !empty($row['jenjang'])) {
            $key = strtolower($row['kelas'] . '|' . $row['jenjang']);
            if (!$kelasRecords->has($key)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'kelas', 'message' => "Kelas '{$row['kelas']}' tidak ditemukan untuk jenjang '{$row['jenjang']}'"];
            }
        }

        return $errors;
    }
}
