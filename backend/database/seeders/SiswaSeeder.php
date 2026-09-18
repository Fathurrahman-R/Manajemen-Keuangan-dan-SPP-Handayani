<?php

namespace Database\Seeders;

use App\Models\Ayah;
use App\Models\Branch;
use App\Models\Ibu;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Wali;
use App\Services\AkunSiswaService;
use Database\Seeders\Support\DataRealistis;
use Database\Seeders\Support\TarifDemo;
use Illuminate\Database\Seeder;

/**
 * Membangun populasi siswa yang konsisten secara internal, bukan sekadar baris
 * acak. Aturan yang dijaga:
 *
 * - Nama mengikuti jenis kelamin (versi lama mengacak keduanya secara terpisah,
 *   sehingga muncul siswa bernama "Siti" berjenis kelamin Laki-laki).
 * - Umur mengikuti jenjang dan level kelas: KB 3-4 tahun, TK 5-6 tahun, MI
 *   kelas N berumur sekitar 6+N tahun.
 * - `tahun_diterima` dihitung mundur dari level kelas, jadi siswa Kelas 6 tidak
 *   mungkin tercatat diterima tahun lalu.
 * - Struktur orang tua mengikuti kategori keringanan: Yatim tidak punya ayah,
 *   Piatu tidak punya ibu, Yatim Piatu hanya punya wali.
 * - Riwayat kelas ditulis untuk dua tahun ajaran, sehingga tagihan tahun lalu
 *   punya konteks kelas yang benar.
 */
class SiswaSeeder extends Seeder
{
    private int $nisCounter = 1;

    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $tahunAjaranAktif = TahunAjaran::where('branch_id', $branch->id)
                ->where('status', 'Aktif')
                ->first();

            $tahunAjaranLalu = TahunAjaran::where('branch_id', $branch->id)
                ->where('nama', '2025/2026')
                ->first();

            if (! $tahunAjaranAktif) {
                continue;
            }

            $kategoris = Kategori::where('branch_id', $branch->id)->get()->keyBy('nama');
            $kelasPerJenjang = [];

            foreach (['KB', 'TK', 'MI'] as $jenjang) {
                $kelasPerJenjang[$jenjang] = Kelas::where('branch_id', $branch->id)
                    ->where('jenjang', $jenjang)
                    ->orderBy('level')
                    ->get();
            }

            // Jumlah siswa per jenjang mengikuti angka nyata sekolah, dibagi
            // rata ke rombel yang tersedia. Sisa pembagian ditaruh di rombel
            // pertama supaya totalnya persis, bukan kira-kira.
            $targetPerJenjang = TarifDemo::JUMLAH_SISWA[$branch->location]
                ?? TarifDemo::JUMLAH_SISWA['Darma Putra'];

            foreach (['KB', 'TK', 'MI'] as $jenjang) {
                $jumlahKelas = max(1, $kelasPerJenjang[$jenjang]->count());
                $target = $targetPerJenjang[$jenjang] ?? 0;
                $dasar = intdiv($target, $jumlahKelas);
                $sisa = $target % $jumlahKelas;

                foreach ($kelasPerJenjang[$jenjang] as $indeks => $kelas) {
                    $kuota = $dasar + ($indeks < $sisa ? 1 : 0);

                    for ($i = 0; $i < $kuota; $i++) {
                        $siswa = $this->buatSiswa($branch->id, $jenjang, $kelas, $kategoris);

                        SiswaKelas::create([
                            'siswa_id' => $siswa->id,
                            'kelas_id' => $kelas->id,
                            'tahun_ajaran_id' => $tahunAjaranAktif->id,
                        ]);

                        $this->catatRiwayatTahunLalu($siswa, $kelas, $kelasPerJenjang, $tahunAjaranLalu);

                        app(AkunSiswaService::class)->createAccount($siswa);
                    }
                }
            }

            // Angkatan lulus sebesar satu rombel MI, mengikuti ukuran cabang.
            $rombelMi = max(1, $kelasPerJenjang['MI']->count());
            $this->buatAlumni(
                $branch->id,
                $kelasPerJenjang['MI'],
                $kategoris,
                $tahunAjaranLalu,
                intdiv($targetPerJenjang['MI'] ?? 0, $rombelMi),
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Kategori>  $kategoris
     */
    private function buatSiswa(int $branchId, string $jenjang, Kelas $kelas, $kategoris, string $status = 'Aktif'): Siswa
    {
        $jenisKelamin = fake()->randomElement(['Laki-laki', 'Perempuan']);
        $nama = DataRealistis::namaLengkap($jenisKelamin);
        $level = $kelas->level ?? 1;

        // Alumni menempati Kelas 6 pada tahun ajaran lalu, bukan tahun berjalan,
        // jadi seluruh perhitungan umur dan tahun masuknya mundur satu tahun.
        $selisihAngkatan = $status === 'Lulus' ? 1 : 0;
        $tahunDiterima = $this->tahunDiterima($jenjang, $level) - $selisihAngkatan;
        $tanggalLahir = $this->tanggalLahir($jenjang, $level, $selisihAngkatan);

        $kategori = $this->pilihKategori($kategoris, $jenjang);
        [$ayahId, $ibuId, $waliId] = $this->buatOrangTua($kategori?->nama ?? 'Umum', $nama);

        $siswa = Siswa::create([
            'nis' => $this->nisBerikutnya($tahunDiterima),
            'nisn' => $jenjang === 'MI' ? fake()->unique()->numerify('00########') : null,
            'nama' => $nama,
            'jenis_kelamin' => $jenisKelamin,
            'tempat_lahir' => DataRealistis::tempatLahir(),
            'tanggal_lahir' => $tanggalLahir,
            'agama' => 'Islam',
            'alamat' => DataRealistis::alamat(),
            'ayah_id' => $ayahId,
            'ibu_id' => $ibuId,
            'wali_id' => $waliId,
            'jenjang' => $jenjang,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori?->id,
            'asal_sekolah' => $jenjang === 'MI' && $level === 1 ? 'TK Handayani' : null,
            'kelas_diterima' => $jenjang === 'MI' ? 'Kelas 1' : null,
            'tahun_diterima' => $tahunDiterima,
            'status' => $status,
            'keterangan' => null,
            'branch_id' => $branchId,
        ]);

        return $siswa;
    }

    /**
     * Siswa masuk di tingkat terendah jenjangnya lalu naik setahun sekali,
     * jadi tahun diterima selalu mundur sebanyak level dikurangi satu. TK ikut
     * aturan yang sama sejak BULAN/BINTANG/MATAHARI menjadi tiga tingkat
     * berjenjang, bukan tiga rombel sejajar.
     */
    private function tahunDiterima(string $jenjang, int $level): int
    {
        $tahunAjaranBerjalan = 2026;

        return $tahunAjaranBerjalan - ($level - 1);
    }

    /**
     * Umur mengikuti jenjang dan tingkat: KB 3-4 tahun, TK naik dari 4 tahun
     * di BULAN sampai 6 tahun di MATAHARI, MI kelas N sekitar 6+N tahun.
     * Dihitung mundur dari Juli 2026 (awal tahun ajaran berjalan) supaya usia
     * siswa masuk akal saat data ditampilkan.
     */
    private function tanggalLahir(string $jenjang, int $level, int $selisihAngkatan = 0): string
    {
        $umur = match ($jenjang) {
            'KB' => fake()->numberBetween(3, 4),
            'TK' => 3 + $level,
            default => 6 + $level,
        };

        $tahunLahir = 2026 - $umur - $selisihAngkatan;

        return fake()->dateTimeBetween("{$tahunLahir}-01-01", "{$tahunLahir}-12-31")->format('Y-m-d');
    }

    /**
     * Kategori keringanan biaya. Mayoritas siswa masuk kategori Umum; kategori
     * yatim/piatu sengaja dibuat minoritas supaya sebarannya tidak terlihat
     * dibuat-buat, tapi tetap cukup untuk mendemokan potongan biaya.
     *
     * @param  \Illuminate\Support\Collection<string, Kategori>  $kategoris
     */
    private function pilihKategori($kategoris, string $jenjang): ?Kategori
    {
        // KB tidak menerapkan keringanan sama sekali — seluruh siswanya Umum.
        $diizinkan = TarifDemo::KATEGORI_PER_JENJANG[$jenjang] ?? ['Umum'];

        if (count($diizinkan) === 1) {
            return $kategoris[$diizinkan[0]] ?? $kategoris->first();
        }

        $undian = array_merge(
            array_fill(0, 8, 'Umum'),
            array_fill(0, 2, 'Bersaudara'),
            ['Yatim', 'Piatu', 'Yatim Piatu'],
        );

        $nama = fake()->randomElement(array_values(array_intersect($undian, $diizinkan)));

        return $kategoris[$nama] ?? $kategoris->first();
    }

    /**
     * Struktur orang tua mengikuti kategori supaya datanya tidak saling
     * bertentangan: siswa berkategori Yatim tidak boleh punya baris ayah, dan
     * siswa Yatim Piatu harus punya wali.
     *
     * @return array{0: ?int, 1: ?int, 2: ?int} [ayah_id, ibu_id, wali_id]
     */
    private function buatOrangTua(string $namaKategori, string $namaSiswa): array
    {
        $punyaAyah = ! in_array($namaKategori, ['Yatim', 'Yatim Piatu'], true);
        $punyaIbu = ! in_array($namaKategori, ['Piatu', 'Yatim Piatu'], true);

        $ayahId = null;
        $ibuId = null;
        $waliId = null;

        if ($punyaAyah) {
            $namaAyah = DataRealistis::namaLengkap('Laki-laki');
            $ayahId = Ayah::create([
                'nama' => $namaAyah,
                'pendidikan_terakhir' => DataRealistis::pendidikan(),
                'pekerjaan' => DataRealistis::pekerjaanAyah(),
                'email' => fake()->boolean(55) ? DataRealistis::emailDari($namaAyah) : null,
            ])->id;
        }

        if ($punyaIbu) {
            $namaIbu = DataRealistis::namaLengkap('Perempuan');
            $ibuId = Ibu::create([
                'nama' => $namaIbu,
                'pendidikan_terakhir' => DataRealistis::pendidikan(),
                'pekerjaan' => DataRealistis::pekerjaanIbu(),
                'email' => fake()->boolean(40) ? DataRealistis::emailDari($namaIbu) : null,
            ])->id;
        }

        // Wali dibuat ketika salah satu orang tua tidak ada, atau sesekali pada
        // keluarga lengkap yang anaknya dititipkan ke kerabat.
        if (! $punyaAyah || ! $punyaIbu || fake()->boolean(8)) {
            $jenisKelaminWali = fake()->randomElement(['Laki-laki', 'Perempuan']);
            $namaWali = DataRealistis::namaLengkap($jenisKelaminWali);

            // Tabel `walis` hanya punya nama, pekerjaan, alamat, no_hp, email,
            // dan keterangan. `$fillable` model Wali mendaftar jenis_kelamin,
            // agama, dan pendidikan_terakhir yang tidak pernah ada kolomnya —
            // mengirimnya ke sini langsung memicu error kolom tidak dikenal.
            $waliId = Wali::create([
                'nama' => $namaWali,
                'pekerjaan' => $jenisKelaminWali === 'Laki-laki'
                    ? DataRealistis::pekerjaanAyah()
                    : DataRealistis::pekerjaanIbu(),
                'alamat' => DataRealistis::alamat(),
                'no_hp' => DataRealistis::noHp(),
                'keterangan' => fake()->randomElement(['Paman', 'Bibi', 'Kakek', 'Nenek', 'Kakak Kandung']),
                'email' => fake()->boolean(45) ? DataRealistis::emailDari($namaWali) : null,
            ])->id;
        }

        return [$ayahId, $ibuId, $waliId];
    }

    /**
     * Riwayat kelas tahun ajaran 2025/2026. Siswa yang tingkatnya di atas satu
     * berada di tingkat sebelumnya dalam jenjang yang sama; siswa di tingkat
     * terendah berasal dari puncak jenjang di bawahnya — MI Kelas 1 dari
     * MATAHARI (tingkat tertinggi TK), TK BULAN dari PAUD. Tanpa riwayat ini,
     * tagihan tahun lalu menggantung tanpa konteks kelas.
     *
     * @param  array<string, \Illuminate\Support\Collection<int, Kelas>>  $kelasPerJenjang
     */
    private function catatRiwayatTahunLalu(Siswa $siswa, Kelas $kelas, array $kelasPerJenjang, ?TahunAjaran $tahunAjaranLalu): void
    {
        if (! $tahunAjaranLalu) {
            return;
        }

        $level = $kelas->level ?? 1;
        $jenjangDiBawah = ['MI' => 'TK', 'TK' => 'KB'];

        $kelasSebelumnya = match (true) {
            // Naik satu tingkat di dalam jenjang yang sama.
            $level > 1 => $kelasPerJenjang[$siswa->jenjang]->firstWhere('level', $level - 1),

            // Tingkat terendah: datang dari tingkat TERTINGGI jenjang di
            // bawahnya, bukan dari kelas acak. Selama TK masih tiga rombel
            // sejajar hal ini tidak terlihat; setelah TK berjenjang, memilih
            // acak akan mencatat siswa MI Kelas 1 pernah di BULAN — melompati
            // dua tingkat TK sekaligus.
            isset($jenjangDiBawah[$siswa->jenjang]) => $kelasPerJenjang[$jenjangDiBawah[$siswa->jenjang]]
                ->sortByDesc('level')
                ->first(),

            default => null,
        };

        // Siswa KB adalah pendaftar baru — tidak punya riwayat tahun lalu.
        if (! $kelasSebelumnya) {
            return;
        }

        SiswaKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelasSebelumnya->id,
            'tahun_ajaran_id' => $tahunAjaranLalu->id,
        ]);
    }

    /**
     * Angkatan yang lulus MI pada akhir 2025/2026. Tanpa mereka, filter status
     * "Lulus" selalu kosong dan sekolah terlihat seolah belum pernah meluluskan
     * siapa pun padahal riwayat tagihannya sudah setahun penuh.
     *
     * @param  \Illuminate\Support\Collection<int, Kelas>  $kelasMi
     * @param  \Illuminate\Support\Collection<string, Kategori>  $kategoris
     */
    private function buatAlumni(int $branchId, $kelasMi, $kategoris, ?TahunAjaran $tahunAjaranLalu, int $jumlah): void
    {
        $kelasEnam = $kelasMi->firstWhere('level', 6);

        if (! $kelasEnam || ! $tahunAjaranLalu) {
            return;
        }

        for ($i = 0; $i < $jumlah; $i++) {
            $siswa = $this->buatSiswa($branchId, 'MI', $kelasEnam, $kategoris, 'Lulus');

            SiswaKelas::create([
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelasEnam->id,
                'tahun_ajaran_id' => $tahunAjaranLalu->id,
            ]);

            app(AkunSiswaService::class)->createAccount($siswa);
        }
    }

    /**
     * NIS bergaya sekolah: dua digit tahun masuk diikuti nomor urut. Unik
     * lintas cabang karena counter-nya tunggal — kolom `nis` memang unik
     * global, bukan per cabang.
     */
    private function nisBerikutnya(int $tahunDiterima): string
    {
        return substr((string) $tahunDiterima, 2, 2).str_pad((string) $this->nisCounter++, 4, '0', STR_PAD_LEFT);
    }
}
