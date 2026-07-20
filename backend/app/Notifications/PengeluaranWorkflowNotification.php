<?php

namespace App\Notifications;

use App\Models\NotificationLog;
use App\Models\PengeluaranRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class PengeluaranWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * ID of the `notification_logs` row WorkflowNotificationService wrote when
     * this notification was dispatched to the queue (logged optimistically as
     * `sent` at that point — dispatch succeeding just means the job was queued,
     * not that mail delivery succeeded). Set via `withLogId()` before `notify()`
     * so `failed()` below can correct that row to `failed` once the queue
     * exhausts all retries — without this, a queued send that fails after
     * dispatch never touches notification_logs again and stays stuck showing
     * `sent`.
     */
    public ?int $notificationLogId = null;

    public function __construct(
        protected PengeluaranRequest $pengeluaranRequest,
        protected string $event,
        protected ?string $reason = null,
        protected ?string $requesterName = null,
    ) {
        $this->onQueue('notifications');
    }

    public function withLogId(int $notificationLogId): static
    {
        $this->notificationLogId = $notificationLogId;

        return $this;
    }

    /**
     * Called by the queue worker once this job has exhausted all `$tries`
     * attempts. Corrects the `notification_logs` row from `sent` to `failed`
     * — the only place that actually reflects a queued send's real outcome.
     */
    public function failed(Throwable $exception): void
    {
        if ($this->notificationLogId === null) {
            return;
        }

        NotificationLog::where('id', $this->notificationLogId)
            ->where('status', 'sent')
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->pengeluaranRequest->loadMissing(['requester', 'approvalLogs.user']);
        $logs = $this->pengeluaranRequest->approvalLogs;

        $history = [
            'submitted' => $logs->where('new_status', 'submitted')->last(),
            'approved' => $logs->where('new_status', 'approved')->last(),
            'rejected' => $logs->where('new_status', 'rejected')->last(),
            'disbursed' => $logs->where('new_status', 'disbursed')->last(),
        ];

        return (new MailMessage)
            ->subject($this->getSubject())
            ->view('emails.notifications.pengeluaran-workflow', [
                'pengeluaranRequest' => $this->pengeluaranRequest,
                'event' => $this->event,
                'reason' => $this->reason,
                'requesterName' => $this->requesterName ?? $this->pengeluaranRequest->requester?->name ?? 'Sistem',
                'title' => $this->getTitle(),
                'notificationMessage' => $this->getMessage(),
                'history' => $history,
            ]);
    }

    protected function getSubject(): string
    {
        return match ($this->event) {
            'submitted' => 'Request Pengeluaran Baru Menunggu Persetujuan',
            'approved' => 'Request Pengeluaran Disetujui',
            'rejected' => 'Request Pengeluaran Ditolak',
            'disbursed' => 'Pencairan Pengeluaran Selesai',
            default => 'Update Request Pengeluaran',
        };
    }

    protected function getTitle(): string
    {
        return match ($this->event) {
            'submitted' => 'Request Pengeluaran Baru',
            'approved' => 'Request Disetujui',
            'rejected' => 'Request Ditolak',
            'disbursed' => 'Pencairan Selesai',
            default => 'Update Request',
        };
    }

    protected function getMessage(): string
    {
        $uraian = $this->pengeluaranRequest->uraian;
        $jumlah = number_format($this->pengeluaranRequest->jumlah, 0, ',', '.');

        return match ($this->event) {
            'submitted' => "Request pengeluaran \"{$uraian}\" senilai Rp {$jumlah} dari {$this->requesterName} menunggu persetujuan Anda.",
            'approved' => "Request pengeluaran \"{$uraian}\" senilai Rp {$jumlah} telah disetujui. Silakan lakukan pencairan.",
            'rejected' => "Request pengeluaran \"{$uraian}\" senilai Rp {$jumlah} ditolak.".($this->reason ? " Alasan: {$this->reason}" : ''),
            'disbursed' => "Request pengeluaran \"{$uraian}\" telah dicairkan senilai Rp {$jumlah}.",
            default => "Status request pengeluaran \"{$uraian}\" telah diperbarui.",
        };
    }
}
