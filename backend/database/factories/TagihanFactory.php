<?php

namespace Database\Factories;

use App\Models\JenisTagihan;
use App\Models\Siswa;
use App\Services\GenerateKodeTagihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tagihan>
 */
class TagihanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_tagihan'=>GenerateKodeTagihan::generate(),
            'jenis_tagihan_id'=>JenisTagihan::factory(),
            'nis'=>Siswa::factory(),
            'status'=>'Belum Dibayar',
        ];
    }
}
