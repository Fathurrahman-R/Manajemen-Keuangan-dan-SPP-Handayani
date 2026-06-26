<?php

namespace App\Http\Controllers;

use App\Http\Resources\PengeluaranResource;
use App\Http\Requests\PengeluaranRequest;
use App\Models\Pengeluaran;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Support\Facades\Auth;

class PengeluaranController extends Controller
{
    use Traits\Sortable;

    #[HeaderParameter('Authorization')]
    #[QueryParameter('start_date', description: 'Tanggal mulai (YYYY-MM-DD)', required: false, example: '2025-11-01')]
    #[QueryParameter('end_date', description: 'Tanggal akhir (YYYY-MM-DD)', required: false, example: '2025-11-30')]
    #[QueryParameter('tahun_ajaran_id', description: 'Filter periode ajaran. Kirim nilai 0 untuk "semua periode".', required: false, example: 1)]
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 30)]
    #[QueryParameter('sort', description: 'Column to sort by (tanggal, jumlah, keterangan, created_at)', required: false, example: 'tanggal')]
    #[QueryParameter('direction', description: 'Sort direction (asc or desc)', required: false, example: 'desc')]
    public function index()
    {
        $user = Auth::user();

        $query = Pengeluaran::query()
            ->where('branch_id', $user->branch_id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        $start = request('start_date');
        $end = request('end_date');
        if ($start) {
            $query->whereDate('tanggal', '>=', $start);
        }
        if ($end) {
            $query->whereDate('tanggal', '<=', $end);
        }

        // Filter periode ajaran. all_periods=1 atau tahun_ajaran_id=0 = semua periode.
        $tahunAjaranId = request('tahun_ajaran_id');
        $allPeriods = request()->boolean('all_periods')
            || ($tahunAjaranId !== null && $tahunAjaranId !== '' && (int) $tahunAjaranId === 0);

        if ($allPeriods) {
            // Skip filter — tampilkan semua periode
        } elseif ($tahunAjaranId !== null && $tahunAjaranId !== '') {
            $query->where('tahun_ajaran_id', (int) $tahunAjaranId);
        } else {
            // Default: pakai periode aktif kalau tidak diberikan param sama sekali.
            $aktif = \App\Models\TahunAjaran::getAktif($user->branch_id);
            if ($aktif) {
                $query->where('tahun_ajaran_id', $aktif->id);
            }
        }

        $this->applySorting($query, ['tanggal', 'jumlah', 'keterangan', 'created_at'], 'tanggal', 'desc');

        $pengeluarans = $query->paginate(request('per_page', 30));

        return PengeluaranResource::collection($pengeluarans);
    }

    #[HeaderParameter('Authorization')]
    public function create(PengeluaranRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        $pengeluaran = new Pengeluaran($data);
        $pengeluaran->branch_id = $user->branch_id;

        // Auto-resolve tahun_ajaran_id kalau tidak dikirim:
        // pakai periode aktif kalau ada, kalau tidak biarkan null.
        if (empty($pengeluaran->tahun_ajaran_id)) {
            $aktif = \App\Models\TahunAjaran::getAktif($user->branch_id);
            $pengeluaran->tahun_ajaran_id = $aktif?->id;
        }

        $pengeluaran->save();

        return (new PengeluaranResource($pengeluaran))
            ->response()
            ->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function get(string $id)
    {
        $pengeluaran = Pengeluaran::find($id);

        if (!$pengeluaran) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'pengeluaran tidak ditemukan.',
                    ],
                ],
            ], 404));
        }

        return (new PengeluaranResource($pengeluaran))
            ->response()
            ->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function update(PengeluaranRequest $request, string $id)
    {
        $pengeluaran = Pengeluaran::find($id);

        if (!$pengeluaran) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'pengeluaran tidak ditemukan.',
                    ],
                ],
            ], 404));
        }

        $data = $request->validated();

        $pengeluaran->update($data);

        return (new PengeluaranResource($pengeluaran))
            ->response()
            ->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $id)
    {
        $pengeluaran = Pengeluaran::find($id);

        if (!$pengeluaran) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'pengeluaran tidak ditemukan.',
                    ],
                ],
            ], 404));
        }

        $pengeluaran->delete();

        return response([
            'data' => true,
        ])->setStatusCode(200);
    }
}
