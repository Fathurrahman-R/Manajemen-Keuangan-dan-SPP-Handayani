<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaliResource extends JsonResource
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
            'nama'=>$this->nama,
            'pekerjaan'=>$this->pekerjaan,
            'alamat'=>$this->alamat,
            'no_hp'=>$this->no_hp,
            'keterangan'=>$this->keterangan
        ];
    }
}
