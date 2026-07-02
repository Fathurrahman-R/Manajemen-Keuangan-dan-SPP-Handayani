<?php

namespace App\Services\Notifications;

use App\Helpers\NotificationHelper;
use App\Models\Branch;
use App\Models\EmailOptOut;
use App\Models\NotificationLog;
use App\Models\NotificationSentRecord;
use App\Models\NotificationSetting;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Notifications\KwitansiPembayaranNotification;
use App\Notifications\ReminderJatuhTempoNotification;
use App\Notifications\TagihanBaruNotification;
use App\Notifications\TagihanOverdueNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

class NotificationService
{
    public function __construct(
        protected RecipientResolver $recipientResolver
    ) {}

    /**
     * Check if a notification type is enabled for a branch.
     */
    public function isEnabled(int $branchId, string $type): bool
    {
        $setting = NotificationSetting::where('branch_id', $branchId)->first();

        if (!$setting) {
            return true; // Default enabled if no settings configured
        }

        return match ($type) {
            'tagihan_baru' => $setting->tagihan_baru_enabled,
            'reminder' => $setting->reminder_enabled,
            'kwitansi' => $setting->kwitansi_enabled,
            'overdue' => $setting->overdue_enabled,
            default => false,
        };
    }

    /**
     * Check if an email has opted out of a notification type.
     */
    public function isOptedOut(string $email, string $type): bool
    {
        return EmailOptOut::isOptedOut($email, $type);
    }

    /**
     * Validate email format.
     */
    public function validateEmail(?string $email): bool
    {
        return NotificationHelper::isValidEmail($email);
    }

    /**
     * Log a notification event.
     */
    public function logNotification(array $data): NotificationLog
    {
        return NotificationLog::create([
            'branch_id' => $data['branch_id'],
            'recipient_email' => $data['recipient_email'],
            'notification_type' => $data['notification_type'],
            'tagihan_kode' => $data['tagihan_kode'] ?? null,
            'status' => $data['status'],
            'reason' => $data['reason'] ?? null,
            'error_message' => $data['error_message'] ?? null,
            'sent_at' => $data['status'] === 'sent' ? now() : null,
        ]);
    }

    /**
     * Check rate limit for a branch (max 100 emails per hour per branch).
     */
    public function checkRateLimit(int $branchId): bool
    {
        $key = "notification-branch-{$branchId}";

        if (RateLimiter::tooManyAttempts($key, 100)) {
            return false; // Rate limited
        }

        RateLimiter::hit($key, 3600); // 1 hour window
        return true;
    }

    /**
     * Get the RecipientResolver instance.
     */
    public function getRecipientResolver(): RecipientResolver
    {
        return $this->recipientResolver;
    }

    /**
     * Send tagihan baru notification for a batch of tagihans belonging to one siswa.
     */
    public function sendTagihanBaru(Collection $tagihans, Siswa $siswa): void
    {
        $branchId = $siswa->branch_id;
        $tagihanKode = $tagihans->first()?->kode_tagihan;

        // Check branch setting enabled
        if (!$this->isEnabled($branchId, 'tagihan_baru')) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => '',
                'notification_type' => 'tagihan_baru',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'disabled',
            ]);
            return;
        }

        // Resolve recipient
        $siswa->loadMissing(['user', 'wali', 'ibu', 'ayah']);
        $email = $this->recipientResolver->resolve($siswa);

        if (!$email) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => '',
                'notification_type' => 'tagihan_baru',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'no_email_available',
            ]);
            return;
        }

        // Check opt-out
        if ($this->isOptedOut($email, 'tagihan_baru')) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'tagihan_baru',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'opted_out',
            ]);
            return;
        }

        // Validate email
        if (!$this->validateEmail($email)) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'tagihan_baru',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'invalid_email',
            ]);
            return;
        }

        // Check rate limit
        if (!$this->checkRateLimit($branchId)) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'tagihan_baru',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'rate_limited',
            ]);
            return;
        }

        // Dispatch notification
        try {
            Notification::route('mail', $email)
                ->notify(new TagihanBaruNotification($tagihans, $siswa));

            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'tagihan_baru',
                'tagihan_kode' => $tagihanKode,
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send tagihan baru notification', [
                'siswa_nis' => $siswa->nis,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'tagihan_baru',
                'tagihan_kode' => $tagihanKode,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send kwitansi pembayaran notification after a payment is recorded.
     */
    public function sendKwitansiPembayaran(Pembayaran $pembayaran): void
    {
        // Load siswa from pembayaran->tagihan->siswa relationship
        $pembayaran->loadMissing(['tagihan.siswa.user', 'tagihan.siswa.wali', 'tagihan.siswa.ibu', 'tagihan.siswa.ayah']);
        $siswa = $pembayaran->tagihan->siswa;
        $branchId = $pembayaran->branch_id;
        $tagihanKode = $pembayaran->kode_tagihan;

        // Check branch setting enabled
        if (!$this->isEnabled($branchId, 'kwitansi')) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => '',
                'notification_type' => 'kwitansi',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'disabled',
            ]);
            return;
        }

        // Resolve recipient
        $email = $this->recipientResolver->resolve($siswa);

        if (!$email) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => '',
                'notification_type' => 'kwitansi',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'no_email_available',
            ]);
            return;
        }

        // Check opt-out
        if ($this->isOptedOut($email, 'kwitansi')) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'kwitansi',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'opted_out',
            ]);
            return;
        }

        // Validate email
        if (!$this->validateEmail($email)) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'kwitansi',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'invalid_email',
            ]);
            return;
        }

        // Check rate limit
        if (!$this->checkRateLimit($branchId)) {
            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'kwitansi',
                'tagihan_kode' => $tagihanKode,
                'status' => 'skipped',
                'reason' => 'rate_limited',
            ]);
            return;
        }

        // Dispatch notification
        try {
            Notification::route('mail', $email)
                ->notify(new KwitansiPembayaranNotification($pembayaran, $siswa));

            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'kwitansi',
                'tagihan_kode' => $tagihanKode,
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send kwitansi pembayaran notification', [
                'pembayaran_kode' => $pembayaran->kode_pembayaran,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            $this->logNotification([
                'branch_id' => $branchId,
                'recipient_email' => $email,
                'notification_type' => 'kwitansi',
                'tagihan_kode' => $tagihanKode,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process reminder notifications for all branches with reminders enabled.
     * Queries tagihan with upcoming jatuh_tempo based on branch reminder_days_before settings.
     */
    public function processReminders(): void
    {
        $settings = NotificationSetting::where('reminder_enabled', true)->get();

        foreach ($settings as $setting) {
            $branchId = $setting->branch_id;
            $reminderDays = $setting->reminder_days_before ?? [7, 3, 0];

            foreach ($reminderDays as $daysBefore) {
                $targetDate = Carbon::today()->addDays($daysBefore)->toDateString();

                // Query tagihan with jatuh_tempo matching the target date
                $tagihans = Tagihan::where('branch_id', $branchId)
                    ->where('status', '!=', 'Lunas')
                    ->whereHas('jenis_tagihan', function ($query) use ($targetDate) {
                        $query->where('jatuh_tempo', $targetDate);
                    })
                    ->with(['siswa.wali', 'siswa.ibu', 'siswa.ayah', 'jenis_tagihan'])
                    ->get();

                foreach ($tagihans as $tagihan) {
                    $notificationType = "reminder";

                    // Check if already sent today
                    if (NotificationSentRecord::alreadySent($tagihan->kode_tagihan, $notificationType)) {
                        continue;
                    }

                    $siswa = $tagihan->siswa;
                    if (!$siswa) {
                        continue;
                    }

                    // Resolve recipient
                    $email = $this->recipientResolver->resolve($siswa);
                    if (!$email) {
                        $this->logNotification([
                            'branch_id' => $branchId,
                            'recipient_email' => '',
                            'notification_type' => 'reminder',
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'status' => 'skipped',
                            'reason' => 'no_email_available',
                        ]);
                        continue;
                    }

                    // Check opt-out
                    if ($this->isOptedOut($email, 'reminder')) {
                        $this->logNotification([
                            'branch_id' => $branchId,
                            'recipient_email' => $email,
                            'notification_type' => 'reminder',
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'status' => 'skipped',
                            'reason' => 'opted_out',
                        ]);
                        continue;
                    }

                    // Validate email
                    if (!$this->validateEmail($email)) {
                        $this->logNotification([
                            'branch_id' => $branchId,
                            'recipient_email' => $email,
                            'notification_type' => 'reminder',
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'status' => 'skipped',
                            'reason' => 'invalid_email',
                        ]);
                        continue;
                    }

                    // Check rate limit
                    if (!$this->checkRateLimit($branchId)) {
                        $this->logNotification([
                            'branch_id' => $branchId,
                            'recipient_email' => $email,
                            'notification_type' => 'reminder',
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'status' => 'skipped',
                            'reason' => 'rate_limited',
                        ]);
                        continue;
                    }

                    // Dispatch notification
                    try {
                        Notification::route('mail', $email)
                            ->notify(new ReminderJatuhTempoNotification($tagihan, $siswa, $daysBefore));

                        // Record sent to prevent duplicates
                        NotificationSentRecord::create([
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'notification_type' => $notificationType,
                            'sent_date' => Carbon::today()->toDateString(),
                        ]);

                        $this->logNotification([
                            'branch_id' => $branchId,
                            'recipient_email' => $email,
                            'notification_type' => 'reminder',
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'status' => 'sent',
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Failed to send reminder notification', [
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);

                        $this->logNotification([
                            'branch_id' => $branchId,
                            'recipient_email' => $email,
                            'notification_type' => 'reminder',
                            'tagihan_kode' => $tagihan->kode_tagihan,
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Process overdue notifications for all branches with overdue enabled.
     * Sends notifications for tagihan past jatuh_tempo at configured intervals.
     */
    public function processOverdue(): void
    {
        $settings = NotificationSetting::where('overdue_enabled', true)->get();

        foreach ($settings as $setting) {
            $branchId = $setting->branch_id;
            $intervalDays = $setting->overdue_interval_days ?? 7;

            // Query tagihan with jatuh_tempo in the past (overdue)
            $tagihans = Tagihan::where('branch_id', $branchId)
                ->where('status', '!=', 'Lunas')
                ->whereHas('jenis_tagihan', function ($query) {
                    $query->where('jatuh_tempo', '<', Carbon::today()->toDateString());
                })
                ->with(['siswa.wali', 'siswa.ibu', 'siswa.ayah', 'jenis_tagihan'])
                ->get();

            foreach ($tagihans as $tagihan) {
                // Check if last overdue notification was sent >= interval days ago
                $lastSent = NotificationSentRecord::where('tagihan_kode', $tagihan->kode_tagihan)
                    ->where('notification_type', 'overdue')
                    ->orderBy('sent_date', 'desc')
                    ->first();

                if ($lastSent) {
                    $daysSinceLastSent = Carbon::parse($lastSent->sent_date)->diffInDays(Carbon::today());
                    if ($daysSinceLastSent < $intervalDays) {
                        continue; // Not yet time for next overdue notification
                    }
                }

                $siswa = $tagihan->siswa;
                if (!$siswa) {
                    continue;
                }

                // Resolve recipient
                $email = $this->recipientResolver->resolve($siswa);
                if (!$email) {
                    $this->logNotification([
                        'branch_id' => $branchId,
                        'recipient_email' => '',
                        'notification_type' => 'overdue',
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'status' => 'skipped',
                        'reason' => 'no_email_available',
                    ]);
                    continue;
                }

                // Check opt-out
                if ($this->isOptedOut($email, 'overdue')) {
                    $this->logNotification([
                        'branch_id' => $branchId,
                        'recipient_email' => $email,
                        'notification_type' => 'overdue',
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'status' => 'skipped',
                        'reason' => 'opted_out',
                    ]);
                    continue;
                }

                // Validate email
                if (!$this->validateEmail($email)) {
                    $this->logNotification([
                        'branch_id' => $branchId,
                        'recipient_email' => $email,
                        'notification_type' => 'overdue',
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'status' => 'skipped',
                        'reason' => 'invalid_email',
                    ]);
                    continue;
                }

                // Check rate limit
                if (!$this->checkRateLimit($branchId)) {
                    $this->logNotification([
                        'branch_id' => $branchId,
                        'recipient_email' => $email,
                        'notification_type' => 'overdue',
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'status' => 'skipped',
                        'reason' => 'rate_limited',
                    ]);
                    continue;
                }

                // Calculate days overdue
                $daysOverdue = Carbon::parse($tagihan->jenis_tagihan->jatuh_tempo)
                    ->diffInDays(Carbon::today());

                // Dispatch notification
                try {
                    Notification::route('mail', $email)
                        ->notify(new TagihanOverdueNotification($tagihan, $siswa, $daysOverdue));

                    // Record sent to prevent duplicates within interval
                    NotificationSentRecord::create([
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'notification_type' => 'overdue',
                        'sent_date' => Carbon::today()->toDateString(),
                    ]);

                    $this->logNotification([
                        'branch_id' => $branchId,
                        'recipient_email' => $email,
                        'notification_type' => 'overdue',
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'status' => 'sent',
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send overdue notification', [
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);

                    $this->logNotification([
                        'branch_id' => $branchId,
                        'recipient_email' => $email,
                        'notification_type' => 'overdue',
                        'tagihan_kode' => $tagihan->kode_tagihan,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Retry failed notifications by their log IDs.
     * Re-dispatches the appropriate notification based on notification_type.
     *
     * @return int Number of successfully retried notifications
     */
    public function retryFailed(array $logIds): int
    {
        $logs = NotificationLog::whereIn('id', $logIds)
            ->where('status', 'failed')
            ->get();

        $retriedCount = 0;

        foreach ($logs as $log) {
            try {
                $email = $log->recipient_email;

                // Validate email before retrying
                if (!$this->validateEmail($email)) {
                    $log->update([
                        'status' => 'skipped',
                        'reason' => 'invalid_email',
                    ]);
                    continue;
                }

                // Check rate limit
                if (!$this->checkRateLimit($log->branch_id)) {
                    continue; // Will be retried later
                }

                // Re-dispatch based on notification type
                switch ($log->notification_type) {
                    case 'tagihan_baru':
                        $tagihan = Tagihan::where('kode_tagihan', $log->tagihan_kode)
                            ->with(['siswa.wali', 'siswa.ibu', 'siswa.ayah'])
                            ->first();

                        if ($tagihan && $tagihan->siswa) {
                            Notification::route('mail', $email)
                                ->notify(new TagihanBaruNotification(
                                    collect([$tagihan]),
                                    $tagihan->siswa
                                ));
                        }
                        break;

                    case 'reminder':
                        $tagihan = Tagihan::where('kode_tagihan', $log->tagihan_kode)
                            ->with(['siswa.wali', 'siswa.ibu', 'siswa.ayah', 'jenis_tagihan'])
                            ->first();

                        if ($tagihan && $tagihan->siswa) {
                            $daysRemaining = Carbon::today()
                                ->diffInDays(Carbon::parse($tagihan->jenis_tagihan->jatuh_tempo), false);

                            Notification::route('mail', $email)
                                ->notify(new ReminderJatuhTempoNotification(
                                    $tagihan,
                                    $tagihan->siswa,
                                    max(0, $daysRemaining)
                                ));
                        }
                        break;

                    case 'kwitansi':
                        $pembayaran = Pembayaran::where('kode_tagihan', $log->tagihan_kode)
                            ->with(['tagihan.siswa.wali', 'tagihan.siswa.ibu', 'tagihan.siswa.ayah'])
                            ->latest()
                            ->first();

                        if ($pembayaran && $pembayaran->tagihan && $pembayaran->tagihan->siswa) {
                            Notification::route('mail', $email)
                                ->notify(new KwitansiPembayaranNotification(
                                    $pembayaran,
                                    $pembayaran->tagihan->siswa
                                ));
                        }
                        break;

                    case 'overdue':
                        $tagihan = Tagihan::where('kode_tagihan', $log->tagihan_kode)
                            ->with(['siswa.wali', 'siswa.ibu', 'siswa.ayah', 'jenis_tagihan'])
                            ->first();

                        if ($tagihan && $tagihan->siswa) {
                            $daysOverdue = Carbon::parse($tagihan->jenis_tagihan->jatuh_tempo)
                                ->diffInDays(Carbon::today());

                            Notification::route('mail', $email)
                                ->notify(new TagihanOverdueNotification(
                                    $tagihan,
                                    $tagihan->siswa,
                                    $daysOverdue
                                ));
                        }
                        break;

                    default:
                        continue 2; // Skip unknown notification types
                }

                // Update log status to sent
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

                $retriedCount++;
            } catch (\Throwable $e) {
                Log::error('Failed to retry notification', [
                    'log_id' => $log->id,
                    'notification_type' => $log->notification_type,
                    'error' => $e->getMessage(),
                ]);

                $log->update([
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $retriedCount;
    }
}
