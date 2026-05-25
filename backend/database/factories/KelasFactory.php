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
            'jenjang' => $this->faker->randomElement(['TK', 'MI', 'KB']),
            'nama' => $this->faker->randomElement(['Kelas 1', 'Kelas 2', 'Kelas 3', 'TK A', 'TK B', 'KB']),
            'branch_id' => \App\Models\Branch::factory(),
            'level' => $this->faker->numberBetween(1, 6),
        ];
    }
}
