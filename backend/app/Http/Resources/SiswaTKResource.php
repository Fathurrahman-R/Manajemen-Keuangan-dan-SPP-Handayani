<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiswaTKResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'nis'=>$this->nis,
            'nama'=>$this->nama,
            'jenis_kelamin'=>$this->jenis_kelamin,
            'tempat_lahir'=>$this->tempat_lahir,
            'tanggal_lahir'=>$this->tanggal_lahir,
            'agama'=>$this->agama,
            'alamat'=>$this->alamat,
            'wali_id'=>$this->whenLoaded('wali', new WaliMIResource($this->wali)),
            'jenjang'=>$this->jenjang,
            'kelas_id'=>$this->whenLoaded('kelas', new KelasResource($this->kelas)),
            'kategori_id'=>$this->whenLoaded('kategori', new KategoriResource($this->kategori)),
            'status'=>$this->status,
            'keterangan'=>$this->keterangan
        ];
    }
}
