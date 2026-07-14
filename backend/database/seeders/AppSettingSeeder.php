<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            AppSetting::firstOrCreate(
                ['branch_id' => $branch->id],
                [
                    'nama_sekolah' => 'Lembaga Pendidikan Handayani '.$branch->location,
                    'lokasi' => $branch->location,
                    'alamat' => 'Jl. Pendidikan No. '.rand(1, 100).', '.$branch->location,
                    'email' => 'handayani.'.strtolower(str_replace(' ', '', $branch->location)).'@example.com',
                    'telepon' => '0761-'.rand(100000, 999999),
                    'kepala_sekolah' => fake()->name(),
                    'bendahara' => fake()->name(),
                    'kode_pos' => (string) rand(28000, 29999),
                    'logo' => 'logo-handayani.png',
                ]
            );
        }
    }
}
