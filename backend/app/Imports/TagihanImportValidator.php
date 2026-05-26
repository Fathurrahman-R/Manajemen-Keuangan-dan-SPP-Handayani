<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TagihanImportValidator implements ToCollection, WithHeadingRow
{
    private array $rows = [];

    /**
     * Process the collection of rows from the uploaded file.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Skip completely empty rows
            $rowArray = $row->toArray();
            if ($this->isEmptyRow($rowArray)) {
                continue;
            }

            $this->rows[] = $this->normalizeRow($rowArray);
        }
    }

    /**
     * Get all parsed rows.
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Check if a row is completely empty.
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Normalize row data: trim strings, convert numeric values.
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            $normalized[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        // Ensure NIS is string
        if (isset($normalized['nis'])) {
            $normalized['nis'] = (string) $normalized['nis'];
        }

        return $normalized;
    }

    /**
     * Normalize column header key (lowercase, underscores).
     */
    private function normalizeKey(string $key): string
    {
        return str_replace(' ', '_', strtolower(trim($key)));
    }
}
