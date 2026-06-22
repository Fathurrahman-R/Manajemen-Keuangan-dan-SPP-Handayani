<?php

namespace App\Services;

use App\Models\JenisTagihan;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Resolve tahun_ajaran_id — returns Periode_Aktif when null.
     */
    public function resolveTahunAjaranId(?int $tahunAjaranId, int $branchId): ?int
    {
        if ($tahunAjaranId !== null) {
            return $tahunAjaranId;
        }

        $aktif = TahunAjaran::getAktif($branchId);
        return $aktif?->id;
    }

    /**
     * Generate cache key with format dashboard:{branch_id}:{tahun_ajaran_id}:{endpoint}
     */
    public function getCacheKey(string $endpoint, int $branchId, ?int $tahunAjaranId): string
    {
        return "dashboard:{$branchId}:{$tahunAjaranId}:{$endpoint}";
    }

    /**
     * Invalidate all dashboard cache keys for a branch.
     */
    public static function invalidateCache(int $branchId): void
    {
        $endpoints = [
            'summary',
            'pembayaran-bulanan',
            'tunggakan-jenjang',
            'kas-bulanan',
            'status-tagihan',
            'top-tunggakan',
            'tagihan-jatuh-tempo',
            'pembayaran-terbaru',
        ];

        // Get all active tahun ajaran for this branch to clear all period caches
        $tahunAjaranIds = TahunAjaran::where('branch_id', $branchId)->pluck('id');

        foreach ($tahunAjaranIds as $taId) {
            foreach ($endpoints as $endpoint) {
                Cache::forget("dashboard:{$branchId}:{$taId}:{$endpoint}");
            }
        }

        // Also clear with null tahun_ajaran_id
        foreach ($endpoints as $endpoint) {
            Cache::forget("dashboard:{$branchId}::{$endpoint}");
        }
    }

    /**
     * Invalidate kas-bulanan cache for a branch (pengeluaran changes).
     */
    public static function invalidateKasCache(int $branchId): void
    {
        $tahunAjaranIds = TahunAjaran::where('branch_id', $branchId)->pluck('id');

        foreach ($tahunAjaranIds as $taId) {
            Cache::forget("dashboard:{$branchId}:{$taId}:kas-bulanan");
        }
        Cache::forget("dashboard:{$branchId}::kas-bulanan");
    }

    /**
     * Get dashboard summary KPI data.
     */
    public function getSummary(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('summary', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            $totalTagihan = Tagihan::where('tagihans.branch_id', $branchId)
                ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
                ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
                ->sum('jenis_tagihans.jumlah');

            $totalTerbayar = Pembayaran::whereHas('tagihan', function ($q) use ($branchId, $tahunAjaranId) {
                $q->where('branch_id', $branchId)->where('tahun_ajaran_id', $tahunAjaranId);
            })->sum('jumlah');

            $totalTunggakan = $totalTagihan - $totalTerbayar;

            $jumlahSiswaAktif = Siswa::where('branch_id', $branchId)
                ->where('status', 'Aktif')
                ->count();

            $jumlahSiswaMenunggak = Tagihan::where('branch_id', $branchId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('status', '!=', 'Lunas')
                ->distinct('nis')
                ->count('nis');

            $persentasePelunasan = $totalTagihan > 0
                ? round(($totalTerbayar / $totalTagihan) * 100, 2)
                : 0;

            return [
                'total_tagihan' => $totalTagihan,
                'total_terbayar' => $totalTerbayar,
                'total_tunggakan' => $totalTunggakan,
                'jumlah_siswa_aktif' => $jumlahSiswaAktif,
                'jumlah_siswa_menunggak' => $jumlahSiswaMenunggak,
                'persentase_pelunasan' => $persentasePelunasan,
            ];
        });
    }

    /**
     * Get monthly payment chart data (12 months).
     */
    public function getChartPembayaranBulanan(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('pembayaran-bulanan', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            $data = Pembayaran::select(
                DB::raw('MONTH(pembayarans.tanggal) as bulan'),
                DB::raw('SUM(pembayarans.jumlah) as total')
            )
                ->join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
                ->where('tagihans.branch_id', $branchId)
                ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
                ->groupBy(DB::raw('MONTH(pembayarans.tanggal)'))
                ->pluck('total', 'bulan')
                ->toArray();

            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = [
                    'bulan' => $i,
                    'nama_bulan' => self::NAMA_BULAN[$i],
                    'total' => $data[$i] ?? 0,
                ];
            }

            return $result;
        });
    }

    /**
     * Get tunggakan per jenjang chart data.
     */
    public function getChartTunggakanJenjang(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('tunggakan-jenjang', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            $jenjangList = ['TK', 'MI', 'KB'];
            $result = [];

            foreach ($jenjangList as $jenjang) {
                $totalTagihan = Tagihan::where('tagihans.branch_id', $branchId)
                    ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
                    ->join('siswas', 'tagihans.nis', '=', 'siswas.nis')
                    ->where('siswas.jenjang', $jenjang)
                    ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
                    ->sum('jenis_tagihans.jumlah');

                $totalTerbayar = Pembayaran::join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
                    ->join('siswas', 'tagihans.nis', '=', 'siswas.nis')
                    ->where('tagihans.branch_id', $branchId)
                    ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
                    ->where('siswas.jenjang', $jenjang)
                    ->sum('pembayarans.jumlah');

                $result[] = [
                    'jenjang' => $jenjang,
                    'total_tagihan' => $totalTagihan,
                    'total_terbayar' => $totalTerbayar,
                    'total_tunggakan' => $totalTagihan - $totalTerbayar,
                ];
            }

            return $result;
        });
    }

    /**
     * Get kas bulanan chart data (pemasukan vs pengeluaran per month).
     */
    public function getChartKasBulanan(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('kas-bulanan', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            // Pemasukan (pembayaran grouped by month)
            $pemasukan = Pembayaran::select(
                DB::raw('MONTH(pembayarans.tanggal) as bulan'),
                DB::raw('SUM(pembayarans.jumlah) as total')
            )
                ->join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
                ->where('tagihans.branch_id', $branchId)
                ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
                ->groupBy(DB::raw('MONTH(pembayarans.tanggal)'))
                ->pluck('total', 'bulan')
                ->toArray();

            // Pengeluaran filtered by TahunAjaran date range
            $tahunAjaran = TahunAjaran::find($tahunAjaranId);
            $pengeluaran = [];

            if ($tahunAjaran) {
                $pengeluaran = Pengeluaran::select(
                    DB::raw('MONTH(tanggal) as bulan'),
                    DB::raw('SUM(jumlah) as total')
                )
                    ->where('branch_id', $branchId)
                    ->whereBetween('tanggal', [$tahunAjaran->tanggal_mulai, $tahunAjaran->tanggal_selesai])
                    ->groupBy(DB::raw('MONTH(tanggal)'))
                    ->pluck('total', 'bulan')
                    ->toArray();
            }

            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = [
                    'bulan' => $i,
                    'nama_bulan' => self::NAMA_BULAN[$i],
                    'pemasukan' => $pemasukan[$i] ?? 0,
                    'pengeluaran' => $pengeluaran[$i] ?? 0,
                ];
            }

            return $result;
        });
    }

    /**
     * Get status tagihan chart data.
     */
    public function getChartStatusTagihan(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('status-tagihan', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            $statusList = ['Lunas', 'Belum Lunas', 'Belum Dibayar'];

            $counts = Tagihan::where('branch_id', $branchId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->select('status', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('status')
                ->pluck('jumlah', 'status')
                ->toArray();

            $totalCount = array_sum($counts);

            $result = [];
            foreach ($statusList as $status) {
                $jumlah = $counts[$status] ?? 0;
                $result[] = [
                    'status' => $status,
                    'jumlah' => $jumlah,
                    'persentase' => $totalCount > 0 ? round(($jumlah / $totalCount) * 100, 2) : 0,
                ];
            }

            return $result;
        });
    }

    /**
     * Get top 10 siswa with highest tunggakan.
     */
    public function getTopTunggakan(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('top-tunggakan', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            // Get per-siswa tagihan totals and pembayaran totals
            $results = DB::select("
                SELECT 
                    s.nis,
                    s.nama,
                    s.jenjang,
                    COALESCE(k.nama, '-') as kelas,
                    COALESCE(SUM(jt.jumlah), 0) as total_tagihan,
                    COALESCE((
                        SELECT SUM(p.jumlah) 
                        FROM pembayarans p 
                        JOIN tagihans t2 ON p.kode_tagihan = t2.kode_tagihan 
                        WHERE t2.nis = s.nis 
                        AND t2.branch_id = ? 
                        AND t2.tahun_ajaran_id = ?
                    ), 0) as total_terbayar
                FROM siswas s
                JOIN tagihans t ON t.nis = s.nis AND t.branch_id = ? AND t.tahun_ajaran_id = ?
                JOIN jenis_tagihans jt ON t.jenis_tagihan_id = jt.id
                LEFT JOIN siswa_kelas sk ON sk.siswa_id = s.id AND sk.tahun_ajaran_id = ?
                LEFT JOIN kelas k ON sk.kelas_id = k.id
                WHERE s.branch_id = ?
                GROUP BY s.nis, s.nama, s.jenjang, k.nama
                HAVING (total_tagihan - total_terbayar) > 0
                ORDER BY (total_tagihan - total_terbayar) DESC
                LIMIT 10
            ", [$branchId, $tahunAjaranId, $branchId, $tahunAjaranId, $tahunAjaranId, $branchId]);

            return collect($results)->map(function ($row) {
                return [
                    'nis' => $row->nis,
                    'nama' => $row->nama,
                    'kelas' => $row->kelas,
                    'jenjang' => $row->jenjang,
                    'total_tagihan' => (float) $row->total_tagihan,
                    'total_terbayar' => (float) $row->total_terbayar,
                    'total_tunggakan' => (float) $row->total_tagihan - (float) $row->total_terbayar,
                ];
            })->toArray();
        });
    }

    /**
     * Get tagihan due within next 7 days.
     */
    public function getTagihanJatuhTempo(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('tagihan-jatuh-tempo', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            $today = now()->toDateString();
            $nextWeek = now()->addDays(7)->toDateString();

            return Tagihan::select(
                'tagihans.kode_tagihan',
                'siswas.nama as nama_siswa',
                'jenis_tagihans.nama as nama_jenis_tagihan',
                'jenis_tagihans.jatuh_tempo',
                'jenis_tagihans.jumlah',
                'tagihans.status'
            )
                ->join('siswas', 'tagihans.nis', '=', 'siswas.nis')
                ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
                ->where('tagihans.branch_id', $branchId)
                ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
                ->where('tagihans.status', '!=', 'Lunas')
                ->whereBetween('jenis_tagihans.jatuh_tempo', [$today, $nextWeek])
                ->orderBy('jenis_tagihans.jatuh_tempo', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'kode_tagihan' => $item->kode_tagihan,
                        'nama_siswa' => $item->nama_siswa,
                        'nama_jenis_tagihan' => $item->nama_jenis_tagihan,
                        'jatuh_tempo' => $item->jatuh_tempo,
                        'jumlah' => (float) $item->jumlah,
                        'status' => $item->status,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get 5 most recent pembayaran.
     */
    public function getPembayaranTerbaru(int $branchId, ?int $tahunAjaranId): array
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        $cacheKey = $this->getCacheKey('pembayaran-terbaru', $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            return Pembayaran::select(
                'pembayarans.kode_pembayaran',
                'siswas.nama as nama_siswa',
                'jenis_tagihans.nama as nama_jenis_tagihan',
                'pembayarans.tanggal',
                'pembayarans.metode',
                'pembayarans.jumlah'
            )
                ->join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
                ->join('siswas', 'tagihans.nis', '=', 'siswas.nis')
                ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
                ->where('tagihans.branch_id', $branchId)
                ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
                ->orderBy('pembayarans.tanggal', 'desc')
                ->orderBy('pembayarans.created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'kode_pembayaran' => $item->kode_pembayaran,
                        'nama_siswa' => $item->nama_siswa,
                        'nama_jenis_tagihan' => $item->nama_jenis_tagihan,
                        'tanggal' => $item->tanggal,
                        'metode' => $item->metode,
                        'jumlah' => (float) $item->jumlah,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get siswa personal dashboard data (no caching).
     */
    public function getSiswaDashboard(int $siswaId, int $branchId): array
    {
        $siswa = Siswa::where('id', $siswaId)->where('branch_id', $branchId)->first();

        if (!$siswa) {
            return [
                'total_tagihan' => 0,
                'total_terbayar' => 0,
                'total_tunggakan' => 0,
                'tagihan_list' => [],
                'pembayaran_terbaru' => [],
            ];
        }

        $tahunAjaranId = $this->resolveTahunAjaranId(null, $branchId);

        // If no active tahun ajaran, return empty data
        if ($tahunAjaranId === null) {
            return [
                'total_tagihan' => 0,
                'total_terbayar' => 0,
                'total_tunggakan' => 0,
                'tagihan_list' => [],
                'pembayaran_terbaru' => [],
            ];
        }

        $totalTagihan = Tagihan::where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId)
            ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
            ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
            ->sum('jenis_tagihans.jumlah');

        $totalTerbayar = Pembayaran::join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
            ->where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId)
            ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
            ->sum('pembayarans.jumlah');

        $tagihanList = Tagihan::select(
            'jenis_tagihans.nama as nama_jenis_tagihan',
            'jenis_tagihans.jumlah',
            'jenis_tagihans.jatuh_tempo',
            'tagihans.status'
        )
            ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
            ->where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId)
            ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
            ->get()
            ->map(fn($item) => [
                'nama_jenis_tagihan' => $item->nama_jenis_tagihan,
                'jumlah' => (float) $item->jumlah,
                'jatuh_tempo' => $item->jatuh_tempo,
                'status' => $item->status,
            ])
            ->toArray();

        $pembayaranTerbaru = Pembayaran::select(
            'pembayarans.tanggal',
            'jenis_tagihans.nama as nama_jenis_tagihan',
            'pembayarans.metode',
            'pembayarans.jumlah'
        )
            ->join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
            ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
            ->where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId)
            ->where('tagihans.tahun_ajaran_id', $tahunAjaranId)
            ->orderBy('pembayarans.tanggal', 'desc')
            ->orderBy('pembayarans.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'tanggal' => $item->tanggal,
                'nama_jenis_tagihan' => $item->nama_jenis_tagihan,
                'metode' => $item->metode,
                'jumlah' => (float) $item->jumlah,
            ])
            ->toArray();

        return [
            'total_tagihan' => $totalTagihan,
            'total_terbayar' => $totalTerbayar,
            'total_tunggakan' => $totalTagihan - $totalTerbayar,
            'tagihan_list' => $tagihanList,
            'pembayaran_terbaru' => $pembayaranTerbaru,
        ];
    }
}
