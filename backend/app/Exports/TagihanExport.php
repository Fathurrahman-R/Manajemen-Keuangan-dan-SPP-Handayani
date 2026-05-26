<?php

namespace App\Exports;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TagihanExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    public function __construct(
        private Builder $query,
    ) {}

    public function query(): Builder
    {
        return $this->query->with(['siswa', 'jenis_tagihan', 'pembayaran']);
    }

    public function headings(): array
    {
        return [
            'Kode Tagihan',
            'NIS',
            'Nama Siswa',
            'Jenjang',
            'Kelas',
            'Jenis Tagihan',
            'Jumlah Tagihan',
            'Total Sudah Dibayar',
            'Sisa Tagihan',
            'Status',
            'Jatuh Tempo',
        ];
    }

    /**
     * @param \App\Models\Tagihan $tagihan
     */
    public function map($tagihan): array
    {
        $jumlahTagihan = $tagihan->jenis_tagihan?->jumlah ?? 0;
        $totalDibayar = $tagihan->tmp ?? 0;
        $sisaTagihan = $jumlahTagihan - $totalDibayar;

        // Resolve kelas name from siswa's kelas relations
        $kelasName = $this->resolveKelasName($tagihan->siswa);

        return [
            $tagihan->kode_tagihan,
            $tagihan->nis,
            $tagihan->siswa?->nama,
            $tagihan->siswa?->jenjang,
            $kelasName,
            $tagihan->jenis_tagihan?->nama,
            $jumlahTagihan,
            $totalDibayar,
            $sisaTagihan,
            $tagihan->status,
            $tagihan->jenis_tagihan?->jatuh_tempo,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Resolve kelas name from the siswa's current kelas.
     */
    private function resolveKelasName($siswa): ?string
    {
        if (!$siswa) {
            return null;
        }

        // Try to get kelas from siswa_kelas for the tagihan's tahun_ajaran
        $siswaKelas = $siswa->siswaKelas()
            ->orderByDesc('tahun_ajaran_id')
            ->first();

        if ($siswaKelas) {
            $kelas = Kelas::find($siswaKelas->kelas_id);
            return $kelas?->nama;
        }

        return $siswa->kelas?->nama;
    }
}
