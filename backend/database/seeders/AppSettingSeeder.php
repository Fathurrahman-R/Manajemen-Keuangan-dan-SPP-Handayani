<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\AppSetting;
class AppSettingSeeder extends Seeder
{
    public function run(): void{
        AppSetting::create([
            'logo' => 'logo.png',
            'kode_pos' => '12345',
            'bendahara' => 'Siti',
            'kepala_sekolah' => 'Ahmad',
            'telepon' => '081234567890',
            'email' => 'info@sekolahcontoh.test',
            'alamat' => 'Jl. Parit H. Husai 2, Komp. Acisa Asri No.47, Bansir Darat Pontianak Tenggara',
            'lokasi' => 'Pontianak',
            'nama_sekolah' => 'LPA Handayani',
        ]);
    }
}
