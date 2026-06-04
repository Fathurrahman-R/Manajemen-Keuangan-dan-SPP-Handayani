<?php

namespace App\Livewire;

use App\Services\ApiService;
use Exception;
use Livewire\Component;

class SiswaDashboard extends Component
{
    public array $dashboardData = [];
    public ?int $selectedSiswaId = null;
    public array $childOptions = [];
    public bool $loading = true;
    public ?string $error = null;

    public function mount(): void
    {
        $this->loadChildOptions();
        $this->loadData();
    }

    public function loadChildOptions(): void
    {
        // Child options will be derived from session data if available
        $roles = session()->get('data.roles', []);
        if (in_array('wali', $roles)) {
            // Wali may have multiple children; the API will handle defaults
            $this->childOptions = session()->get('data.children', []);
        }
    }

    public function loadData(): void
    {
        $this->loading = true;
        $this->error = null;

        $params = $this->selectedSiswaId
            ? ['siswa_id' => $this->selectedSiswaId]
            : [];

        try {
            $response = ApiService::client()->get('/dashboard/siswa', $params);

            if ($response->status() === 403) {
                $this->error = 'Anda tidak memiliki akses ke data ini.';
                $this->dashboardData = [];
            } else {
                $this->dashboardData = $response->json('data') ?? [];
            }
        } catch (Exception $e) {
            $this->error = 'Gagal memuat data dashboard. Silakan coba lagi.';
        }

        $this->loading = false;
    }

    public function updatedSelectedSiswaId(): void
    {
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.siswa-dashboard');
    }
}
