<?php

namespace App\Exports;

use App\Models\Kelas;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SiswaImportTemplate implements FromArray, WithHeadings, WithStyles
{
    private array $kelasNames = [];

    /**
     * Field definitions as [heading, sample, dropdownOptions|null].
     * Columns shown depend on jenjang, matching the create/edit siswa form:
     * MI collects NISN/asal_sekolah/kelas_diterima/tahun_diterima/ayah/ibu,
     * KB/TK collects wali instead — mirrors App\Exports\SiswaExport.
     */
    private const BASE_FIELDS = [
        'nis' => ['nis', '12345', null],
        'nama' => ['nama', 'Ahmad Fauzi', null],
        'jenis_kelamin' => ['jenis_kelamin', 'L', ['L', 'P']],
        'tempat_lahir' => ['tempat_lahir', 'Jakarta', null],
        'tanggal_lahir' => ['tanggal_lahir', '2015-05-15', null],
        'agama' => ['agama', 'Islam', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']],
        'alamat' => ['alamat', 'Jl. Merdeka No. 1', null],
        'jenjang' => ['jenjang', null, ['TK', 'MI', 'KB']], // sample filled dynamically per branch
        'kelas' => ['kelas', null, null], // sample + dropdown filled dynamically from branch kelas
        'kategori' => ['kategori', 'Reguler', null],
        'status' => ['status', 'Aktif', ['Aktif', 'Lulus', 'Pindah', 'Keluar']],
        'keterangan_siswa' => ['keterangan_siswa', 'Siswa pindahan', null],
    ];

    private const MI_FIELDS = [
        'nisn' => ['nisn', '1234567890', null],
        'asal_sekolah' => ['asal_sekolah', 'TK Melati', null],
        'kelas_diterima' => ['kelas_diterima', 'I', ['I', 'II', 'III', 'IV', 'V', 'VI']],
        'tahun_diterima' => ['tahun_diterima', '2023', null],
        'nama_ayah' => ['nama_ayah', 'Budi Santoso', null],
        'pendidikan_terakhir_ayah' => ['pendidikan_terakhir_ayah', 'S1', null],
        'pekerjaan_ayah' => ['pekerjaan_ayah', 'Wiraswasta', null],
        'email_ayah' => ['email_ayah', 'budi@example.com', null],
        'nama_ibu' => ['nama_ibu', 'Siti Aminah', null],
        'pendidikan_terakhir_ibu' => ['pendidikan_terakhir_ibu', 'S1', null],
        'pekerjaan_ibu' => ['pekerjaan_ibu', 'Guru', null],
        'email_ibu' => ['email_ibu', 'siti@example.com', null],
    ];

    private const NON_MI_FIELDS = [
        'nama_wali' => ['nama_wali', 'Andi Wijaya', null],
        'pekerjaan_wali' => ['pekerjaan_wali', 'Wiraswasta', null],
        'no_hp_wali' => ['no_hp_wali', '081234567890', null],
        'alamat_wali' => ['alamat_wali', 'Jl. Merdeka No. 2', null],
        'keterangan_wali' => ['keterangan_wali', '', null],
        'email_wali' => ['email_wali', 'andi@example.com', null],
    ];

    public function __construct(
        private int $branchId,
        private ?string $jenjang = null,
    ) {
        $kelasQuery = Kelas::where('branch_id', $branchId);
        if ($this->jenjang) {
            $kelasQuery->where('jenjang', $this->jenjang);
        }
        $this->kelasNames = $kelasQuery->pluck('nama')->unique()->toArray();
    }

    /**
     * Resolve the field set for the current jenjang (or all fields if none specified).
     *
     * @return array<string, array{0: string, 1: ?string, 2: ?array}>
     */
    private function fields(): array
    {
        return match ($this->jenjang) {
            'MI' => [...self::BASE_FIELDS, ...self::MI_FIELDS],
            'KB', 'TK' => [...self::BASE_FIELDS, ...self::NON_MI_FIELDS],
            default => [...self::BASE_FIELDS, ...self::MI_FIELDS, ...self::NON_MI_FIELDS],
        };
    }

    public function headings(): array
    {
        return array_column($this->fields(), 0);
    }

    public function array(): array
    {
        $kelas = Kelas::where('branch_id', $this->branchId)
            ->when($this->jenjang, fn ($q) => $q->where('jenjang', $this->jenjang))
            ->first();
        $jenjangSample = $this->jenjang ?? ($kelas ? $kelas->jenjang : 'MI');
        $kelasSample = $kelas ? $kelas->nama : 'Kelas 1A';

        $row = [];
        foreach ($this->fields() as $key => [$heading, $sample]) {
            $row[] = match ($key) {
                'jenjang' => $jenjangSample,
                'kelas' => $kelasSample,
                default => $sample,
            };
        }

        return [$row];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = 1000;

        foreach (array_values($this->fields()) as $index => [$heading, $sample, $options]) {
            $column = Coordinate::stringFromColumnIndex($index + 1);

            if ($heading === 'kelas') {
                if (! empty($this->kelasNames)) {
                    $this->addDropdownValidation($sheet, $column, 2, $lastRow, $this->kelasNames);
                }

                continue;
            }

            if ($options !== null) {
                $this->addDropdownValidation($sheet, $column, 2, $lastRow, $options);
            }
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Add dropdown data validation to a range of cells.
     */
    private function addDropdownValidation(Worksheet $sheet, string $column, int $startRow, int $endRow, array $options): void
    {
        $optionString = '"'.implode(',', $options).'"';

        for ($row = $startRow; $row <= min($startRow + 99, $endRow); $row++) {
            $cell = $sheet->getCell("{$column}{$row}");
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($optionString);
        }
    }
}
