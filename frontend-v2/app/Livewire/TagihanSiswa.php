<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;
use Filament\Notifications\Notification;

class TagihanSiswa extends Component
{
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
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
            $this->tagihanData = [];
            $this->siblings = [];
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan yang tidak terduga. Silakan coba lagi atau hubungi support.')
                ->danger()
                ->persistent()
                ->send();
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

    protected function handleApiError($response): void
    {
        try {
            $json = $response->json();
            $errors = $json['errors'] ?? [];

            if (isset($errors['message'])) {
                $message = is_array($errors['message']) ? $errors['message'][0] : $errors['message'];
            } else {
                $firstKey = array_key_first($errors);
                $message = $firstKey ? (is_array($errors[$firstKey]) ? $errors[$firstKey][0] : $errors[$firstKey]) : 'Terjadi kesalahan.';
            }

            Notification::make()
                ->title($message)
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan yang tidak terduga. Silakan coba lagi atau hubungi support.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.tagihan-siswa');
    }
}
