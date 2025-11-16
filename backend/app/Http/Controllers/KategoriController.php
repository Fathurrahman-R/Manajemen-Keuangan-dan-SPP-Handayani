<?php

namespace App\Http\Controllers;

use App\Http\Requests\KategoriRequest;
use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KategoriController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        $kategori = Kategori::all();
        if($kategori->isEmpty())
        {
            throw new HttpResponseException(response([
                'errors'=>[
                    'message'=>[
                        'belum ada data kategori.'
                    ]
                ]
            ],404));
        }
        return KategoriResource::collection($kategori);
    }

    public function create(KategoriRequest $request)
    {
        $auth = Auth::user();
        $data = $request->validated();
        $kategori = new Kategori($data);
        $kategori->save();
        return (new KategoriResource($kategori))->response()->setStatusCode(201);
    }

    public function get(string $id)
    {
        $auth = Auth::user();
        $kategori = Kategori::query()->find($id);
        if(!$kategori)
        {
            throw new HttpResponseException(response([
                'errors'=>[
                    'message'=>[
                        'kategori tidak ditemukan.'
                    ]
                ]
            ],404));
        }
        return (new KategoriResource($kategori))->response()->setStatusCode(200);
    }

    public function update(KategoriRequest $request, string $id)
    {
        $auth = Auth::user();
        $data = $request->validated();
        $kategori = Kategori::query()->find($id);
        if(!$kategori)
        {
            throw new HttpResponseException(response([
                'errors'=>[
                    'message'=>[
                        'kategori tidak ditemukan.'
                    ]
                ]
            ],404));
        }
        $kategori->update($data);
        return (new KategoriResource($kategori))->response()->setStatusCode(200);
    }

    public function delete(string $id)
    {
        $auth = Auth::user();
        $kategori = Kategori::query()->find($id);
        if(!$kategori)
        {
            throw new HttpResponseException(response([
                'errors'=>[
                    'message'=>[
                        'kategori tidak ditemukan.'
                    ]
                ]
            ],404));
        }
        if($kategori->siswa()->exists())
        {
            throw new HttpResponseException(response([
                'errors'=>[
                    'message'=>[
                        'kategori digunakan pada data siswa.'
                    ]
                ]
            ],400));
        }
        $kategori->delete();
        return response([
            'data'=>true
        ], 200);
    }
}
