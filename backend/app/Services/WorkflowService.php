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
     * Rincian saldo cabang, dipakai baik oleh validasi submit/disburse
     * (`assertSaldoMencukupi()`) maupun oleh endpoint stats halaman
     * Pengeluaran & dashboard. Selalu branch-wide (semua tahun ajaran) —
     * TIDAK pernah difilter per periode, supaya proteksi saldo minus tidak
     * bisa dilewati dengan submit request di periode yang "kelihatan"
     * longgar padahal cabangnya sendiri sudah defisit.
     *
     * @return array{total_saldo_cabang: float, total_outstanding: float, saldo_tersedia: float}
     */
    public function getSaldoBreakdown(int $branchId, ?int $excludeRequestId = null): array
    {
        // Pemasukan = total pembayaran di cabang.
        $totalPemasukan = (float) \App\Models\Pembayaran::query()
            ->join('tagihans', 'tagihans.kode_tagihan', '=', 'pembayarans.kode_tagihan')
            ->where('tagihans.branch_id', $branchId)
            ->sum('pembayarans.jumlah');

        // Pengeluaran yang sudah dicairkan (final).
        $totalPengeluaranTerealisasi = (float) Pengeluaran::where('branch_id', $branchId)->sum('jumlah');

        $totalSaldoCabang = $totalPemasukan - $totalPengeluaranTerealisasi;

        // Pengeluaran yang sedang dalam proses (submitted/approved, belum
        // disbursed) — sudah "menjatah" sebagian saldo meski belum dicairkan.
        $outstandingQuery = PengeluaranRequest::where('branch_id', $branchId)
            ->whereIn('status', ['submitted', 'approved']);

        if ($excludeRequestId !== null) {
            $outstandingQuery->where('id', '!=', $excludeRequestId);
        }

        $totalOutstanding = (float) $outstandingQuery->sum('jumlah');

        return [
            'total_saldo_cabang' => $totalSaldoCabang,
            'total_outstanding' => $totalOutstanding,
            'saldo_tersedia' => $totalSaldoCabang - $totalOutstanding,
        ];
    }

    /**
     * Pastikan saldo cabang masih cukup untuk menampung jumlah pengeluaran
     * dari request ini.
     *
     * Untuk mencegah race antar request bersamaan, perhitungan ini dilakukan
     * dalam transaction lock di pemanggil (submit/disburse).
     *
     * @throws ValidationException kalau saldo tidak mencukupi.
     */
    private function assertSaldoMencukupi(PengeluaranRequest $request): void
    {
        $jumlah = (float) $request->jumlah;
        $saldoTersedia = $this->getSaldoBreakdown($request->branch_id, excludeRequestId: $request->id)['saldo_tersedia'];

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
