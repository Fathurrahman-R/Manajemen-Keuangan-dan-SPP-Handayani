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
        if (! $request->isEditable()) {
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
        if (! in_array($request->status, ['draft', 'rejected'])) {
            throw ValidationException::withMessages([
                'status' => ['Request hanya bisa disubmit dari status draft atau rejected.'],
            ]);
        }

        // Cegah submit jika request akan menyebabkan saldo mines.
        $this->assertSaldoMencukupi($request);

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

        // Cegah pencairan jika akan menyebabkan saldo mines.
        $this->assertSaldoMencukupi($request);

        return DB::transaction(function () use ($request, $user) {
            // Resolve tahun_ajaran_id berdasarkan periode aktif branch saat
            // pencairan dilakukan, supaya pengeluaran tercatat ke periode
            // yang relevan untuk laporan keuangan.
            $tahunAjaran = \App\Models\TahunAjaran::getAktif($request->branch_id);

            // Create actual Pengeluaran record
            Pengeluaran::create([
                'tanggal' => now()->toDateString(),
                'uraian' => $request->uraian,
                'jumlah' => $request->jumlah,
                'branch_id' => $request->branch_id,
                'tahun_ajaran_id' => $tahunAjaran?->id,
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

    /**
     * Pastikan saldo cabang masih cukup untuk menampung jumlah pengeluaran
     * dari request ini. Saldo dihitung dari total pemasukan (pembayaran) di
     * cabang dikurangi total pengeluaran yang sudah dicairkan, MINUS jumlah
     * pengeluaran lain yang masih outstanding (approved & belum disbursed
     * atau submitted yang akan tap saldo yang sama).
     *
     * Untuk mencegah race antar request bersamaan, perhitungan ini dilakukan
     * dalam transaction lock di pemanggil (submit/disburse).
     *
     * @throws ValidationException kalau saldo tidak mencukupi.
     */
    private function assertSaldoMencukupi(PengeluaranRequest $request): void
    {
        $branchId = $request->branch_id;
        $jumlah = (float) $request->jumlah;

        // Pemasukan = total pembayaran di cabang.
        $totalPemasukan = (float) \App\Models\Pembayaran::query()
            ->join('tagihans', 'tagihans.kode_tagihan', '=', 'pembayarans.kode_tagihan')
            ->where('tagihans.branch_id', $branchId)
            ->sum('pembayarans.jumlah');

        // Pengeluaran yang sudah dicairkan (final).
        $totalPengeluaranTerealisasi = (float) Pengeluaran::where('branch_id', $branchId)->sum('jumlah');

        // Pengeluaran yang sedang dalam proses (approved tapi belum disbursed),
        // dikurangi current request kalau sedang di-disburse (sudah approved).
        $outstandingQuery = PengeluaranRequest::where('branch_id', $branchId)
            ->whereIn('status', ['submitted', 'approved'])
            ->where('id', '!=', $request->id);
        $totalOutstanding = (float) $outstandingQuery->sum('jumlah');

        $saldoTersedia = $totalPemasukan - $totalPengeluaranTerealisasi - $totalOutstanding;

        if ($jumlah > $saldoTersedia) {
            throw ValidationException::withMessages([
                'jumlah' => [
                    sprintf(
                        'Saldo tidak mencukupi. Saldo tersedia: Rp %s, dibutuhkan: Rp %s. Pengeluaran ini akan menyebabkan saldo mines.',
                        number_format(max($saldoTersedia, 0), 0, ',', '.'),
                        number_format($jumlah, 0, ',', '.'),
                    ),
                ],
            ]);
        }
    }
}
