<?php

namespace App\Http\Controllers;

use App\Http\Requests\KategoriRequest;
use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\Exceptions\HttpResponseException;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::query()->get();
        // Return koleksi (bisa kosong) tanpa error
        return KategoriResource::collection($kategori);
    }

    public function create(KategoriRequest $request)
    {
        $data = $request->validated();
        $namaUp = strtoupper($data['nama']);
        $exists = Kategori::query()->whereRaw('UPPER(nama) = ?', [$namaUp])->exists();
        if ($exists) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['nama kategori sudah ada.']
                ]
            ], 400));
        }
        $kategori = new Kategori([
            'nama' => $namaUp
        ]);
        $kategori->save();
        return (new KategoriResource($kategori))->response()->setStatusCode(201);
    }

    public function get(string $id)
    {
        $kategori = Kategori::query()->find($id);
        if (!$kategori) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'kategori tidak ditemukan.'
                    ]
                ]
            ], 404));
        }
        return (new KategoriResource($kategori))->response()->setStatusCode(200);
    }

    public function update(KategoriRequest $request, string $id)
    {
        $data = $request->validated();
        $kategori = Kategori::query()->find($id);
        if (!$kategori) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'kategori tidak ditemukan.'
                    ]
                ]
            ], 404));
        }
        $namaUp = strtoupper($data['nama']);
        $duplicate = Kategori::query()
            ->whereRaw('UPPER(nama) = ?', [$namaUp])
            ->where('id', '<>', $kategori->id)
            ->exists();
        if ($duplicate) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['nama kategori sudah ada.']
                ]
            ], 400));
        }
        $kategori->nama = $namaUp;
        $kategori->save();
        return (new KategoriResource($kategori))->response()->setStatusCode(200);
    }

    public function delete(string $id)
    {
        $kategori = Kategori::query()->find($id);
        if (!$kategori) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'kategori tidak ditemukan.'
                    ]
                ]
            ], 404));
        }
        if ($kategori->siswa()->exists()) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'kategori digunakan pada data siswa.'
                    ]
                ]
            ], 400));
        }
        $kategori->delete();
        return response([
            'data' => true
        ], 200);
    }
}
