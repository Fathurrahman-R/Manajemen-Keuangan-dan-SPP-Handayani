<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class DataCategory extends Component
{   
    public $perPage = 10;
    public $search = '';

    public function render()
    {
        try {
            $response = Http::
            withHeaders([
                // 'Authorization' => 'Bearer ' . session()->get('data')['token'] 
                'Authorization' => session()->get('data')['token'] 
            ])
            ->get(env('API_URL') . '/kategori');

            if (!$response->ok()) {
                throw new Exception($response->json()['errors']['message'][0]);
            }

            $data = $response->json();

            return view('livewire.data-category', [
                'items' => $data['data']
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
