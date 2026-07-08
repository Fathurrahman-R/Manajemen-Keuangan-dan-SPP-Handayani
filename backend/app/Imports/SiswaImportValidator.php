<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SiswaImportValidator implements ToCollection, WithHeadingRow
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
            
            // Handle Excel dates
            if ($normalizedKey === 'tanggal_lahir' && is_numeric($value)) {
                try {
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    // fallback to the original value if it can't be parsed
                }
            }
            
            $normalized[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        // Ensure NIS and NISN are strings
        if (isset($normalized['nis'])) {
            $normalized['nis'] = (string) $normalized['nis'];
        }
        if (isset($normalized['nisn'])) {
            $normalized['nisn'] = (string) $normalized['nisn'];
        }

        // Normalize Jenis Kelamin
        if (!empty($normalized['jenis_kelamin'])) {
            $jk = strtolower(str_replace(' ', '', $normalized['jenis_kelamin']));
            if (in_array($jk, ['l', 'laki', 'laki-laki', 'lakilaki'])) {
                $normalized['jenis_kelamin'] = 'Laki-laki';
            } elseif (in_array($jk, ['p', 'perempuan', 'wanita'])) {
                $normalized['jenis_kelamin'] = 'Perempuan';
            }
        }

        // Normalize Agama
        if (!empty($normalized['agama'])) {
            $agama = strtolower(trim($normalized['agama']));
            if ($agama === 'protestan') {
                $normalized['agama'] = 'Kristen';
            } else {
                $normalized['agama'] = ucfirst($agama);
            }
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
