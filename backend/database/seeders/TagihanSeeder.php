<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Database\Seeders\Support\SkemaTagihan;
use Database\Seeders\Support\TarifDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menerbitkan tagihan untuk SELURUH siswa di semua jenjang, bukan hanya 20
 * siswa MI seperti versi sebelumnya — dengan sampel sekecil itu halaman
 * tagihan TK/KB kosong dan laporan kas tidak mencerminkan apa pun.
 *
 * Jenis tagihan dicari lewat nama persis yang disusun SkemaTagihan, bukan
 * dicocokkan dengan substring. Kategori "Yatim" adalah awalan dari "Yatim
 * Piatu", jadi pencocokan longgar akan menagihkan SPP tarif yatim kepada siswa
 * yatim piatu yang seharusnya dibebaskan.
 *
 * Ditulis lewat insert batch: jumlahnya ribuan baris, dan satu query per baris
 * membuat seeding berjalan bermenit-menit tanpa alasan.
 */
class TagihanSeeder extends Seeder
{
    /** @var array<string, int> nomor urut kode tagihan per prefix bulan */
    private array $urutan = [];

    /** @var list<array<string, mixed>> */
    private array $buffer = [];

    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $tahunAjarans = TahunAjaran::where('branch_id', $branch->id)
                ->whereIn('nama', ['2025/2026', '2026/2027'])
                ->orderBy('nama')
                ->get();

            if ($tahunAjarans->isEmpty()) {
                continue;
            }

            $siswas = Siswa::with('kategori')->where('branch_id', $branch->id)->get();

            // Riwayat kelas menentukan siswa mana terdaftar di tahun ajaran mana.
            $terdaftar = DB::table('siswa_kelas')
                ->whereIn('siswa_id', $siswas->pluck('id'))
                ->get()
                ->groupBy('siswa_id');

            foreach ($tahunAjarans as $tahunAjaran) {
                $indeksJenis = JenisTagihan::where('branch_id', $branch->id)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->get()
                    ->keyBy('nama');

                $tahunDitutup = $tahunAjaran->status !== 'Aktif';

                foreach ($siswas as $siswa) {
                    $ikutTahunIni = ($terdaftar[$siswa->id] ?? collect())
                        ->contains(fn ($baris) => (int) $baris->tahun_ajaran_id === (int) $tahunAjaran->id);

                    if (! $ikutTahunIni) {
                        continue;
                    }

                    $this->terbitkanUntukSiswa($siswa, $tahunAjaran, $indeksJenis, $branch->id, $tahunDitutup);
                }
            }
        }

        $this->flush();
    }

    /**
     * @param  \Illuminate\Support\Collection<string, JenisTagihan>  $indeksJenis
     */
    private function terbitkanUntukSiswa(Siswa $siswa, TahunAjaran $tahunAjaran, $indeksJenis, int $branchId, bool $tahunDitutup): void
    {
        $kategori = $siswa->kategori?->nama ?? 'Umum';
        $siswaBaru = (int) $siswa->tahun_diterima === (int) substr($tahunAjaran->nama, 0, 4);
        $profil = $this->profilPembayar($tahunDitutup);

        $namaDitagih = [];

        // SPP bulanan — dilewati untuk kategori yang dibebaskan.
        if ($kategori !== TarifDemo::KATEGORI_BEBAS_SPP) {
            foreach (SkemaTagihan::bulanTerbit($tahunAjaran) as $bulan) {
                $namaDitagih[] = SkemaTagihan::namaSpp($siswa->jenjang, $kategori, $bulan);
            }
        }

        foreach (SkemaTagihan::tagihanTahunan($tahunAjaran) as $item) {
            // Uang pangkal & seragam hanya untuk siswa baru; daftar ulang
            // justru sebaliknya, hanya untuk siswa lama.
            if ($item['hanya_siswa_baru'] !== null && $item['hanya_siswa_baru'] !== $siswaBaru) {
                continue;
            }

            $namaDitagih[] = SkemaTagihan::namaTahunan($item['label'], $siswa->jenjang, $tahunAjaran->nama);
        }

        foreach ($namaDitagih as $nama) {
            $jenis = $indeksJenis[$nama] ?? null;

            if (! $jenis) {
                continue;
            }

            [$status, $tmp] = $this->tentukanStatus((float) $jenis->jumlah, $profil);

            $this->buffer[] = [
                'kode_tagihan' => $this->kodeTagihan($jenis->jatuh_tempo),
                'jenis_tagihan_id' => $jenis->id,
                'nis' => $siswa->nis,
                'tmp' => $tmp,
                'status' => $status,
                'branch_id' => $branchId,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'created_at' => $jenis->jatuh_tempo,
                'updated_at' => $jenis->jatuh_tempo,
            ];

            if (count($this->buffer) >= 1000) {
                $this->flush();
            }
        }
    }

    /**
     * Kebiasaan membayar melekat pada keluarga, bukan pada tiap lembar tagihan.
     *
     * Mengundi status tiap tagihan secara terpisah terlihat benar di angka
     * agregat — 70% lembar tagihan tetap lunas — tapi salah total di tingkat
     * siswa: dengan tujuh tagihan per anak, peluang seluruhnya lunas hanya
     * 0,7^7 atau sekitar 8%, sehingga dashboard melaporkan hampir semua siswa
     * menunggak. Keluarga sungguhan tidak begitu; yang tertib membayar tertib
     * terus, dan tunggakan menumpuk di sedikit keluarga.
     */
    private function profilPembayar(bool $tahunDitutup): string
    {
        if ($tahunDitutup) {
            // Menjelang kenaikan kelas hampir semua tagihan sudah diselesaikan.
            return fake()->randomElement(array_merge(
                array_fill(0, 93, 'patuh'),
                array_fill(0, 5, 'cicil'),
                array_fill(0, 2, 'nunggak'),
            ));
        }

        return fake()->randomElement(array_merge(
            array_fill(0, 70, 'patuh'),
            array_fill(0, 20, 'cicil'),
            array_fill(0, 10, 'nunggak'),
        ));
    }

    /**
     * @return array{0: string, 1: float}
     */
    private function tentukanStatus(float $jumlah, string $profil): array
    {
        $undian = fake()->numberBetween(1, 100);

        return match ($profil) {
            // Tertib: sesekali telat satu lembar, tapi tidak menunggak lama.
            'patuh' => $undian <= 94
                ? ['Lunas', $jumlah]
                : ['Belum Dibayar', 0.0],

            // Mencicil: sebagian lunas, sisanya dibayar separuh-separuh.
            'cicil' => match (true) {
                $undian <= 45 => ['Lunas', $jumlah],
                $undian <= 90 => ['Belum Lunas', $this->cicilan($jumlah)],
                default => ['Belum Dibayar', 0.0],
            },

            // Menunggak: mayoritas tagihan tidak tersentuh.
            default => match (true) {
                $undian <= 15 => ['Lunas', $jumlah],
                $undian <= 35 => ['Belum Lunas', $this->cicilan($jumlah)],
                default => ['Belum Dibayar', 0.0],
            },
        };
    }

    /**
     * Cicilan dibulatkan ke sepuluh ribu terdekat — orang tua membayar dengan
     * pecahan uang nyata, bukan angka pecahan hasil persentase. Tidak pernah
     * melebihi tagihannya sendiri, supaya tidak ada cicilan yang lebih besar
     * dari nilai yang dicicil.
     */
    private function cicilan(float $jumlah): float
    {
        $bagian = $jumlah * fake()->randomFloat(2, 0.25, 0.75);
        $dibulatkan = round($bagian / 10_000) * 10_000;

        return min($jumlah, max(10_000, $dibulatkan));
    }

    /**
     * Kode tagihan memakai bulan jatuh tempo, bukan bulan saat seeder
     * dijalankan — kalau tidak, seluruh tagihan tahun lalu ikut berkode bulan
     * ini dan urutan kodenya bertentangan dengan tanggalnya.
     */
    private function kodeTagihan(string $jatuhTempo): string
    {
        $prefix = 'TAG-'.Carbon::parse($jatuhTempo)->format('ym');
        $this->urutan[$prefix] = ($this->urutan[$prefix] ?? 0) + 1;

        return $prefix.'-'.str_pad((string) $this->urutan[$prefix], 5, '0', STR_PAD_LEFT);
    }

    private function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        DB::table('tagihans')->insert($this->buffer);
        $this->buffer = [];
    }
}
