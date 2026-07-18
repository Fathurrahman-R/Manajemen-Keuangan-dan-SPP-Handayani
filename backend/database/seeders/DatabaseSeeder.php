<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            PermissionResourceSeeder::class,
            PermissionMetadataSeeder::class,
            PermissionEndpointSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            AppSettingSeeder::class,
            KategoriSeeder::class,
            KelasSeeder::class,
            TahunAjaranSeeder::class,
            NotificationSettingSeeder::class,
            SiswaSeeder::class,
            JenisTagihanSeeder::class,
            TagihanSeeder::class,
            PembayaranSeeder::class,
//            PengeluaranSeeder::class,
            PengeluaranRequestSeeder::class,
        ]);
    }
}
