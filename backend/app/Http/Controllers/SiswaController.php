<?php

namespace App\Http\Controllers;

use App\Http\Requests\SiswaRequest;
use App\Http\Resources\SiswaMIResource;
use App\Http\Resources\SiswaResource;
use App\Models\Ayah;
use App\Models\Ibu;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    #[HeaderParameter('Authorization')]
    #[QueryParameter('search')]
    #[QueryParameter('kelas_id')]
    public function index(string $jenjang)
    {
        $baseQuery = Siswa::with(['ayah','ibu','wali','kelas','kategori'])
            ->where('jenjang', strtoupper($jenjang));
        $query = clone $baseQuery;
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
        $kelasId = request('kelas_id');
        if (!is_null($kelasId) && $kelasId !== '') {
            $query->where('kelas_id', (int) $kelasId);
        }
        $siswa = $query->paginate(request('per_page', 30));
        return SiswaResource::collection($siswa);
    }

    #[HeaderParameter('Authorization')]
    public function create(SiswaRequest $request,string $jenjang)
    {
        $data = $request->validated();

        $ayahId = null; $ibuId = null; $waliId = null;

        $exists = Siswa::where('jenjang', strtoupper($jenjang))
            ->where('nis', $data['nis'])
            ->exists();
        if ($exists) {
            throw new HttpResponseException(response([
                "errors" => ["message" => ["siswa dengan NIS tersebut sudah terdaftar."]]
            ], 400));
        }

        // build related models from nested fields
        if(strtoupper($jenjang)!== 'MI')
        {
            $wali = new Wali([
                'nama' => $data['wali_nama'],
                'agama' => $data['wali_agama'],
                'jenis_kelamin' => $data['wali_jenis_kelamin'],
                'pendidikan_terakhir' => $data['wali_pendidikan_terakhir'],
                'pekerjaan' => $data['wali_pekerjaan'] ?? null,
                'alamat' => $data['wali_alamat'],
                'no_hp' => $data['wali_no_hp'],
                'keterangan' => $data['wali_keterangan'] ?? null,
            ]);
            $wali->save();
            $waliId = $wali->id;
        }

        if (strtoupper($jenjang) === 'MI') {
            $ayah = new Ayah([
                'nama' => $data['ayah_nama'],
                'pendidikan' => $data['ayah_pendidikan'] ?? null,
                'pekerjaan' => $data['ayah_pekerjaan'] ?? null,
            ]);
            $ayah->save();
            $ayahId = $ayah->id;
            $ibu = new Ibu([
                'nama' => $data['ibu_nama'],
                'pendidikan' => $data['ibu_pendidikan'] ?? null,
                'pekerjaan' => $data['ibu_pekerjaan'] ?? null,
            ]);
            $ibu->save();
            $ibuId = $ibu->id;
        }


        $siswa = new Siswa($data);
        $siswa->jenjang = strtoupper($jenjang);
        if ($ayahId) { $siswa->ayah_id = $ayahId; }
        if ($ibuId) { $siswa->ibu_id = $ibuId; }
        if ($waliId) { $siswa->wali_id = $waliId; }
        $siswa->save();
        $user = new User();
        $user->username = $data['nis'];
        $user->password = Hash::make($data['tanggal_lahir']);
        $user->save();
        $siswa->refresh();
        $siswa->load(['ayah','ibu','wali','kelas','kategori']);

        return (new SiswaResource($siswa))->response()->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function update(SiswaRequest $request, string $jenjang, string $id)
    {
        $data = $request->validated();
        $siswa = Siswa::where('id', $id)->first();
        if (!$siswa || strtoupper($siswa->jenjang) !== strtoupper($jenjang)) {
            throw new HttpResponseException(response([
                "errors" => ["message" => ["siswa tidak ditemukan."]]
            ], 404));
        }

        if (strtoupper($jenjang) === 'MI') {
            if ($siswa->ayah_id) {
                $ayah = Ayah::find($siswa->ayah_id);
                if ($ayah) {
                    $ayah->update([
                        'nama' => $data['ayah_nama'],
                        'pendidikan' => $data['ayah_pendidikan'] ?? null,
                        'pekerjaan' => $data['ayah_pekerjaan'] ?? null,
                    ]);
                }
            }
            if ($siswa->ibu_id) {
                $ibu = Ibu::find($siswa->ibu_id);
                if ($ibu) {
                    $ibu->update([
                        'nama' => $data['ibu_nama'],
                        'pendidikan' => $data['ibu_pendidikan'] ?? null,
                        'pekerjaan' => $data['ibu_pekerjaan'] ?? null,
                    ]);
                }
            }
        } else {
            // update related models
            if ($siswa->wali_id) {
                $wali = Wali::find($siswa->wali_id);
                if ($wali) {
                    $wali->update([
                        'nama' => $data['wali_nama'],
                        'pekerjaan' => $data['wali_pekerjaan'] ?? null,
                        'alamat' => $data['wali_alamat'],
                        'no_hp' => $data['wali_no_hp'],
                        'keterangan' => $data['wali_keterangan'] ?? null,
                    ]);
                }
            }
        }

        // update siswa main fields excluding nested inputs
        $updateFields = collect($data)->except([
            'wali_nama','wali_pekerjaan','wali_alamat','wali_no_hp','wali_keterangan',
            'ayah_nama','ayah_pendidikan','ayah_pekerjaan','ibu_nama','ibu_pendidikan','ibu_pekerjaan'
        ])->toArray();
        $siswa->update($updateFields);

        return (new SiswaResource($siswa->load(['ayah','ibu','wali','kelas','kategori'])))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function get(string $jenjang, string $id)
    {
        $siswa = Siswa::where('jenjang', strtoupper($jenjang))->find($id);
        if (!$siswa) {
            throw new HttpResponseException(response([
                "errors" => ["message" => ["siswa tidak ditemukan."]]
            ], 404));
        }
        return (new SiswaResource($siswa->load(['ayah','ibu','wali','kelas','kategori'])))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $jenjang, string $id)
    {
        $siswa = Siswa::where('id', $id)->where('jenjang', $jenjang)->first();
        if (!$siswa) {
            throw new HttpResponseException(response([
                "errors" => ["message" => ["siswa tidak ditemukan."]]
            ], 404));
        }
        $user = User::where('username', $siswa->nis)->first();
        if ($user) { $user->delete(); }

        // optionally delete related (cascade already on ayah/ibu, wali not cascade) -> keep wali for other siblings? retain.
        $siswa->delete();
        return response(['data' => true])->setStatusCode(200);
    }
}
