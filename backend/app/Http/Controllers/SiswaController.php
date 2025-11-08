<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiswaController extends Controller
{
    protected function resolveRequest(string $jenjang)
    {
        return match (strtoupper($jenjang)) {
            'TK' => app(\App\Http\Requests\SiswaTKRequest::class),
            'MI' => app(\App\Http\Requests\SiswaMIRequest::class),
            'KB' => app(\App\Http\Requests\SiswaKBRequest::class),
            default => abort(404, 'Jenjang tidak ditemukan'),
        };
    }

    protected function resolveResource(string $jenjang)
    {
        return match (strtoupper($jenjang)) {
            'TK' => \App\Http\Resources\SiswaTKResource::class,
            'MI' => \App\Http\Resources\SiswaMIResource::class,
            'KB' => \App\Http\Resources\SiswaKBResource::class,
            default => abort(404, 'Jenjang tidak ditemukan'),
        };
    }

    public function index(string $jenjang)
    {
        $auth = Auth::user();
        $siswa = Siswa::where('jenjang',strtoupper($jenjang))->get();

        $resource = $this->resolveRequest($jenjang);
        return $resource->collection($siswa);
    }

    public function create(string $jenjang)
    {
        $auth = Auth::user();
        $request = $this->resolveRequest($jenjang);
        $data = $request->validated();

        $siswa = new Siswa($data);
        $siswa->save();
        $resource = $this->resolveResource($jenjang);
        return (new $resource($siswa))->response()->setStatusCode(201);
    }
}
