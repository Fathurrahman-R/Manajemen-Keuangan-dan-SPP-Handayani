<?php

namespace App\Http\Resources;

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
        $branchId = $this->branch_id;
        if (! $branchId && \Illuminate\Support\Facades\Auth::check()) {
            $branchId = \Illuminate\Support\Facades\Auth::user()->branch_id;
        }

        $setting = \App\Models\AppSetting::where('branch_id', $branchId)->first();
        $settingData = $setting ? (new \App\Http\Resources\AppSettingResource($setting))->toArray($request) : null;

        return [
            'kode_pembayaran' => $this->kode_pembayaran,
            'setting' => $settingData,
            'tanggal' => $this->tanggal,
            'pembayar' => $this->pembayar,
            'jumlah' => $this->jumlah,
            'untuk' => GenerateKeteranganKwitansi::generate($this->kode_pembayaran),
            'sejumlah' => GenerateSejumlahKwitansi::generateFromPembayaran($this->kode_pembayaran),
        ];
    }
}
