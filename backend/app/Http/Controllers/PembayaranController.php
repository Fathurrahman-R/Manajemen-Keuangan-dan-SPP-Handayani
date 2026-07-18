<?php

namespace App\Http\Controllers;

use App\Events\PembayaranRecorded;
use App\Exceptions\Midtrans\CannotDeleteOnlinePembayaranException;
use App\Http\Requests\BatchPaymentRequest;
use App\Http\Requests\BayarTidakLunasRequest;
use App\Http\Resources\KwitansiResource;
use App\Http\Resources\PembayaranGroupedResource;
use App\Http\Resources\PembayaranResource;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\GenerateKodePembayaran;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    use Traits\Sortable;

    #[HeaderParameter('Authorization')]
    #[QueryParameter('search', description: 'Pencarian nama / nis siswa', required: false, example: 'Ahmad')]
    #[QueryParameter('jenjang', description: 'Filter jenjang (TK/MI/KB)', required: false, example: 'MI')]
    #[QueryParameter('kelas_id', description: 'Filter kelas (id)', required: false, example: 3)]
    #[QueryParameter('metode', description: 'Filter metode pembayaran (offline/online_midtrans)', required: false, example: 'offline')]
    #[QueryParameter('tahun_ajaran_id', description: 'Filter periode ajaran', required: false, example: 1)]
    #[QueryParameter('sort', description: 'Sort siswa berdasarkan pembayaran terbaru/terlama (latest/oldest) atau nama', required: false, example: 'latest')]
    #[QueryParameter('per_page', description: 'Jumlah siswa per halaman (max 100)', required: false, example: 10)]
    public function grouped()
    {
        $user = Auth::user();

        $query = \App\Models\Siswa::query()
            ->where('branch_id', $user->branch_id)
            ->whereHas('tagihan.pembayaran');

        if ($user && ! $user->hasAnyRole(['superadmin', 'admin'])) {
            $query->where('nis', $user->siswa?->nis ?? $user->username);
        }

        $tahunAjaranId = request('tahun_ajaran_id');
        $allPeriods = request()->boolean('all_periods')
            || ($tahunAjaranId !== null && $tahunAjaranId !== '' && (int) $tahunAjaranId === 0);

        if ($allPeriods) {
            $tahunAjaranId = null;
        } elseif ($tahunAjaranId) {
            // Hanya tampilkan siswa yang punya pembayaran (via tagihan) di periode terpilih.
            $query->whereHas('tagihan', function ($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', (int) $tahunAjaranId)
                    ->whereHas('pembayaran');
            });
        }

        $search = request('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $jenjang = request('jenjang');
        if ($jenjang) {
            $query->where('jenjang', $jenjang);
        }

        $kelasId = request('kelas_id');
        if (! is_null($kelasId) && $kelasId !== '') {
            $query->where('kelas_id', (int) $kelasId);
        }

        $metode = request('metode');
        if ($metode) {
            $query->whereHas('tagihan.pembayaran', function ($q) use ($metode) {
                $q->where('metode', $metode);
            });
        }

        $sort = request('sort', 'nama');
        if ($sort === 'latest' || $sort === 'oldest') {
            $direction = $sort === 'latest' ? 'desc' : 'asc';

            // Subquery: ambil tanggal pembayaran terakhir per siswa (via tagihan.nis -> siswa.nis).
            $latestSub = \App\Models\Pembayaran::query()
                ->selectRaw('tagihans.nis AS nis, MAX(pembayarans.tanggal) AS last_paid_at')
                ->join('tagihans', 'tagihans.kode_tagihan', '=', 'pembayarans.kode_tagihan')
                ->groupBy('tagihans.nis');

            $query
                ->leftJoinSub($latestSub, 'last_pay', function ($join) {
                    $join->on('last_pay.nis', '=', 'siswas.nis');
                })
                ->select('siswas.*')
                ->orderBy('last_pay.last_paid_at', $direction)
                ->orderBy('siswas.nama', 'asc');
        } else {
            $query->orderBy('nama', 'asc');
        }

        $perPage = min((int) request('per_page', 10), 100);
        $siswaList = $query->paginate($perPage);

        $siswaList->load(['kelas']);
        $siswaList->each(function ($siswa) use ($metode, $tahunAjaranId) {
            $siswa->setRelation('pembayaran', $siswa->pembayaranForGroupedView($metode, $tahunAjaranId ? (int) $tahunAjaranId : null));
        });

        return PembayaranGroupedResource::collection($siswaList);
    }

    #[HeaderParameter('Authorization')]
    #[QueryParameter('search', description: 'Pencarian kode_pembayaran / nama / nis', required: false, example: 'PAY-2025')]
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 30)]
    #[QueryParameter('sort', description: 'Column to sort by (tanggal, jumlah, metode, kode_pembayaran)', required: false, example: 'tanggal')]
    #[QueryParameter('direction', description: 'Sort direction (asc or desc)', required: false, example: 'desc')]
    public function index()
    {
        $user = Auth::user();
        $search = request('search');
        $perPage = (int) request('per_page', 30);

        $query = Pembayaran::query()
            ->with([
                'tagihan' => function ($q) {
                    $q->with(['jenis_tagihan'])->select(['kode_tagihan', 'nis', 'jenis_tagihan_id', 'tmp', 'status']);
                },
                'tagihan.siswa' => function ($q) {
                    $q->select(['id', 'nis', 'nama', 'jenjang', 'kelas_id', 'kategori_id']);
                },
            ])

            ->where('branch_id', $user->branch_id)
            ->select(['kode_pembayaran', 'kode_tagihan', 'tanggal', 'metode', 'jumlah', 'pembayar']);

        if ($user && ! $user->hasAnyRole(['superadmin', 'admin'])) {
            $query->whereHas('tagihan', function ($q) use ($user) {
                $q->where('nis', $user->siswa?->nis ?? $user->username);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_pembayaran', 'like', "%{$search}%")
                    ->orWhereHas('tagihan', function ($qq) use ($search) {
                        $qq->where('nis', 'like', "{$search}%")
                            ->orWhereHas('siswa', function ($qs) use ($search) {
                                $qs->where('nama', 'like', "{$search}%");
                            });
                    });
            });
        }

        $this->applySorting($query, ['tanggal', 'jumlah', 'metode', 'kode_pembayaran'], 'tanggal', 'desc');

        $pembayaran = $query->paginate($perPage);

        return PembayaranResource::collection($pembayaran);
    }

    /**
     * Process batch payment (lunas) for multiple tagihan in a single transaction.
     */
    #[HeaderParameter('Authorization')]
    public function batchLunas(BatchPaymentRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        // Load all tagihan with their jenis_tagihan
        $tagihanList = Tagihan::with('jenis_tagihan')
            ->whereIn('kode_tagihan', $data['kode_tagihan'])
            ->get();

        // Verify all tagihan belong to user's branch
        foreach ($tagihanList as $tagihan) {
            if ($tagihan->branch_id !== $user->branch_id) {
                throw new HttpResponseException(response([
                    'errors' => ['message' => ["Tagihan {$tagihan->kode_tagihan} tidak ditemukan atau bukan milik branch Anda."]],
                ], 400));
            }
        }

        // Verify none of the tagihan have status "Lunas"
        foreach ($tagihanList as $tagihan) {
            if ($tagihan->status === 'Lunas') {
                throw new HttpResponseException(response([
                    'errors' => ['message' => ["Tagihan {$tagihan->kode_tagihan} sudah berstatus Lunas."]],
                ], 400));
            }
        }

        try {
            $pembayaranRecords = DB::transaction(function () use ($tagihanList, $data, $user) {
                $records = collect();

                foreach ($tagihanList as $tagihan) {
                    $jumlah = $tagihan->jenis_tagihan->jumlah - $tagihan->tmp;

                    $pembayaran = Pembayaran::create([
                        'kode_pembayaran' => GenerateKodePembayaran::generate(),
                        'kode_tagihan' => $tagihan->kode_tagihan,
                        'tanggal' => now()->format('Y-m-d'),
                        'metode' => $data['metode'],
                        'jumlah' => $jumlah,
                        'pembayar' => $data['pembayar'],
                        'branch_id' => $user->branch_id,
                    ]);

                    $tagihan->update([
                        'status' => 'Lunas',
                        'tmp' => $tagihan->jenis_tagihan->jumlah,
                    ]);

                    $records->push($pembayaran);
                }

                return $records;
            });

            // Load relationships for response
            $pembayaranRecords->each(function ($pembayaran) {
                $pembayaran->load(['tagihan', 'tagihan.jenis_tagihan', 'tagihan.siswa']);

                // Dispatch email notification event
                PembayaranRecorded::dispatch($pembayaran);
            });

            return PembayaranResource::collection($pembayaranRecords)->response()->setStatusCode(200);
        } catch (\Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['Terjadi kesalahan saat memproses pembayaran.']],
            ], 500));
        }
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $kode_pembayaran)
    {
        $pembayaran = Pembayaran::with([
            'tagihan' => function ($q) {
                $q->select(['kode_tagihan', 'jenis_tagihan_id', 'tmp']);
            },
            'tagihan.jenis_tagihan' => function ($q) {
                $q->select(['id', 'jumlah']);
            },
        ])->where('branch_id', Auth::user()->branch_id)->select(['kode_pembayaran', 'kode_tagihan', 'jumlah', 'metode'])->find($kode_pembayaran);

        if (! $pembayaran || ! $pembayaran->tagihan) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['pembayaran tidak ditemukan.']],
            ], 404));
        }

        // Guard: online Midtrans pembayaran cannot be deleted unless user has both permissions
        if (($pembayaran->metode ?? null) === 'online_midtrans') {
            $user = Auth::user();
            if (! ($user->can('delete-pembayaran') && $user->can('manage-midtrans-config'))) {
                throw new CannotDeleteOnlinePembayaranException($kode_pembayaran);
            }
        }

        $tagihan = $pembayaran->tagihan;
        $jenis = $tagihan->jenis_tagihan;
        if (! $jenis) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['jenis tagihan tidak ditemukan.']],
            ], 404));
        }

        $tmpBaru = $tagihan->tmp - $pembayaran->jumlah;
        if ($tmpBaru < 0) {
            // Inkonistensi data
            throw new HttpResponseException(response([
                'errors' => ['message' => ['jumlah pembayaran melebihi akumulasi tagihan yang tersimpan.']],
            ], 400));
        }
        $tagihanTmpSebelum = $tagihan->getOriginal('tmp'); // setelah update original masih nilai sebelum? (optional)
        $statusBaru = ($tmpBaru == $jenis->jumlah) ? 'Lunas' : ($tmpBaru == 0 ? 'Belum Dibayar' : 'Belum Lunas');
        $tagihan->update([
            'tmp' => $tmpBaru,
            'status' => $statusBaru,
        ]);
        $pembayaran->delete();

        return response([
            'data' => true,
            //            'tmp_old' => $tagihanTmpSebelum,
            //            'jumlah_bayar' => $pembayaran->jumlah,
            //            'tmp_new' => $tmpBaru,
            //            'status_new' => $statusBaru
        ])->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function bayar(BayarTidakLunasRequest $request, string $kode_tagihan)
    {
        $data = $request->validated();
        $tagihan = Tagihan::with([
            'siswa' => function ($q) {
                $q->select(['nis', 'nama']);
            },
            'jenis_tagihan' => function ($q) {
                $q->select(['id', 'jumlah']);
            },
        ])
            ->select(['kode_tagihan', 'tmp', 'jenis_tagihan_id', 'nis', 'status'])
            ->find($kode_tagihan);

        if (! $tagihan) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan tidak ditemukan.']],
            ], 404));
        }

        // Check if tagihan is already fully paid
        if ($tagihan->status === 'Lunas') {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan sudah dibayar lunas.']],
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
                    ],
                ],
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
        $pembayaran->load(['tagihan', 'tagihan.jenis_tagihan', 'tagihan.siswa']);

        // Dispatch email notification event
        PembayaranRecorded::dispatch($pembayaran);

        return (new PembayaranResource($pembayaran))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public static function kwitansi(string $kode_pembayaran)
    {
        $pembayaran = Pembayaran::with(['tagihan'])
            ->where('branch_id', Auth::user()->branch_id)
            ->find($kode_pembayaran);
        if (! $pembayaran) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['pembayaran tidak ditemukan.']],
            ], 404));
        }

        return new KwitansiResource($pembayaran);
    }

    /**
     * List Pembayaran for the logged-in siswa. Used by the Portal "Riwayat
     * Pembayaran" page since the admin `/pembayaran` endpoint is gated by
     * `deny_siswa` + `permission:view-pembayaran`.
     */
    #[HeaderParameter('Authorization')]
    #[QueryParameter('search', description: 'Pencarian kode_pembayaran', required: false, example: 'PAY-2025')]
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 10)]
    #[QueryParameter('include_pending', description: 'Sertakan transaksi Midtrans status pending sebagai item pseudo. Pembayaran final tetap diutamakan.', required: false, example: 'true')]
    public function siswaView(Request $request)
    {
        $user = Auth::user();

        if (! $user->siswa_id) {
            return response()->json([
                'errors' => ['message' => ['Akun ini bukan akun siswa.']],
            ], 403);
        }

        $siswa = $user->siswa;
        if (! $siswa) {
            return response()->json([
                'errors' => ['message' => ['Data siswa tidak ditemukan.']],
            ], 404);
        }

        $query = Pembayaran::query()
            ->with([
                'tagihan' => fn ($q) => $q->with(['jenis_tagihan'])->select(['kode_tagihan', 'nis', 'jenis_tagihan_id', 'tmp', 'status']),
                'tagihan.siswa' => fn ($q) => $q->select(['id', 'nis', 'nama']),
            ])

            ->whereHas('tagihan', fn ($q) => $q->where('nis', $siswa->nis))
            ->select(['kode_pembayaran', 'kode_tagihan', 'tanggal', 'metode', 'jumlah', 'pembayar']);

        $search = $request->query('search');
        if ($search) {
            $query->where('kode_pembayaran', 'like', "%{$search}%");
        }

        $query->orderByDesc('tanggal')->orderByDesc('kode_pembayaran');

        $perPage = min((int) $request->query('per_page', 10), 100);
        $pembayaran = $query->paginate($perPage);

        // Sertakan transaksi Midtrans pending (belum jadi Pembayaran final)
        // sebagai pseudo-row di awal list — hanya pada halaman pertama.
        if ($request->boolean('include_pending') && $pembayaran->currentPage() === 1) {
            $pendingTrx = \App\Models\MidtransTransaction::query()
                ->where('nis', $siswa->nis)
                ->whereIn('status', ['pending', 'authorize'])
                ->with(['tagihan' => fn ($q) => $q->with('jenis_tagihan')])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn ($trx) => [
                    'kode_pembayaran' => $trx->order_id,
                    'kode_tagihan' => $trx->kode_tagihan,
                    'tanggal' => $trx->created_at?->toDateString(),
                    'metode' => 'online_midtrans',
                    'jumlah' => (float) $trx->amount_paid,
                    'pembayar' => $siswa->nama,
                    'status' => 'pending',
                    'is_pending' => true,
                    'order_id' => $trx->order_id,
                    'kode_tagihan_relation' => $trx->tagihan ? [
                        'kode_tagihan' => $trx->tagihan->kode_tagihan,
                        'jenis_tagihan' => [
                            'nama' => $trx->tagihan->jenis_tagihan->nama ?? '-',
                        ],
                    ] : null,
                ])
                ->toArray();

            $resource = PembayaranResource::collection($pembayaran);

            return $resource->additional([
                'pending' => $pendingTrx,
            ]);
        }

        return PembayaranResource::collection($pembayaran);
    }
}
