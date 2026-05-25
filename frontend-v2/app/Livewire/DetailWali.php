<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;
use Exception;

class DetailWali extends Component
{
    public ?int $id;

    public function mount (int $id): void 
    {
        $this->id = $id;
    }

    public function render()
    {
        $response = ApiService::client()
            ->get('/wali/' . $this->id);

        if (!$response->ok()) {
            throw new Exception($response->json()['errors']['message'][0]);
        }

        $data = $response->json();
        
        return view('livewire.detail-wali', [
            'item' => $data['data']
        ]);
    }
}
