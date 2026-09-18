<?php

namespace Tests\Unit\ImportExport;

use App\Services\ImportExport\ImportErrorReport;
use PHPUnit\Framework\TestCase;

/**
 * Pure PHP unit test (no DB, no app boot) — locks the shape and Indonesian
 * wording of the per-row error report consumed by ImportPreviewDTO and
 * rendered in ImportPreviewTable.
 */
class ImportErrorReportTest extends TestCase
{
    public function test_groups_multiple_errors_for_the_same_row(): void
    {
        $report = new ImportErrorReport('siswa');

        $report->add(0, 2, ['nis' => '000123'], [
            ['column' => 'nis', 'message' => 'NIS sudah terdaftar di sistem'],
            ['column' => 'agama', 'message' => 'Agama wajib diisi'],
        ]);

        $invalidRows = $report->invalidRows();

        $this->assertCount(1, $invalidRows);
        $this->assertSame(['nis', 'agama'], $invalidRows[0]['columns']);
        $this->assertSame(
            ['NIS sudah terdaftar di sistem', 'Agama wajib diisi'],
            $invalidRows[0]['messages']
        );
    }

    public function test_labels_row_with_nis_and_nisn_for_siswa(): void
    {
        $report = new ImportErrorReport('siswa');

        $report->add(0, 5, ['nis' => '000123', 'nisn' => '1234567890'], [
            ['column' => 'agama', 'message' => 'Agama wajib diisi'],
        ]);

        $invalidRows = $report->invalidRows();

        $this->assertSame('Baris 5 (NIS 000123 / NISN 1234567890)', $invalidRows[0]['label']);
        $this->assertSame(['nis' => '000123', 'nisn' => '1234567890'], $invalidRows[0]['identity']);
    }

    public function test_labels_row_without_nis_as_belum_diisi(): void
    {
        $report = new ImportErrorReport('siswa');

        $report->add(0, 7, ['nama' => 'Budi'], [
            ['column' => 'nis', 'message' => 'NIS wajib diisi'],
        ]);

        $this->assertSame('Baris 7 (NIS belum diisi)', $report->invalidRows()[0]['label']);
    }

    public function test_labels_row_with_nis_and_jenis_tagihan_for_tagihan(): void
    {
        $report = new ImportErrorReport('tagihan');

        $report->add(0, 3, ['nis' => '000123', 'jenis_tagihan' => 'SPP'], [
            ['column' => 'jenis_tagihan', 'message' => "Jenis tagihan 'SPP' tidak ditemukan untuk periode aktif"],
        ]);

        $this->assertSame('Baris 3 (NIS 000123 / SPP)', $report->invalidRows()[0]['label']);
    }

    public function test_flat_errors_carries_row_column_and_message(): void
    {
        $report = new ImportErrorReport('siswa');

        $report->add(0, 2, ['nis' => '000123'], [
            ['column' => 'nis', 'message' => 'NIS sudah terdaftar di sistem'],
        ]);

        $this->assertSame(
            [['row' => 2, 'column' => 'nis', 'message' => 'NIS sudah terdaftar di sistem']],
            $report->flatErrors()
        );
    }

    public function test_valid_row_is_not_recorded(): void
    {
        $report = new ImportErrorReport('siswa');

        $report->add(0, 2, ['nis' => '000123'], []);

        $this->assertFalse($report->hasErrors());
        $this->assertSame([], $report->invalidRows());
        $this->assertSame([], $report->flatErrors());
    }

    public function test_summary_mentions_row_number_and_identity(): void
    {
        $report = new ImportErrorReport('siswa');
        $report->add(0, 2, ['nis' => '000123'], [
            ['column' => 'nama', 'message' => 'Nama wajib diisi'],
        ]);

        $summary = $report->summary(10);

        $this->assertStringContainsString('1 dari 10 baris tidak valid', $summary);
        $this->assertStringContainsString('baris 2 (NIS 000123)', $summary);
        $this->assertStringContainsString('Tidak ada data yang disimpan.', $summary);
    }

    public function test_summary_is_empty_when_no_errors(): void
    {
        $report = new ImportErrorReport('siswa');

        $this->assertSame('', $report->summary(10));
    }

    public function test_summary_truncates_after_ten_rows(): void
    {
        $report = new ImportErrorReport('siswa');

        for ($i = 1; $i <= 12; $i++) {
            $report->add($i, $i + 1, ['nis' => (string) (100 + $i)], [
                ['column' => 'nama', 'message' => 'Nama wajib diisi'],
            ]);
        }

        $summary = $report->summary(12);

        $this->assertStringContainsString('12 dari 12 baris tidak valid', $summary);
        $this->assertStringContainsString('dan 2 baris lainnya', $summary);
    }
}
