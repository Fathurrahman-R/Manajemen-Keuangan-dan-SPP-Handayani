<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;

class TagihanSiswa extends Component
{
    use \App\Livewire\Concerns\HandlesApiErrors;
    public array $tagihanData = [];
    public array $siblings = [];
    public ?int $selectedSiswaId = null;
    public ?string $selectedSiswaName = null;
    public ?int $ownerSiswaId = null;
    public ?string $ownerSiswaName = null;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $params = [];

        if ($this->selectedSiswaId) {
            $params['siswa_id'] = $this->selectedSiswaId;
        }

        try {
            $response = ApiService::client()->get('/tagihan/siswa', $params);

            if ($response->ok()) {
                $json = $response->json();
                $data = $json['data'] ?? [];
                $this->tagihanData = $data['tagihan'] ?? [];
                $this->siblings = $data['siblings'] ?? [];
                $this->selectedSiswaId = $data['selected_siswa_id'] ?? null;
                $this->selectedSiswaName = $data['selected_siswa_nama'] ?? null;

                // Store owner info on first load (when no siswa_id param was sent)
                if ($this->ownerSiswaId === null) {
                    $this->ownerSiswaId = $this->selectedSiswaId;
                    $this->ownerSiswaName = $this->selectedSiswaName;
                }
            } else {
                $this->handleApiError($response);
                $this->tagihanData = [];
                $this->siblings = [];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->notifyConnectionError();
            $this->tagihanData = [];
            $this->siblings = [];
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
            $this->tagihanData = [];
            $this->siblings = [];
        }
    }

    public function updatedSelectedSiswaId(): void
    {
        $this->loadData();
    }

    public function hasSiblings(): bool
    {
        return count($this->siblings) > 0;
    }



    public function render()
    {
        return view('livewire.tagihan-siswa');
    }
}
