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
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembayaranController extends Controller
{
    #[HeaderParameter('Authorization')]
    #[QueryParameter('search', description: 'Pencarian kode_pembayaran / nama / nis', required: false, example: 'PAY-2025')]
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 30)]
    public function index()
    {
        $user = Auth::user();
        $search = request('search');
        $perPage = (int) request('per_page', 30);

        $query = Pembayaran::query()
            ->with([
                'tagihan' => function ($q) {
                    $q->with(['jenis_tagihan'])->select(['kode_tagihan','nis','jenis_tagihan_id','tmp','status']);
                },
                'tagihan.siswa' => function ($q) {
                    $q->select(['id','nis','nama','jenjang','kelas_id','kategori_id']);
                }
            ])
            ->where('branch_id', Auth::user()->branch_id)
            ->select(['kode_pembayaran','kode_tagihan','tanggal','metode','jumlah','pembayar']);

        if ($user && $user->role !== 'admin') {
            $query->whereHas('tagihan', function ($q) use ($user) {
                $q->where('nis', $user->username);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_pembayaran','like',"%{$search}%")
                  ->orWhereHas('tagihan', function ($qq) use ($search) {
                      $qq->where('nis','like',"{$search}%")
                         ->orWhereHas('siswa', function ($qs) use ($search) {
                             $qs->where('nama','like',"{$search}%");
                         });
                  });
            });
        }

        $pembayaran = $query->paginate($perPage);

        return PembayaranResource::collection($pembayaran);
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $kode_pembayaran)
    {
        $pembayaran = Pembayaran::with([
            'tagihan' => function ($q) {
                $q->select(['kode_tagihan','jenis_tagihan_id','tmp']);
            },
            'tagihan.jenis_tagihan' => function ($q) {
                $q->select(['id','jumlah']);
            }
        ])->select(['kode_pembayaran','kode_tagihan','jumlah'])->find($kode_pembayaran);

        if (!$pembayaran || !$pembayaran->tagihan) {
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['pembayaran tidak ditemukan.'] ]
            ], 404));
        }

        $tagihan = $pembayaran->tagihan;
        $jenis = $tagihan->jenis_tagihan;
        if (!$jenis) {
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['jenis tagihan tidak ditemukan.'] ]
            ], 404));
        }

        $tmpBaru = $tagihan->tmp - $pembayaran->jumlah;
        if ($tmpBaru < 0) {
            // Inkonistensi data
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['jumlah pembayaran melebihi akumulasi tagihan yang tersimpan.'] ]
            ], 400));
        }
        $tagihanTmpSebelum = $tagihan->getOriginal('tmp'); // setelah update original masih nilai sebelum? (optional)
        $statusBaru = ($tmpBaru == $jenis->jumlah) ? 'Lunas' : ($tmpBaru == 0 ? 'Belum Dibayar':'Belum Lunas');
        $tagihan->update([
            'tmp' => $tmpBaru,
            'status' => $statusBaru
        ]);
        $pembayaran->delete();

        return response([
            'data' => true
//            'tmp_old' => $tagihanTmpSebelum,
//            'jumlah_bayar' => $pembayaran->jumlah,
//            'tmp_new' => $tmpBaru,
//            'status_new' => $statusBaru
        ])->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function lunas(BayarLunasRequest $request, string $kode_tagihan)
    {
        $data = $request->validated();
        // Pastikan tagihan ada sebelum proses (TagihanController akan melakukan validasi lanjutan)
        $tagihan = Tagihan::select(['kode_tagihan','jenis_tagihan_id','tmp'])->find($kode_tagihan);
        if (!$tagihan) {
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['tagihan tidak ditemukan.'] ]
            ], 404));
        }
        $lunas = Pembayaran::query()
            ->with([
                'tagihan' => fn($q) => $q->where('status','Lunas')
            ])
            ->where('kode_tagihan',$kode_tagihan)
            ->exists();
        if ($lunas)
        {
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['tagihan sudah dibayar lunas.'] ]
            ], 400));
        }
        $jumlah = TagihanController::lunas($request, $kode_tagihan);
        $pembayaran = Pembayaran::create([
            'kode_pembayaran' => GenerateKodePembayaran::generate(),
            'kode_tagihan' => $kode_tagihan,
            'tanggal' => now()->format('Y-m-d'),
            'metode' => $data['metode'],
            'jumlah' => $jumlah,
            'pembayar' => $data['pembayar'],
            'branch_id' => Auth::user()->branch_id,
        ]);
        $pembayaran->load(['tagihan','tagihan.jenis_tagihan','tagihan.siswa']);
        return (new PembayaranResource($pembayaran))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function bayar(BayarTidakLunasRequest $request, string $kode_tagihan)
    {
        $data = $request->validated();
        $tagihan = Tagihan::with([
            'siswa' => function ($q) { $q->select(['nis','nama']); },
            'jenis_tagihan' => function ($q) { $q->select(['id','jumlah']); }
        ])
            ->select(['kode_tagihan','tmp','jenis_tagihan_id','nis'])
            ->find($kode_tagihan);

        if (!$tagihan) {
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['tagihan tidak ditemukan.'] ]
            ], 404));
        }

        $lunas = Pembayaran::query()
            ->with([
                'tagihan' => fn($q) => $q->where('status','Lunas')
            ])
            ->where('kode_tagihan',$kode_tagihan)
            ->exists();
        if ($lunas)
        {
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['tagihan sudah dibayar lunas.'] ]
            ], 400));
        }

        $akumulasi = $tagihan->tmp + $data['jumlah'];
        $biaya_tagihan = $tagihan->jenis_tagihan->jumlah ?? 0;

        if ($akumulasi > $biaya_tagihan) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'jumlah pembayaran melebihi sisa/jumlah biaya tagihan.',
//                        "akumulasi: {$akumulasi}",
//                        "biaya tagihan: {$biaya_tagihan}"
                    ]
                ]
            ], 400));
        }

        $pembayaran = Pembayaran::create([
            'kode_pembayaran' => GenerateKodePembayaran::generate(),
            'kode_tagihan' => $kode_tagihan,
            'tanggal' => now()->format('Y-m-d'),
            'metode' => $data['metode'],
            'jumlah' => $data['jumlah'],
            'pembayar' => $data['pembayar'],
            'branch_id' => Auth::user()->branch_id,
        ]);
        TagihanController::bayar($request, $kode_tagihan);
        $pembayaran->load(['tagihan','tagihan.jenis_tagihan','tagihan.siswa']);
        return (new PembayaranResource($pembayaran))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public static function kwitansi(string $kode_pembayaran)
    {
        $pembayaran = Pembayaran::with(['tagihan'])
            ->find($kode_pembayaran);
        if (!$pembayaran) {
            throw new HttpResponseException(response([
                'errors' => [ 'message' => ['pembayaran tidak ditemukan.'] ]
            ], 404));
        }
        return (new KwitansiResource($pembayaran));
    }
}
