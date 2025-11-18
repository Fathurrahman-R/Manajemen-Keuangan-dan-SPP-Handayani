<?php

namespace App\Http\Resources;

use App\Http\Controllers\AppSettingController;
use App\Services\GenerateKeteranganKwitansi;
use App\Services\GenerateSejumlahKwitansi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KwitansiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'setting'=>AppSettingController::get(),
            'tanggal'=>$this->tanggal,
            'pembayar'=>$this->pembayar,
            'jumlah'=>$this->jumlah,
            'untuk'=>GenerateKeteranganKwitansi::generate($this->kode_pembayaran),
            'sejumlah'=>GenerateSejumlahKwitansi::generateFromPembayaran($this->kode_pembayaran)
        ];
    }
}
