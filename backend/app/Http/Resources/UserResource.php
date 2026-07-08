<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'is_active' => (bool) $this->is_active,
            'must_change_password' => (bool) $this->must_change_password,
            'branch' => $this->whenLoaded('branch', fn() => [
                'id' => $this->branch->id,
                'location' => $this->branch->location,
            ]),
            'roles' => $this->whenLoaded('roles', fn() => $this->getRoleNames()->toArray()),
            'siswa' => $this->whenLoaded('siswa', fn() => [
                'id' => $this->siswa->id,
                'ayah' => $this->siswa->ayah ? [
                    'nama' => $this->siswa->ayah->nama,
                    'email' => $this->siswa->ayah->email,
                ] : null,
                'ibu' => $this->siswa->ibu ? [
                    'nama' => $this->siswa->ibu->nama,
                    'email' => $this->siswa->ibu->email,
                ] : null,
                'wali' => $this->siswa->wali ? [
                    'nama' => $this->siswa->wali->nama,
                    'email' => $this->siswa->wali->email,
                ] : null,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
