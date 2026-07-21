<?php

namespace App\Exports;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SiswaExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    private const BASE_HEADINGS = [
        'NIS', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
        'Agama', 'Alamat', 'Jenjang', 'Kelas', 'Kategori', 'Status', 'Keterangan Siswa',
    ];

    private const MI_HEADINGS = [
        'NISN', 'Asal Sekolah', 'Kelas Diterima', 'Tahun Diterima',
        'Nama Ayah', 'Pendidikan Terakhir Ayah', 'Pekerjaan Ayah', 'Email Ayah',
        'Nama Ibu', 'Pendidikan Terakhir Ibu', 'Pekerjaan Ibu', 'Email Ibu',
    ];

    private const NON_MI_HEADINGS = [
        'Nama Wali', 'Pekerjaan Wali', 'No HP Wali', 'Alamat Wali', 'Keterangan Wali', 'Email Wali',
    ];

    public function __construct(
        private Builder $query,
        private ?int $tahunAjaranId = null,
        private ?string $jenjang = null,
    ) {}

    public function query(): Builder
    {
        return $this->query->with(['ayah', 'ibu', 'wali', 'kategori']);
    }

    /**
     * Columns shown depend on jenjang: MI students have ayah/ibu (no wali),
     * KB/TK students have wali (no ayah/ibu/NISN/asal_sekolah/kelas_diterima/tahun_diterima)
     * — matches the fields actually collected in the create/edit siswa form per jenjang.
     * When no jenjang filter is set (mixed export), show every column.
     */
    public function headings(): array
    {
        return match ($this->jenjang) {
            'MI' => [...self::BASE_HEADINGS, ...self::MI_HEADINGS],
            'KB', 'TK' => [...self::BASE_HEADINGS, ...self::NON_MI_HEADINGS],
            default => [...self::BASE_HEADINGS, ...self::MI_HEADINGS, ...self::NON_MI_HEADINGS],
        };
    }

    /**
     * @param  \App\Models\Siswa  $siswa
     */
    public function map($siswa): array
    {
        $kelasName = $this->resolveKelasName($siswa);

        $base = [
            $siswa->nis,
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
            $siswa->keterangan,
        ];

        $mi = [
            $siswa->nisn,
            $siswa->asal_sekolah,
            $siswa->kelas_diterima,
            $siswa->tahun_diterima,
            $siswa->ayah?->nama,
            $siswa->ayah?->pendidikan_terakhir,
            $siswa->ayah?->pekerjaan,
            $siswa->ayah?->email,
            $siswa->ibu?->nama,
            $siswa->ibu?->pendidikan_terakhir,
            $siswa->ibu?->pekerjaan,
            $siswa->ibu?->email,
        ];

        $nonMi = [
            $siswa->wali?->nama,
            $siswa->wali?->pekerjaan,
            $siswa->wali?->no_hp,
            $siswa->wali?->alamat,
            $siswa->wali?->keterangan,
            $siswa->wali?->email,
        ];

        return match ($this->jenjang) {
            'MI' => [...$base, ...$mi],
            'KB', 'TK' => [...$base, ...$nonMi],
            default => [...$base, ...$mi, ...$nonMi],
        };
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
        if (! empty($siswa->resolved_kelas_id)) {
            $kelas = Kelas::find($siswa->resolved_kelas_id);

            return $kelas?->nama;
        }

        // Fallback to direct kelas relation
        return $siswa->kelas?->nama;
    }
}
