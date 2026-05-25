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
            'branch' => $this->whenLoaded('branch', fn() => [
                'id' => $this->branch->id,
                'location' => $this->branch->location,
            ]),
            'roles' => $this->whenLoaded('roles', fn() => $this->getRoleNames()->toArray()),
        ];
    }
}
