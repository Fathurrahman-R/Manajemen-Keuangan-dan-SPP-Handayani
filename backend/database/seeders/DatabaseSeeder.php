<?php

namespace Database\Seeders;

use App\Models\Ayah;
use App\Models\Ibu;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
//            WaliSeeder::class,
//            AyahSeeder::class,
//            IbuSeeder::class,
            KategoriSeeder::class,
            KelasSeeder::class,
//            SiswaSeeder::class,
            AppSettingSeeder::class,
//            JenisTagihanSeeder::class,
//            TagihanSeeder::class,
//            PembayaranSeeder::class,
        ]);
        Siswa::factory(100)->create([
            'jenjang'=>'MI'
        ]);
        Siswa::factory(58)->create([
            'jenjang'=>'TK'
        ]);
        Siswa::factory(6)->create([
            'jenjang'=>'KB'
        ]);
    }
}
