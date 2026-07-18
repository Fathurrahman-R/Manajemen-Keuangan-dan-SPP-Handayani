<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'recipient_email' => $this->faker->safeEmail(),
            'notification_type' => $this->faker->randomElement(['tagihan_baru', 'reminder', 'kwitansi', 'overdue']),
            'tagihan_kode' => null,
            'status' => $this->faker->randomElement(['sent', 'failed', 'skipped']),
            'reason' => null,
            'error_message' => null,
            'sent_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
