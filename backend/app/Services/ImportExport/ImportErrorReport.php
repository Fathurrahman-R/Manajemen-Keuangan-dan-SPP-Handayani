<?php

namespace App\Services\ImportExport;

/**
 * Collects per-row validation errors during an import preview/patch pass and
 * exposes them in the shapes needed by ImportPreviewDTO: a flat legacy list
 * (kept for backward compatibility with existing callers/tests) and a
 * per-row grouped list carrying row identity (NIS/NISN for siswa, NIS +
 * jenis_tagihan for tagihan) for user-facing reporting.
 *
 * Shared by SiswaImportService and TagihanImportService so both build
 * reports with identical shape and wording.
 */
final class ImportErrorReport
{
    private const MAX_SUMMARY_ROWS = 10;

    /**
     * @var array<int, array{row: int, column: string, message: string}>
     */
    private array $flatErrors = [];

    /**
     * @var array<int, array{row: int, index: int, identity: array, label: string, columns: array, messages: array, data: array}>
     */
    private array $invalidRows = [];

    public function __construct(private readonly string $importType) {}

    /**
     * Register the errors found for a single row (no-op if $rowErrors is empty).
     *
     * @param  array<int, array{column: string, message: string}>  $rowErrors
     */
    public function add(int $index, int $rowNumber, array $row, array $rowErrors): void
    {
        if (empty($rowErrors)) {
            return;
        }

        foreach ($rowErrors as $error) {
            $this->flatErrors[] = [
                'row' => $rowNumber,
                'column' => $error['column'],
                'message' => $error['message'],
            ];
        }

        $identity = $this->identityOf($row);

        $this->invalidRows[] = [
            'row' => $rowNumber,
            'index' => $index,
            'identity' => $identity,
            'label' => $this->labelOf($rowNumber, $identity),
            'columns' => array_values(array_unique(array_column($rowErrors, 'column'))),
            'messages' => array_column($rowErrors, 'message'),
            'data' => $row,
        ];
    }

    public function hasErrors(): bool
    {
        return ! empty($this->invalidRows);
    }

    /**
     * @return array<int, array{row: int, column: string, message: string}>
     */
    public function flatErrors(): array
    {
        return $this->flatErrors;
    }

    /**
     * @return array<int, array{row: int, index: int, identity: array, label: string, columns: array, messages: array, data: array}>
     */
    public function invalidRows(): array
    {
        return $this->invalidRows;
    }

    /**
     * Build a ready-to-display Indonesian sentence summarizing the invalid rows.
     */
    public function summary(int $totalRows): string
    {
        if (! $this->hasErrors()) {
            return '';
        }

        $errorCount = count($this->invalidRows);

        $labels = array_map(
            fn (array $invalidRow): string => "baris {$invalidRow['row']} ({$this->identityText($invalidRow['identity'])})",
            array_slice($this->invalidRows, 0, self::MAX_SUMMARY_ROWS)
        );

        $listText = implode(', ', $labels);

        if ($errorCount > self::MAX_SUMMARY_ROWS) {
            $sisa = $errorCount - self::MAX_SUMMARY_ROWS;
            $listText .= ", dan {$sisa} baris lainnya";
        }

        return "Import dibatalkan. {$errorCount} dari {$totalRows} baris tidak valid: {$listText}. Tidak ada data yang disimpan.";
    }

    /**
     * @return array{nis?: string, nisn?: string, jenis_tagihan?: string}
     */
    private function identityOf(array $row): array
    {
        if ($this->importType === 'tagihan') {
            $identity = [];
            if (! empty($row['nis'])) {
                $identity['nis'] = (string) $row['nis'];
            }
            if (! empty($row['jenis_tagihan'])) {
                $identity['jenis_tagihan'] = (string) $row['jenis_tagihan'];
            }

            return $identity;
        }

        $identity = [];
        if (! empty($row['nis'])) {
            $identity['nis'] = (string) $row['nis'];
        }
        if (! empty($row['nisn'])) {
            $identity['nisn'] = (string) $row['nisn'];
        }

        return $identity;
    }

    private function labelOf(int $rowNumber, array $identity): string
    {
        return "Baris {$rowNumber} ({$this->identityText($identity)})";
    }

    private function identityText(array $identity): string
    {
        if ($this->importType === 'tagihan') {
            if (empty($identity['nis'])) {
                return 'NIS belum diisi';
            }

            $text = "NIS {$identity['nis']}";
            if (! empty($identity['jenis_tagihan'])) {
                $text .= " / {$identity['jenis_tagihan']}";
            }

            return $text;
        }

        if (empty($identity['nis']) && empty($identity['nisn'])) {
            return 'NIS belum diisi';
        }

        if (empty($identity['nis'])) {
            return "NISN {$identity['nisn']}";
        }

        $text = "NIS {$identity['nis']}";
        if (! empty($identity['nisn'])) {
            $text .= " / NISN {$identity['nisn']}";
        }

        return $text;
    }
}
