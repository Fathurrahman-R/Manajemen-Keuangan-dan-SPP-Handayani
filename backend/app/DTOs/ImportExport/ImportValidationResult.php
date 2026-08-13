<?php

namespace App\DTOs\ImportExport;

/**
 * Result of validating a full set of import rows (used by SiswaImportService
 * and TagihanImportService's shared `validateRows()`). Consumed by the
 * upload path, the confirm-time revalidation gate, and the inline row-patch
 * path so all three build an ImportPreviewDTO from the same shape.
 */
class ImportValidationResult
{
    /**
     * @param  array  $rows  Semua baris (urutan asli, index = kunci patch)
     * @param  array  $validData  Baris yang lolos validasi
     * @param  array<int, array{row: int, column: string, message: string}>  $errors  Detail error per baris (flat)
     * @param  array<int, array{row: int, index: int, identity: array, label: string, columns: array, messages: array, data: array}>  $invalidRows
     * @param  string  $summary  Kalimat laporan siap tampil
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $validData,
        public readonly array $errors,
        public readonly array $invalidRows,
        public readonly string $summary,
        public readonly int $validCount,
        public readonly int $errorCount,
    ) {}
}
