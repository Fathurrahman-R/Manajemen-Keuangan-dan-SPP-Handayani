<?php

namespace App\Exports\Sheets;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class KasHarianPemasukanSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private Builder $query,
    ) {}

    public function title(): string
    {
        return 'Pemasukan';
    }

    public function query(): Builder
    {
        return $this->query->with(['tagihan.siswa', 'tagihan.jenis_tagihan']);
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kode Pembayaran',
            'NIS',
            'Nama Siswa',
            'Jenis Tagihan',
            'Metode',
            'Jumlah',
            'Pembayar',
        ];
    }

    /**
     * @param \App\Models\Pembayaran $pembayaran
     */
    public function map($pembayaran): array
    {
        return [
            $pembayaran->tanggal,
            $pembayaran->kode_pembayaran,
            $pembayaran->tagihan?->nis,
            $pembayaran->tagihan?->siswa?->nama,
            $pembayaran->tagihan?->jenis_tagihan?->nama,
            $pembayaran->metode,
            $pembayaran->jumlah,
            $pembayaran->pembayar,
        ];
    }
}
