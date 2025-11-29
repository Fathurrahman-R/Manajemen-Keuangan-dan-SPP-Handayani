<?php

namespace Database\Factories;

use App\Models\Ibu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ibu>
 */
class IbuFactory extends Factory
{
    protected $model = Ibu::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->name('female'),
            'pendidikan' => $this->faker->randomElement(['SD','SMP','SMA','D3','S1']),
            'pekerjaan' => $this->faker->jobTitle(),
        ];
    }
}
