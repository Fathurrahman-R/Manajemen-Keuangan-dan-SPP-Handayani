<?php

namespace App\Imports;

use App\Imports\Normalizers\TagihanRowNormalizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TagihanImportValidator implements ToCollection, WithHeadingRow, WithMultipleSheets
{
    /**
     * Restrict parsing to the first physical sheet ("Data Import").
     * The official template (TagihanImportTemplate) ships a second
     * "Referensi" sheet listing valid jenis_tagihan values for the
     * dropdown; without this, Excel::import() reads collection() once
     * per sheet and the reference rows get misparsed as bogus data rows
     * (every one flagged "NIS wajib diisi").
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

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

            $this->rows[] = TagihanRowNormalizer::normalize($rowArray);
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
}
