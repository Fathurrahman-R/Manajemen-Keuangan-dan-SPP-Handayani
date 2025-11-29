<?php

namespace App\Http\Controllers;

use App\Http\Resources\PengeluaranResource;
use App\Http\Requests\PengeluaranRequest;
use App\Models\Pengeluaran;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;

class PengeluaranController extends Controller
{
    #[HeaderParameter('Authorization')]
    #[QueryParameter('start_date', description: 'Tanggal mulai (YYYY-MM-DD)', required: false, example: '2025-11-01')]
    #[QueryParameter('end_date', description: 'Tanggal akhir (YYYY-MM-DD)', required: false, example: '2025-11-30')]
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 30)]
    public function index()
    {
        $query = Pengeluaran::query()->orderByDesc('tanggal')->orderByDesc('id');

        $start = request('start_date');
        $end = request('end_date');
        if ($start) {
            $query->whereDate('tanggal', '>=', $start);
        }
        if ($end) {
            $query->whereDate('tanggal', '<=', $end);
        }

        $pengeluarans = $query->paginate(request('per_page', 30));

        return PengeluaranResource::collection($pengeluarans);
    }

    #[HeaderParameter('Authorization')]
    public function create(PengeluaranRequest $request)
    {
        $data = $request->validated();

        $pengeluaran = Pengeluaran::create($data);

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
