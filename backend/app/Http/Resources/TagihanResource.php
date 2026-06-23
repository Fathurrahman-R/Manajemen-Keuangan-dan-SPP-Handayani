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
            'branch_id' => $this->branch_id,
            'midtrans_pending' => $this->resolveMidtransPending(),
        ];
    }

    /**
     * Whether this Tagihan has an in-flight (non-expired) Midtrans
     * transaction in `pending` status. The portal uses this to hide the
     * "Bayar Online" button and show a "Menunggu Pembayaran" badge.
     */
    private function resolveMidtransPending(): bool
    {
        return \App\Models\MidtransTransaction::query()
            ->where('kode_tagihan', $this->kode_tagihan)
            ->where('status', 'pending')
            ->where('expired_at', '>', now())
            ->exists();
    }
}
