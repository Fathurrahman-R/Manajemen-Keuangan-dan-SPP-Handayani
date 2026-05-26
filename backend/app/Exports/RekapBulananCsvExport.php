<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapBulananCsvExport implements FromCollection, WithHeadings
{
    public function __construct(
        private array $summary,
        private Builder $pemasukanQuery,
        private Builder $pengeluaranQuery,
        private int $tahun,
    ) {}

    public function headings(): array
    {
        return [
            'Bulan',
            'Tipe',
            'Uraian',
            'Jumlah',
        ];
    }

    public function collection()
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $rows = collect();

        // Add pemasukan records
        $pemasukan = $this->pemasukanQuery->with(['tagihan.siswa', 'tagihan.jenis_tagihan'])->get();
        foreach ($pemasukan as $item) {
            $bulan = (int) date('n', strtotime($item->tanggal));
            $uraian = ($item->tagihan?->siswa?->nama ?? '-') . ' - ' . ($item->tagihan?->jenis_tagihan?->nama ?? '-');
            $rows->push([
                'bulan' => $namaBulan[$bulan] ?? $bulan,
                'tipe' => 'Pemasukan',
                'uraian' => $uraian,
                'jumlah' => $item->jumlah,
            ]);
        }

        // Add pengeluaran records
        $pengeluaran = $this->pengeluaranQuery->get();
        foreach ($pengeluaran as $item) {
            $bulan = (int) date('n', strtotime($item->tanggal));
            $rows->push([
                'bulan' => $namaBulan[$bulan] ?? $bulan,
                'tipe' => 'Pengeluaran',
                'uraian' => $item->uraian,
                'jumlah' => $item->jumlah,
            ]);
        }

        return $rows->sortBy('bulan')->values();
    }
}
