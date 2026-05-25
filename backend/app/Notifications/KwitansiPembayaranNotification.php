<?php

namespace App\Notifications;

use App\Models\NotificationLog;
use App\Models\Pembayaran;
use App\Models\Siswa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KwitansiPembayaranNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        protected Pembayaran $pembayaran,
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
            ->subject('Kwitansi Pembayaran - ' . $this->siswa->nama)
            ->view('emails.notifications.kwitansi-pembayaran', [
                'siswa' => $this->siswa,
                'pembayaran' => $this->pembayaran,
                'unsubscribeUrl' => '#unsubscribe',
            ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        NotificationLog::where('tagihan_kode', $this->pembayaran->kode_tagihan)
            ->where('notification_type', 'kwitansi_pembayaran')
            ->where('status', 'sent')
            ->latest()
            ->first()
            ?->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }
}
