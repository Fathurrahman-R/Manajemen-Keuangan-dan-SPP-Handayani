<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nama_sekolah'=>$this->nama_sekolah,
            'lokasi'=>$this->lokasi,
            'alamat'=>$this->alamat,
            'email'=>$this->email,
            'telepon'=>$this->telepon,
            'kepala_sekolah'=>$this->kepala_sekolah,
            'bendahara'=>$this->bendahara,
            'kode_pos'=>$this->kode_pos,
            'logo'=>$this->logo
        ];
    }
}
