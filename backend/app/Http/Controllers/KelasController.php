<?php

namespace App\Http\Controllers;

use App\Http\Requests\KelasRequest;
use App\Http\Resources\KelasResource;
use App\Models\Kelas;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KelasController extends Controller
{
    public function index(string $jenjang)
    {
        $auth = Auth::user();
        $kelas = Kelas::where('jenjang',strtoupper($jenjang))->get();

        if ($kelas->isEmpty())
        {
            throw new HttpResponseException(response()->json([
                'errors'=>[
                    'message'=>[
                        'belum ada data kelas.'
                    ]
                ]
            ],400));
        }

        return KelasResource::collection($kelas);
    }

    public function create(KelasRequest $request, string $jenjang)
    {
        $auth = Auth::user();
        $data = $request->validated();

        if (Kelas::query()->where('kelas.jenjang', $jenjang and 'kelas.nama', $data['nama'])->first())
        {
            throw new HttpResponseException(response()->json([
                'errors'=>[
                    'message'=>[
                        'nama kelas sudah ada.'
                    ]
                ]
            ],400));
        }

        $kelas = new Kelas($data);
        $kelas->jenjang = strtoupper($jenjang);
        $kelas->nama = strtoupper($data['nama']);
        $kelas->save();
        return (new KelasResource($kelas))->response()->setStatusCode(201);
    }

    public function update(KelasRequest $request, string $jenjang, string $id)
    {
        $auth = Auth::user();
        $data = $request->validated();
        $kelas = Kelas::query()->where('kelas.jenjang',strtoupper($jenjang))->find($id);

        if (!$kelas)
        {
            throw new HttpResponseException(response()->json([
                'errors'=>[
                    'message'=>[
                        'kelas tidak ditemukan.'
                    ]
                ]
            ],404));
        }

        if ($kelas->nama == strtoupper($data['nama']))
        {
            throw new HttpResponseException(response()->json([
                'errors'=>[
                    'message'=>[
                        'nama kelas sudah ada.'
                    ]
                ]
            ],400));
        }
        $data['nama'] = strtoupper($data['nama']);
        $kelas->update($data);
        return (new KelasResource($kelas))->response()->setStatusCode(200);
    }

    public function get(string $jenjang, string $id)
    {
        $auth = Auth::user();
        $kelas = Kelas::query()->where('kelas.jenjang', strtoupper($jenjang))->find($id);

        if (!$kelas)
        {
            throw new HttpResponseException(response()->json([
                'errors'=>[
                    'message'=>[
                        'kelas tidak ditemukan.'
                    ]
                ]
            ],404));
        }

        return (new KelasResource($kelas))->response()->setStatusCode(200);

    }

    public function delete(string $jenjang, string $id)
    {
        $auth = Auth::user();
        $kelas = Kelas::query()->where('kelas.jenjang', strtoupper($jenjang))->find($id);

        if (!$kelas)
        {
            throw new HttpResponseException(response()->json([
                'errors'=>[
                    'message'=>[
                        'kelas tidak ditemukan.'
                    ]
                ]
            ],404));
        }

        if($kelas->siswa()->exists())
        {
            throw new HttpResponseException(response([
                'errors'=>[
                    'message'=>[
                        'kelas digunakan pada data siswa.'
                    ]
                ]
            ],400));
        }

        $kelas->delete();
        return response([
            "data"=>[
                'message'=>true
            ]
        ])->setStatusCode(200);
    }
}
