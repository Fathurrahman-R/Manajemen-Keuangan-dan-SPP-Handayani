<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Tiga tahun ajaran berurutan tanpa lompatan. Versi sebelumnya membuat
     * 2023/2024 lalu langsung 2025/2026 — celah satu tahun itu janggal untuk
     * sekolah yang berjalan terus, dan membuat riwayat kelas siswa mustahil
     * ditelusuri karena tidak ada tahun ajaran perantara.
     *
     * 2025/2026 sudah selesai dan dipakai sebagai sumber riwayat tagihan &
     * pembayaran satu tahun penuh; 2026/2027 adalah tahun berjalan.
     */
    public function run(): void
    {
        $daftar = [
            ['nama' => '2024/2025', 'mulai' => '2024-07-01', 'selesai' => '2025-06-30', 'status' => 'Non-Aktif'],
            ['nama' => '2025/2026', 'mulai' => '2025-07-01', 'selesai' => '2026-06-30', 'status' => 'Non-Aktif'],
            ['nama' => '2026/2027', 'mulai' => '2026-07-01', 'selesai' => '2027-06-30', 'status' => 'Aktif'],
        ];

        foreach (Branch::all() as $branch) {
            foreach ($daftar as $data) {
                TahunAjaran::firstOrCreate([
                    'nama' => $data['nama'],
                    'branch_id' => $branch->id,
                ], [
                    'tanggal_mulai' => $data['mulai'],
                    'tanggal_selesai' => $data['selesai'],
                    'status' => $data['status'],
                ]);
            }
        }
    }
}
