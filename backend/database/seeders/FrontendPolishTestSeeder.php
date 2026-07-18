<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Notification;
use App\Models\PengeluaranRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder khusus untuk testing Frontend Polish Phase 3 & 4.
 *
 * Jalankan SETELAH DatabaseSeeder:
 *   php artisan db:seed --class=FrontendPolishTestSeeder
 *
 * Atau jalankan semua dari awal:
 *   php artisan migrate:fresh --seed
 *   php artisan db:seed --class=FrontendPolishTestSeeder
 */
class FrontendPolishTestSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch = Branch::first();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'superadmin'))->first();

        if (! $mainBranch || ! $admin) {
            $this->command->error('Jalankan DatabaseSeeder terlebih dahulu!');

            return;
        }

        // 1. Notifications — untuk testing bell icon & notification panel
        $this->seedNotifications($admin);

        // 2. PengeluaranRequest — untuk testing workflow approval
        $this->seedPengeluaranRequests($mainBranch, $admin);

        // 3. Siswa user account — untuk testing portal
        $this->seedSiswaUser($mainBranch);

        $this->command->info('✅ FrontendPolishTestSeeder completed!');
        $this->command->info('');
        $this->command->info('Test accounts:');
        $this->command->info('  Admin    → admin123 / admin123');
        $this->command->info('  Siswa    → siswa_test / password123');
        $this->command->info('  New user → user_baru / password123 (must change password)');
    }

    private function seedNotifications(User $admin): void
    {
        $notifications = [
            [
                'title' => 'Tagihan Baru Diterbitkan',
                'message' => 'Tagihan SPP bulan Juli 2025 telah diterbitkan untuk 45 siswa.',
                'is_read' => false,
                'created_at' => now()->subMinutes(5),
            ],
            [
                'title' => 'Pembayaran Masuk',
                'message' => 'Pembayaran SPP dari Ahmad Fauzi sebesar Rp 150.000 telah dikonfirmasi.',
                'is_read' => false,
                'created_at' => now()->subMinutes(30),
            ],
            [
                'title' => 'Pengeluaran Disetujui',
                'message' => 'Request pengeluaran "Pembelian ATK" sebesar Rp 250.000 telah disetujui.',
                'is_read' => false,
                'created_at' => now()->subHour(),
            ],
            [
                'title' => 'Siswa Baru Terdaftar',
                'message' => 'Siti Aminah telah terdaftar di kelas TK A untuk tahun ajaran 2024/2025.',
                'is_read' => true,
                'created_at' => now()->subHours(3),
            ],
            [
                'title' => 'Reminder: Tagihan Jatuh Tempo',
                'message' => '12 siswa memiliki tagihan yang jatuh tempo dalam 3 hari.',
                'is_read' => true,
                'created_at' => now()->subHours(6),
            ],
            [
                'title' => 'Laporan Bulanan Tersedia',
                'message' => 'Rekap kas bulanan Mei 2025 siap diunduh.',
                'is_read' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'title' => 'Backup Data Berhasil',
                'message' => 'Backup otomatis data sekolah telah berhasil dilakukan.',
                'is_read' => true,
                'created_at' => now()->subDays(2),
            ],
            [
                'title' => 'Update Sistem',
                'message' => 'Sistem telah diperbarui ke versi terbaru. Fitur baru tersedia.',
                'is_read' => false,
                'created_at' => now()->subDays(3),
            ],
        ];

        foreach ($notifications as $data) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'system',
                'title' => $data['title'],
                'message' => $data['message'],
                'is_read' => $data['is_read'],
                'created_at' => $data['created_at'],
            ]);
        }

        $this->command->info('  → 8 notifications created (3 unread, 5 read)');
    }

    private function seedPengeluaranRequests(Branch $branch, User $admin): void
    {
        $requests = [
            ['uraian' => 'Pembelian kertas A4 5 rim', 'jumlah' => 275000, 'status' => 'draft', 'days_ago' => 1],
            ['uraian' => 'Bayar internet bulan Juni', 'jumlah' => 450000, 'status' => 'submitted', 'days_ago' => 2],
            ['uraian' => 'Pembelian tinta printer', 'jumlah' => 180000, 'status' => 'approved', 'days_ago' => 5],
            ['uraian' => 'Biaya cleaning service', 'jumlah' => 600000, 'status' => 'disbursed', 'days_ago' => 10],
            ['uraian' => 'Perbaikan atap bocor', 'jumlah' => 2500000, 'status' => 'rejected', 'days_ago' => 7],
            ['uraian' => 'Pembelian spidol whiteboard', 'jumlah' => 85000, 'status' => 'draft', 'days_ago' => 0],
        ];

        $aktiveTahunAjaran = \App\Models\TahunAjaran::getAktif($branch->id);

        foreach ($requests as $data) {
            $pengeluaranRequest = PengeluaranRequest::create([
                'uraian' => $data['uraian'],
                'jumlah' => $data['jumlah'],
                'tanggal_kebutuhan' => now()->addDays(rand(1, 14))->format('Y-m-d'),
                'kategori_pengeluaran' => fake()->randomElement(['ATK', 'Utilitas', 'Perbaikan', 'Kebersihan', null]),
                'status' => $data['status'],
                'requester_id' => $admin->id,
                'branch_id' => $branch->id,
            ]);

            // Saat status disbursed, buat record Pengeluaran yang terkait.
            if ($data['status'] === 'disbursed') {
                \App\Models\Pengeluaran::create([
                    'tanggal' => now()->subDays($data['days_ago'])->format('Y-m-d'),
                    'uraian' => $data['uraian'],
                    'jumlah' => $data['jumlah'],
                    'branch_id' => $branch->id,
                    'tahun_ajaran_id' => $aktiveTahunAjaran?->id,
                    'pengeluaran_request_id' => $pengeluaranRequest->id,
                ]);
            }
        }

        $this->command->info('  → 6 pengeluaran requests created (mixed statuses)');
    }

    private function seedSiswaUser(Branch $branch): void
    {
        // Create a siswa user account for portal testing
        $siswa = \App\Models\Siswa::where('branch_id', $branch->id)->first();

        if ($siswa) {
            $existingUser = User::where('username', 'siswa_test')->first();
            if (! $existingUser) {
                $user = User::create([
                    'username' => 'siswa_test',
                    'name' => 'Siswa Test - '.$siswa->nama,
                    'password' => Hash::make('password123'),
                    'branch_id' => $branch->id,
                    'siswa_id' => $siswa->id,
                ]);
                $user->assignRole('siswa');
                $this->command->info('  → Siswa user created (siswa_test / password123)');
            }
        }

        // Create a "must change password" user for testing ChangePassword page
        $existingNewUser = User::where('username', 'user_baru')->first();
        if (! $existingNewUser) {
            $newUser = User::create([
                'username' => 'user_baru',
                'name' => 'User Baru',
                'password' => Hash::make('password123'),
                'branch_id' => $branch->id,
                'must_change_password' => true,
            ]);
            $newUser->assignRole('admin');
            $this->command->info('  → Must-change-password user created (user_baru / password123)');
        }
    }
}
