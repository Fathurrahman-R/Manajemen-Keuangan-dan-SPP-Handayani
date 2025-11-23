<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class DataWali extends Component
{
    public $perPage = 10;
    public $search = '';

    public function render()
    {
        $response = Http::withHeaders([
                'Authorization' => session()->get('data')['token']
            ])
            ->get(env('API_URL') . '/wali');

        if (!$response->ok()) {
            throw new Exception($response->json()['errors']['message'][0]);
        }

        $data = $response->json();

        return view('livewire.data-wali', [
            'items' => $data['data']
        ]);
    }
}
