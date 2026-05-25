<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;

class BulkAkunSiswa extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;

    public string $filterJenjang = '';
    public ?int $filterKelasId = null;
    public array $kelasList = [];
    public array $siswaList = [];
    public array $selectedSiswaIds = [];
    public bool $selectAll = false;
    public bool $processing = false;
    public bool $showSummaryModal = false;
    public int $createdCount = 0;
    public array $errors = [];

    // Pagination
    public int $currentPage = 1;
    public int $lastPage = 1;
    public int $total = 0;

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('manage-akun-siswa', $permissions)) {
            abort(403);
        }

        $this->loadUnregisteredSiswa();
    }

    /**
     * Load unregistered siswa from the API with current filters.
     */
    public function loadUnregisteredSiswa(): void
    {
        try {
            $params = ['page' => $this->currentPage, 'per_page' => 15];

            if ($this->filterJenjang) {
                $params['jenjang'] = $this->filterJenjang;
            }

            if ($this->filterKelasId) {
                $params['kelas_id'] = $this->filterKelasId;
            }

            $response = ApiService::client()->get('/akun-siswa/unregistered', $params);

            if ($response->ok()) {
                $data = $response->json();
                $this->siswaList = $data['data'] ?? [];
                $this->currentPage = $data['current_page'] ?? 1;
                $this->lastPage = $data['last_page'] ?? 1;
                $this->total = $data['total'] ?? 0;
            } else {
                $this->siswaList = [];
                $this->total = 0;
                $this->handleApiError($response);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->siswaList = [];
            $this->total = 0;
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            $this->siswaList = [];
            $this->total = 0;
            Notification::make()
                ->title('Terjadi kesalahan saat memuat data siswa.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Load kelas list for the selected jenjang filter.
     */
    public function loadKelasList(): void
    {
        if (!$this->filterJenjang) {
            $this->kelasList = [];
            return;
        }

        try {
            $response = ApiService::client()->get("/kelas/{$this->filterJenjang}");

            if ($response->ok()) {
                $this->kelasList = $response->json()['data'] ?? [];
            } else {
                $this->kelasList = [];
            }
        } catch (\Throwable $e) {
            $this->kelasList = [];
        }
    }

    /**
     * Handle jenjang filter change.
     */
    public function updatedFilterJenjang(): void
    {
        $this->filterKelasId = null;
        $this->currentPage = 1;
        $this->selectedSiswaIds = [];
        $this->selectAll = false;
        $this->loadKelasList();
        $this->loadUnregisteredSiswa();
    }

    /**
     * Handle kelas filter change.
     */
    public function updatedFilterKelasId(): void
    {
        $this->currentPage = 1;
        $this->selectedSiswaIds = [];
        $this->selectAll = false;
        $this->loadUnregisteredSiswa();
    }

    /**
     * Toggle select all visible siswa.
     */
    public function toggleSelectAll(): void
    {
        $this->selectAll = !$this->selectAll;

        if ($this->selectAll) {
            $this->selectedSiswaIds = collect($this->siswaList)->pluck('id')->map(fn($id) => (int) $id)->toArray();
        } else {
            $this->selectedSiswaIds = [];
        }
    }

    /**
     * Toggle selection of a single siswa.
     */
    public function toggleSiswa(int $siswaId): void
    {
        if (in_array($siswaId, $this->selectedSiswaIds)) {
            $this->selectedSiswaIds = array_values(array_filter(
                $this->selectedSiswaIds,
                fn($id) => $id !== $siswaId
            ));
        } else {
            $this->selectedSiswaIds[] = $siswaId;
        }

        // Update selectAll state
        $allIds = collect($this->siswaList)->pluck('id')->map(fn($id) => (int) $id)->toArray();
        $this->selectAll = !empty($allIds) && empty(array_diff($allIds, $this->selectedSiswaIds));
    }

    /**
     * Execute bulk account creation for selected siswa.
     */
    public function buatAkun(): void
    {
        if (empty($this->selectedSiswaIds)) {
            Notification::make()
                ->title('Pilih minimal satu siswa untuk membuat akun.')
                ->warning()
                ->send();
            return;
        }

        $this->processing = true;

        try {
            $response = ApiService::client()->post('/akun-siswa/bulk', [
                'siswa_ids' => $this->selectedSiswaIds,
            ]);

            if ($response->ok()) {
                $data = $response->json()['data'] ?? [];
                $this->createdCount = $data['created'] ?? 0;
                $this->errors = $data['errors'] ?? [];
                $this->showSummaryModal = true;

                // Reset selection and reload data
                $this->selectedSiswaIds = [];
                $this->selectAll = false;
                $this->loadUnregisteredSiswa();
            } else {
                $this->handleApiError($response);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan saat membuat akun.')
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->processing = false;
        }
    }

    /**
     * Close the summary modal.
     */
    public function closeSummaryModal(): void
    {
        $this->showSummaryModal = false;
        $this->createdCount = 0;
        $this->errors = [];
    }

    /**
     * Navigate to a specific page.
     */
    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
        $this->selectedSiswaIds = [];
        $this->selectAll = false;
        $this->loadUnregisteredSiswa();
    }

    /**
     * Handle API error responses with Filament notification.
     */
    protected function handleApiError($response): void
    {
        try {
            $json = $response->json();
            $errors = $json['errors'] ?? [];

            if (isset($errors['message'])) {
                $message = is_array($errors['message']) ? $errors['message'][0] : $errors['message'];
            } else {
                $firstKey = array_key_first($errors);
                $message = $firstKey
                    ? (is_array($errors[$firstKey]) ? $errors[$firstKey][0] : $errors[$firstKey])
                    : 'Terjadi kesalahan.';
            }

            Notification::make()
                ->title($message)
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan yang tidak terduga.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.bulk-akun-siswa');
    }
}
