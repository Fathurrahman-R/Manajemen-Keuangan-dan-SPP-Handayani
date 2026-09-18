<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            AppSettingSeeder::class,
            KategoriSeeder::class,
            KelasSeeder::class,
            TahunAjaranSeeder::class,
            NotificationSettingSeeder::class,
            SiswaSeeder::class,

            // Urutannya mengikat: JenisTagihan harus ada sebelum Tagihan,
            // Tagihan sebelum Pembayaran (pembayaran membaca `tmp` dan jatuh
            // tempo tagihan), dan PengeluaranRequest terakhir karena ikut
            // menulis baris Pengeluaran untuk permintaan yang sudah cair.
            JenisTagihanSeeder::class,
            TagihanSeeder::class,
            PembayaranSeeder::class,
            PengeluaranSeeder::class,
            PengeluaranRequestSeeder::class,
        ]);
    }
}
