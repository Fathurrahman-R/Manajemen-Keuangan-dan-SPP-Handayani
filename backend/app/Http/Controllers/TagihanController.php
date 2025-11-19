<?php

namespace App\Http\Controllers;

use App\Http\Requests\BayarLunasRequest;
use App\Http\Requests\BayarTidakLunasRequest;
use App\Http\Requests\TagihanRequest;
use App\Http\Resources\TagihanResource;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\GenerateKodeTagihan;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagihanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Tagihan::with([
            'siswa',
            'jenis_tagihan',
        ]);

        // Jika bukan admin, filter berdasarkan relasi tagihan
        if ($user->role !== 'admin') {
            $query->whereHas('siswa', function ($q) use ($user) {
                $q->where('nis', $user->username);
            });
        }

        $tagihan = $query->paginate(request('per_page',30));

        if ($tagihan->isEmpty()) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["belum ada data tagihan."]
                ]
            ], 404));
        }

        return TagihanResource::collection($tagihan);
    }

    public function create(TagihanRequest $request)
    {
        $data = $request->validated();
        $siswa = Siswa::with([
            'kelas',
            'kategori'
        ])
            ->select(['id', 'nis'])
            ->whereKelasId($data['kelas_id'])
            ->whereJenjang($data['jenjang'])
            ->whereKategoriId($data['kategori_id'])
            ->get();

        if ($siswa->isEmpty()) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "siswa tidak ditemukan."
                    ]
                ]
            ], 404));
        }

        $created = collect();
        foreach ($siswa as $s) {
            $tagihan = Tagihan::create([
                'kode_tagihan' => GenerateKodeTagihan::generate(),
                'jenis_tagihan_id' => $data['jenis_tagihan_id'],
                'nis' => $s->nis,
            ]);
            $created->push($tagihan->fresh(['siswa', 'jenis_tagihan']));
        }

        return TagihanResource::collection($created);
    }

    public function delete(string $kode_tagihan)
    {
        $tagihan = Tagihan::query()->find($kode_tagihan);
        $pembayaran = Pembayaran::query()->find($kode_tagihan);
        if (!$tagihan) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["tagihan tidak ditemukan."]
                ]
            ],404));
        }
        if($pembayaran)
        {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["tagihan ini sudah memiliki data pembayaran."]
                ]
            ],400));
        }
        $tagihan->delete();
        return response([
            "data"=>true
        ])->setStatusCode(200);

    }

    public static function lunas(BayarLunasRequest $request, string $kode_tagihan)
    {
        $tagihan = Tagihan::with([
            'siswa',
            'jenis_tagihan'
        ])->find($kode_tagihan);
        if(!$tagihan){
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "tagihan tidak ditemukan."
                    ]
                ]
            ],404));
        }
        $jumlah = $tagihan->jenis_tagihan->jumlah;
        $tagihan->update([
            'status'=>'Lunas',
            'tmp'=>$jumlah
        ]);
        return $jumlah;
    }

    public static function bayar(BayarTidakLunasRequest $request, string $kode_tagihan)
    {
        $data = $request->validated();
        $tagihan = Tagihan::with([
            'siswa',
            'jenis_tagihan'
        ])->find($kode_tagihan);

        if(!$tagihan){
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "tagihan tidak ditemukan."
                    ]
                ]
            ],404));
        }
        $jumlah_tagihan = $tagihan->jenis_tagihan->jumlah;
        if($jumlah_tagihan < $data['jumlah']){
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "jumlah bayar tidak boleh melebihi jumlah tagihan."
                    ]
                ]
            ],400));
        }

        $jumlah = $data['jumlah']==null?$data['jumlah']:$tagihan->tmp+$data['jumlah'];

        $tagihan->update([
            'tmp'=>$jumlah,
            'status'=>$jumlah_tagihan==$jumlah?"Lunas":"Belum Lunas"
        ]);
    }
}
