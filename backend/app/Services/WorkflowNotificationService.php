<?php

namespace App\Services;

use App\Models\PengeluaranRequest;
use App\Models\User;
use App\Notifications\PengeluaranWorkflowNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class WorkflowNotificationService
{
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

            try {
                Notification::route('mail', $email)
                    ->notify(new PengeluaranWorkflowNotification(
                        $request,
                        'submitted',
                        null,
                        $requesterName,
                    ));
            } catch (\Throwable $e) {
                Log::error('Failed to send pengeluaran workflow email to approver', [
                    'user_id' => $approver->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
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

        $recipients = $recipients->unique();

        if ($recipients->isEmpty()) {
            Log::info("Skipped pengeluaran workflow email ({$event}): no valid email for requester/disburser", [
                'pengeluaran_request_id' => $request->id,
            ]);

            return;
        }

        foreach ($recipients as $email) {
            try {
                Notification::route('mail', $email)
                    ->notify(new PengeluaranWorkflowNotification(
                        $request,
                        $event,
                        $reason,
                    ));
            } catch (\Throwable $e) {
                Log::error("Failed to send pengeluaran workflow email ({$event})", [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
