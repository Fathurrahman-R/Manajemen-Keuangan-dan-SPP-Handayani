<?php

namespace App\Listeners;

use App\Events\PembayaranRecorded;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendKwitansiNotification implements ShouldQueue
{
    public $queue = 'notifications';

    public function __construct(protected NotificationService $notificationService) {}

    public function handle(PembayaranRecorded $event): void
    {
        $this->notificationService->sendKwitansiPembayaran($event->pembayaran);
    }
}
