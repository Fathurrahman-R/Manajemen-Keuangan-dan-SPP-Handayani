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
            'bendahara' => 'Siti Bendahara',
            'kepala_sekolah' => 'Ahmad Kepala',
            'telepon' => '081234567890',
            'email' => 'info@sekolahcontoh.test',
            'alamat' => 'Jl. Pendidikan No. 1',
            'lokasi' => 'Kecamatan Contoh',
            'nama_sekolah' => 'SD Islam Contoh',
        ]);
    }
}
