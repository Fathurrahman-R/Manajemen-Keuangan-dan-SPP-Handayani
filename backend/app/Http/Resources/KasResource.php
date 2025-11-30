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
            'tanggal' => $this->tanggal,
            'total_masuk' => isset($this->total_masuk) ? (float) $this->total_masuk : 0.0,
            'total_keluar' => isset($this->total_keluar) ? (float) $this->total_keluar : 0.0,
            'saldo' => isset($this->saldo) ? (float) $this->saldo : 0.0,
        ];
    }
}
