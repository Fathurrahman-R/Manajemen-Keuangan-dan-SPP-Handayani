<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TagihanImportReferenceSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private array $jenisTagihanNames) {}

    public function title(): string
    {
        return 'Referensi';
    }

    public function headings(): array
    {
        return [
            'No',
            'Jenis Tagihan (tersedia)',
        ];
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->jenisTagihanNames as $index => $nama) {
            $data[] = [$index + 1, $nama];
        }

        if (empty($data)) {
            $data[] = [1, '(Tidak ada jenis tagihan untuk periode aktif)'];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
