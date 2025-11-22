<?php

namespace Database\Seeders;

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
            WaliSeeder::class,
            KategoriSeeder::class,
            KelasSeeder::class,
            SiswaSeeder::class,
            AppSettingSeeder::class,
            JenisTagihanSeeder::class,
            TagihanSeeder::class,
            PembayaranSeeder::class,
        ]);
    }
}
