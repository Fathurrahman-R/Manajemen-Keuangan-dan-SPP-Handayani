<?php

namespace Database\Factories;

use App\Models\BatchPromosi;
use App\Models\Branch;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BatchPromosiFactory extends Factory
{
    protected $model = BatchPromosi::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'batch_type' => $this->faker->randomElement(['bulk_promotion', 'kelulusan', 'tinggal_kelas', 'pindah_jenjang']),
            'source_tahun_ajaran_id' => TahunAjaran::factory(),
            'target_tahun_ajaran_id' => TahunAjaran::factory(),
            'kelas_id' => Kelas::factory(),
            'processed_by' => User::factory(),
            'processed_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => 'completed',
            'branch_id' => Branch::factory(),
        ];
    }
}
