<?php

namespace Database\Factories;

use App\Models\Ayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ayah>
 */
class AyahFactory extends Factory
{
    protected $model = Ayah::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->name('male'),
            'pendidikan' => $this->faker->randomElement(['SD','SMP','SMA','D3','S1']),
            'pekerjaan' => $this->faker->jobTitle(),
        ];
    }
}
