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
            'pekerjaan'=>$this->faker->jobTitle(),
            'alamat' => $this->faker->address(),
            'no_hp' => $this->faker->numerify('08##########'),
            'keterangan' => $this->faker->optional()->sentence(),
        ];
    }
}
