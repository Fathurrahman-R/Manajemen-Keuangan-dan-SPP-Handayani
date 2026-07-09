<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiswaDetailResource extends JsonResource
{
    /**
     * Transform the siswa resource into an array with full biodata
     * and conditional parent/guardian details by jenjang.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $jenjang = $this->jenjang;

        return [
            'siswa' => [
                'id' => $this->id,
                'nis' => $this->nis,
                'nisn' => $this->nisn,
                'nama' => $this->nama,
                'jenis_kelamin' => $this->jenis_kelamin,
                'tempat_lahir' => $this->tempat_lahir,
                'tanggal_lahir' => $this->tanggal_lahir,
                'agama' => $this->agama,
                'alamat' => $this->alamat,
                'jenjang' => $jenjang,
                'kelas' => $this->whenLoaded('kelas', fn () => [
                    'nama' => $this->kelas?->nama,
                    'level' => $this->kelas?->level,
                ]),
                'kategori' => $this->whenLoaded('kategori', fn () => [
                    'nama' => $this->kategori?->nama,
                ]),
                'asal_sekolah' => $this->asal_sekolah,
                'tahun_diterima' => $this->tahun_diterima,
                'kelas_diterima' => $this->kelas_diterima,
                'status' => $this->status,
                'keterangan' => $this->keterangan,
            ],
            'ayah' => $jenjang === 'MI' && $this->relationLoaded('ayah') && $this->ayah
                ? new AyahResource($this->ayah)
                : null,
            'ibu' => $jenjang === 'MI' && $this->relationLoaded('ibu') && $this->ibu
                ? new IbuResource($this->ibu)
                : null,
            'wali' => in_array($jenjang, ['TK', 'KB']) && $this->relationLoaded('wali') && $this->wali
                ? new WaliResource($this->wali)
                : null,
        ];
    }
}
