<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\NotificationSetting;
use Illuminate\Database\Seeder;

class NotificationSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            NotificationSetting::firstOrCreate(
                ['branch_id' => $branch->id],
                [
                    'tagihan_baru_enabled' => true,
                    'reminder_enabled' => true,
                    'kwitansi_enabled' => true,
                    'overdue_enabled' => true,
                    'reminder_days_before' => [7, 3, 1],
                    'overdue_interval_days' => 7,
                ]
            );
        }
    }
}
