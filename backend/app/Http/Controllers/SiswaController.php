<?php

namespace App\Http\Controllers;

use App\Http\Resources\SiswaMIResource;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    protected function resolveRequest(string $jenjang)
    {
        return match (strtoupper($jenjang)) {
            'TK' => app(\App\Http\Requests\SiswaTKRequest::class),
            'MI' => app(\App\Http\Requests\SiswaMIRequest::class),
            'KB' => app(\App\Http\Requests\SiswaKBRequest::class),
            default => throw new HttpResponseException(response([
                "errors" => [
                    "message"=>[
                        "jenjang tidak ditemukan."
                    ]
                ]
            ],404)),
        };
    }

    protected function resolveResource(string $jenjang)
    {
        return match (strtoupper($jenjang)) {
            'TK' => \App\Http\Resources\SiswaTKResource::class,
            'MI' => \App\Http\Resources\SiswaMIResource::class,
            'KB' => \App\Http\Resources\SiswaKBResource::class,
            default => throw new HttpResponseException(response([
                "errors" => [
                    "message"=>[
                        "jenjang tidak ditemukan."
                    ]
                ]
            ],404)),
        };
    }

    public function index(string $jenjang)
    {
        $auth = Auth::user();
        $siswa = Siswa::with([
            'ayah',
            'ibu',
            'wali',
            'kelas',
            'kategori'
        ])->where('jenjang',strtoupper($jenjang))->get();

        if($siswa->isEmpty())
        {
            throw new HttpResponseException(response([
                "errors" => [
                    "message"=>[
                        "belum ada data siswa dengan jenjang tersebut."
                    ]
                ]
            ],404));
        }

        $resource = $this->resolveResource($jenjang);
        return $resource::collection($siswa);
    }

    public function create(string $jenjang)
    {
        $auth = Auth::user();
        $request = $this->resolveRequest($jenjang);
        $data = $request->validated();

        // cek nis terdaftar
        $exists = Siswa::where('jenjang', strtoupper($jenjang))
            ->where(function ($q) use ($data) {
                $q->where('nis', $data['nis'])
                    ->orWhere('nisn', $data['nisn']);
            })
            ->exists();

        if ($exists) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "Siswa dengan NIS/NISN tersebut sudah terdaftar."
                    ]
                ]
            ], 400));
        }

        $user = new User();
        $user->username = $data['nis'];
        $user->password = Hash::make($data['tanggal_lahir']);
        $user->save();
        $siswa = new Siswa($data);
        $siswa->jenjang = strtoupper($jenjang);
        $siswa->save();
        $siswa->refresh();
        $siswa->load(['ayah', 'ibu', 'wali', 'kelas', 'kategori']);
        $resource = $this->resolveResource($jenjang);
        return (new $resource($siswa))->response()->setStatusCode(201);
    }

    public function update(string $jenjang, string $id)
    {
        $auth = Auth::user();
        $request = $this->resolveRequest($jenjang);
        $data = $request->validated();

        $siswa = Siswa::where('id',$id)->first();
        $siswa->update($data);

        $resource = $this->resolveResource($jenjang);
        return (new $resource($siswa))->response()->setStatusCode(200);
    }

    public function get(string $jenjang, string $id)
    {
        $auth = Auth::user();

        $siswa = Siswa::where('id',$id)->where('jenjang', $jenjang)->first();
        $resource = $this->resolveResource($jenjang);
        return (new $resource($siswa))->response()->setStatusCode(200);
    }

    public function delete(string $jenjang, string $id)
    {
        $auth = Auth::user();
        $siswa = Siswa::where('id',$id)->where('jenjang', $jenjang)->first();
        $user = User::where('username',$siswa->nis)->first();
        $siswa->delete();
        $user->delete();
        return response([
            'data'=>true
        ])->setStatusCode(200);
    }
}
