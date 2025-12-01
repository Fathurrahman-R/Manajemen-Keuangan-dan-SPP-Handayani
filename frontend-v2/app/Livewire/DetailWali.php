<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
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
        $response = Http::withHeaders([
            'Authorization' => session()->get('data')['token']
        ])
            ->get(env('API_URL') . '/wali/' . $this->id);

        if (!$response->ok()) {
            throw new Exception($response->json()['errors']['message'][0]);
        }

        $data = $response->json();
        
        return view('livewire.detail-wali', [
            'item' => $data['data']
        ]);
    }
}
