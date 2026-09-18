<?php

namespace Tests\Unit\ImportExport;

use App\Imports\Normalizers\SiswaRowNormalizer;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pure PHP unit test (no DB, no app boot) — locks the row-normalization
 * behavior shared by the Excel import path (SiswaImportValidator) and the
 * inline row-repair path (SiswaImportService::patchRow) so both keep
 * producing identical output for the same input.
 */
class SiswaRowNormalizerTest extends TestCase
{
    public function test_normalizes_header_keys_to_snake_case(): void
    {
        $row = SiswaRowNormalizer::normalize(['Tempat Lahir' => 'Jakarta', ' Nama ' => 'Budi']);

        $this->assertSame('Jakarta', $row['tempat_lahir']);
        $this->assertSame('Budi', $row['nama']);
    }

    #[DataProvider('jenisKelaminAliasProvider')]
    public function test_normalizes_jenis_kelamin_aliases(string $input, string $expected): void
    {
        $row = SiswaRowNormalizer::normalize(['jenis_kelamin' => $input]);

        $this->assertSame($expected, $row['jenis_kelamin']);
    }

    public static function jenisKelaminAliasProvider(): array
    {
        return [
            'L' => ['L', 'Laki-laki'],
            'laki' => ['laki', 'Laki-laki'],
            'laki-laki' => ['laki-laki', 'Laki-laki'],
            'P' => ['P', 'Perempuan'],
            'perempuan' => ['perempuan', 'Perempuan'],
            'wanita' => ['wanita', 'Perempuan'],
        ];
    }

    public function test_normalizes_protestan_to_kristen(): void
    {
        $row = SiswaRowNormalizer::normalize(['agama' => 'protestan']);

        $this->assertSame('Kristen', $row['agama']);
    }

    public function test_normalizes_agama_casing(): void
    {
        $row = SiswaRowNormalizer::normalize(['agama' => 'islam']);

        $this->assertSame('Islam', $row['agama']);
    }

    public function test_casts_nis_and_nisn_to_string(): void
    {
        $row = SiswaRowNormalizer::normalize(['nis' => 123, 'nisn' => 456]);

        $this->assertSame('123', $row['nis']);
        $this->assertSame('456', $row['nisn']);
    }

    public function test_converts_excel_serial_date_to_ymd(): void
    {
        $serial = Date::dateTimeToExcel(new \DateTime('2015-05-20'));

        $row = SiswaRowNormalizer::normalize(['tanggal_lahir' => $serial]);

        $this->assertSame('2015-05-20', $row['tanggal_lahir']);
    }

    public function test_leaves_plain_ymd_date_string_untouched(): void
    {
        $row = SiswaRowNormalizer::normalize(['tanggal_lahir' => '2015-05-20']);

        $this->assertSame('2015-05-20', $row['tanggal_lahir']);
    }

    public function test_trims_string_values(): void
    {
        $row = SiswaRowNormalizer::normalize(['nama' => '  Budi  ']);

        $this->assertSame('Budi', $row['nama']);
    }
}
