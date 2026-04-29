<?php

namespace Database\Seeders;

use App\Models\Ayah;
use App\Models\Branch;
use App\Models\Ibu;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Wali;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
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
            'jenjang'=>'MI',
            'branch_id'=>1,
        ]);
        $tk = Kelas::factory()->create([
            'jenjang'=>'TK',
            'nama'=>'MATAHARI',
            'branch_id'=>1,
        ]);
        Siswa::factory(58)->create([
            'nisn'=>null,
            'jenjang'=>'TK',
            'kelas_id' => $tk->id,
            'ayah_id'=>null,
            'ibu_id'=>null,
            'wali_id' => Wali::factory(),
            'branch_id'=>1,
        ]);
        $kb = Kelas::factory()->create([
            'jenjang'=>'KB',
            'nama'=>'AMAN',
            'branch_id'=>1,
        ]);
        Siswa::factory(6)->create([
            'nisn'=>null,
            'jenjang'=>'KB',
            'kelas_id' => $kb->id,
            'ayah_id'=>null,
            'ibu_id'=>null,
            'wali_id' => Wali::factory(),
            'branch_id'=>1,
        ]);
    }
}
