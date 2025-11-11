<?php

namespace Database\Factories;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Wali;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        return [
            'nis' => $this->faker->unique()->numerify('######'),
            'nisn' => $this->faker->unique()->numerify('######'),
            'nama' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'agama' => 'Islam',
            'alamat' => $this->faker->address(),

            // relasi otomatis
            'ayah_id' => Wali::factory(),
            'ibu_id' => Wali::factory(),
            'wali_id' => Wali::factory(),
            'kelas_id' => Kelas::factory(),
            'kategori_id' => Kategori::factory(),

            'jenjang' => 'MI',
            'asal_sekolah' => null,
            'kelas_diterima' => null,
            'tahun_diterima' => null,
            'status' => 'Aktif',
            'keterangan' => null,
        ];
    }
}
