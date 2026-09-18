<?php

namespace App\DTOs\ImportExport;

class ImportPreviewDTO
{
    /**
     * @param  string  $previewId  UUID untuk referensi session
     * @param  int  $totalRows  Total baris dalam file
     * @param  int  $validRows  Jumlah baris valid
     * @param  int  $errorRows  Jumlah baris error
     * @param  array<int, array{row: int, column: string, message: string}>  $errors  Detail error per baris (flat)
     * @param  array  $validData  Parsed valid rows (stored in cache)
     * @param  bool  $requiresQueue  true jika >500 rows
     * @param  array<int, array{row: int, index: int, identity: array, label: string, columns: array, messages: array, data: array}>  $invalidRows  Error dikelompokkan per baris
     * @param  string  $summary  Kalimat laporan siap tampil untuk pengguna
     */
    public function __construct(
        public readonly string $previewId,
        public readonly int $totalRows,
        public readonly int $validRows,
        public readonly int $errorRows,
        public readonly array $errors,
        public readonly array $validData,
        public readonly bool $requiresQueue,
        public readonly array $invalidRows = [],
        public readonly string $summary = '',
    ) {}

    /**
     * Create from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            previewId: $data['previewId'],
            totalRows: $data['totalRows'],
            validRows: $data['validRows'],
            errorRows: $data['errorRows'],
            errors: $data['errors'] ?? [],
            validData: $data['validData'] ?? [],
            requiresQueue: $data['requiresQueue'] ?? false,
            invalidRows: $data['invalidRows'] ?? [],
            summary: $data['summary'] ?? '',
        );
    }

    /**
     * Convert to array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'previewId' => $this->previewId,
            'totalRows' => $this->totalRows,
            'validRows' => $this->validRows,
            'errorRows' => $this->errorRows,
            'errors' => $this->errors,
            'validData' => $this->validData,
            'requiresQueue' => $this->requiresQueue,
            'invalidRows' => $this->invalidRows,
            'summary' => $this->summary,
        ];
    }
}
