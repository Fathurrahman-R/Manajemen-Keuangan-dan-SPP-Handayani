<?php

namespace App\Http\Controllers;

use App\Http\Requests\TahunAjaranRequest;
use App\Http\Resources\TahunAjaranResource;
use App\Models\TahunAjaran;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TahunAjaranController extends Controller
{
    /**
     * List all TahunAjaran for the authenticated user's branch.
     */
    #[HeaderParameter('Authorization')]
    public function index()
    {
        $user = Auth::user();
        $tahunAjarans = TahunAjaran::where('branch_id', $user->branch_id)
            ->orderBy('tanggal_mulai', 'desc')
            ->get();

        return TahunAjaranResource::collection($tahunAjarans);
    }

    /**
     * Create a new TahunAjaran.
     */
    #[HeaderParameter('Authorization')]
    public function store(TahunAjaranRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        // Validate nama format: second year must equal first year + 1
        $this->validateNamaFormat($data['nama']);

        // Validate uniqueness per branch (case-insensitive)
        $exists = TahunAjaran::where('branch_id', $user->branch_id)
            ->whereRaw('LOWER(nama) = ?', [strtolower($data['nama'])])
            ->exists();

        if ($exists) {
            throw new HttpResponseException(response([
                'errors' => ['nama' => ['Nama tahun ajaran sudah ada untuk branch ini.']],
            ], 422));
        }

        $tahunAjaran = TahunAjaran::create([
            'nama' => $data['nama'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'status' => 'Non-Aktif',
            'branch_id' => $user->branch_id,
        ]);

        return (new TahunAjaranResource($tahunAjaran))->response()->setStatusCode(201);
    }

    /**
     * Show a single TahunAjaran.
     */
    #[HeaderParameter('Authorization')]
    public function show(int $id)
    {
        $tahunAjaran = $this->findAndVerifyBranch($id);

        return (new TahunAjaranResource($tahunAjaran))->response()->setStatusCode(200);
    }

    /**
     * Update a TahunAjaran.
     */
    #[HeaderParameter('Authorization')]
    public function update(TahunAjaranRequest $request, int $id)
    {
        $data = $request->validated();
        $user = Auth::user();
        $tahunAjaran = $this->findAndVerifyBranch($id);

        // Validate nama format: second year must equal first year + 1
        $this->validateNamaFormat($data['nama']);

        // Validate uniqueness per branch (case-insensitive), excluding self
        $exists = TahunAjaran::where('branch_id', $user->branch_id)
            ->whereRaw('LOWER(nama) = ?', [strtolower($data['nama'])])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            throw new HttpResponseException(response([
                'errors' => ['nama' => ['Nama tahun ajaran sudah ada untuk branch ini.']],
            ], 422));
        }

        $tahunAjaran->update([
            'nama' => $data['nama'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
        ]);

        return (new TahunAjaranResource($tahunAjaran->fresh()))->response()->setStatusCode(200);
    }

    /**
     * Delete a TahunAjaran.
     */
    #[HeaderParameter('Authorization')]
    public function destroy(int $id)
    {
        $tahunAjaran = $this->findAndVerifyBranch($id);

        // Check for associated records
        $hasTagihan = $tahunAjaran->tagihans()->exists();
        $hasJenisTagihan = $tahunAjaran->jenisTagihans()->exists();
        $hasSiswaKelas = $tahunAjaran->siswaKelas()->exists();

        if ($hasTagihan || $hasJenisTagihan || $hasSiswaKelas) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['Tahun ajaran tidak dapat dihapus karena memiliki data terkait.']],
            ], 409));
        }

        $tahunAjaran->delete();

        return response(['data' => true])->setStatusCode(200);
    }

    /**
     * Activate a TahunAjaran (deactivates all others in the same branch).
     */
    #[HeaderParameter('Authorization')]
    public function activate(int $id)
    {
        $tahunAjaran = $this->findAndVerifyBranch($id);

        // If already active, return current without changes
        if ($tahunAjaran->status === 'Aktif') {
            return (new TahunAjaranResource($tahunAjaran))->response()->setStatusCode(200);
        }

        DB::transaction(function () use ($tahunAjaran) {
            // Deactivate all others in the same branch
            TahunAjaran::where('branch_id', $tahunAjaran->branch_id)
                ->where('status', 'Aktif')
                ->update(['status' => 'Non-Aktif']);

            // Activate the target
            $tahunAjaran->update(['status' => 'Aktif']);
        });

        return (new TahunAjaranResource($tahunAjaran->fresh()))->response()->setStatusCode(200);
    }

    /**
     * Deactivate a TahunAjaran.
     */
    #[HeaderParameter('Authorization')]
    public function deactivate(int $id)
    {
        $tahunAjaran = $this->findAndVerifyBranch($id);

        $tahunAjaran->update(['status' => 'Non-Aktif']);

        return (new TahunAjaranResource($tahunAjaran->fresh()))->response()->setStatusCode(200);
    }

    /**
     * Find TahunAjaran by ID and verify it belongs to the authenticated user's branch.
     */
    private function findAndVerifyBranch(int $id): TahunAjaran
    {
        $tahunAjaran = TahunAjaran::find($id);

        if (! $tahunAjaran) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['Tahun ajaran tidak ditemukan.']],
            ], 404));
        }

        if ($tahunAjaran->branch_id !== Auth::user()->branch_id) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['Anda tidak memiliki izin untuk melakukan operasi ini.']],
            ], 403));
        }

        return $tahunAjaran;
    }

    /**
     * Validate that the nama follows YYYY/YYYY format where second year = first year + 1.
     */
    private function validateNamaFormat(string $nama): void
    {
        $parts = explode('/', $nama);
        if (count($parts) !== 2) {
            throw new HttpResponseException(response([
                'errors' => ['nama' => ['Format nama harus YYYY/YYYY dengan tahun kedua = tahun pertama + 1.']],
            ], 422));
        }

        $firstYear = (int) $parts[0];
        $secondYear = (int) $parts[1];

        if ($secondYear !== $firstYear + 1) {
            throw new HttpResponseException(response([
                'errors' => ['nama' => ['Format nama harus YYYY/YYYY dengan tahun kedua = tahun pertama + 1.']],
            ], 422));
        }
    }
}
