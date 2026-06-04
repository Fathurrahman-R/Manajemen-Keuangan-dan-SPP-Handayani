<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PengeluaranRequest;
use App\Models\User;

class WorkflowNotificationService
{
    /**
     * Notify all users with approve-pengeluaran permission in the same branch.
     */
    public function notifyApprovers(PengeluaranRequest $request): void
    {
        $approvers = User::where('branch_id', $request->branch_id)
            ->where('is_active', true)
            ->permission('approve-pengeluaran')
            ->where('id', '!=', $request->requester_id)
            ->get();

        foreach ($approvers as $approver) {
            Notification::create([
                'user_id' => $approver->id,
                'type' => 'pengeluaran_submitted',
                'title' => 'Request Pengeluaran Baru',
                'message' => "Request pengeluaran \"{$request->uraian}\" senilai Rp " . number_format($request->jumlah, 0, ',', '.') . " menunggu persetujuan Anda.",
                'data' => [
                    'pengeluaran_request_id' => $request->id,
                    'requester_name' => $request->requester->name ?? $request->requester->username,
                ],
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Notify the requester about status change.
     */
    public function notifyRequester(PengeluaranRequest $request, string $event, ?string $reason = null): void
    {
        $titles = [
            'approved' => 'Request Disetujui',
            'rejected' => 'Request Ditolak',
            'disbursed' => 'Pencairan Selesai',
        ];

        $messages = [
            'approved' => "Request pengeluaran \"{$request->uraian}\" telah disetujui.",
            'rejected' => "Request pengeluaran \"{$request->uraian}\" ditolak." . ($reason ? " Alasan: {$reason}" : ''),
            'disbursed' => "Request pengeluaran \"{$request->uraian}\" telah dicairkan senilai Rp " . number_format($request->jumlah, 0, ',', '.') . ".",
        ];

        Notification::create([
            'user_id' => $request->requester_id,
            'type' => "pengeluaran_{$event}",
            'title' => $titles[$event] ?? 'Update Request',
            'message' => $messages[$event] ?? "Status request berubah menjadi {$event}.",
            'data' => [
                'pengeluaran_request_id' => $request->id,
            ],
            'created_at' => now(),
        ]);
    }
}
