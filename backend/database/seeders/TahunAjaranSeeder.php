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
                'nama' => '2024/2025',
                'branch_id' => $branch->id,
            ], [
                'tanggal_mulai' => '2024-07-01',
                'tanggal_selesai' => '2025-06-30',
                'status' => 'Aktif',
            ]);
        }
    }
}
