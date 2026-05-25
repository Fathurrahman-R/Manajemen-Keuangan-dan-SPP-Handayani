<?php

namespace App\Notifications;

use App\Models\NotificationLog;
use App\Models\Siswa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class TagihanBaruNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        protected Collection $tagihans,
        protected Siswa $siswa
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
            ->subject('Tagihan Baru - ' . $this->siswa->nama)
            ->view('emails.notifications.tagihan-baru', [
                'siswa' => $this->siswa,
                'tagihans' => $this->tagihans,
                'unsubscribeUrl' => '#unsubscribe',
            ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $tagihanKode = $this->tagihans->first()?->kode_tagihan;

        NotificationLog::where('tagihan_kode', $tagihanKode)
            ->where('notification_type', 'tagihan_baru')
            ->where('status', 'sent')
            ->latest()
            ->first()
            ?->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }
}
