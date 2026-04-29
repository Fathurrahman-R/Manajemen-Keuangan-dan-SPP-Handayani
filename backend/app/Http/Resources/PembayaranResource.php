<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PembayaranResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pembayaran'=>$this->kode_pembayaran,
            'kode_tagihan'=>TagihanResource::make($this->whenLoaded('tagihan')),
            'tanggal'=>$this->tanggal,
            'metode'=>$this->metode,
            'jumlah'=>$this->jumlah,
            'pembayar'=>$this->pembayar,
            'branch_id'=>$this->branch_id
        ];
    }
}
