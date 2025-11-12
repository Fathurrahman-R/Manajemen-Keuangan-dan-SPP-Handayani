<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    public function definition(): array
    {
        return [
            'jenjang'=>$this->faker->randomElement(['TK','MI','KB']),
            'nama'=>'KELAS 1',
        ];
    }
}
