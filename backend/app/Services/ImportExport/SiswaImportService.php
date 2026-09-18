<?php

namespace App\Services\ImportExport;

use App\DTOs\ImportExport\ImportPreviewDTO;
use App\DTOs\ImportExport\ImportValidationResult;
use App\Exceptions\ImportHasInvalidRowsException;
use App\Imports\Normalizers\SiswaRowNormalizer;
use App\Models\Ayah;
use App\Models\Ibu;
use App\Models\ImportBatch;
use App\Models\Kategori;
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
     * Allowed kelas_diterima values — must match the Roman-numeral options
     * used by the Select field in the siswa create/edit form (DataSiswa.php),
     * otherwise an imported value silently fails to display as selected.
     */
    private const ALLOWED_KELAS_DITERIMA = ['I', 'II', 'III', 'IV', 'V', 'VI'];

    /**
     * Allowed status values — must match the `status` enum on the `siswas`
     * table (2025_11_08_090937_create_siswas_table.php).
     */
    private const ALLOWED_STATUS = ['Aktif', 'Lulus', 'Pindah', 'Keluar'];

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
            'importType' => 'siswa',
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

        if (($cached['branchId'] ?? null) !== $branchId || ($cached['importType'] ?? null) !== 'siswa') {
            throw new \InvalidArgumentException('Sesi preview tidak valid untuk cabang ini.');
        }

        $rows = $cached['rows'];

        if (! array_key_exists($rowIndex, $rows)) {
            throw new \InvalidArgumentException('Baris tidak ditemukan dalam sesi preview.');
        }

        $rows[$rowIndex] = SiswaRowNormalizer::normalize(array_merge($rows[$rowIndex], $data));

        $result = $this->validateRows($rows, $branchId);

        Cache::put("import_preview:{$previewId}", [
            'importType' => 'siswa',
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
     */
    public function validateRows(array $rows, int $branchId): ImportValidationResult
    {
        // NIS and NISN are globally unique on the `siswas` table (not scoped
        // to branch), so duplicate checks must be global too — otherwise a
        // row can pass validation here and then fail with a raw SQL error
        // on insert.
        $existingNis = array_flip(Siswa::pluck('nis')->all());
        $existingNisn = array_flip(Siswa::whereNotNull('nisn')->pluck('nisn')->all());

        $kelasRecords = Kelas::where('branch_id', $branchId)
            ->get()
            ->keyBy(function ($kelas) {
                return strtolower($kelas->nama.'|'.$kelas->jenjang);
            });

        $kategoriRecords = array_flip(
            Kategori::where('branch_id', $branchId)->pluck('nama')->map(fn ($nama) => strtolower($nama))->all()
        );

        $validRows = [];
        $report = new ImportErrorReport('siswa');

        // Registered for every row (valid or not) so a duplicate NIS/NISN is
        // still caught even when its first occurrence errored for an
        // unrelated reason (e.g. missing nama).
        $nisInFile = [];
        $nisnInFile = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is header, data starts at row 2
            $rowErrors = $this->validateRow(
                $row,
                $rowNumber,
                $existingNis,
                $existingNisn,
                $kelasRecords,
                $kategoriRecords,
                $nisInFile,
                $nisnInFile
            );

            if (! empty($row['nis'])) {
                $nisInFile[] = (string) $row['nis'];
            }
            if (! empty($row['nisn'])) {
                $nisnInFile[] = (string) $row['nisn'];
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
     * row is still invalid, regardless of what the cached preview counts
     * say — the cache can be stale (e.g. another user inserted a
     * conflicting NIS between upload and confirm).
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

        // Check Periode_Aktif exists
        $periodeAktif = TahunAjaran::getAktif($branchId);
        if (! $periodeAktif) {
            throw new \InvalidArgumentException('Periode aktif belum diatur untuk cabang ini.');
        }

        $result = $this->validateRows($cached['rows'], $branchId);

        if ($result->errorCount > 0) {
            throw new ImportHasInvalidRowsException($result->summary, $result->invalidRows, $result->errors);
        }

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
            'import_type' => 'siswa',
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
                if (! empty($row['nama_ayah'])) {
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
                if (! empty($row['nama_ibu'])) {
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
                if (! empty($row['nama_wali'])) {
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
                if (! empty($row['kelas'])) {
                    $jenjang = $row['jenjang'] ?? null;
                    $kelas = Kelas::where('branch_id', $branchId)
                        ->where('nama', $row['kelas'])
                        ->when($jenjang, fn ($q) => $q->where('jenjang', $jenjang))
                        ->first();
                    $kelasId = $kelas?->id;
                }

                // Resolve kategori_id
                $kategoriId = null;
                if (! empty($row['kategori'])) {
                    $kategori = Kategori::where('nama', $row['kategori'])->first();
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
        $import = new \App\Imports\SiswaImportValidator;
        Excel::import($import, $file);

        return $import->getRows();
    }

    /**
     * Validate a single row and return errors (empty array if valid).
     */
    private function validateRow(
        array $row,
        int $rowNumber,
        array $existingNis,
        array $existingNisn,
        $kelasRecords,
        array $kategoriRecords,
        array $nisInFile,
        array $nisnInFile
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
        if (empty($row['tempat_lahir'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'tempat_lahir', 'message' => 'Tempat lahir wajib diisi'];
        }
        if (empty($row['tanggal_lahir'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'tanggal_lahir', 'message' => 'Tanggal lahir wajib diisi'];
        }
        if (empty($row['agama'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'agama', 'message' => 'Agama wajib diisi'];
        }
        if (empty($row['alamat'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'alamat', 'message' => 'Alamat wajib diisi'];
        }

        // NIS format: numeric, max 20 chars
        if (! empty($row['nis'])) {
            $nis = (string) $row['nis'];
            if (! ctype_digit($nis)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS harus berupa angka'];
            } elseif (strlen($nis) > 20) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS maksimal 20 karakter'];
            }
        }

        // NISN format: numeric, exactly 10 digits (if provided)
        if (! empty($row['nisn'])) {
            $nisn = (string) $row['nisn'];
            if (! ctype_digit($nisn) || strlen($nisn) !== 10) {
                $errors[] = ['row' => $rowNumber, 'column' => 'nisn', 'message' => 'NISN harus berupa 10 digit angka'];
            }
        }

        // Jenis kelamin validation
        if (! empty($row['jenis_kelamin']) && ! in_array($row['jenis_kelamin'], ['Laki-laki', 'Perempuan'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenis_kelamin', 'message' => 'Jenis kelamin harus Laki-laki atau Perempuan'];
        }

        // Jenjang validation
        if (! empty($row['jenjang']) && ! in_array($row['jenjang'], ['TK', 'MI', 'KB'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'jenjang', 'message' => 'Jenjang harus TK, MI, atau KB'];
        }

        // Tanggal lahir format
        if (! empty($row['tanggal_lahir'])) {
            $date = $row['tanggal_lahir'];
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'tanggal_lahir', 'message' => 'Format tanggal lahir harus YYYY-MM-DD'];
            }
        }

        // Agama validation
        if (! empty($row['agama']) && ! in_array($row['agama'], self::ALLOWED_AGAMA)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'agama', 'message' => 'Agama tidak valid. Pilihan: '.implode(', ', self::ALLOWED_AGAMA)];
        }

        // Kelas diterima validation: must be Roman numeral (I-VI) to match the edit form's Select options
        if (! empty($row['kelas_diterima']) && ! in_array($row['kelas_diterima'], self::ALLOWED_KELAS_DITERIMA)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'kelas_diterima', 'message' => 'Kelas diterima harus salah satu dari: '.implode(', ', self::ALLOWED_KELAS_DITERIMA)];
        }

        // Status validation
        if (! empty($row['status']) && ! in_array($row['status'], self::ALLOWED_STATUS)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'status', 'message' => 'Status harus salah satu dari: '.implode(', ', self::ALLOWED_STATUS)];
        }

        // Tahun diterima validation: 4-digit year (if provided)
        if (! empty($row['tahun_diterima']) && ! preg_match('/^\d{4}$/', (string) $row['tahun_diterima'])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'tahun_diterima', 'message' => 'Tahun diterima harus 4 digit angka'];
        }

        // Max length checks
        if (! empty($row['nama']) && strlen((string) $row['nama']) > 100) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nama', 'message' => 'Nama maksimal 100 karakter'];
        }
        if (! empty($row['tempat_lahir']) && strlen((string) $row['tempat_lahir']) > 100) {
            $errors[] = ['row' => $rowNumber, 'column' => 'tempat_lahir', 'message' => 'Tempat lahir maksimal 100 karakter'];
        }
        if (! empty($row['asal_sekolah']) && strlen((string) $row['asal_sekolah']) > 150) {
            $errors[] = ['row' => $rowNumber, 'column' => 'asal_sekolah', 'message' => 'Asal sekolah maksimal 150 karakter'];
        }

        // Duplicate NIS check (existing in DB — global, NIS is unique across branches)
        if (! empty($row['nis']) && isset($existingNis[(string) $row['nis']])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS sudah terdaftar di sistem'];
        }

        // Duplicate NIS check (within file)
        if (! empty($row['nis']) && in_array((string) $row['nis'], $nisInFile)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nis', 'message' => 'NIS duplikat dalam file'];
        }

        // Duplicate NISN check (existing in DB — global, NISN is unique across branches)
        if (! empty($row['nisn']) && isset($existingNisn[(string) $row['nisn']])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nisn', 'message' => "NISN '{$row['nisn']}' sudah terdaftar di sistem"];
        }

        // Duplicate NISN check (within file)
        if (! empty($row['nisn']) && in_array((string) $row['nisn'], $nisnInFile)) {
            $errors[] = ['row' => $rowNumber, 'column' => 'nisn', 'message' => 'NISN duplikat dalam file'];
        }

        // Kelas validation (if provided)
        if (! empty($row['kelas']) && ! empty($row['jenjang'])) {
            $key = strtolower($row['kelas'].'|'.$row['jenjang']);
            if (! $kelasRecords->has($key)) {
                $errors[] = ['row' => $rowNumber, 'column' => 'kelas', 'message' => "Kelas '{$row['kelas']}' tidak ditemukan untuk jenjang '{$row['jenjang']}'"];
            }
        }

        // Kategori validation (if provided)
        if (! empty($row['kategori']) && ! isset($kategoriRecords[strtolower($row['kategori'])])) {
            $errors[] = ['row' => $rowNumber, 'column' => 'kategori', 'message' => "Kategori '{$row['kategori']}' tidak ditemukan"];
        }

        return $errors;
    }
}
