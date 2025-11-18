<?php

namespace App\Http\Controllers;

use App\Http\Requests\BayarLunasRequest;
use App\Http\Requests\BayarTidakLunasRequest;
use App\Http\Resources\KwitansiResource;
use App\Http\Resources\PembayaranResource;
use App\Http\Resources\TagihanResource;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\GenerateKodePembayaran;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembayaranController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Pembayaran::with('tagihan');

        // Jika bukan admin, filter berdasarkan relasi tagihan
        if ($user->role !== 'admin') {
            $query->whereHas('tagihan', function ($q) use ($user)
            {
                $q->where('nis', $user->username);
            });
        }

        $pembayaran = $query->get();

        if ($pembayaran->isEmpty()) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["belum ada data pembayaran."]
                ]
            ], 404));
        }

        return TagihanResource::collection($pembayaran);
    }
    public function lunas(BayarLunasRequest $request, string $kode_tagihan)
    {
        $data = $request->validated();
        $jumlah = TagihanController::lunas($request, $kode_tagihan);
        $pembayaran = Pembayaran::create([
            'kode_pembayaran' => GenerateKodePembayaran::generate(),
            'kode_tagihan'=>$kode_tagihan,
            'tanggal'=>now()->format('Y-m-d'),
            'metode'=>$data['metode'],
            'jumlah'=>$jumlah,
            'pembayar'=>$data['pembayar']
        ]);
        $pembayaran->load(['tagihan','tagihan.jenis_tagihan','tagihan.siswa']);
        return (new PembayaranResource($pembayaran))->response()->setStatusCode(200);
    }
    public function bayar(BayarTidakLunasRequest $request, string $kode_tagihan)
    {
        $data = $request->validated();
        $tagihan = Tagihan::with([
            'siswa'=>function($q){
                $q->select('nis','nama');
            },
            'jenis_tagihan'=>function($q){
                $q->select('id','jumlah');
            },
        ])->select(['kode_tagihan','tmp'])->find($kode_tagihan);

        $akumulasi = $tagihan->tmp + $data['jumlah'];
        $biaya_tagihan = $tagihan->jumlah;

        if($akumulasi > $biaya_tagihan){
            throw new HttpResponseException(response([
                "errors" => [
                    "message"=>[
                        'jumlah pembayaran melebihi sisa/jumlah biaya tagihan.'
                    ]
                ]
            ],400));
        }

        $pembayaran = Pembayaran::create([
            'kode_pembayaran' => GenerateKodePembayaran::generate(),
            'kode_tagihan'=>$kode_tagihan,
            'tanggal'=>now()->format('Y-m-d'),
            'metode'=>$data['metode'],
            'jumlah'=>$data['jumlah'],
            'pembayar'=>$data['pembayar']
        ]);
        TagihanController::bayar($request, $kode_tagihan);
        $pembayaran->load(['tagihan','tagihan.jenis_tagihan','tagihan.siswa']);
        return (new PembayaranResource($pembayaran))->response()->setStatusCode(200);
    }
    public function kwitansi(string $kode_pembayaran)
    {
        $pembayaran = Pembayaran::with([
            'tagihan'
        ])->find($kode_pembayaran);
        if (!$pembayaran) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["pembayaran tidak ditemukan."]
                ]
            ],404));
        }
        return (new KwitansiResource($pembayaran))->response()->setStatusCode(200);
    }
}
