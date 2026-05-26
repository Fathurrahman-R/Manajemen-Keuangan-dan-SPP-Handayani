<?php

namespace App\Exports\Sheets;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class KasHarianRingkasanSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Builder $pemasukanQuery,
        private Builder $pengeluaranQuery,
        private int $bulan,
        private int $tahun,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return [
            'Keterangan',
            'Jumlah',
        ];
    }

    public function collection()
    {
        $totalPemasukan = (clone $this->pemasukanQuery)->sum('jumlah');
        $totalPengeluaran = (clone $this->pengeluaranQuery)->sum('jumlah');
        $saldo = $totalPemasukan - $totalPengeluaran;

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return collect([
            ['Periode', $namaBulan[$this->bulan] . ' ' . $this->tahun],
            ['Total Pemasukan', $totalPemasukan],
            ['Total Pengeluaran', $totalPengeluaran],
            ['Saldo', $saldo],
        ]);
    }
}
