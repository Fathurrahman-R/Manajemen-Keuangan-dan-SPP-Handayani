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
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    private int $nisCounter = 1;

    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $tahunAjarans = TahunAjaran::where('branch_id', $branch->id)->get();
            $aktiveTahunAjaran = $tahunAjarans->firstWhere('status', 'Aktif');

            if (! $aktiveTahunAjaran) {
                continue;
            }

            $kelasMap = [];
            foreach (['MI', 'TK', 'KB'] as $jenjang) {
                $kelasMap[$jenjang] = Kelas::where('branch_id', $branch->id)->where('jenjang', $jenjang)->get();
            }

            // MI students (60 total, spread across 6 classes)
            foreach ($kelasMap['MI'] as $kelas) {
                $count = rand(8, 12);
                for ($i = 0; $i < $count; $i++) {
                    $nis = str_pad($this->nisCounter++, 6, '0', STR_PAD_LEFT);
                    $ayah = Ayah::create([
                        'nama' => fake()->name('male'),
                        'pendidikan_terakhir' => fake()->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2']),
                        'pekerjaan' => fake()->jobTitle(),
                        'email' => fake()->optional(0.7)->safeEmail(),
                    ]);
                    $ibu = Ibu::create([
                        'nama' => fake()->name('female'),
                        'pendidikan_terakhir' => fake()->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2']),
                        'pekerjaan' => fake()->randomElement(['Ibu Rumah Tangga', fake()->jobTitle()]),
                        'email' => fake()->optional(0.5)->safeEmail(),
                    ]);

                    $siswa = Siswa::create([
                        'nis' => $nis,
                        'nisn' => fake()->unique()->numerify('##########'),
                        'nama' => fake()->name(),
                        'jenis_kelamin' => fake()->randomElement(['Laki-laki', 'Perempuan']),
                        'tempat_lahir' => fake()->city(),
                        'tanggal_lahir' => fake()->dateTimeBetween('-12 years', '-6 years')->format('Y-m-d'),
                        'agama' => 'Islam',
                        'alamat' => fake()->address(),
                        'ayah_id' => $ayah->id,
                        'ibu_id' => $ibu->id,
                        'wali_id' => null,
                        'jenjang' => 'MI',
                        'kelas_id' => $kelas->id,
                        'kategori_id' => Kategori::where('branch_id', $branch->id)->inRandomOrder()->first()->id,
                        'asal_sekolah' => fake()->optional(0.3)->company(),
                        'kelas_diterima' => 'Kelas 1',
                        'tahun_diterima' => fake()->numberBetween(2019, 2024),
                        'status' => 'Aktif',
                        'keterangan' => null,
                        'branch_id' => $branch->id,
                    ]);

                    SiswaKelas::create([
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $kelas->id,
                        'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                    ]);

                    app(AkunSiswaService::class)->createAccount($siswa);
                }
            }

            // TK students (20 total, spread across 2 classes)
            foreach ($kelasMap['TK'] as $kelas) {
                $count = rand(8, 12);
                for ($i = 0; $i < $count; $i++) {
                    $nis = str_pad($this->nisCounter++, 6, '0', STR_PAD_LEFT);
                    $wali = Wali::create([
                        'nama' => fake()->name(),
                        'pekerjaan' => fake()->jobTitle(),
                        'alamat' => fake()->address(),
                        'no_hp' => fake()->numerify('08##########'),
                        'keterangan' => null,
                        'email' => fake()->optional(0.6)->safeEmail(),
                    ]);

                    $siswa = Siswa::create([
                        'nis' => $nis,
                        'nisn' => null,
                        'nama' => fake()->name(),
                        'jenis_kelamin' => fake()->randomElement(['Laki-laki', 'Perempuan']),
                        'tempat_lahir' => fake()->city(),
                        'tanggal_lahir' => fake()->dateTimeBetween('-6 years', '-4 years')->format('Y-m-d'),
                        'agama' => 'Islam',
                        'alamat' => fake()->address(),
                        'ayah_id' => null,
                        'ibu_id' => null,
                        'wali_id' => $wali->id,
                        'jenjang' => 'TK',
                        'kelas_id' => $kelas->id,
                        'kategori_id' => Kategori::where('branch_id', $branch->id)->inRandomOrder()->first()->id,
                        'asal_sekolah' => null,
                        'kelas_diterima' => null,
                        'tahun_diterima' => fake()->numberBetween(2022, 2024),
                        'status' => 'Aktif',
                        'keterangan' => null,
                        'branch_id' => $branch->id,
                    ]);

                    SiswaKelas::create([
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $kelas->id,
                        'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                    ]);

                    app(AkunSiswaService::class)->createAccount($siswa);
                }
            }

            // KB students (6 total)
            foreach ($kelasMap['KB'] as $kelas) {
                for ($i = 0; $i < 6; $i++) {
                    $nis = str_pad($this->nisCounter++, 6, '0', STR_PAD_LEFT);
                    $wali = Wali::create([
                        'nama' => fake()->name(),
                        'pekerjaan' => fake()->jobTitle(),
                        'alamat' => fake()->address(),
                        'no_hp' => fake()->numerify('08##########'),
                        'keterangan' => null,
                        'email' => fake()->optional(0.6)->safeEmail(),
                    ]);

                    $siswa = Siswa::create([
                        'nis' => $nis,
                        'nisn' => null,
                        'nama' => fake()->name(),
                        'jenis_kelamin' => fake()->randomElement(['Laki-laki', 'Perempuan']),
                        'tempat_lahir' => fake()->city(),
                        'tanggal_lahir' => fake()->dateTimeBetween('-4 years', '-3 years')->format('Y-m-d'),
                        'agama' => 'Islam',
                        'alamat' => fake()->address(),
                        'ayah_id' => null,
                        'ibu_id' => null,
                        'wali_id' => $wali->id,
                        'jenjang' => 'KB',
                        'kelas_id' => $kelas->id,
                        'kategori_id' => Kategori::where('branch_id', $branch->id)->inRandomOrder()->first()->id,
                        'asal_sekolah' => null,
                        'kelas_diterima' => null,
                        'tahun_diterima' => fake()->numberBetween(2023, 2024),
                        'status' => 'Aktif',
                        'keterangan' => null,
                        'branch_id' => $branch->id,
                    ]);

                    SiswaKelas::create([
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $kelas->id,
                        'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                    ]);

                    app(AkunSiswaService::class)->createAccount($siswa);
                }
            }
        }
    }
}
