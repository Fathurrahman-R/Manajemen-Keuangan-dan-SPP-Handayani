<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PembayaranItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Used within PembayaranGroupedResource to represent individual pembayaran items.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pembayaran' => $this->kode_pembayaran,
            'tanggal' => $this->tanggal,
            'metode' => $this->metode,
            'jumlah' => $this->jumlah,
            'pembayar' => $this->pembayar,
            'kode_tagihan' => $this->kode_tagihan,
            'jenis_tagihan' => [
                'nama' => $this->jenis_tagihan_nama,
                'jumlah' => $this->jenis_tagihan_jumlah,
            ],
        ];
    }
}
