<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RekapBulananRingkasanSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private array $summary,
        private int $tahun,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return [
            'Bulan',
            'Total Pemasukan',
            'Total Pengeluaran',
            'Saldo',
        ];
    }

    public function collection()
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return collect($this->summary)->map(function ($item) use ($namaBulan) {
            return [
                'bulan' => $namaBulan[$item['bulan']] ?? $item['bulan'],
                'total_pemasukan' => $item['total_pemasukan'],
                'total_pengeluaran' => $item['total_pengeluaran'],
                'saldo' => $item['saldo'],
            ];
        });
    }
}
