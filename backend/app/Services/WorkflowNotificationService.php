<?php

namespace App\Services;

use App\Models\EmailOptOut;
use App\Models\PengeluaranRequest;
use App\Models\User;
use App\Notifications\PengeluaranWorkflowNotification;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class WorkflowNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Notify all users with approve-pengeluaran permission in the same branch via email.
     */
    public function notifyApprovers(PengeluaranRequest $request): void
    {
        $approvers = User::where('branch_id', $request->branch_id)
            ->where('is_active', true)
            ->permission('approve-pengeluaran')
            ->get();

        $requesterName = $request->requester->name ?? $request->requester->username ?? 'Tidak diketahui';

        foreach ($approvers as $approver) {
            $email = $approver->email;

            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::info('Skipped pengeluaran workflow email: no valid email', [
                    'user_id' => $approver->id,
                    'pengeluaran_request_id' => $request->id,
                ]);

                continue;
            }

            if (EmailOptOut::isOptedOut($email, 'workflow')) {
                $this->logWorkflowNotification($request, $email, 'submitted', 'skipped', reason: 'opted_out');

                continue;
            }

            $log = $this->logWorkflowNotification($request, $email, 'submitted', 'sent');

            try {
                Notification::route('mail', $email)
                    ->notify((new PengeluaranWorkflowNotification(
                        $request,
                        'submitted',
                        null,
                        $requesterName,
                    ))->withLogId($log->id));
            } catch (\Throwable $e) {
                Log::error('Failed to send pengeluaran workflow email to approver', [
                    'user_id' => $approver->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }
    }

    /**
     * Notify the requester (and disburser) about status change via email.
     */
    public function notifyRequester(PengeluaranRequest $request, string $event, ?string $reason = null): void
    {
        $requester = $request->requester;
        $recipients = collect();

        if ($requester && ! empty($requester->email) && filter_var($requester->email, FILTER_VALIDATE_EMAIL)) {
            $recipients->push($requester->email);
        }

        // Jika event pencairan, tambahkan juga pencair ke penerima email
        if ($event === 'disbursed') {
            $request->load('approvalLogs.user');
            $disburserLog = $request->approvalLogs->where('new_status', 'disbursed')->last();
            if ($disburserLog && $disburserLog->user) {
                $disburserEmail = $disburserLog->user->email;
                if (! empty($disburserEmail) && filter_var($disburserEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipients->push($disburserEmail);
                }
            }
        }

        if ($recipients->isEmpty()) {
            Log::info("Skipped pengeluaran workflow email ({$event}): no valid email for requester/disburser", [
                'pengeluaran_request_id' => $request->id,
            ]);

            return;
        }

        // Opt-out check happens per-recipient inside the loop (like
        // notifyApprovers()) — NOT as a bulk reject()+early-return — so an
        // opted-out requester/disburser still gets a `skipped`/`opted_out`
        // notification_logs row instead of vanishing with no trace. That bulk
        // early-return was the actual bug: once EVERY recipient happened to be
        // opted out (e.g. right after clicking "unsubscribe" in a prior email),
        // this method returned after only a Log::info() — nothing in
        // notification_logs — so the next reject/approve/disburse looked like
        // it silently failed to send with no record of why.
        foreach ($recipients->unique() as $email) {
            if (EmailOptOut::isOptedOut($email, 'workflow')) {
                $this->logWorkflowNotification($request, $email, $event, 'skipped', reason: 'opted_out');

                continue;
            }

            $log = $this->logWorkflowNotification($request, $email, $event, 'sent');

            try {
                Notification::route('mail', $email)
                    ->notify((new PengeluaranWorkflowNotification(
                        $request,
                        $event,
                        $reason,
                    ))->withLogId($log->id));
            } catch (\Throwable $e) {
                Log::error("Failed to send pengeluaran workflow email ({$event})", [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }
    }

    private function logWorkflowNotification(
        PengeluaranRequest $request,
        string $email,
        string $event,
        string $status,
        ?string $reason = null,
        ?string $errorMessage = null,
    ): \App\Models\NotificationLog {
        return $this->notificationService->logNotification([
            'branch_id' => $request->branch_id,
            'recipient_email' => $email,
            'notification_type' => 'workflow',
            'pengeluaran_request_id' => $request->id,
            'workflow_event' => $event,
            'status' => $status,
            'reason' => $reason,
            'error_message' => $errorMessage,
        ]);
    }
}
