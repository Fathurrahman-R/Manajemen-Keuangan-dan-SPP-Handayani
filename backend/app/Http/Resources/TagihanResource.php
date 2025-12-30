<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_tagihan' => $this->kode_tagihan,
            'jenis_tagihan' => JenisTagihanResource::make($this->whenLoaded('jenis_tagihan')),
            'siswa' => TagihanSiswaResource::make($this->whenLoaded('siswa')),
            'tmp'=>$this->tmp,
            'status' => $this->status,
            'branch_id' => $this->branch_id
        ];
    }
}
