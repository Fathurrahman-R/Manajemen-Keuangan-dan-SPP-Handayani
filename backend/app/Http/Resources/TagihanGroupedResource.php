<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanGroupedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Returns siswa with nested tagihan array for the grouped endpoint.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nis' => $this->nis,
            'nama' => $this->nama,
            'jenjang' => $this->jenjang,
            'kelas' => new KelasResource($this->whenLoaded('kelas')),
            'tagihan' => TagihanItemResource::collection($this->whenLoaded('tagihan')),
        ];
    }
}
