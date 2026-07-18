<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PembayaranCardView extends Component implements HasActions, HasSchemas
{
    use \App\Livewire\Concerns\HandlesApiErrors;
    use \App\Livewire\Concerns\HasPeriodFilter;
    use InteractsWithActions, InteractsWithSchemas;

    public string $search = '';

    public string $filterJenjang = '';

    public string $filterKelas = '';

    public string $filterMetode = '';

    public string $sort = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public bool $loading = true;

    public array $siswaData = [];

    public array $meta = [];

    public array $kelasOptions = [];

    public function mount(): void
    {
        $this->mountHasPeriodFilter();
        $this->loadKelasOptions();
        $this->loadData();
    }

    public function loadKelasOptions(): void
    {
        try {
            // Kalau jenjang dipilih, fetch kelas hanya untuk jenjang itu.
            $endpoint = filled($this->filterJenjang) ? '/kelas/'.$this->filterJenjang : '/kelas';
            $response = ApiService::client()->get($endpoint);
            if ($response->ok()) {
                $this->kelasOptions = collect($response->json('data') ?? [])
                    ->mapWithKeys(function ($k) {
                        $label = $k['nama'];
                        // Kalau endpoint global (lintas jenjang), tampilkan jenjang dalam label.
                        if (filled($this->filterJenjang) === false && ! empty($k['jenjang'])) {
                            $label .= ' ('.$k['jenjang'].')';
                        }

                        return [$k['id'] => $label];
                    })
                    ->toArray();
            } else {
                $this->kelasOptions = [];
            }
        } catch (\Throwable $e) {
            $this->kelasOptions = [];
        }
    }

    public function loadData(): void
    {
        $this->loading = true;

        $params = [
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];

        if (filled($this->search)) {
            $params['search'] = $this->search;
        }

        if (filled($this->filterJenjang)) {
            $params['jenjang'] = $this->filterJenjang;
        }

        if (filled($this->filterKelas)) {
            $params['kelas_id'] = $this->filterKelas;
        }

        if (filled($this->filterMetode)) {
            $params['metode'] = $this->filterMetode;
        }

        if (filled($this->sort)) {
            $params['sort'] = $this->sort;
        }

        if ($this->selectedTahunAjaranId) {
            $params['tahun_ajaran_id'] = $this->selectedTahunAjaranId;
        } else {
            $params['all_periods'] = 1;
        }

        try {
            $response = ApiService::client()->get('/pembayaran/grouped', $params);

            if ($response->ok()) {
                $json = $response->json();
                $this->siswaData = $json['data'] ?? [];
                $this->meta = $json['meta'] ?? [];
            } else {
                $this->handleApiError($response);
                $this->siswaData = [];
                $this->meta = [];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->notifyConnectionError();
            $this->siswaData = [];
            $this->meta = [];
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
            $this->siswaData = [];
            $this->meta = [];
        }

        $this->loading = false;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadData();
    }

    public function updatedFilterJenjang(): void
    {
        $this->page = 1;
        $this->filterKelas = '';
        $this->loadKelasOptions();
        $this->loadData();
    }

    public function updatedFilterKelas(): void
    {
        $this->page = 1;
        $this->loadData();
    }

    public function updatedFilterMetode(): void
    {
        $this->page = 1;
        $this->loadData();
    }

    public function updatedSort(): void
    {
        $this->page = 1;
        $this->loadData();
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
        $this->loadData();
    }

    public function goToPage(int $page): void
    {
        $this->page = $page;
        $this->loadData();
        $this->dispatch('scroll-to-top');
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadData();
            $this->dispatch('scroll-to-top');
        }
    }

    public function nextPage(): void
    {
        if ($this->page < ($this->meta['last_page'] ?? 1)) {
            $this->page++;
            $this->loadData();
            $this->dispatch('scroll-to-top');
        }
    }

    public function canDelete(): bool
    {
        return PermissionHelper::hasResource('pembayaran.delete');
    }

    public function deletePembayaran(string $kodePembayaran): void
    {
        $this->deletingKodePembayaran = $kodePembayaran;
        $this->mountAction('deletePembayaran');
    }

    public ?string $deletingKodePembayaran = null;

    public function deletePembayaranAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('deletePembayaran')
            ->visible(fn (): bool => PermissionHelper::hasResource('pembayaran.delete'))
            ->requiresConfirmation()
            ->modalHeading('Hapus Pembayaran')
            ->modalDescription('Apakah kamu yakin ingin menghapus pembayaran ini? Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Hapus')
            ->color('danger')
            ->action(function (): void {
                if (! $this->deletingKodePembayaran) {
                    return;
                }

                try {
                    $response = ApiService::client()->delete('/pembayaran/'.$this->deletingKodePembayaran);

                    if ($response->ok()) {
                        Notification::make()->title('Pembayaran Berhasil Dihapus')->success()->send();
                        $this->loadData();
                    } else {
                        $this->handleApiError($response);
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $this->notifyConnectionError();
                }

                $this->deletingKodePembayaran = null;
            });
    }

    public function downloadKwitansi(string $kodePembayaran): StreamedResponse
    {
        $filename = 'kwitansi-'.$kodePembayaran.'.pdf';

        $response = ApiService::client()
            ->withHeaders(['Accept' => 'application/pdf'])
            ->get('/pembayaran/kwitansi/'.$kodePembayaran);

        if (! $response->ok()) {
            Notification::make()
                ->title('Kwitansi tidak ditemukan')
                ->danger()
                ->send();

            return response()->streamDownload(fn () => null, $filename);
        }

        Storage::disk('local')->put($filename, $response->body());
        $path = Storage::disk('local')->path($filename);

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
            unlink($path);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function render()
    {
        return view('livewire.pembayaran-card-view');
    }
}
