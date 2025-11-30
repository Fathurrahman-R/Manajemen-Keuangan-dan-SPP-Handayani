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
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TagihanController extends Controller
{
    #[QueryParameter('search', description: 'Pencarian kode_tagihan / nama / nis', required: false, example: 'TAG-2025')]
    #[QueryParameter('jenjang', description: 'Filter jenjang (TK/MI/KB)', required: false, example: 'MI')]
    #[QueryParameter('status', description: 'Filter status tagihan (Lunas/Belum Lunas)', required: false, example: 'Belum Lunas')]
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 30)]
    public function index()
    {
        $user = Auth::user();
        $query = Tagihan::query()
            ->with([
                'siswa' => function ($q) { $q->select(['id','nis','nama','jenjang','kelas_id','kategori_id']); },
                'jenis_tagihan' => function ($q) { $q->select(['id','nama','jatuh_tempo','jumlah']); },
            ])
            ->select(['kode_tagihan','jenis_tagihan_id','nis','tmp','status']);
        if ($user && $user->role !== 'admin') {
            $query->whereHas('siswa', fn($q) => $q->where('nis',$user->username));
        }
        $search = request('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode_tagihan','like',"%{$search}%")
                  ->orWhereHas('siswa', function($qs) use ($search){
                      $qs->where('nama','like',"%{$search}%")
                         ->orWhere('nis','like',"%{$search}%");
                  });
            });
        }
        $jenjang = request('jenjang');
        if ($jenjang) {
            $query->where(function($q) use ($jenjang) {
                $q->whereHas('siswa',function($qs) use ($jenjang){
                    $qs->where('jenjang',$jenjang);
                });
            });
        }
        $status = request('status');
        if ($status) {
            $query->where('status',$status);
        }
        $tagihan = $query->paginate(request('per_page',30));
        // Kembalikan langsung koleksi resource (status 200 meski kosong)
        return TagihanResource::collection($tagihan);
    }

    #[HeaderParameter('Authorization')]
    public function get(string $kode_tagihan)
    {
        $tagihan = Tagihan::with([
            'siswa' => fn($q) => $q->select(['id','nis','nama','jenjang','kelas_id','kategori_id']),
            'jenis_tagihan' => fn($q) => $q->select(['id','nama','jatuh_tempo','jumlah']),
        ])->select(['kode_tagihan','jenis_tagihan_id','nis','tmp','status'])->find($kode_tagihan);
        if (!$tagihan) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan tidak ditemukan.']]
            ],404));
        }
        return (new TagihanResource($tagihan))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function create(TagihanRequest $request)
    {
        $data = $request->validated();
        $siswa = Siswa::query()->select(['id','nis'])
            ->where('kelas_id',$data['kelas_id'])
            ->where('jenjang',$data['jenjang'])
            ->where('kategori_id',$data['kategori_id'])
            ->get();
        if ($siswa->isEmpty()) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['siswa tidak ditemukan.']]
            ],404));
        }
        $created = collect();
        foreach ($siswa as $s) {
            $t = Tagihan::create([
                'kode_tagihan' => GenerateKodeTagihan::generate(),
                'jenis_tagihan_id' => $data['jenis_tagihan_id'],
                'nis' => $s->nis,
            ]);
            $created->push($t->fresh([
                'siswa' => fn($q) => $q->select(['id','nis','nama','jenjang','kelas_id','kategori_id']),
                'jenis_tagihan' => fn($q) => $q->select(['id','nama','jatuh_tempo','jumlah']),
            ]));
        }
        return TagihanResource::collection($created)->response()->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function update(Request $request, string $kode_tagihan)
    {
        $tagihan = Tagihan::find($kode_tagihan);
        if (!$tagihan) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan tidak ditemukan.']]
            ],404));
        }
        $tagihan->update(['jenis_tagihan_id' => $request['jenis_tagihan_id']]);
        $tagihan->load([
            'siswa' => fn($q) => $q->select(['id','nis','nama','jenjang','kelas_id','kategori_id']),
            'jenis_tagihan' => fn($q) => $q->select(['id','nama','jatuh_tempo','jumlah']),
        ]);
        return (new TagihanResource($tagihan))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $kode_tagihan)
    {
        $tagihan = Tagihan::query()->find($kode_tagihan);
        if (!$tagihan) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan tidak ditemukan.']]
            ],404));
        }
        try {
            $tagihan->delete();
        } catch (QueryException|Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan sudah dibayar dan tidak dapat dihapus.']]
            ],409));
        }
        return response(['data' => true])->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public static function lunas(BayarLunasRequest $request, string $kode_tagihan)
    {
        $tagihan = Tagihan::with(['siswa','jenis_tagihan'])->find($kode_tagihan);
        if (!$tagihan) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan tidak ditemukan.']]
            ],404));
        }
        $jumlah = $tagihan->jenis_tagihan->jumlah;
        $tagihan->update(['status' => 'Lunas','tmp' => $jumlah]);
        return $jumlah;
    }

    #[HeaderParameter('Authorization')]
    public static function bayar(BayarTidakLunasRequest $request, string $kode_tagihan)
    {
        $data = $request->validated();
        $tagihan = Tagihan::with(['siswa','jenis_tagihan'])->find($kode_tagihan);
        if (!$tagihan) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan tidak ditemukan.']]
            ],404));
        }
        $jumlah_tagihan = $tagihan->jenis_tagihan->jumlah;
        if ($jumlah_tagihan < $data['jumlah']) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['jumlah bayar tidak boleh melebihi jumlah tagihan.']]
            ],400));
        }
        $jumlah = $data['jumlah'] == null ? $data['jumlah'] : $tagihan->tmp + $data['jumlah'];
        $tagihan->update([
            'tmp' => $jumlah,
            'status' => $jumlah_tagihan == $jumlah ? 'Lunas' : 'Belum Lunas'
        ]);
    }
}
