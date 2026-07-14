<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriNames = ['Bersaudara', 'Yatim', 'Piatu', 'Yatim Piatu', 'Umum'];
        foreach (Branch::all() as $branch) {
            foreach ($kategoriNames as $nama) {
                Kategori::firstOrCreate([
                    'nama' => $nama,
                    'branch_id' => $branch->id,
                ]);
            }
        }
    }
}
