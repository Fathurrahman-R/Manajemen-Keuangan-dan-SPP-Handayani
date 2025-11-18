<?php

namespace Database\Factories;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\GenerateKodePembayaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pembayaran>
 */
class PembayaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_pembayaran'=>GenerateKodePembayaran::generate(),
            'kode_tagihan'=>Tagihan::factory(),
            'tanggal'=>$this->faker->date(),
            'metode'=>$this->faker->randomElement(['Tunai','Non-Tunai']),
            'jumlah'=>100000,
            'pembayar'=>$this->faker->name()
        ];
    }
}
