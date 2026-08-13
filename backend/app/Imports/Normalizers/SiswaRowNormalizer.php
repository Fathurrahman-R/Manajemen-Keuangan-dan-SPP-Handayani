<?php

namespace App\Imports\Normalizers;

class SiswaRowNormalizer
{
    /**
     * Normalize a raw siswa row: trim strings, convert numeric values,
     * normalize headings and known enum aliases.
     *
     * Shared by the Excel import path (SiswaImportValidator) and the
     * inline row-repair path (SiswaImportService::patchRow) so both
     * produce identical data for the same input.
     */
    public static function normalize(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = self::normalizeKey($key);

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
        if (! empty($normalized['jenis_kelamin'])) {
            $jk = strtolower(str_replace(' ', '', $normalized['jenis_kelamin']));
            if (in_array($jk, ['l', 'laki', 'laki-laki', 'lakilaki'])) {
                $normalized['jenis_kelamin'] = 'Laki-laki';
            } elseif (in_array($jk, ['p', 'perempuan', 'wanita'])) {
                $normalized['jenis_kelamin'] = 'Perempuan';
            }
        }

        // Normalize Agama
        if (! empty($normalized['agama'])) {
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
    private static function normalizeKey(string $key): string
    {
        return str_replace(' ', '_', strtolower(trim($key)));
    }
}
