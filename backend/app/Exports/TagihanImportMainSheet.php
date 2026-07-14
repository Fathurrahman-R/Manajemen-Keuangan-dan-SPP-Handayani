<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TagihanImportMainSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private array $jenisTagihanNames) {}

    public function title(): string
    {
        return 'Data Import';
    }

    public function headings(): array
    {
        return [
            'nis',
            'nama_siswa',
            'jenis_tagihan',
        ];
    }

    public function array(): array
    {
        return [
            [
                '12345',
                'Ahmad Fauzi',
                $this->jenisTagihanNames[0] ?? 'SPP Bulanan',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Apply dropdown validation for jenis_tagihan (column C)
        if (! empty($this->jenisTagihanNames)) {
            $optionString = '"'.implode(',', $this->jenisTagihanNames).'"';

            for ($row = 2; $row <= 101; $row++) {
                $cell = $sheet->getCell("C{$row}");
                $validation = $cell->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($optionString);
            }
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
