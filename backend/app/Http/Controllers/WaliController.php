<?php

namespace App\Http\Controllers;

use App\Http\Requests\WaliRequest;
use App\Http\Resources\WaliResource;
use App\Models\Siswa;
use App\Models\Wali;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WaliController extends Controller
{
    public function index()
    {
        $search = request('search');
        $perPage = (int) request('per_page', 30);

        $query = Wali::query()
            ->select(['id', 'nama', 'jenis_kelamin', 'agama', 'pendidikan_terakhir', 'pekerjaan', 'alamat', 'no_hp', 'keterangan'])
            ->when($search, function ($q) use ($search) {
                $q->where('nama', 'like', "%$search%");
            })
            ->orderBy('nama');

        $paginated = $query->paginate($perPage);

        return WaliResource::collection($paginated);
    }

    public function create(WaliRequest $request)
    {
        $data = $request->validated();

        $wali = new Wali($data);
        $wali->save();
        return (new WaliResource($wali))->response()->setStatusCode(201);
    }

    public function get(string $id)
    {
        $wali = Wali::query()->find($id);

        if (!$wali) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "wali tidak ditemukan."
                    ]
                ]
            ], 404));
        }
        return (new WaliResource($wali))->response()->setStatusCode(200);
    }

    public function update(WaliRequest $request, string $id)
    {
        $auth = Auth::user();
        $data = $request->validated();
        $wali = Wali::query()->find($id);
        if (!$wali) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "wali tidak ditemukan."
                    ]
                ]
            ], 404));
        }

        $wali->update($data);
        return (new WaliResource($wali))->response()->setStatusCode(200);
    }

    public function delete(string $id)
    {
        $wali = Wali::find($id);
        if (!$wali) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "wali tidak ditemukan."
                    ]
                ]
            ], 404));
        }
        if ($wali->siswa()->exists()) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "wali digunakan pada data siswa."
                    ]
                ]
            ], 400));
        }
        $wali->delete();
        return response([
            "data" => true
        ])->setStatusCode(200);
    }
}
