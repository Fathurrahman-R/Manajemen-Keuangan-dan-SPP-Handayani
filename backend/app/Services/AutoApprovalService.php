<?php

namespace App\Services;

use App\Models\ApprovalLog;
use App\Models\BranchApprovalSetting;
use App\Models\PengeluaranRequest;

class AutoApprovalService
{
    public function shouldAutoApprove(PengeluaranRequest $request): bool
    {
        $settings = BranchApprovalSetting::where('branch_id', $request->branch_id)->first();

        if (! $settings) {
            return false;
        }

        return $settings->auto_approval_enabled
            && $settings->auto_approval_threshold > 0
            && $request->jumlah <= $settings->auto_approval_threshold;
    }

    public function processAutoApproval(PengeluaranRequest $request): void
    {
        $request->status = 'approved';
        $request->save();

        // Use requester as user_id for system auto-approval log
        ApprovalLog::create([
            'pengeluaran_request_id' => $request->id,
            'previous_status' => 'submitted',
            'new_status' => 'approved',
            'user_id' => $request->requester_id,
            'note' => 'Auto-approved: jumlah dalam batas threshold',
            'created_at' => now(),
        ]);

        // Notify requester (no reason — that field is reserved for rejections)
        app(WorkflowNotificationService::class)->notifyRequester($request, 'approved');
    }
}
