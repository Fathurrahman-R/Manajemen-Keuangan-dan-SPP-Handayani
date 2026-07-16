<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            TahunAjaran::firstOrCreate([
                'nama' => '2023/2024',
                'branch_id' => $branch->id,
            ], [
                'tanggal_mulai' => '2023-07-01',
                'tanggal_selesai' => '2024-06-30',
                'status' => 'Non-Aktif',
            ]);

            TahunAjaran::firstOrCreate([
                'nama' => '2025/2026',
                'branch_id' => $branch->id,
            ], [
                'tanggal_mulai' => '2025-07-01',
                'tanggal_selesai' => '2026-06-30',
                'status' => 'Non-Aktif',
            ]);

            TahunAjaran::firstOrCreate([
                'nama' => '2026/2027',
                'branch_id' => $branch->id,
            ], [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'status' => 'Aktif',
            ]);
        }
    }
}
