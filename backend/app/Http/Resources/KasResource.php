<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tanggal'=>$this->tanggal,
            'total_masuk'=>$this->total_masuk,
            'total_keluar'=>$this->total_keluar,
            'saldo'=>$this->saldo
        ];
    }
}
