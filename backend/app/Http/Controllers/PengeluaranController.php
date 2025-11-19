<?php

namespace App\Http\Controllers;

use App\Http\Resources\PengeluaranResource;
use App\Http\Requests\PengeluaranRequest;
use App\Models\Pengeluaran;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
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

    public function create(PengeluaranRequest $request)
    {
        $data = $request->validated();

        $pengeluaran = Pengeluaran::create($data);

        return (new PengeluaranResource($pengeluaran))
            ->response()
            ->setStatusCode(201);
    }

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
