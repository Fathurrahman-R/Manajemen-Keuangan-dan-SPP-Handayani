<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;

class SiswaDashboard extends Component
{
    use HandlesApiErrors;

    public array $dashboardData = [];
    public ?int $selectedSiswaId = null;
    public array $childOptions = [];
    public bool $loading = true;

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

        $params = $this->selectedSiswaId
            ? ['siswa_id' => $this->selectedSiswaId]
            : [];

        try {
            $response = ApiService::client()->get('/dashboard/siswa', $params);

            if (!$response->ok()) {
                \Illuminate\Support\Facades\Log::error('SiswaDashboard API not ok: ' . $response->status() . ' - ' . $response->body());
                $this->handleApiError($response);
                $this->dashboardData = [];
                $this->loading = false;
                return;
            }

            $this->dashboardData = $response->json('data') ?? [];
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
            $this->dashboardData = [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SiswaDashboard error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->notifyUnexpectedError();
            $this->dashboardData = [];
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
