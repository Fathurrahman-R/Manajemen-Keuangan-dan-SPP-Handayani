<?php

namespace App\Http\Controllers;

use App\Http\Resources\KasResource;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KasController extends Controller
{
    #[HeaderParameter('Authorization')]
    #[QueryParameter('bulan', description: 'Filter bulan dalam angka (1-12)', required: true, example: 11)]
    #[QueryParameter('tahun', description: 'Filter tahun dalam 4 digit', required: true, example: 2025)]
    public function kasHarian(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;

        // Validasi parameter
        if (!$bulan || !$tahun) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['parameter bulan dan tahun wajib.']
                ]
            ], 400));
        }
        if (!ctype_digit((string)$bulan) || (int)$bulan < 1 || (int)$bulan > 12) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'bulan' => ['bulan harus angka antara 1 sampai 12.']
                ]
            ], 400));
        }
        if (!preg_match('/^\d{4}$/', (string)$tahun)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'tahun' => ['tahun harus 4 digit.']
                ]
            ], 400));
        }

        // Range bulan
        $bulan = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
        $start = "$tahun-$bulan-01";
        $end   = date('Y-m-t', strtotime($start));

        /*
        |--------------------------------------------------------------------------
        | 1) Ambil pemasukan & pengeluaran per tanggal (di bulan yang difilter)
        |--------------------------------------------------------------------------
        */
        $pemasukan = Pembayaran::query()->selectRaw('DATE(tanggal) as tanggal, SUM(jumlah) as total')
            ->where('branch_id', Auth::user()->branch_id)
            ->whereBetween('tanggal', [$start, $end])
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $pengeluaran = Pengeluaran::selectRaw('DATE(tanggal) as tanggal, SUM(jumlah) as total')
            ->where('branch_id', Auth::user()->branch_id)
            ->whereBetween('tanggal', [$start, $end])
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        /*
        |--------------------------------------------------------------------------
        | 2) Gabungkan tanggal, urutkan ASC agar running balance benar
        |--------------------------------------------------------------------------
        */
        $dates = collect(array_merge(
            $pemasukan->keys()->toArray(),
            $pengeluaran->keys()->toArray()
        ))->unique()->sort();

//        if ($dates->isEmpty()) {
//            throw new HttpResponseException(response()->json([
//                'errors' => [
//                    'message' => ['Data tidak ditemukan untuk filter yang diberikan.']
//                ]
//            ], 404));
//        }

        /*
        |--------------------------------------------------------------------------
        | 3) Loop tanggal → hitung saldo GLOBAL sampai tanggal itu
        |--------------------------------------------------------------------------
        */
        $kas = [];

        foreach ($dates as $tanggal) {

            (float)$masuk  = $pemasukan[$tanggal]->total ?? 0;
            (float)$keluar = $pengeluaran[$tanggal]->total ?? 0;

            // ❗ SALDO GLOBAL — sesuai buku kas
            (float)$saldoGlobal =
                Pembayaran::query()
                    ->where('branch_id', Auth::user()->branch_id)
                    ->whereDate('tanggal', '<=', $tanggal)->sum('jumlah')
                - Pengeluaran::query()
                    ->where('branch_id', Auth::user()->branch_id)
                    ->whereDate('tanggal', '<=', $tanggal)->sum('jumlah');

            $kas[] = (object) [
                'tanggal'      => \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y'),
                'total_masuk'  => floatval($masuk),
                'total_keluar' => floatval($keluar),
                'saldo'        => floatval($saldoGlobal),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4) Urutkan DESC (transaksi terbaru di atas)
        |--------------------------------------------------------------------------
        */
        $kas = collect($kas)->sortByDesc(fn($x) => $x->tanggal)->values();

        return KasResource::collection($kas);
    }

    #[HeaderParameter('Authorization')]
    #[QueryParameter('tahun', description: 'Filter tahun dalam 4 digit', required: true, example: 2025)]
    public function rekapBulanan(Request $request)
    {
        $tahun = $request->tahun;

        if (!$tahun) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['parameter tahun wajib.']
                ]
            ], 400));
        }
        if (!preg_match('/^\d{4}$/', (string)$tahun)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'tahun' => ['tahun harus 4 digit.']
                ]
            ], 400));
        }

        /*
        |--------------------------------------------------------------------------
        | 1) Ambil pemasukan dan pengeluaran per bulan dalam tahun tersebut
        |--------------------------------------------------------------------------
        */
        $pemasukan = Pembayaran::query()
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as total")
            ->where('branch_id', Auth::user()->branch_id)
            ->whereYear('tanggal', $tahun)
            ->groupBy('bulan')
            ->get()
            ->keyBy('bulan');

        $pengeluaran = Pengeluaran::query()
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as total")
            ->where('branch_id', Auth::user()->branch_id)
            ->whereYear('tanggal', $tahun)
            ->groupBy('bulan')
            ->get()
            ->keyBy('bulan');

        /*
        |--------------------------------------------------------------------------
        | 2) Gabungkan bulan + urut ASC (Jan → Des)
        |--------------------------------------------------------------------------
        */
        $months = collect(array_merge(
            $pemasukan->keys()->toArray(),
            $pengeluaran->keys()->toArray()
        ))->unique()->sort();

//        if ($months->isEmpty()) {
//            throw new HttpResponseException(response()->json([
//                'errors' => [
//                    'message' => ['Data tidak ditemukan untuk filter yang diberikan.']
//                ]
//            ], 404));
//        }

        /*
        |--------------------------------------------------------------------------
        | 3) Hitung saldo GLOBAL sampai akhir bulan tersebut
        |--------------------------------------------------------------------------
        */
        $kas = [];

        foreach ($months as $bulan) {

            $masuk  = $pemasukan[$bulan]->total ?? 0;
            $keluar = $pengeluaran[$bulan]->total ?? 0;

            // Ambil tanggal terakhir bulan tsb
            $lastDate = \Carbon\Carbon::parse("$bulan-01")->endOfMonth()->format('Y-m-d');

            // ❗ SALDO GLOBAL SAMPAI AKHIR BULAN
            $saldoGlobal =
                Pembayaran::query()
                    ->where('branch_id', Auth::user()->branch_id)
                    ->whereDate('tanggal', '<=', $lastDate)->sum('jumlah')
                - Pengeluaran::query()
                    ->where('branch_id', Auth::user()->branch_id)
                    ->whereDate('tanggal', '<=', $lastDate)->sum('jumlah');

            $kas[] = (object)[
                'tanggal'      => \Carbon\Carbon::parse("$bulan-01")->locale('id')->translatedFormat('F Y'),
                'total_masuk'  => $masuk,
                'total_keluar' => $keluar,
                'saldo'        => $saldoGlobal,
            ];
        }

        return KasResource::collection($kas);
    }

    /**
     * Detail of a single Kas Harian row (one date) — list of pemasukan and
     * pengeluaran transactions for the given branch on the given date.
     *
     * GET /api/laporan/kas/detail?tanggal=YYYY-MM-DD
     */
    #[HeaderParameter('Authorization')]
    #[QueryParameter('tanggal', description: 'Tanggal (YYYY-MM-DD)', required: true, example: '2025-11-15')]
    public function kasDetail(Request $request): JsonResponse
    {
        $request->validate([
            'tanggal' => 'required|date_format:Y-m-d',
        ]);

        $branchId = Auth::user()->branch_id;
        $tanggal = $request->query('tanggal');

        return response()->json([
            'data' => [
                'tanggal' => $tanggal,
                'pemasukan' => $this->buildPemasukanDetail(
                    Pembayaran::query()
                        ->where('branch_id', $branchId)
                        ->whereDate('tanggal', $tanggal),
                ),
                'pengeluaran' => $this->buildPengeluaranDetail(
                    Pengeluaran::query()
                        ->where('branch_id', $branchId)
                        ->whereDate('tanggal', $tanggal),
                ),
            ],
        ]);
    }

    /**
     * Detail of a single Rekap Bulanan row (one month) — list of pemasukan and
     * pengeluaran transactions for the given branch in the given month/year.
     *
     * GET /api/laporan/rekap/detail?bulan=11&tahun=2025
     */
    #[HeaderParameter('Authorization')]
    #[QueryParameter('bulan', description: 'Bulan (1-12)', required: true, example: 11)]
    #[QueryParameter('tahun', description: 'Tahun 4 digit', required: true, example: 2025)]
    public function rekapDetail(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|digits:4',
        ]);

        $branchId = Auth::user()->branch_id;
        $bulan = (int) $request->query('bulan');
        $tahun = (int) $request->query('tahun');

        return response()->json([
            'data' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'pemasukan' => $this->buildPemasukanDetail(
                    Pembayaran::query()
                        ->where('branch_id', $branchId)
                        ->whereYear('tanggal', $tahun)
                        ->whereMonth('tanggal', $bulan),
                ),
                'pengeluaran' => $this->buildPengeluaranDetail(
                    Pengeluaran::query()
                        ->where('branch_id', $branchId)
                        ->whereYear('tanggal', $tahun)
                        ->whereMonth('tanggal', $bulan),
                ),
            ],
        ]);
    }

    /** @return list<array{tanggal:string,nis:?string,nama:?string,nama_tagihan:?string,jumlah:int}> */
    private function buildPemasukanDetail($baseQuery): array
    {
        return $baseQuery
            ->with(['tagihan.siswa:id,nis,nama', 'tagihan.jenis_tagihan:id,nama'])
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($p) => [
                'tanggal' => (string) $p->tanggal,
                'nis' => $p->tagihan?->nis,
                'nama' => $p->tagihan?->siswa?->nama,
                'nama_tagihan' => $p->tagihan?->jenis_tagihan?->nama,
                'jumlah' => (int) $p->jumlah,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{tanggal:string,nama_pengeluaran:string,jumlah:int,pengaju:?string,penyetuju:?string}> */
    private function buildPengeluaranDetail($baseQuery): array
    {
        return $baseQuery
            ->with(['pengeluaranRequest.requester:id,name', 'pengeluaranRequest.approvalLogs.user:id,name'])
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($p) => [
                'tanggal' => (string) $p->tanggal,
                'nama_pengeluaran' => (string) $p->uraian,
                'jumlah' => (int) $p->jumlah,
                'pengaju' => $p->pengaju_name,
                'penyetuju' => $p->penyetuju_name,
            ])
            ->values()
            ->all();
    }
}
