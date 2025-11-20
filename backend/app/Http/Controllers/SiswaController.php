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
                    "message" => [
                        "jenjang tidak ditemukan."
                    ]
                ]
            ], 404)),
        };
    }
    protected function resolveUpdateRequest(string $jenjang)
    {
        return match (strtoupper($jenjang)) {
            'TK' => app(\App\Http\Requests\SiswaTKUpdateRequest::class),
            'MI' => app(\App\Http\Requests\SiswaMIUpdateRequest::class),
            'KB' => app(\App\Http\Requests\SiswaKBUpdateRequest::class),
            default => throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "jenjang tidak ditemukan."
                    ]
                ]
            ], 404)),
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
                    "message" => [
                        "jenjang tidak ditemukan."
                    ]
                ]
            ], 404)),
        };
    }

    public function index(string $jenjang)
    {
        $auth = Auth::user();
        $baseQuery = Siswa::with([
            'ayah',
            'ibu',
            'wali',
            'kelas',
            'kategori'
        ])->where('jenjang', strtoupper($jenjang));

        $query = clone $baseQuery;

        // search by nama | nis | nisn (khusus MI)
        $term = request('search', request('q'));
        if ($term) {
            $query->where(function ($q) use ($term, $jenjang) {
                $q->where('nama', 'like', "{$term}%")
                    ->orWhere('nis', 'like', "{$term}%");
                if (strtoupper($jenjang) === 'MI') {
                    $q->orWhere('nisn', 'like', "{$term}%");
                }
            });
        }

        $siswa = $query->paginate(request('per_page', 30));
        // jika ada data di jenjang tsb tetapi hasil search kosong,
        // kembalikan 200 dengan data kosong (paginator tetap)

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
                $q->where('nis', $data['nis']);
            })
            ->exists();

        if ($exists) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "siswa dengan NIS tersebut sudah terdaftar."
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
        $request = $this->resolveUpdateRequest($jenjang);
        $data = $request->validated();

        $siswa = Siswa::where('id', $id)->first();
        if (!$siswa || strtoupper($siswa->jenjang) !== strtoupper($jenjang)) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "siswa tidak ditemukan."
                    ]
                ]
            ], 404));
        }
        $siswa->jenjang = strtoupper($jenjang);
        $siswa->update($data);

        $resource = $this->resolveResource($jenjang);
        return (new $resource($siswa))->response()->setStatusCode(200);
    }

    public function get(string $jenjang, string $id)
    {
        $auth = Auth::user();

        $siswa = Siswa::where('jenjang', strtoupper($jenjang))->find($id);
        if (!$siswa) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "siswa tidak ditemukan."
                    ]
                ]
            ], 404));
        }
        $resource = $this->resolveResource($jenjang);
        return (new $resource($siswa))->response()->setStatusCode(200);
    }

    public function delete(string $jenjang, string $id)
    {
        $auth = Auth::user();
        $siswa = Siswa::where('id', $id)->where('jenjang', $jenjang)->first();

        if (!$siswa) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "siswa tidak ditemukan."
                    ]
                ]
            ], 404));
        }

        $user = User::where('username', $siswa->nis)->first();

        if ($user) {
            $user->delete();
        }

        $siswa->delete();

        return response([
            'data' => true
        ])->setStatusCode(200);
    }
}
