<?php

namespace App\Exports;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SiswaExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    public function __construct(
        private Builder $query,
        private ?int $tahunAjaranId = null,
    ) {}

    public function query(): Builder
    {
        return $this->query->with(['ayah', 'ibu', 'wali', 'kategori']);
    }

    public function headings(): array
    {
        return [
            'NIS',
            'NISN',
            'Nama',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Alamat',
            'Jenjang',
            'Kelas',
            'Kategori',
            'Status',
            'Tahun Diterima',
            'Nama Ayah',
            'Pekerjaan Ayah',
            'Nama Ibu',
            'Pekerjaan Ibu',
            'Nama Wali',
            'Pekerjaan Wali',
            'No HP Wali',
        ];
    }

    /**
     * @param \App\Models\Siswa $siswa
     */
    public function map($siswa): array
    {
        // Resolve kelas name from the joined siswa_kelas or fallback to direct relation
        $kelasName = $this->resolveKelasName($siswa);

        return [
            $siswa->nis,
            $siswa->nisn,
            $siswa->nama,
            $siswa->jenis_kelamin,
            $siswa->tempat_lahir,
            $siswa->tanggal_lahir,
            $siswa->agama,
            $siswa->alamat,
            $siswa->jenjang,
            $kelasName,
            $siswa->kategori?->nama,
            $siswa->status,
            $siswa->tahun_diterima,
            $siswa->ayah?->nama,
            $siswa->ayah?->pekerjaan,
            $siswa->ibu?->nama,
            $siswa->ibu?->pekerjaan,
            $siswa->wali?->nama,
            $siswa->wali?->pekerjaan,
            $siswa->wali?->no_hp,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Resolve kelas name from the resolved_kelas_id (joined from siswa_kelas)
     * or fallback to the direct kelas relationship.
     */
    private function resolveKelasName($siswa): ?string
    {
        // If we have a resolved_kelas_id from the join, use it
        if (!empty($siswa->resolved_kelas_id)) {
            $kelas = Kelas::find($siswa->resolved_kelas_id);
            return $kelas?->nama;
        }

        // Fallback to direct kelas relation
        return $siswa->kelas?->nama;
    }
}
