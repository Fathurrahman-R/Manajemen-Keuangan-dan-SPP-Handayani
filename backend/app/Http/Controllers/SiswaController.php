<?php

namespace App\Http\Controllers;

use App\Http\Requests\SiswaRequest;
use App\Http\Resources\SiswaResource;
use App\Models\Ayah;
use App\Models\Ibu;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Services\AkunSiswaService;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiswaController extends Controller
{
    use Traits\Sortable;

    protected AkunSiswaService $akunSiswaService;

    public function __construct(AkunSiswaService $akunSiswaService)
    {
        $this->akunSiswaService = $akunSiswaService;
    }

    #[HeaderParameter('Authorization')]
    #[QueryParameter('search')]
    #[QueryParameter('kelas_id')]
    #[QueryParameter('jenis_kelamin')]
    #[QueryParameter('agama')]
    #[QueryParameter('sort', description: 'Column to sort by (nama, nis, kelas_id, created_at)', required: false, example: 'nama')]
    #[QueryParameter('direction', description: 'Sort direction (asc or desc)', required: false, example: 'asc')]
    public function index(string $jenjang)
    {
        $baseQuery = Siswa::with(['ayah', 'ibu', 'wali', 'kelas', 'kategori'])
            ->where('jenjang', strtoupper($jenjang))->where('siswas.branch_id', Auth::user()->branch_id);
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
        if (! is_null($kelasId) && $kelasId !== '') {
            $query->where('kelas_id', (int) $kelasId);
        }

        $jenisKelamin = request('jenis_kelamin');
        if (! is_null($jenisKelamin) && $jenisKelamin !== '') {
            $query->where('jenis_kelamin', $jenisKelamin);
        }

        $agama = request('agama');
        if (! is_null($agama) && $agama !== '') {
            $query->where('agama', $agama);
        }

        $status = request('status');
        if (! is_null($status) && $status !== '') {
            $query->where('status', $status);
        }

        $this->applySorting($query, ['nama', 'nis', 'kelas_id', 'created_at'], 'nama', 'asc');

        $siswa = $query->paginate(request('per_page', 30));

        return SiswaResource::collection($siswa);
    }

    #[HeaderParameter('Authorization')]
    public function create(SiswaRequest $request, string $jenjang)
    {
        $data = $request->validated();

        $ayahId = null;
        $ibuId = null;
        $waliId = null;

        $exists = Siswa::where('jenjang', strtoupper($jenjang))
            ->where('nis', $data['nis'])
            ->exists();
        if ($exists) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['siswa dengan NIS tersebut sudah terdaftar.']],
            ], 400));
        }

        // build related models from nested fields
        if (strtoupper($jenjang) !== 'MI') {
            if (! empty($data['wali_id'])) {
                // Use existing wali record
                $waliId = $data['wali_id'];
            } else {
                $wali = new Wali([
                    'nama' => $data['wali_nama'],
                    'pekerjaan' => $data['wali_pekerjaan'] ?? null,
                    'alamat' => $data['wali_alamat'],
                    'no_hp' => $data['wali_no_hp'],
                    'keterangan' => $data['wali_keterangan'] ?? null,
                    'email' => $data['wali_email'] ?? null,
                ]);
                $wali->save();
                $waliId = $wali->id;
            }
        }

        if (strtoupper($jenjang) === 'MI') {
            if (! empty($data['ayah_id'])) {
                // Use existing ayah record
                $ayahId = $data['ayah_id'];
            } else {
                $ayah = new Ayah([
                    'nama' => $data['ayah_nama'],
                    'pendidikan_terakhir' => strtoupper($data['ayah_pendidikan_terakhir']) ?? null,
                    'pekerjaan' => $data['ayah_pekerjaan'] ?? null,
                    'email' => $data['ayah_email'] ?? null,
                ]);
                $ayah->save();
                $ayahId = $ayah->id;
            }

            if (! empty($data['ibu_id'])) {
                // Use existing ibu record
                $ibuId = $data['ibu_id'];
            } else {
                $ibu = new Ibu([
                    'nama' => $data['ibu_nama'],
                    'pendidikan_terakhir' => strtoupper($data['ibu_pendidikan_terakhir']) ?? null,
                    'pekerjaan' => $data['ibu_pekerjaan'] ?? null,
                    'email' => $data['ibu_email'] ?? null,
                ]);
                $ibu->save();
                $ibuId = $ibu->id;
            }
        }

        $siswa = new Siswa($data);
        $siswa->jenjang = strtoupper($jenjang);
        $siswa->branch_id = Auth::user()->branch_id;
        if ($ayahId) {
            $siswa->ayah_id = $ayahId;
        }
        if ($ibuId) {
            $siswa->ibu_id = $ibuId;
        }
        if ($waliId) {
            $siswa->wali_id = $waliId;
        }
        $siswa->save();

        // Sync SiswaKelas for Periode_Aktif when kelas_id is provided
        if (! empty($data['kelas_id'])) {
            $this->syncSiswaKelas($siswa);
        }

        // Create akun siswa via service (non-blocking: if it fails, siswa is still created)
        try {
            $this->akunSiswaService->createAccount($siswa);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat akun siswa untuk NIS '.$siswa->nis.': '.$e->getMessage());
        }

        $siswa->refresh();
        $siswa->load(['ayah', 'ibu', 'wali', 'kelas', 'kategori']);

        return (new SiswaResource($siswa))->response()->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function update(SiswaRequest $request, string $jenjang, string $id)
    {
        $data = $request->validated();
        $siswa = Siswa::where('id', $id)->where('branch_id', Auth::user()->branch_id)->first();
        if (! $siswa || strtoupper($siswa->jenjang) !== strtoupper($jenjang)) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['siswa tidak ditemukan.']],
            ], 404));
        }

        if (strtoupper($jenjang) === 'MI') {
            if ($siswa->ayah_id) {
                $ayah = Ayah::find($siswa->ayah_id);
                if ($ayah) {
                    $ayah->update([
                        'nama' => $data['ayah_nama'],
                        'pendidikan_terakhir' => strtoupper($data['ayah_pendidikan_terakhir']) ?? null,
                        'pekerjaan' => $data['ayah_pekerjaan'] ?? null,
                        'email' => $data['ayah_email'] ?? null,
                    ]);
                }
            }
            if ($siswa->ibu_id) {
                $ibu = Ibu::find($siswa->ibu_id);
                if ($ibu) {
                    $ibu->update([
                        'nama' => $data['ibu_nama'],
                        'pendidikan_terakhir' => strtoupper($data['ibu_pendidikan_terakhir']) ?? null,
                        'pekerjaan' => $data['ibu_pekerjaan'] ?? null,
                        'email' => $data['ibu_email'] ?? null,
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
                        //                        'agama' => $data['wali_agama'],
                        //                        'jenis_kelamin' => $data['wali_jenis_kelamin'],
                        //                        'pendidikan_terakhir' => strtoupper($data['wali_pendidikan_terakhir']),
                        'pekerjaan' => $data['wali_pekerjaan'] ?? null,
                        'alamat' => $data['wali_alamat'],
                        'no_hp' => $data['wali_no_hp'],
                        'keterangan' => $data['wali_keterangan'] ?? null,
                        'email' => $data['wali_email'] ?? null,
                    ]);
                }
            }
        }

        // update siswa main fields excluding nested inputs
        $updateFields = collect($data)->except([
            'wali_nama', 'wali_pekerjaan', 'wali_alamat', 'wali_no_hp', 'wali_keterangan', 'wali_email',
            'ayah_nama', 'ayah_pendidikan', 'ayah_pekerjaan', 'ayah_email',
            'ibu_nama', 'ibu_pendidikan', 'ibu_pekerjaan', 'ibu_email',
        ])->toArray();
        $siswa->update($updateFields);

        // Sync SiswaKelas for Periode_Aktif when kelas_id is provided
        if (isset($data['kelas_id'])) {
            $this->syncSiswaKelas($siswa);
        }

        return (new SiswaResource($siswa->load(['ayah', 'ibu', 'wali', 'kelas', 'kategori'])))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function get(string $jenjang, string $id)
    {
        $siswa = Siswa::where('jenjang', strtoupper($jenjang))->where('branch_id', Auth::user()->branch_id)->find($id);
        if (! $siswa) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['siswa tidak ditemukan.']],
            ], 404));
        }

        return (new SiswaResource($siswa->load(['ayah', 'ibu', 'wali', 'kelas', 'kategori'])))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function delete(string $jenjang, string $id)
    {
        $siswa = Siswa::where('id', $id)->where('jenjang', $jenjang)->where('branch_id', Auth::user()->branch_id)->first();
        if (! $siswa) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['siswa tidak ditemukan.']],
            ], 404));
        }

        // Capture related IDs before deleting siswa
        $jenjangSiswa = strtoupper($siswa->jenjang);
        $ayahId = $siswa->ayah_id;
        $ibuId = $siswa->ibu_id;
        $waliId = $siswa->wali_id;
        $nis = $siswa->nis;

        // Delete linked user account if exists
        $user = User::where('username', $nis)->first();
        if ($user) {
            $user->delete();
        }

        // Delete siswa first to avoid FK constraints
        $siswa->delete();

        // Delete related records based on jenjang
        if ($jenjangSiswa === 'MI') {
            if ($ayahId) {
                Ayah::where('id', $ayahId)->delete();
            }
            if ($ibuId) {
                Ibu::where('id', $ibuId)->delete();
            }
        } elseif (in_array($jenjangSiswa, ['TK', 'KB'])) {
            if ($waliId) {
                Wali::where('id', $waliId)->delete();
            }
        }

        return response(['data' => true])->setStatusCode(200);
    }

    /**
     * Sync SiswaKelas record for the Periode_Aktif when kelas_id is set.
     * Also keeps siswas.kelas_id in sync.
     */
    private function syncSiswaKelas(Siswa $siswa): void
    {
        $user = Auth::user();
        $periodeAktif = TahunAjaran::getAktif($user->branch_id);

        if (! $periodeAktif) {
            // No active period — skip silently for backward compatibility
            // (kelas_id is already set on siswas table directly)
            return;
        }

        DB::transaction(function () use ($siswa, $periodeAktif) {
            SiswaKelas::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $periodeAktif->id,
                ],
                [
                    'kelas_id' => $siswa->kelas_id,
                ]
            );
        });
    }
}
