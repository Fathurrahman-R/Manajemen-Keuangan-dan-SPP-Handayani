<?php

namespace App\Notifications;

use App\Models\NotificationLog;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TagihanOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        protected Tagihan $tagihan,
        protected Siswa $siswa,
        protected int $daysOverdue
    ) {
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tagihan Terlambat - '.$this->siswa->nama)
            ->view('emails.notifications.tagihan-overdue', [
                'siswa' => $this->siswa,
                'tagihan' => $this->tagihan,
                'daysOverdue' => $this->daysOverdue,
                'unsubscribeUrl' => '#unsubscribe',
            ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        NotificationLog::where('tagihan_kode', $this->tagihan->kode_tagihan)
            ->where('notification_type', 'tagihan_overdue')
            ->where('status', 'sent')
            ->latest()
            ->first()
            ?->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }
}
