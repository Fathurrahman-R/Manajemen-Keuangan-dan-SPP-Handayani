<?php

namespace App\Http\Controllers;

use App\Http\Requests\KelasRequest;
use App\Http\Resources\KelasResource;
use App\Models\Kelas;
use Illuminate\Http\Exceptions\HttpResponseException;
use Dedoc\Scramble\Attributes\HeaderParameter;

class KelasController extends Controller
{
    #[HeaderParameter('Authorization')]
    public function index(string $jenjang)
    {
        $jenjangUp = strtoupper($jenjang);
        if (!in_array($jenjangUp, ['MI', 'TK', 'KB'])) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['jenjang tidak valid.']
                ]
            ], 400));
        }
        $kelas = Kelas::where('jenjang', $jenjangUp)->get();
        return KelasResource::collection($kelas);
    }

    #[HeaderParameter('Authorization')]
    public function create(KelasRequest $request, string $jenjang)
    {
        $jenjangUp = strtoupper($jenjang);
        if (!in_array($jenjangUp, ['MI', 'TK', 'KB'])) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['jenjang tidak valid.']
                ]
            ], 400));
        }
        $data = $request->validated();
        $namaUp = strtoupper($data['nama']);
        $exists = Kelas::where('jenjang', $jenjangUp)
            ->whereRaw('UPPER(nama) = ?', [$namaUp])
            ->exists();
        if ($exists) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['nama kelas sudah ada.']
                ]
            ], 400));
        }
        $kelas = new Kelas([
            'jenjang' => $jenjangUp,
            'nama' => $namaUp,
        ]);
        $kelas->save();
        return (new KelasResource($kelas))->response()->setStatusCode(201);
    }

    #[HeaderParameter('Authorization')]
    public function update(KelasRequest $request, string $jenjang, string $id)
    {
        $jenjangUp = strtoupper($jenjang);
        if (!in_array($jenjangUp, ['MI', 'TK', 'KB'])) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['jenjang tidak valid.']
                ]
            ], 400));
        }
        $data = $request->validated();
        $kelas = Kelas::where('jenjang', $jenjangUp)->find($id);

        if (!$kelas) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'kelas tidak ditemukan.'
                    ]
                ]
            ], 404));
        }

        $namaUp = strtoupper($data['nama']);
        $duplicate = Kelas::where('jenjang', $jenjangUp)
            ->whereRaw('UPPER(nama) = ?', [$namaUp])
            ->where('id', '<>', $kelas->id)
            ->exists();
        if ($duplicate) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['nama kelas sudah ada.']
                ]
            ], 400));
        }
        $kelas->nama = $namaUp;
        $kelas->save();
        return (new KelasResource($kelas))->response()->setStatusCode(200);
    }

    #[HeaderParameter('Authorization')]
    public function get(string $jenjang, string $id)
    {
        $jenjangUp = strtoupper($jenjang);
        if (!in_array($jenjangUp, ['MI', 'TK', 'KB'])) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['jenjang tidak valid.']
                ]
            ], 400));
        }
        $kelas = Kelas::where('jenjang', $jenjangUp)->find($id);

        if (!$kelas) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'kelas tidak ditemukan.'
                    ]
                ]
            ], 404));
        }

        return (new KelasResource($kelas))->response()->setStatusCode(200);
    }

    public function delete(string $jenjang, string $id)
    {
        $jenjangUp = strtoupper($jenjang);
        if (!in_array($jenjangUp, ['MI', 'TK', 'KB'])) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['jenjang tidak valid.']
                ]
            ], 400));
        }
        $kelas = Kelas::where('jenjang', $jenjangUp)->find($id);

        if (!$kelas) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'kelas tidak ditemukan.'
                    ]
                ]
            ], 404));
        }

        if ($kelas->siswa()->exists()) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'kelas digunakan pada data siswa.'
                    ]
                ]
            ], 400));
        }

        $kelas->delete();
        return response([
            "data" => true
        ])->setStatusCode(200);
    }
}
