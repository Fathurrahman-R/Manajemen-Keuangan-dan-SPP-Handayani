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
            'NIS/NISN',
            'Nama',
            'Nama Tagihan / Pengeluaran',
            'Pengaju',
            'Penyetuju',
            'Jumlah',
        ];
    }

    public function collection()
    {
        $rows = collect();

        // Add pemasukan records
        $pemasukan = $this->pemasukanQuery->with(['tagihan.siswa', 'tagihan.jenis_tagihan'])->get();
        foreach ($pemasukan as $item) {
            $rows->push([
                'tanggal' => $item->tanggal,
                'tipe' => 'Pemasukan',
                'nis' => $item->tagihan?->nis,
                'nama' => $item->tagihan?->siswa?->nama,
                'detail' => $item->tagihan?->jenis_tagihan?->nama,
                'pengaju' => null,
                'penyetuju' => null,
                'jumlah' => $item->jumlah,
            ]);
        }

        // Add pengeluaran records
        $pengeluaran = $this->pengeluaranQuery
            ->with(['pengeluaranRequest.requester', 'pengeluaranRequest.approvalLogs.user'])
            ->get();
        foreach ($pengeluaran as $item) {
            $rows->push([
                'tanggal' => $item->tanggal,
                'tipe' => 'Pengeluaran',
                'nis' => null,
                'nama' => null,
                'detail' => $item->uraian,
                'pengaju' => $item->pengaju_name,
                'penyetuju' => $item->penyetuju_name,
                'jumlah' => $item->jumlah,
            ]);
        }

        return $rows->sortBy('tanggal')->values();
    }
}
