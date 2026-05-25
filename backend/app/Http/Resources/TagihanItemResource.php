<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Used within TagihanGroupedResource to represent individual tagihan items.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_tagihan' => $this->kode_tagihan,
            'jenis_tagihan' => [
                'id' => $this->jenis_tagihan->id ?? null,
                'nama' => $this->jenis_tagihan->nama ?? null,
                'jumlah' => $this->jenis_tagihan->jumlah ?? null,
                'jatuh_tempo' => $this->jenis_tagihan->jatuh_tempo ?? null,
            ],
            'tmp' => $this->tmp,
            'status' => $this->status,
        ];
    }
}
