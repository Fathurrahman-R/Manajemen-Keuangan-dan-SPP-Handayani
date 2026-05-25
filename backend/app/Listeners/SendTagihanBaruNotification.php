<?php

namespace App\Listeners;

use App\Events\TagihanCreated;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTagihanBaruNotification implements ShouldQueue
{
    public $queue = 'notifications';

    public function __construct(protected NotificationService $notificationService) {}

    public function handle(TagihanCreated $event): void
    {
        $this->notificationService->sendTagihanBaru($event->tagihans, $event->siswa);
    }
}
