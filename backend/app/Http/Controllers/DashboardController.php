<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Get dashboard summary KPI.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getSummary($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get pembayaran per bulan chart data.
     */
    public function chartPembayaranBulanan(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getChartPembayaranBulanan($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get tunggakan per jenjang chart data.
     */
    public function chartTunggakanJenjang(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getChartTunggakanJenjang($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get kas bulanan chart data (pemasukan vs pengeluaran).
     */
    public function chartKasBulanan(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getChartKasBulanan($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get status tagihan chart data.
     */
    public function chartStatusTagihan(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getChartStatusTagihan($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get top 10 siswa with highest tunggakan.
     */
    public function topTunggakan(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getTopTunggakan($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get tagihan due within next 7 days.
     */
    public function tagihanJatuhTempo(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getTagihanJatuhTempo($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get 5 most recent pembayaran.
     */
    public function pembayaranTerbaru(Request $request): JsonResponse
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
        ]);

        $branchId = $request->user()->branch_id;
        $tahunAjaranId = $request->input('tahun_ajaran_id');

        return response()->json([
            'data' => $this->dashboardService->getPembayaranTerbaru($branchId, $tahunAjaranId),
        ]);
    }

    /**
     * Get siswa/wali personal dashboard.
     */
    public function siswaDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'siswa_id' => 'nullable|integer|exists:siswas,id',
        ]);

        /** @var User $user */
        $user = $request->user();
        $branchId = $user->branch_id;
        $siswaId = $request->input('siswa_id');

        // Determine the siswa to show
        if ($siswaId) {
            // Verify the siswa belongs to this user (wali access control)
            $siswa = Siswa::where('id', $siswaId)
                ->where('branch_id', $branchId)
                ->first();

            if (!$siswa) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke data siswa ini.',
                ], 403);
            }

            // Check if user is the wali of this siswa
            if ($user->siswa_id && $user->siswa_id !== $siswaId) {
                // User is a siswa viewing their own data — deny access to other siswa
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke data siswa ini.',
                ], 403);
            }

            // For wali users, verify the siswa is their child
            if (!$user->siswa_id && $user->hasRole('wali')) {
                $isChild = Siswa::where('id', $siswaId)
                    ->where('branch_id', $branchId)
                    ->whereHas('wali', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->exists();

                if (!$isChild) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke data siswa ini.',
                    ], 403);
                }
            }
        } else {
            // Default: get the user's own siswa or first child for wali
            if ($user->siswa_id) {
                $siswaId = $user->siswa_id;
            } else {
                // Wali: get first child ordered by NIS ascending
                $firstChild = Siswa::where('branch_id', $branchId)
                    ->whereHas('wali', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->orderBy('nis', 'asc')
                    ->first();

                if (!$firstChild) {
                    return response()->json([
                        'data' => [
                            'total_tagihan' => 0,
                            'total_terbayar' => 0,
                            'total_tunggakan' => 0,
                            'tagihan_list' => [],
                            'pembayaran_terbaru' => [],
                        ],
                    ]);
                }

                $siswaId = $firstChild->id;
            }
        }

        return response()->json([
            'data' => $this->dashboardService->getSiswaDashboard($siswaId, $branchId),
        ]);
    }
}
