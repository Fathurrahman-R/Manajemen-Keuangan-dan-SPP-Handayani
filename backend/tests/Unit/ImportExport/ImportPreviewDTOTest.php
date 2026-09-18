<?php

namespace Tests\Unit\ImportExport;

use App\DTOs\ImportExport\ImportPreviewDTO;
use PHPUnit\Framework\TestCase;

/**
 * Pure PHP unit test (no DB, no app boot).
 */
class ImportPreviewDTOTest extends TestCase
{
    public function test_to_array_includes_invalid_rows_and_summary(): void
    {
        $dto = new ImportPreviewDTO(
            previewId: 'abc-123',
            totalRows: 3,
            validRows: 2,
            errorRows: 1,
            errors: [['row' => 3, 'column' => 'nis', 'message' => 'NIS wajib diisi']],
            validData: [['nis' => '1'], ['nis' => '2']],
            requiresQueue: false,
            invalidRows: [['row' => 3, 'index' => 2, 'identity' => [], 'label' => 'Baris 3 (NIS belum diisi)', 'columns' => ['nis'], 'messages' => ['NIS wajib diisi'], 'data' => []]],
            summary: 'Import dibatalkan. 1 dari 3 baris tidak valid.',
        );

        $array = $dto->toArray();

        $this->assertSame('abc-123', $array['previewId']);
        $this->assertCount(1, $array['invalidRows']);
        $this->assertSame('Import dibatalkan. 1 dari 3 baris tidak valid.', $array['summary']);
        // Field errors lama tetap flat — TagihanImportTest bergantung pada bentuk ini.
        $this->assertSame([['row' => 3, 'column' => 'nis', 'message' => 'NIS wajib diisi']], $array['errors']);
    }

    public function test_from_array_defaults_invalid_rows_and_summary_when_absent(): void
    {
        $dto = ImportPreviewDTO::fromArray([
            'previewId' => 'abc-123',
            'totalRows' => 1,
            'validRows' => 1,
            'errorRows' => 0,
        ]);

        $this->assertSame([], $dto->errors);
        $this->assertSame([], $dto->invalidRows);
        $this->assertSame('', $dto->summary);
        $this->assertFalse($dto->requiresQueue);
    }

    public function test_from_array_round_trips_all_fields(): void
    {
        $original = [
            'previewId' => 'abc-123',
            'totalRows' => 2,
            'validRows' => 1,
            'errorRows' => 1,
            'errors' => [['row' => 2, 'column' => 'nis', 'message' => 'NIS wajib diisi']],
            'validData' => [['nis' => '1']],
            'requiresQueue' => true,
            'invalidRows' => [['row' => 2, 'index' => 1, 'identity' => [], 'label' => 'Baris 2', 'columns' => ['nis'], 'messages' => ['NIS wajib diisi'], 'data' => []]],
            'summary' => 'Import dibatalkan.',
        ];

        $dto = ImportPreviewDTO::fromArray($original);

        $this->assertSame($original, $dto->toArray());
    }
}
