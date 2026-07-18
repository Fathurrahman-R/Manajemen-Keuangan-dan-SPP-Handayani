<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            'Selat Panjang',
            'Desa Kapur',
            'Darma Putra',
        ];

        foreach ($branches as $location) {
            Branch::firstOrCreate(['location' => $location]);
        }
    }
}
