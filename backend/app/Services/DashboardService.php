<?php

namespace App\Services;

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
     *
     * NOTE: Helper ini DULUNYA juga dipakai oleh method dashboard ber-cache,
     * yang menyebabkan request "Semua Periode" (null) selalu di-coerce ke
     * periode aktif. Sekarang setiap method dashboard punya parameter
     * `$allPeriods` untuk membedakan "default ke aktif" vs "lintas semua
     * periode" — helper ini tetap dipertahankan untuk backward-compat.
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
     * Apply tahun_ajaran_id filter ke query kalau bukan mode all-periods.
     * Dipakai di hampir semua method dashboard agar query bisa di-skip
     * filter saat user pilih "Semua Periode".
     */
    private function applyPeriodFilter($query, ?int $tahunAjaranId, bool $allPeriods, string $column = 'tagihans.tahun_ajaran_id')
    {
        if (! $allPeriods && $tahunAjaranId !== null) {
            $query->where($column, $tahunAjaranId);
        }

        return $query;
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
    public function getSummary(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('summary'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            $totalTagihanQuery = Tagihan::where('tagihans.branch_id', $branchId)
                ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id');
            $this->applyPeriodFilter($totalTagihanQuery, $tahunAjaranId, $allPeriods);
            $totalTagihan = $totalTagihanQuery->sum('jenis_tagihans.jumlah');

            $totalTerbayar = Pembayaran::whereHas('tagihan', function ($q) use ($branchId, $tahunAjaranId, $allPeriods) {
                $q->where('branch_id', $branchId);
                if (! $allPeriods && $tahunAjaranId !== null) {
                    $q->where('tahun_ajaran_id', $tahunAjaranId);
                }
            })->sum('jumlah');

            $totalTunggakan = $totalTagihan - $totalTerbayar;

            $jumlahSiswaAktif = Siswa::where('branch_id', $branchId)
                ->where('status', 'Aktif')
                ->count();

            $jumlahSiswaMenunggakQuery = Tagihan::where('branch_id', $branchId)
                ->where('status', '!=', 'Lunas');
            $this->applyPeriodFilter($jumlahSiswaMenunggakQuery, $tahunAjaranId, $allPeriods, 'tahun_ajaran_id');
            $jumlahSiswaMenunggak = $jumlahSiswaMenunggakQuery->distinct('nis')->count('nis');

            $jumlahSiswaPunyaTagihanQuery = Tagihan::where('branch_id', $branchId);
            $this->applyPeriodFilter($jumlahSiswaPunyaTagihanQuery, $tahunAjaranId, $allPeriods, 'tahun_ajaran_id');
            $jumlahSiswaPunyaTagihan = $jumlahSiswaPunyaTagihanQuery->distinct('nis')->count('nis');

            $persentasePelunasan = $totalTagihan > 0
                ? round(($totalTerbayar / $totalTagihan) * 100, 2)
                : 0;

            return [
                'total_tagihan' => $totalTagihan,
                'total_terbayar' => $totalTerbayar,
                'total_tunggakan' => $totalTunggakan,
                'jumlah_siswa_aktif' => $jumlahSiswaAktif,
                'jumlah_siswa_punya_tagihan' => $jumlahSiswaPunyaTagihan,
                'jumlah_siswa_menunggak' => $jumlahSiswaMenunggak,
                'persentase_pelunasan' => $persentasePelunasan,
            ];
        });
    }

    /**
     * Get kas (pemasukan vs pengeluaran) summary for either a single
     * tahun ajaran (date range based on TahunAjaran::tanggal_mulai/selesai)
     * or all-time (when $tahunAjaranId is null).
     */
    public function getKasSummary(int $branchId, ?int $tahunAjaranId): array
    {
        $cacheKey = $this->getCacheKey('kas-summary', $branchId, $tahunAjaranId ?? 0);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId) {
            $pembayaranQuery = Pembayaran::join(
                'tagihans',
                'pembayarans.kode_tagihan',
                '=',
                'tagihans.kode_tagihan'
            )->where('tagihans.branch_id', $branchId);

            $pengeluaranQuery = Pengeluaran::where('branch_id', $branchId);

            if ($tahunAjaranId) {
                $tahunAjaran = TahunAjaran::where('id', $tahunAjaranId)
                    ->where('branch_id', $branchId)
                    ->first();

                if ($tahunAjaran) {
                    $pembayaranQuery->where('tagihans.tahun_ajaran_id', $tahunAjaranId);
                    $pengeluaranQuery->where('tahun_ajaran_id', $tahunAjaranId);
                }
            }

            $totalPemasukan = (int) $pembayaranQuery->sum('pembayarans.jumlah');
            $totalPengeluaran = (int) $pengeluaranQuery->sum('jumlah');

            return [
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo' => $totalPemasukan - $totalPengeluaran,
                'is_all_time' => $tahunAjaranId === null,
            ];
        });
    }

    /**
     * Get all-time aggregate summary (across every tahun ajaran in this branch).
     */
    public function getAllTimeSummary(int $branchId): array
    {
        $cacheKey = $this->getCacheKey('all-time-summary', $branchId, 0);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId) {
            $totalTagihan = Tagihan::where('tagihans.branch_id', $branchId)
                ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
                ->sum('jenis_tagihans.jumlah');

            $kas = $this->getKasSummary($branchId, null);

            return [
                'total_tagihan' => (int) $totalTagihan,
                'total_pemasukan' => $kas['total_pemasukan'],
                'total_pengeluaran' => $kas['total_pengeluaran'],
                'saldo' => $kas['saldo'],
            ];
        });
    }

    /**
     * Get monthly payment chart data (12 months).
     */
    public function getChartPembayaranBulanan(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('pembayaran-bulanan'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            $query = Pembayaran::select(
                DB::raw('MONTH(pembayarans.tanggal) as bulan'),
                DB::raw('SUM(pembayarans.jumlah) as total')
            )
                ->join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
                ->where('tagihans.branch_id', $branchId);
            $this->applyPeriodFilter($query, $tahunAjaranId, $allPeriods);

            $data = $query
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
    public function getChartTunggakanJenjang(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('tunggakan-jenjang'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            $jenjangList = ['TK', 'MI', 'KB'];
            $result = [];

            foreach ($jenjangList as $jenjang) {
                $totalTagihanQuery = Tagihan::where('tagihans.branch_id', $branchId)
                    ->join('siswas', 'tagihans.nis', '=', 'siswas.nis')
                    ->where('siswas.jenjang', $jenjang)
                    ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id');
                $this->applyPeriodFilter($totalTagihanQuery, $tahunAjaranId, $allPeriods);
                $totalTagihan = $totalTagihanQuery->sum('jenis_tagihans.jumlah');

                $totalTerbayarQuery = Pembayaran::join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
                    ->join('siswas', 'tagihans.nis', '=', 'siswas.nis')
                    ->where('tagihans.branch_id', $branchId)
                    ->where('siswas.jenjang', $jenjang);
                $this->applyPeriodFilter($totalTerbayarQuery, $tahunAjaranId, $allPeriods);
                $totalTerbayar = $totalTerbayarQuery->sum('pembayarans.jumlah');

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
    public function getChartKasBulanan(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('kas-bulanan'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            // Pemasukan (pembayaran grouped by month).
            $pemasukanQuery = Pembayaran::select(
                DB::raw('MONTH(pembayarans.tanggal) as bulan'),
                DB::raw('SUM(pembayarans.jumlah) as total')
            )
                ->join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
                ->where('tagihans.branch_id', $branchId);
            $this->applyPeriodFilter($pemasukanQuery, $tahunAjaranId, $allPeriods);

            $pemasukan = $pemasukanQuery
                ->groupBy(DB::raw('MONTH(pembayarans.tanggal)'))
                ->pluck('total', 'bulan')
                ->toArray();

            // Pengeluaran:
            //   mode periode tertentu → filter by date range tahun ajaran
            //   mode all-periods    → akumulasi semua pengeluaran branch
            $pengeluaran = [];
            if ($allPeriods) {
                $pengeluaran = Pengeluaran::select(
                    DB::raw('MONTH(tanggal) as bulan'),
                    DB::raw('SUM(jumlah) as total')
                )
                    ->where('branch_id', $branchId)
                    ->groupBy(DB::raw('MONTH(tanggal)'))
                    ->pluck('total', 'bulan')
                    ->toArray();
            } else {
                $tahunAjaran = TahunAjaran::find($tahunAjaranId);
                if ($tahunAjaran) {
                    $pengeluaran = Pengeluaran::select(
                        DB::raw('MONTH(tanggal) as bulan'),
                        DB::raw('SUM(jumlah) as total')
                    )
                        ->where('branch_id', $branchId)
                        ->where('tahun_ajaran_id', $tahunAjaranId)
                        ->groupBy(DB::raw('MONTH(tanggal)'))
                        ->pluck('total', 'bulan')
                        ->toArray();
                }
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
    public function getChartStatusTagihan(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('status-tagihan'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            $statusList = ['Lunas', 'Belum Lunas', 'Belum Dibayar'];

            $countsQuery = Tagihan::where('branch_id', $branchId)
                ->select('status', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('status');
            $this->applyPeriodFilter($countsQuery, $tahunAjaranId, $allPeriods, 'tahun_ajaran_id');
            $counts = $countsQuery->pluck('jumlah', 'status')->toArray();

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
    public function getTopTunggakan(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('top-tunggakan'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            // Bangun klausa periode: kosong saat all-periods, AND ke t/t2/sk
            // saat periode tertentu.
            if ($allPeriods) {
                $periodFilterT = '';
                $periodFilterT2 = '';
                $periodFilterSK = '';
                $bindings = [$branchId, $branchId, $branchId];
            } else {
                $periodFilterT = ' AND t.tahun_ajaran_id = ?';
                $periodFilterT2 = ' AND t2.tahun_ajaran_id = ?';
                $periodFilterSK = ' AND sk.tahun_ajaran_id = ?';
                $bindings = [
                    $branchId,                      // subquery total_terbayar t2.branch_id
                    $tahunAjaranId,                 // subquery total_terbayar t2.tahun_ajaran_id
                    $branchId,                      // join tagihans t.branch_id
                    $tahunAjaranId,                 // join tagihans t.tahun_ajaran_id
                    $tahunAjaranId,                 // siswa_kelas sk.tahun_ajaran_id
                    $branchId,                      // siswas.branch_id
                ];
            }

            $sql = "
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
                          AND t2.branch_id = ?{$periodFilterT2}
                    ), 0) as total_terbayar
                FROM siswas s
                JOIN tagihans t ON t.nis = s.nis AND t.branch_id = ?{$periodFilterT}
                JOIN jenis_tagihans jt ON t.jenis_tagihan_id = jt.id
                LEFT JOIN siswa_kelas sk ON sk.siswa_id = s.id{$periodFilterSK}
                LEFT JOIN kelas k ON sk.kelas_id = k.id
                WHERE s.branch_id = ?
                GROUP BY s.nis, s.nama, s.jenjang, k.nama
                HAVING (total_tagihan - total_terbayar) > 0
                ORDER BY (total_tagihan - total_terbayar) DESC
                LIMIT 10
            ";

            // Susun ulang bindings sesuai urutan placeholder dalam SQL.
            // Urutan placeholder: t2.branch_id, [t2.tahun_ajaran_id], t.branch_id, [t.tahun_ajaran_id], [sk.tahun_ajaran_id], s.branch_id
            $orderedBindings = $allPeriods
                ? [$branchId, $branchId, $branchId]
                : [$branchId, $tahunAjaranId, $branchId, $tahunAjaranId, $tahunAjaranId, $branchId];

            $results = DB::select($sql, $orderedBindings);

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
    public function getTagihanJatuhTempo(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('tagihan-jatuh-tempo'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            $today = now()->toDateString();
            $nextWeek = now()->addDays(7)->toDateString();

            $query = Tagihan::select(
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
                ->where('tagihans.status', '!=', 'Lunas')
                ->whereBetween('jenis_tagihans.jatuh_tempo', [$today, $nextWeek])
                ->orderBy('jenis_tagihans.jatuh_tempo', 'asc');
            $this->applyPeriodFilter($query, $tahunAjaranId, $allPeriods);

            return $query->get()
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
    public function getPembayaranTerbaru(int $branchId, ?int $tahunAjaranId, bool $allPeriods = false): array
    {
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);
        } else {
            $tahunAjaranId = null;
        }

        $cacheKey = $this->getCacheKey('pembayaran-terbaru'.($allPeriods ? '-all' : ''), $branchId, $tahunAjaranId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $tahunAjaranId, $allPeriods) {
            $query = Pembayaran::select(
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
                ->orderBy('pembayarans.tanggal', 'desc')
                ->orderBy('pembayarans.created_at', 'desc')
                ->limit(5);
            $this->applyPeriodFilter($query, $tahunAjaranId, $allPeriods);

            return $query->get()
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
    public function getSiswaDashboard(int $siswaId, int $branchId, ?int $tahunAjaranId = null, bool $allPeriods = false): array
    {
        $siswa = Siswa::where('id', $siswaId)->where('branch_id', $branchId)->first();

        if (! $siswa) {
            return [
                'total_tagihan' => 0,
                'total_terbayar' => 0,
                'total_tunggakan' => 0,
                'tagihan_list' => [],
                'pembayaran_terbaru' => [],
            ];
        }

        // Saat $allPeriods = true, kita TIDAK memfilter tahun ajaran (lihat
        // semua data lintas periode). Saat $tahunAjaranId diberikan, pakai
        // periode itu. Kalau tidak keduanya, default ke periode aktif.
        if (! $allPeriods) {
            $tahunAjaranId = $this->resolveTahunAjaranId($tahunAjaranId, $branchId);

            if ($tahunAjaranId === null) {
                return [
                    'total_tagihan' => 0,
                    'total_terbayar' => 0,
                    'total_tunggakan' => 0,
                    'tagihan_list' => [],
                    'pembayaran_terbaru' => [],
                ];
            }
        }

        $applyPeriode = function ($query, string $tableAlias = 'tagihans') use ($allPeriods, $tahunAjaranId) {
            if (! $allPeriods && $tahunAjaranId !== null) {
                $query->where("{$tableAlias}.tahun_ajaran_id", $tahunAjaranId);
            }

            return $query;
        };

        $totalTagihanQuery = Tagihan::where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId)
            ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id');
        $applyPeriode($totalTagihanQuery);
        $totalTagihan = $totalTagihanQuery->sum('jenis_tagihans.jumlah');

        $totalTerbayarQuery = Pembayaran::join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
            ->where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId);
        $applyPeriode($totalTerbayarQuery);
        $totalTerbayar = $totalTerbayarQuery->sum('pembayarans.jumlah');

        $tagihanListQuery = Tagihan::select(
            'jenis_tagihans.nama as nama_jenis_tagihan',
            'jenis_tagihans.jumlah',
            'jenis_tagihans.jatuh_tempo',
            'tagihans.status'
        )
            ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
            ->where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId);
        $applyPeriode($tagihanListQuery);
        $tagihanList = $tagihanListQuery->get()
            ->map(fn ($item) => [
                'nama_jenis_tagihan' => $item->nama_jenis_tagihan,
                'jumlah' => (float) $item->jumlah,
                'jatuh_tempo' => $item->jatuh_tempo,
                'status' => $item->status,
            ])
            ->toArray();

        $pembayaranTerbaruQuery = Pembayaran::select(
            'pembayarans.kode_pembayaran',
            'pembayarans.tanggal',
            'jenis_tagihans.nama as nama_jenis_tagihan',
            'pembayarans.metode',
            'pembayarans.jumlah'
        )
            ->join('tagihans', 'pembayarans.kode_tagihan', '=', 'tagihans.kode_tagihan')
            ->join('jenis_tagihans', 'tagihans.jenis_tagihan_id', '=', 'jenis_tagihans.id')
            ->where('tagihans.nis', $siswa->nis)
            ->where('tagihans.branch_id', $branchId);
        $applyPeriode($pembayaranTerbaruQuery);
        $pembayaranTerbaru = $pembayaranTerbaruQuery
            ->orderBy('pembayarans.tanggal', 'desc')
            ->orderBy('pembayarans.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'kode_pembayaran' => $item->kode_pembayaran,
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
