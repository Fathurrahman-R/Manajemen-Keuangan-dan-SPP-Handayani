<?php

namespace Database\Factories;

use App\Models\Ayah;
use App\Models\Ibu;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Wali;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Siswa>
 */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        return [
            'nis' => $this->faker->unique()->numerify('######'),
//            'nis' => '000001',
            'nisn' => $this->faker->unique()->numerify('######'),
//            'nisn' => '000001',
            'nama' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'agama' => 'Islam',
            'alamat' => $this->faker->address(),

            // relasi otomatis
            'ayah_id' => Ayah::factory(),
            'ibu_id' => Ibu::factory(),
//            'wali_id' => Wali::factory(),
            'kelas_id' => $this->faker->numberBetween(1,6),
            'kategori_id' => $this->faker->numberBetween(1,5),

            'jenjang' => 'MI',
            'asal_sekolah' => null,
            'kelas_diterima' => null,
            'tahun_diterima' => null,
            'status' => 'Aktif',
            'keterangan' => null,
        ];
    }

    public function custom()
    {
        return $this->state(function (array $attributes) {
            return [
                'nis' =>'000001',
                'nisn' =>'000001',
            ];
        });
    }
}
