<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JenisTagihan>
 */
class JenisTagihanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama'=>$this->faker->randomElement(['Pendaftaran Ulang','SPP Januari','SPP Februari','SPP Maret']),
            'jatuh_tempo'=>$this->faker->date(),
            'jumlah'=>$this->faker->numberBetween(50000,100000),
        ];
    }
}
