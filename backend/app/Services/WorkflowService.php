<?php

namespace App\Services;

use App\Models\ApprovalLog;
use App\Models\Pengeluaran;
use App\Models\PengeluaranRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowService
{
    public function __construct(
        private readonly AutoApprovalService $autoApprovalService,
        private readonly WorkflowNotificationService $notificationService,
    ) {}

    public function create(array $data, User $user): PengeluaranRequest
    {
        return PengeluaranRequest::create([
            'uraian' => $data['uraian'],
            'jumlah' => $data['jumlah'],
            'tanggal_kebutuhan' => $data['tanggal_kebutuhan'],
            'kategori_pengeluaran' => $data['kategori_pengeluaran'] ?? null,
            'lampiran' => $data['lampiran'] ?? null,
            'status' => 'draft',
            'requester_id' => $user->id,
            'branch_id' => $user->branch_id,
        ]);
    }

    public function update(PengeluaranRequest $request, array $data): PengeluaranRequest
    {
        if (!$request->isEditable()) {
            throw ValidationException::withMessages([
                'status' => ['Request hanya bisa diubah saat status draft atau rejected.'],
            ]);
        }

        $request->update([
            'uraian' => $data['uraian'] ?? $request->uraian,
            'jumlah' => $data['jumlah'] ?? $request->jumlah,
            'tanggal_kebutuhan' => $data['tanggal_kebutuhan'] ?? $request->tanggal_kebutuhan,
            'kategori_pengeluaran' => $data['kategori_pengeluaran'] ?? $request->kategori_pengeluaran,
            'lampiran' => $data['lampiran'] ?? $request->lampiran,
        ]);

        return $request->fresh();
    }

    public function submit(PengeluaranRequest $request, User $user): PengeluaranRequest
    {
        if (!in_array($request->status, ['draft', 'rejected'])) {
            throw ValidationException::withMessages([
                'status' => ['Request hanya bisa disubmit dari status draft atau rejected.'],
            ]);
        }

        return DB::transaction(function () use ($request, $user) {
            $previousStatus = $request->status;
            $request->status = 'submitted';
            $request->save();

            $this->createLog($request, $previousStatus, 'submitted', $user);

            // Check auto-approval
            if ($this->autoApprovalService->shouldAutoApprove($request)) {
                $this->autoApprovalService->processAutoApproval($request);
            } else {
                $this->notificationService->notifyApprovers($request);
            }

            return $request->fresh();
        });
    }

    public function approve(PengeluaranRequest $request, User $user, ?string $note = null): PengeluaranRequest
    {
        if ($request->status !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => ['Request hanya bisa diapprove dari status submitted.'],
            ]);
        }

        return DB::transaction(function () use ($request, $user, $note) {
            $request->status = 'approved';
            $request->save();

            $this->createLog($request, 'submitted', 'approved', $user, $note);
            $this->notificationService->notifyRequester($request, 'approved');

            return $request->fresh();
        });
    }

    public function reject(PengeluaranRequest $request, User $user, string $reason): PengeluaranRequest
    {
        if ($request->status !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => ['Request hanya bisa direject dari status submitted.'],
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => ['Alasan penolakan wajib diisi.'],
            ]);
        }

        return DB::transaction(function () use ($request, $user, $reason) {
            $request->status = 'rejected';
            $request->save();

            $this->createLog($request, 'submitted', 'rejected', $user, $reason);
            $this->notificationService->notifyRequester($request, 'rejected', $reason);

            return $request->fresh();
        });
    }

    public function disburse(PengeluaranRequest $request, User $user): PengeluaranRequest
    {
        if ($request->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => ['Request hanya bisa dicairkan dari status approved.'],
            ]);
        }

        return DB::transaction(function () use ($request, $user) {
            // Create actual Pengeluaran record
            Pengeluaran::create([
                'tanggal' => now()->toDateString(),
                'uraian' => $request->uraian,
                'jumlah' => $request->jumlah,
                'branch_id' => $request->branch_id,
                'pengeluaran_request_id' => $request->id,
            ]);

            $request->status = 'disbursed';
            $request->save();

            $this->createLog($request, 'approved', 'disbursed', $user);
            $this->notificationService->notifyRequester($request, 'disbursed');

            return $request->fresh();
        });
    }

    private function createLog(PengeluaranRequest $request, string $previousStatus, string $newStatus, User $user, ?string $note = null): void
    {
        ApprovalLog::create([
            'pengeluaran_request_id' => $request->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'user_id' => $user->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
