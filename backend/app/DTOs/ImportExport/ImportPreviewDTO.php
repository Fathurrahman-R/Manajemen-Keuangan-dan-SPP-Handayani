<?php

namespace App\DTOs\ImportExport;

class ImportPreviewDTO
{
    /**
     * @param  string  $previewId  UUID untuk referensi session
     * @param  int  $totalRows  Total baris dalam file
     * @param  int  $validRows  Jumlah baris valid
     * @param  int  $errorRows  Jumlah baris error
     * @param  array<int, array{row: int, column: string, message: string}>  $errors  Detail error per baris
     * @param  array  $validData  Parsed valid rows (stored in cache)
     * @param  bool  $requiresQueue  true jika >500 rows
     */
    public function __construct(
        public readonly string $previewId,
        public readonly int $totalRows,
        public readonly int $validRows,
        public readonly int $errorRows,
        public readonly array $errors,
        public readonly array $validData,
        public readonly bool $requiresQueue,
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
        ];
    }
}
