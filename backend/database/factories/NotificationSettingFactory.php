<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationSettingFactory extends Factory
{
    protected $model = NotificationSetting::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'tagihan_baru_enabled' => true,
            'reminder_enabled' => true,
            'kwitansi_enabled' => true,
            'overdue_enabled' => true,
            'reminder_days_before' => [7, 3, 1],
            'overdue_interval_days' => 7,
        ];
    }
}
