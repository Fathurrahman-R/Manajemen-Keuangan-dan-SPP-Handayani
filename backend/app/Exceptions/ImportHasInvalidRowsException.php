<?php

namespace App\Exceptions;

/**
 * Thrown by SiswaImportService::confirm() / TagihanImportService::confirm()
 * (and any other all-or-nothing gate) when the file being confirmed still
 * has invalid rows. Extends InvalidArgumentException so the existing
 * `catch (\InvalidArgumentException $e)` blocks in ImportExportController
 * keep working as a fallback; controllers should catch this type first to
 * return the structured invalid_rows/summary payload.
 */
class ImportHasInvalidRowsException extends \InvalidArgumentException
{
    /**
     * @param  array<int, array{row: int, index: int, identity: array, label: string, columns: array, messages: array, data: array}>  $invalidRows
     * @param  array<int, array{row: int, column: string, message: string}>  $errors
     */
    public function __construct(
        string $summary,
        public readonly array $invalidRows,
        public readonly array $errors,
    ) {
        parent::__construct($summary);
    }
}
