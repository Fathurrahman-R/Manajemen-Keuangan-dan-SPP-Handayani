<?php

namespace App\Http\Controllers;

use App\Http\Requests\JenisTagihanRequest;
use App\Http\Resources\JenisTagihanResource;
use App\Models\JenisTagihan;
use App\Models\TahunAjaran;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Throwable;

class JenisTagihanController extends Controller
{
    #[HeaderParameter('Authorization')]
    public function index()
    {
        $user = Auth::user();

        // Resolve tahun_ajaran_id filter
        $tahunAjaranId = $this->resolveTahunAjaranFilter($user);
        if ($tahunAjaranId === null) {
            // No active period and no filter provided — return empty collection
            return JenisTagihanResource::collection(collect());
        }

        $query = JenisTagihan::query()
            ->where('branch_id', $user->branch_id);

        if ($tahunAjaranId !== 'all') {
            $query->where('tahun_ajaran_id', $tahunAjaranId);
        }

        return JenisTagihanResource::collection($query->get());
    }

    #[HeaderParameter('Authorization')]
    public function create(JenisTagihanRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        // Resolve tahun_ajaran_id: auto-assign Periode_Aktif if not provided
        $tahunAjaranId = $request->input('tahun_ajaran_id');
        if (! $tahunAjaranId) {
            $periodeAktif = TahunAjaran::getAktif($user->branch_id);
            if (! $periodeAktif) {
                throw new HttpResponseException(response([
                    'errors' => ['tahun_ajaran_id' => ['Periode aktif harus diatur terlebih dahulu.']],
                ], 422));
            }
            $tahunAjaranId = $periodeAktif->id;
        } else {
            // Validate branch ownership of provided tahun_ajaran_id
            $tahunAjaran = TahunAjaran::find($tahunAjaranId);
            if (! $tahunAjaran || $tahunAjaran->branch_id !== $user->branch_id) {
                throw new HttpResponseException(response([
                    'errors' => ['tahun_ajaran_id' => ['Tahun ajaran tidak ditemukan atau bukan milik branch Anda.']],
                ], 422));
            }
        }

        try {
            $jt = new JenisTagihan($data);
            $jt->branch_id = $user->branch_id;
            $jt->tahun_ajaran_id = $tahunAjaranId;
            $jt->save();
        } catch (QueryException|Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['gagal menyimpan jenis tagihan.'],
                ],
            ], 500));
        }

        return (new JenisTagihanResource($jt))->response()->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function get(string $id)
    {
        $jt = JenisTagihan::query()->find($id);
        if (! $jt) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan tidak ditemukan.'],
                ],
            ], 404));
        }

        return (new JenisTagihanResource($jt))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function update(JenisTagihanRequest $request, string $id)
    {
        $jt = JenisTagihan::query()->find($id);
        if (! $jt) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan tidak ditemukan.'],
                ],
            ], 404));
        }
        $data = $request->validated();
        try {
            $jt->update($data);
        } catch (QueryException|Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['gagal update jenis tagihan.'],
                ],
            ], 500));
        }

        return (new JenisTagihanResource($jt))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $id)
    {
        $jt = JenisTagihan::query()->find($id);
        if (! $jt) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan tidak ditemukan.'],
                ],
            ], 404));
        }
        try {
            $jt->delete();
        } catch (QueryException|Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan digunakan dan tidak dapat dihapus.'],
                ],
            ], 409));
        }

        return response(['data' => true])->setStatusCode(200);
    }

    /**
     * Resolve the tahun_ajaran_id filter from request or default to Periode_Aktif.
     *
     * Return value:
     *   - int  : id periode terpilih (atau periode aktif default)
     *   - 'all': user request "Semua Periode" via `all_periods=1` atau `tahun_ajaran_id=0`
     *   - null : tidak ada periode aktif dan tidak ada filter eksplisit
     */
    private function resolveTahunAjaranFilter($user): int|string|null
    {
        // Eksplisit minta semua periode
        if (request()->boolean('all_periods')) {
            return 'all';
        }

        $tahunAjaranId = request('tahun_ajaran_id');

        // tahun_ajaran_id=0 juga diperlakukan sebagai "semua periode" (back-compat)
        if ($tahunAjaranId !== null && $tahunAjaranId !== '' && (int) $tahunAjaranId === 0) {
            return 'all';
        }

        if ($tahunAjaranId) {
            // Validate branch ownership
            $tahunAjaran = TahunAjaran::find($tahunAjaranId);
            if (! $tahunAjaran || $tahunAjaran->branch_id !== $user->branch_id) {
                throw new HttpResponseException(response([
                    'errors' => ['tahun_ajaran_id' => ['Tahun ajaran tidak ditemukan atau bukan milik branch Anda.']],
                ], 422));
            }

            return (int) $tahunAjaranId;
        }

        // Default to Periode_Aktif
        $periodeAktif = TahunAjaran::getAktif($user->branch_id);

        return $periodeAktif?->id;
    }
}
