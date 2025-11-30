<?php

namespace App\Http\Resources;

use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiswaMIResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir,
            'agama' => $this->agama,
            'alamat' => $this->alamat,
            'ayah' => $this->whenLoaded('ayah', new AyahResource($this->ayah)),
            'ibu' => $this->whenLoaded('ibu', new IbuResource($this->ibu)),
            'jenjang' => $this->jenjang,
            'kelas' => $this->whenLoaded('kelas', new KelasResource($this->kelas)),
            'kategori' => $this->whenLoaded('kategori', new KategoriResource($this->kategori)),
            'asal_sekolah' => $this->asal_sekolah,
            'kelas_diterima' => $this->kelas_diterima,
            'tahun_diterima' => $this->tahun_diterima,
            'status' => $this->status,
            'keterangan' => $this->keterangan
        ];
    }
}
