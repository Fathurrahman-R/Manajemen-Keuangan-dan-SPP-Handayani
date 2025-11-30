<?php

namespace App\Http\Controllers;

use App\Http\Requests\JenisTagihanRequest;
use App\Http\Resources\JenisTagihanResource;
use App\Models\JenisTagihan;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\QueryException;
use Throwable;
use Dedoc\Scramble\Attributes\HeaderParameter;

class JenisTagihanController extends Controller
{
    #[HeaderParameter('Authorization')]
    public function index()
    {
        $jt = JenisTagihan::query()->get();
        // Kembalikan ke bentuk asli: langsung koleksi resource
        return JenisTagihanResource::collection($jt);
    }

    #[HeaderParameter('Authorization')]
    public function create(JenisTagihanRequest $request)
    {
        $data = $request->validated();
        try {
            $jt = JenisTagihan::query()->create($data);
        } catch (QueryException|Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['gagal menyimpan jenis tagihan.']
                ]
            ], 500));
        }
        // Bentuk asli: Resource response 201
        return (new JenisTagihanResource($jt))->response()->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function get(string $id)
    {
        $jt = JenisTagihan::query()->find($id);
        if (!$jt) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan tidak ditemukan.']
                ]
            ], 404));
        }
        // Bentuk asli: Resource response 200
        return (new JenisTagihanResource($jt))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function update(JenisTagihanRequest $request, string $id)
    {
        $jt = JenisTagihan::query()->find($id);
        if (!$jt) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan tidak ditemukan.']
                ]
            ], 404));
        }
        $data = $request->validated();
        try {
            $jt->update($data);
        } catch (QueryException|Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['gagal update jenis tagihan.']
                ]
            ], 500));
        }
        // Bentuk asli: Resource response 200
        return (new JenisTagihanResource($jt))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $id)
    {
        $jt = JenisTagihan::query()->find($id);
        if (!$jt) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan tidak ditemukan.']
                ]
            ], 404));
        }
        try {
            $jt->delete();
        } catch (QueryException|Throwable $e) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['jenis tagihan digunakan dan tidak dapat dihapus.']
                ]
            ], 409));
        }
        // Bentuk asli: data true status 200
        return response(['data' => true])->setStatusCode(200);
    }
}
