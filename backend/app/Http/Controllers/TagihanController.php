<?php

namespace App\Http\Controllers;

use App\Events\TagihanCreated;
use App\Http\Requests\BayarLunasRequest;
use App\Http\Requests\BayarTidakLunasRequest;
use App\Http\Requests\TagihanRequest;
use App\Http\Resources\TagihanGroupedResource;
use App\Http\Resources\TagihanResource;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Services\GenerateKodeTagihan;
use App\Services\SiblingDetectionService;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TagihanController extends Controller
{
    use Traits\Sortable;

    protected SiblingDetectionService $siblingDetectionService;

    public function __construct(SiblingDetectionService $siblingDetectionService)
    {
        $this->siblingDetectionService = $siblingDetectionService;
    }
    /**
     * Get tagihan data grouped by siswa with pagination at the siswa level.
     */
    #[QueryParameter('search', description: 'Pencarian nama / nis siswa', required: false, example: 'Ahmad')]
    #[QueryParameter('jenjang', description: 'Filter jenjang (TK/MI/KB)', required: false, example: 'MI')]
    #[QueryParameter('status', description: 'Filter status tagihan (Lunas/Belum Lunas/Belum Dibayar)', required: false, example: 'Belum Lunas')]
    #[QueryParameter('per_page', description: 'Jumlah siswa per halaman (max 100)', required: false, example: 10)]
    public function grouped()
    {
        $user = Auth::user();

        // Resolve tahun_ajaran_id filter
        $tahunAjaranId = $this->resolveTahunAjaranFilter($user);
        if ($tahunAjaranId === null) {
            // No active period and no filter provided — return empty
            return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 10, 'total' => 0]]);
        }

        $query = Siswa::query()
            ->whereHas('tagihan', function ($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId);
            })
            ->where('branch_id', $user->branch_id);

        // Non-admin users can only see their own data
        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            $query->where('nis', $user->username);
        }

        // Search filter: case-insensitive substring match on nama or nis
        $search = request('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        // Jenjang filter: exact match
        $jenjang = request('jenjang');
        if ($jenjang) {
            $query->where('jenjang', $jenjang);
        }

        // Status filter: only include siswa that have at least one tagihan with matching status
        $status = request('status');
        if ($status) {
            $query->whereHas('tagihan', function ($q) use ($status, $tahunAjaranId) {
                $q->where('status', $status)->where('tahun_ajaran_id', $tahunAjaranId);
            });
        }

        // Sort alphabetically by nama
        $query->orderBy('nama', 'asc');

        // Paginate at siswa level
        $perPage = min((int) request('per_page', 10), 100);
        $siswaList = $query->paginate($perPage);

        // Eager load tagihan (scoped to period) with jenis_tagihan and kelas
        $siswaList->load(['kelas']);
        $siswaList->each(function ($siswa) use ($tahunAjaranId) {
            $siswa->setRelation('tagihan', $siswa->tagihan()->where('tahun_ajaran_id', $tahunAjaranId)->with('jenis_tagihan')->get());
        });

        return TagihanGroupedResource::collection($siswaList);
    }

    #[QueryParameter('search', description: 'Pencarian kode_tagihan / nama / nis', required: false, example: 'TAG-2025')]
    #[QueryParameter('jenjang', description: 'Filter jenjang (TK/MI/KB)', required: false, example: 'MI')]
    #[QueryParameter('status', description: 'Filter status tagihan (Lunas/Belum Lunas)', required: false, example: 'Belum Lunas')]
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 30)]
    #[QueryParameter('sort', description: 'Column to sort by (kode_tagihan, nis, status, tmp, created_at)', required: false, example: 'kode_tagihan')]
    #[QueryParameter('direction', description: 'Sort direction (asc or desc)', required: false, example: 'desc')]
    public function index()
    {
        $user = Auth::user();

        // Resolve tahun_ajaran_id filter
        $tahunAjaranId = $this->resolveTahunAjaranFilter($user);
        if ($tahunAjaranId === null) {
            // No active period and no filter provided — return empty collection
            return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 30, 'total' => 0]]);
        }

        $query = Tagihan::query()
            ->with([
                'siswa' => function ($q) { $q->select(['id','nis','nama','jenjang','kelas_id','kategori_id']); },
                'jenis_tagihan' => function ($q) { $q->select(['id','nama','jatuh_tempo','jumlah']); },
            ])
            ->select(['kode_tagihan','jenis_tagihan_id','nis','tmp','status','branch_id','tahun_ajaran_id'])
            ->where('branch_id', $user->branch_id)
            ->where('tahun_ajaran_id', $tahunAjaranId);

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            $query->whereHas('siswa', fn($q) => $q->where('nis', $user->username));
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

        $this->applySorting(
            $query,
            ['kode_tagihan', 'nis', 'status', 'tmp', 'created_at'],
            'kode_tagihan',
            'desc'
        );

        $tagihan = $query->paginate(request('per_page',30));
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
        $user = Auth::user();

        // Resolve tahun_ajaran_id: auto-assign Periode_Aktif if not provided
        $tahunAjaranId = $request->input('tahun_ajaran_id');
        if (!$tahunAjaranId) {
            $periodeAktif = TahunAjaran::getAktif($user->branch_id);
            if (!$periodeAktif) {
                throw new HttpResponseException(response([
                    'errors' => ['tahun_ajaran_id' => ['Periode aktif harus diatur terlebih dahulu.']]
                ], 422));
            }
            $tahunAjaranId = $periodeAktif->id;
        } else {
            // Validate branch ownership of provided tahun_ajaran_id
            $tahunAjaran = TahunAjaran::find($tahunAjaranId);
            if (!$tahunAjaran || $tahunAjaran->branch_id !== $user->branch_id) {
                throw new HttpResponseException(response([
                    'errors' => ['tahun_ajaran_id' => ['Tahun ajaran tidak ditemukan atau bukan milik branch Anda.']]
                ], 422));
            }
        }

        $siswa = Siswa::query()->select(['id','nis'])
            ->where('kelas_id',$data['kelas_id'])
            ->where('jenjang',$data['jenjang'])
            ->where('kategori_id',$data['kategori_id'])
            ->where('branch_id', $user->branch_id)
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
                'branch_id' => $user->branch_id,
                'tahun_ajaran_id' => $tahunAjaranId,
            ]);
            $freshTagihan = $t->fresh([
                'siswa' => fn($q) => $q->select(['id','nis','nama','jenjang','kelas_id','kategori_id']),
                'jenis_tagihan' => fn($q) => $q->select(['id','nama','jatuh_tempo','jumlah']),
            ]);
            $created->push($freshTagihan);

            // Dispatch email notification event
            TagihanCreated::dispatch(collect([$freshTagihan]), $s);
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

        // Explicitly check for associated pembayaran records
        if ($tagihan->pembayaran()->exists()) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['tagihan sudah dibayar dan tidak dapat dihapus.']]
            ],409));
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
        $sisa = $tagihan->jenis_tagihan->jumlah - $tagihan->tmp;
        $jumlah = when($tagihan->status=='Belum Lunas', $sisa, $tagihan->jenis_tagihan->jumlah);
        $tagihan->update(['status' => 'Lunas','tmp' => $tagihan->jenis_tagihan->jumlah]);
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

    /**
     * Get tagihan for the logged-in siswa with sibling support.
     *
     * Returns tagihan data for the selected siswa (self or sibling),
     * along with a sibling list for the selector UI.
     */
    #[QueryParameter('siswa_id', description: 'Optional siswa ID to view sibling tagihan', required: false, example: 1)]
    public function siswaView(Request $request): JsonResponse
    {
        $user = Auth::user();

        // 1. Check if user has a siswa_id (is a siswa account)
        if (!$user->siswa_id) {
            return response()->json([
                'errors' => ['message' => ['Akun ini bukan akun siswa.']]
            ], 403);
        }

        // 2. Find the Siswa record for the authenticated user
        $siswa = Siswa::find($user->siswa_id);
        if (!$siswa) {
            return response()->json([
                'errors' => ['message' => ['Data siswa tidak ditemukan.']]
            ], 404);
        }

        // 3. Use SiblingDetectionService to find siblings
        $siblings = $this->siblingDetectionService->findSiblings($siswa);

        // 4. Determine which siswa_id to show tagihan for
        $requestedSiswaId = $request->query('siswa_id');
        $selectedSiswaId = $siswa->id; // default to account owner

        if ($requestedSiswaId !== null) {
            $requestedSiswaId = (int) $requestedSiswaId;

            // Validate: must be self or a detected sibling
            $validIds = $siblings->pluck('id')->push($siswa->id)->toArray();

            if (!in_array($requestedSiswaId, $validIds)) {
                return response()->json([
                    'errors' => ['message' => ['Anda tidak memiliki akses ke data siswa ini.']]
                ], 403);
            }

            $selectedSiswaId = $requestedSiswaId;
        }

        // 5. Get the selected siswa's NIS for tagihan query
        $selectedSiswa = $selectedSiswaId === $siswa->id
            ? $siswa
            : Siswa::find($selectedSiswaId);

        // 6. Query tagihan for the selected siswa (with jenis_tagihan relationship)
        $tagihan = Tagihan::where('nis', $selectedSiswa->nis)
            ->with('jenis_tagihan')
            ->get();

        // 7. Build sibling list for selector (id + nama)
        $siblingList = $siblings->map(function ($sibling) {
            return [
                'id' => $sibling->id,
                'nama' => $sibling->nama,
            ];
        })->values();

        return response()->json([
            'data' => [
                'tagihan' => TagihanResource::collection($tagihan),
                'siblings' => $siblingList,
                'selected_siswa_id' => $selectedSiswaId,
                'selected_siswa_nama' => $selectedSiswa->nama,
            ]
        ]);
    }

    /**
     * Resolve the tahun_ajaran_id filter from request or default to Periode_Aktif.
     * Returns null if no filter provided and no Periode_Aktif exists.
     */
    private function resolveTahunAjaranFilter($user): ?int
    {
        $tahunAjaranId = request('tahun_ajaran_id');

        if ($tahunAjaranId) {
            // Validate branch ownership
            $tahunAjaran = TahunAjaran::find($tahunAjaranId);
            if (!$tahunAjaran || $tahunAjaran->branch_id !== $user->branch_id) {
                throw new HttpResponseException(response([
                    'errors' => ['tahun_ajaran_id' => ['Tahun ajaran tidak ditemukan atau bukan milik branch Anda.']]
                ], 422));
            }
            return (int) $tahunAjaranId;
        }

        // Default to Periode_Aktif
        $periodeAktif = TahunAjaran::getAktif($user->branch_id);
        return $periodeAktif?->id;
    }
}
