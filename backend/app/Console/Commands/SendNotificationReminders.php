<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;

class SendNotificationReminders extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $description = 'Send reminder and overdue notifications for upcoming and past-due tagihan';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Processing reminder notifications...');
        $notificationService->processReminders();

        $this->info('Processing overdue notifications...');
        $notificationService->processOverdue();

        $this->info('Notification processing complete.');
        return Command::SUCCESS;
    }
}
