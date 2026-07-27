<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class SiswaDashboard extends Component
{
    use HandlesApiErrors;

    public array $dashboardData = [];

    public bool $loading = true;

    public function placeholder(): View
    {
        return view('components.global-loading-spinner', ['static' => true, 'message' => 'Memuat dashboard...']);
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loading = true;

        $params = [];

        try {
            $response = ApiService::client()->get('/dashboard/siswa', $params);

            if (! $response->ok()) {
                \Illuminate\Support\Facades\Log::error('SiswaDashboard API not ok: '.$response->status().' - '.$response->body());
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
            \Illuminate\Support\Facades\Log::error('SiswaDashboard error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->notifyUnexpectedError();
            $this->dashboardData = [];
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.siswa-dashboard');
    }
}
