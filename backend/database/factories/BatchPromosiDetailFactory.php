<?php

namespace Database\Factories;

use App\Models\BatchPromosiDetail;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchPromosiDetailFactory extends Factory
{
    protected $model = BatchPromosiDetail::class;

    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'action' => $this->faker->randomElement(['naik_kelas', 'tinggal_kelas', 'lulus', 'pindah_jenjang']),
            'source_kelas_id' => Kelas::factory(),
            'target_kelas_id' => Kelas::factory(),
            'previous_status' => 'Aktif',
            'previous_jenjang' => null,
        ];
    }
}
