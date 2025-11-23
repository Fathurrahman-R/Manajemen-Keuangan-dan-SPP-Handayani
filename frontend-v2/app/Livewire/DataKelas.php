<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class DataKelas extends Component
{
    public $perPage = 10;
    public $search = '';
    public $activeTab = 'TK';

    public function render()
    {
        $response = Http::withHeaders([
            'Authorization' => session()->get('data')['token']
        ])
            ->get(env('API_URL') . '/kelas' . '/' . $this->activeTab);

        if (!$response->ok()) {
            throw new Exception($response->json()['errors']['message'][0]);
        }

        $data = $response->json();

        return view('livewire.data-kelas', [
            'items' => $data['data'],
            'activeTab' => $this->activeTab
        ]);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
}
