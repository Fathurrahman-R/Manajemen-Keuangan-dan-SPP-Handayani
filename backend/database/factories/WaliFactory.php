<?php

namespace Database\Factories;

use App\Models\Wali;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaliFactory extends Factory
{
    protected $model = Wali::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'agama'=>'islam',
            'pendidikan_terakhir'=>$this->faker->randomElement(['SD','SMP','SMA','SMK','D1','D2','D3','D4','S1','S2']),
            'pekerjaan'=>$this->faker->jobTitle(),
            'alamat' => $this->faker->address(),
            'no_hp' => $this->faker->phoneNumber(),
            'keterangan' => $this->faker->text(),
        ];
    }
}
