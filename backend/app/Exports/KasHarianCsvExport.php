<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KasHarianCsvExport implements FromCollection, WithHeadings
{
    public function __construct(
        private Builder $pemasukanQuery,
        private Builder $pengeluaranQuery,
        private int $bulan,
        private int $tahun,
    ) {}

    public function headings(): array
    {
        return [
            'Tanggal',
            'Tipe',
            'Uraian',
            'Jumlah',
        ];
    }

    public function collection()
    {
        $rows = collect();

        // Add pemasukan records
        $pemasukan = $this->pemasukanQuery->with(['tagihan.siswa', 'tagihan.jenis_tagihan'])->get();
        foreach ($pemasukan as $item) {
            $uraian = ($item->tagihan?->siswa?->nama ?? '-') . ' - ' . ($item->tagihan?->jenis_tagihan?->nama ?? '-');
            $rows->push([
                'tanggal' => $item->tanggal,
                'tipe' => 'Pemasukan',
                'uraian' => $uraian,
                'jumlah' => $item->jumlah,
            ]);
        }

        // Add pengeluaran records
        $pengeluaran = $this->pengeluaranQuery->get();
        foreach ($pengeluaran as $item) {
            $rows->push([
                'tanggal' => $item->tanggal,
                'tipe' => 'Pengeluaran',
                'uraian' => $item->uraian,
                'jumlah' => $item->jumlah,
            ]);
        }

        return $rows->sortBy('tanggal')->values();
    }
}
