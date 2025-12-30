<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengeluaranResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => Carbon::parse($this->tanggal)->locale('id')->translatedFormat('d F Y'),
            'uraian' => $this->uraian,
            'jumlah' => (float) $this->jumlah,
            'branch_id' => $this->branch_id
        ];
    }
}
