<?php

namespace App\Notifications;

use App\Models\NotificationLog;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderJatuhTempoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        protected Tagihan $tagihan,
        protected Siswa $siswa,
        protected int $daysBefore
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
            ->subject('Pengingat Jatuh Tempo Tagihan - '.$this->siswa->nama)
            ->view('emails.notifications.reminder-jatuh-tempo', [
                'siswa' => $this->siswa,
                'tagihan' => $this->tagihan,
                'daysBefore' => $this->daysBefore,
                'unsubscribeUrl' => '#unsubscribe',
            ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        NotificationLog::where('tagihan_kode', $this->tagihan->kode_tagihan)
            ->where('notification_type', 'reminder_jatuh_tempo')
            ->where('status', 'sent')
            ->latest()
            ->first()
            ?->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }
}
