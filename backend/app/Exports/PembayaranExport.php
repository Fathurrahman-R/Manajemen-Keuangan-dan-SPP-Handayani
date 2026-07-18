<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PembayaranExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(
        private Builder $query,
    ) {}

    public function query(): Builder
    {
        return $this->query->with(['tagihan.siswa', 'tagihan.jenis_tagihan']);
    }

    public function headings(): array
    {
        return [
            'Kode Pembayaran',
            'Kode Tagihan',
            'NIS',
            'Nama Siswa',
            'Jenis Tagihan',
            'Tanggal Pembayaran',
            'Metode',
            'Jumlah Pembayaran',
            'Pembayar',
        ];
    }

    /**
     * @param  \App\Models\Pembayaran  $pembayaran
     */
    public function map($pembayaran): array
    {
        return [
            $pembayaran->kode_pembayaran,
            $pembayaran->kode_tagihan,
            $pembayaran->tagihan?->nis,
            $pembayaran->tagihan?->siswa?->nama,
            $pembayaran->tagihan?->jenis_tagihan?->nama,
            $pembayaran->tanggal,
            $pembayaran->metode,
            $pembayaran->jumlah,
            $pembayaran->pembayar,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
