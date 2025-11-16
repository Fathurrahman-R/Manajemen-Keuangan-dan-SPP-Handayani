<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanSiswaResource extends JsonResource
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
            'jenjang'=>$this->jenjang,
            'kelas'=>$this->whenLoaded('kelas', new KelasResource($this->kelas)),
            'kategori'=>$this->whenLoaded('kategori', new KategoriResource($this->kategori)),
        ];
    }
}
