<?php

namespace App\Http\Controllers;

use App\Http\Resources\KasResource;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class KasController extends Controller
{
    public function kasHarian(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;

        // Validasi parameter
        if (!$bulan || !$tahun) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['Parameter bulan dan tahun wajib.']
                ]
            ], 400));
        }
        if (!ctype_digit((string)$bulan) || (int)$bulan < 1 || (int)$bulan > 12) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'bulan' => ['Bulan harus angka antara 1 sampai 12.']
                ]
            ], 400));
        }
        if (!preg_match('/^\d{4}$/', (string)$tahun)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'tahun' => ['Tahun harus 4 digit.']
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
        $pemasukan = Pembayaran::selectRaw('DATE(tanggal) as tanggal, SUM(jumlah) as total')
            ->whereBetween('tanggal', [$start, $end])
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $pengeluaran = Pengeluaran::selectRaw('DATE(tanggal) as tanggal, SUM(jumlah) as total')
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

        if ($dates->isEmpty()) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['Data tidak ditemukan untuk filter yang diberikan.']
                ]
            ], 404));
        }

        /*
        |--------------------------------------------------------------------------
        | 3) Loop tanggal → hitung saldo GLOBAL sampai tanggal itu
        |--------------------------------------------------------------------------
        */
        $kas = [];

        foreach ($dates as $tanggal) {

            $masuk  = $pemasukan[$tanggal]->total ?? 0;
            $keluar = $pengeluaran[$tanggal]->total ?? 0;

            // ❗ SALDO GLOBAL — sesuai buku kas
            $saldoGlobal =
                Pembayaran::whereDate('tanggal', '<=', $tanggal)->sum('jumlah')
                - Pengeluaran::whereDate('tanggal', '<=', $tanggal)->sum('jumlah');

            $kas[] = (object) [
                'tanggal'      => \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y'),
                'total_masuk'  => $masuk,
                'total_keluar' => $keluar,
                'saldo'        => $saldoGlobal
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
    public function rekapBulanan(Request $request)
    {
        $tahun = $request->tahun;

        if (!$tahun) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['Parameter tahun wajib.']
                ]
            ], 400));
        }
        if (!preg_match('/^\d{4}$/', (string)$tahun)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'tahun' => ['Tahun harus 4 digit.']
                ]
            ], 400));
        }

        /*
        |--------------------------------------------------------------------------
        | 1) Ambil pemasukan dan pengeluaran per bulan dalam tahun tersebut
        |--------------------------------------------------------------------------
        */
        $pemasukan = Pembayaran::selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as total")
            ->whereYear('tanggal', $tahun)
            ->groupBy('bulan')
            ->get()
            ->keyBy('bulan');

        $pengeluaran = Pengeluaran::selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as total")
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

        if ($months->isEmpty()) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['Data tidak ditemukan untuk filter yang diberikan.']
                ]
            ], 404));
        }

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
                Pembayaran::whereDate('tanggal', '<=', $lastDate)->sum('jumlah')
                - Pengeluaran::whereDate('tanggal', '<=', $lastDate)->sum('jumlah');

            $kas[] = (object)[
                'tanggal'      => \Carbon\Carbon::parse("$bulan-01")->locale('id')->translatedFormat('F Y'),
                'total_masuk'  => $masuk,
                'total_keluar' => $keluar,
                'saldo'        => $saldoGlobal,
            ];
        }

        return KasResource::collection($kas);
    }
}
