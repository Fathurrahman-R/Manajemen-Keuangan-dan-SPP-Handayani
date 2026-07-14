<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TahunAjaran>
 */
class TahunAjaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2020, 2026);
        $endYear = $startYear + 1;

        return [
            'nama' => "{$startYear}/{$endYear}",
            'tanggal_mulai' => "{$startYear}-07-01",
            'tanggal_selesai' => "{$endYear}-06-30",
            'status' => 'Non-Aktif',
            'branch_id' => Branch::factory(),
        ];
    }

    /**
     * Indicate that the TahunAjaran is active.
     */
    public function aktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Aktif',
        ]);
    }
}
