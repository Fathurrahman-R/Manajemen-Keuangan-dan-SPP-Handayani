<?php

namespace App\Imports\Normalizers;

class TagihanRowNormalizer
{
    /**
     * Normalize a raw tagihan row: trim strings, convert numeric values,
     * normalize headings.
     *
     * Shared by the Excel import path (TagihanImportValidator) and the
     * inline row-repair path (TagihanImportService::patchRow) so both
     * produce identical data for the same input.
     */
    public static function normalize(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = self::normalizeKey($key);
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
    private static function normalizeKey(string $key): string
    {
        return str_replace(' ', '_', strtolower(trim($key)));
    }
}
