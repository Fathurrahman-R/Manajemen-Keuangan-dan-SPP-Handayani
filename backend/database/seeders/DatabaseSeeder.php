<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Ayah;
use App\Models\Branch;
use App\Models\Ibu;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\NotificationSetting;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\PengeluaranRequest;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Services\GenerateKodePembayaran;
use App\Services\GenerateKodeTagihan;
use App\Services\AkunSiswaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions
        $this->call(RoleAndPermissionSeeder::class);

        // 2. Branches
        $branches = collect([
            Branch::create(['location' => 'Selat Panjang']),
            Branch::create(['location' => 'Desa Kapur']),
            Branch::create(['location' => 'Darma Putra']),
        ]);

        $mainBranch = $branches->first();

        // 3. Users
        $admin = User::create([
            'username' => 'admin123',
            'name' => 'Admin',
            'password' => Hash::make('admin123'),
            'branch_id' => $mainBranch->id,
        ]);
        $admin->assignRole('superadmin');

        foreach ($branches->skip(1) as $branch) {
            $user = User::create([
                'username' => 'admin_' . strtolower(str_replace(' ', '_', $branch->location)),
                'name' => 'Admin ' . $branch->location,
                'password' => Hash::make('admin123'),
                'branch_id' => $branch->id,
            ]);
            $user->assignRole('admin');
        }

        // 4. App Settings per branch
        foreach ($branches as $branch) {
            AppSetting::create([
                'nama_sekolah' => 'Lembaga Pendidikan Handayani ' . $branch->location,
                'lokasi' => $branch->location,
                'alamat' => 'Jl. Pendidikan No. ' . rand(1, 100) . ', ' . $branch->location,
                'email' => 'handayani.' . strtolower(str_replace(' ', '', $branch->location)) . '@example.com',
                'telepon' => '0761-' . rand(100000, 999999),
                'kepala_sekolah' => fake()->name(),
                'bendahara' => fake()->name(),
                'kode_pos' => (string) rand(28000, 29999),
                'logo' => 'logo-handayani.png',
                'branch_id' => $branch->id,
            ]);
        }

        // 5. Kategori per branch
        $kategoriNames = ['Bersaudara', 'Yatim', 'Piatu', 'Yatim Piatu', 'Umum'];
        foreach ($branches as $branch) {
            foreach ($kategoriNames as $nama) {
                Kategori::create(['nama' => $nama, 'branch_id' => $branch->id]);
            }
        }

        // 6. Kelas per branch (MI: 6 level, TK: 2 level, KB: 1 level)
        $kelasConfig = [
            'MI' => [
                ['nama' => 'Kelas 1', 'level' => 1],
                ['nama' => 'Kelas 2', 'level' => 2],
                ['nama' => 'Kelas 3', 'level' => 3],
                ['nama' => 'Kelas 4', 'level' => 4],
                ['nama' => 'Kelas 5', 'level' => 5],
                ['nama' => 'Kelas 6', 'level' => 6],
            ],
            'TK' => [
                ['nama' => 'TK A', 'level' => 1],
                ['nama' => 'TK B', 'level' => 2],
            ],
            'KB' => [
                ['nama' => 'KB', 'level' => 1],
            ],
        ];

        $kelasMap = []; // branch_id => jenjang => [kelas models]
        foreach ($branches as $branch) {
            $kelasMap[$branch->id] = [];
            foreach ($kelasConfig as $jenjang => $kelasList) {
                $kelasMap[$branch->id][$jenjang] = [];
                foreach ($kelasList as $config) {
                    $kelas = Kelas::create([
                        'jenjang' => $jenjang,
                        'nama' => $config['nama'],
                        'level' => $config['level'],
                        'branch_id' => $branch->id,
                    ]);
                    $kelasMap[$branch->id][$jenjang][] = $kelas;
                }
            }
        }

        // 7. Tahun Ajaran per branch
        $tahunAjaranMap = []; // branch_id => [tahun_ajaran models]
        foreach ($branches as $branch) {
            $ta1 = TahunAjaran::create([
                'nama' => '2023/2024',
                'tanggal_mulai' => '2023-07-01',
                'tanggal_selesai' => '2024-06-30',
                'status' => 'Non-Aktif',
                'branch_id' => $branch->id,
            ]);
            $ta2 = TahunAjaran::create([
                'nama' => '2024/2025',
                'tanggal_mulai' => '2024-07-01',
                'tanggal_selesai' => '2025-06-30',
                'status' => 'Aktif',
                'branch_id' => $branch->id,
            ]);
            $tahunAjaranMap[$branch->id] = [$ta1, $ta2];
        }

        // 8. Notification Settings per branch
        foreach ($branches as $branch) {
            NotificationSetting::create([
                'branch_id' => $branch->id,
                'tagihan_baru_enabled' => true,
                'reminder_enabled' => true,
                'kwitansi_enabled' => true,
                'overdue_enabled' => true,
                'reminder_days_before' => [7, 3, 1],
                'overdue_interval_days' => 7,
            ]);
        }

        // Seed data for ALL branches
        foreach ($branches as $branch) {
            $adminForBranch = User::where('branch_id', $branch->id)->whereHas('roles', function($q) {
                $q->where('name', 'admin')->orWhere('name', 'superadmin');
            })->first() ?? $admin;

            // 9. Siswa for branch
            $this->seedSiswaForBranch($branch, $kelasMap[$branch->id], $tahunAjaranMap[$branch->id]);

            // 10. Jenis Tagihan & Tagihan for branch
            $totalPemasukan = $this->seedTagihanForBranch($branch, $tahunAjaranMap[$branch->id]);

            // 11. Pengeluaran for branch (ensure saldo not negative)
            $this->seedPengeluaranForBranch($branch, $totalPemasukan);

            // 12. PengeluaranRequest for branch
            $this->seedPengeluaranRequestsForBranch($branch, $adminForBranch);
        }
    }

    private int $nisCounter = 1;

    private function seedSiswaForBranch(Branch $branch, array $kelasPerJenjang, array $tahunAjarans): void
    {
        $aktiveTahunAjaran = collect($tahunAjarans)->firstWhere('status', 'Aktif');

        // MI students (60 total, spread across 6 classes)
        foreach ($kelasPerJenjang['MI'] as $kelas) {
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

                // Siswa Kelas pivot
                SiswaKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelas->id,
                    'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                ]);
                
                // Generate User account
                app(AkunSiswaService::class)->createAccount($siswa);
            }
        }

        // TK students (20 total, spread across 2 classes)
        foreach ($kelasPerJenjang['TK'] as $kelas) {
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
                
                // Generate User account
                app(AkunSiswaService::class)->createAccount($siswa);
            }
        }

        // KB students (6 total)
        foreach ($kelasPerJenjang['KB'] as $kelas) {
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
                
                // Generate User account
                app(AkunSiswaService::class)->createAccount($siswa);
            }
        }
    }

    private int $tagihanCounter = 1;
    private int $pembayaranCounter = 1;

    private function seedTagihanForBranch(Branch $branch, array $tahunAjarans): int
    {
        $totalPemasukan = 0;
        $aktiveTahunAjaran = collect($tahunAjarans)->firstWhere('status', 'Aktif');

        // Jenis Tagihan — jatuh_tempo dihitung relatif terhadap hari ini agar
        // dashboard "Tagihan Jatuh Tempo 7 Hari" punya data yang relevan
        // ketika seeder dijalankan kapan saja.
        $today = \Carbon\Carbon::today();
        $jenisTagihanData = [
            ['nama' => 'SPP Bulan Ini',     'jatuh_tempo' => $today->copy()->addDays(3)->format('Y-m-d'),  'jumlah' => 150000],
            ['nama' => 'SPP Bulan Depan',   'jatuh_tempo' => $today->copy()->addDays(33)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'SPP 2 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(63)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'SPP 3 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(93)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'SPP 4 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(123)->format('Y-m-d'),'jumlah' => 150000],
            ['nama' => 'SPP 5 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(153)->format('Y-m-d'),'jumlah' => 150000],
            ['nama' => 'Pendaftaran Ulang', 'jatuh_tempo' => $today->copy()->subDays(15)->format('Y-m-d'), 'jumlah' => 500000],
            ['nama' => 'Seragam',           'jatuh_tempo' => $today->copy()->subDays(30)->format('Y-m-d'), 'jumlah' => 350000],
            ['nama' => 'Buku Paket',        'jatuh_tempo' => $today->copy()->subDays(45)->format('Y-m-d'), 'jumlah' => 200000],
        ];

        $jenisTagihans = [];
        foreach ($jenisTagihanData as $data) {
            $jenisTagihans[] = JenisTagihan::create([
                'nama' => $data['nama'],
                'jatuh_tempo' => $data['jatuh_tempo'],
                'jumlah' => $data['jumlah'],
                'branch_id' => $branch->id,
                'tahun_ajaran_id' => $aktiveTahunAjaran->id,
            ]);
        }

        // Create tagihan for some students (first 20 MI students)
        $students = Siswa::where('branch_id', $branch->id)
            ->where('jenjang', 'MI')
            ->limit(20)
            ->get();

        foreach ($students as $siswa) {
            // Give each student 2-4 random jenis tagihan
            $selectedJenis = fake()->randomElements($jenisTagihans, rand(2, 4));

            foreach ($selectedJenis as $jenis) {
                $kodeTagihan = 'TAG-' . now()->format('ym') . '-' . str_pad($this->tagihanCounter++, 4, '0', STR_PAD_LEFT);

                $status = fake()->randomElement(['Lunas', 'Belum Lunas', 'Belum Dibayar', 'Belum Dibayar']);
                $tmp = 0;

                if ($status === 'Lunas') {
                    $tmp = $jenis->jumlah;
                } elseif ($status === 'Belum Lunas') {
                    $tmp = round($jenis->jumlah * fake()->randomFloat(2, 0.2, 0.8), -3);
                }

                $tagihan = Tagihan::create([
                    'kode_tagihan' => $kodeTagihan,
                    'jenis_tagihan_id' => $jenis->id,
                    'nis' => $siswa->nis,
                    'tmp' => $tmp,
                    'status' => $status,
                    'branch_id' => $branch->id,
                    'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                ]);

                // Create pembayaran for paid tagihan
                if ($tmp > 0) {
                    $kodePembayaran = 'PAY-' . now()->format('ym') . '-' . str_pad($this->pembayaranCounter++, 4, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_pembayaran' => $kodePembayaran,
                        'kode_tagihan' => $kodeTagihan,
                        'tanggal' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                        'metode' => 'offline',
                        'jumlah' => $tmp,
                        'pembayar' => fake()->name(),
                        'branch_id' => $branch->id,
                    ]);
                    $totalPemasukan += $tmp;
                }
            }
        }

        return $totalPemasukan;
    }

    private function seedPengeluaranForBranch(Branch $branch, int $maxTotalPengeluaran = 50000000): void
    {
        $pengeluaranData = [
            ['uraian' => 'Pembelian ATK', 'jumlah' => 250000],
            ['uraian' => 'Bayar listrik bulan Januari', 'jumlah' => 850000],
            ['uraian' => 'Bayar listrik bulan Februari', 'jumlah' => 780000],
            ['uraian' => 'Pembelian toner printer', 'jumlah' => 150000],
            ['uraian' => 'Biaya kebersihan', 'jumlah' => 500000],
            ['uraian' => 'Perbaikan AC ruang guru', 'jumlah' => 1200000],
            ['uraian' => 'Pembelian air galon', 'jumlah' => 120000],
            ['uraian' => 'Transport rapat dinas', 'jumlah' => 300000],
            ['uraian' => 'Bayar internet bulan Maret', 'jumlah' => 450000],
            ['uraian' => 'Pembelian bahan praktek', 'jumlah' => 680000],
        ];

        $totalPengeluaran = 0;
        foreach ($pengeluaranData as $data) {
            if ($totalPengeluaran + $data['jumlah'] > ($maxTotalPengeluaran * 0.7)) {
                break; // Stop if we reach 70% of max allowed to leave positive balance
            }
            Pengeluaran::create([
                'tanggal' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'uraian' => $data['uraian'],
                'jumlah' => $data['jumlah'],
                'tahun_ajaran_id' => 1,
                'branch_id' => $branch->id,
            ]);
            $totalPengeluaran += $data['jumlah'];
        }
    }

    private function seedPengeluaranRequestsForBranch(Branch $branch, User $admin): void
    {
        $requests = [
            ['uraian' => 'Pembelian kertas A4 5 rim',          'jumlah' => 275000,   'status' => 'draft',     'days_ago' => 2],
            ['uraian' => 'Bayar internet bulan ini',           'jumlah' => 450000,   'status' => 'submitted', 'days_ago' => 5],
            ['uraian' => 'Pembelian tinta printer',            'jumlah' => 180000,   'status' => 'approved',  'days_ago' => 10],
            ['uraian' => 'Biaya cleaning service',             'jumlah' => 600000,   'status' => 'disbursed', 'days_ago' => 20],
            ['uraian' => 'Perbaikan atap bocor',               'jumlah' => 2500000,  'status' => 'rejected',  'days_ago' => 15],
            ['uraian' => 'Pembelian spidol whiteboard 1 lusin','jumlah' => 85000,    'status' => 'draft',     'days_ago' => 1],
            ['uraian' => 'Transport rapat koordinasi',         'jumlah' => 300000,   'status' => 'submitted', 'days_ago' => 7],
            ['uraian' => 'Pembelian buku referensi guru',      'jumlah' => 750000,   'status' => 'approved',  'days_ago' => 12],
        ];

        foreach ($requests as $data) {
            PengeluaranRequest::create([
                'uraian' => $data['uraian'],
                'jumlah' => $data['jumlah'],
                // Gunakan tanggal lampau (bukan mendatang) agar data tetap muncul
                // di laporan historis dan tidak tergantung tahun ajaran aktif
                'tanggal_kebutuhan' => now()->subDays($data['days_ago'])->format('Y-m-d'),
                'kategori_pengeluaran' => fake()->randomElement(['ATK', 'Utilitas', 'Perbaikan', 'Kebersihan', null]),
                'status' => $data['status'],
                'requester_id' => $admin->id,
                'branch_id' => $branch->id,
            ]);
        }
    }
}
