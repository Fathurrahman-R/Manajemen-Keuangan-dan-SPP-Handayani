<?php

namespace App\Exports;

use App\Models\Kelas;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SiswaImportTemplate implements FromArray, WithHeadings, WithStyles
{
    private array $kelasNames = [];

    public function __construct(private int $branchId)
    {
        $this->kelasNames = Kelas::where('branch_id', $branchId)
            ->pluck('nama')
            ->unique()
            ->toArray();
    }

    public function headings(): array
    {
        return [
            'nis',
            'nisn',
            'nama',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'agama',
            'alamat',
            'jenjang',
            'kelas',
            'kategori',
            'asal_sekolah',
            'kelas_diterima',
            'status',
            'tahun_diterima',
            'keterangan_siswa',
            'nama_ayah',
            'pendidikan_terakhir_ayah',
            'pekerjaan_ayah',
            'email_ayah',
            'nama_ibu',
            'pendidikan_terakhir_ibu',
            'pekerjaan_ibu',
            'email_ibu',
            'nama_wali',
            'pekerjaan_wali',
            'no_hp_wali',
            'alamat_wali',
            'keterangan_wali',
            'email_wali',
        ];
    }

    public function array(): array
    {
        $kelas = Kelas::where('branch_id', $this->branchId)->first();
        $jenjang = $kelas ? $kelas->jenjang : 'MI';
        $namaKelas = $kelas ? $kelas->nama : 'Kelas 1A';

        return [
            [
                '12345',
                '1234567890',
                'Ahmad Fauzi',
                'L',
                'Jakarta',
                '2015-05-15',
                'Islam',
                'Jl. Merdeka No. 1',
                $jenjang,
                $namaKelas,
                'Reguler',
                'TK Melati',
                '1',
                'Aktif',
                '2023',
                'Siswa pindahan',
                'Budi Santoso',
                'S1',
                'Wiraswasta',
                'budi@example.com',
                'Siti Aminah',
                'S1',
                'Guru',
                'siti@example.com',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Apply data validation dropdowns
        $lastRow = 1000; // Apply validation for up to 1000 rows

        // Jenis Kelamin (column D)
        $this->addDropdownValidation($sheet, 'D', 2, $lastRow, ['L', 'P']);

        // Agama (column G)
        $this->addDropdownValidation($sheet, 'G', 2, $lastRow, ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']);

        // Jenjang (column I)
        $this->addDropdownValidation($sheet, 'I', 2, $lastRow, ['TK', 'MI', 'KB']);

        // Kelas (column J)
        if (!empty($this->kelasNames)) {
            $this->addDropdownValidation($sheet, 'J', 2, $lastRow, $this->kelasNames);
        }

        // Status (column N)
        $this->addDropdownValidation($sheet, 'N', 2, $lastRow, ['Aktif', 'Non-Aktif', 'Lulus', 'Pindah']);

        // Style the header row
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Add dropdown data validation to a range of cells.
     */
    private function addDropdownValidation(Worksheet $sheet, string $column, int $startRow, int $endRow, array $options): void
    {
        $optionString = '"' . implode(',', $options) . '"';

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
