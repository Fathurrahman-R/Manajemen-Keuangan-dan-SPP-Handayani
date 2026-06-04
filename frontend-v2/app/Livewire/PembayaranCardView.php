<?php

namespace App\Livewire;

use App\Services\ApiService;
use Exception;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PembayaranCardView extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;
    use \App\Livewire\Concerns\HandlesApiErrors;

    public string $search = '';
    public string $filterJenjang = '';
    public int $perPage = 5;
    public int $page = 1;

    public array $siswaData = [];
    public array $meta = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
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
    }



    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadData();
    }

    public function updatedFilterJenjang(): void
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
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadData();
        }
    }

    public function nextPage(): void
    {
        if ($this->page < ($this->meta['last_page'] ?? 1)) {
            $this->page++;
            $this->loadData();
        }
    }

    public function canDelete(): bool
    {
        return in_array('delete-pembayaran', session()->get('data.permissions', []));
    }

    public function deletePembayaran(string $kodePembayaran): void
    {
        try {
            $response = ApiService::client()->delete('/pembayaran/' . $kodePembayaran);

            if ($response->ok()) {
                Notification::make()
                    ->title('Pembayaran Berhasil Dihapus')
                    ->success()
                    ->send();
                $this->loadData();
            } else {
                $this->handleApiError($response);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->notifyConnectionError();
        }
    }

    public function downloadKwitansi(string $kodePembayaran): StreamedResponse
    {
        $filename = 'kwitansi-' . $kodePembayaran . '.pdf';

        $response = ApiService::client()
            ->withHeaders(['Accept' => 'application/pdf'])
            ->get('/pembayaran/kwitansi/' . $kodePembayaran);

        if (!$response->ok()) {
            Notification::make()
                ->title('Kwitansi tidak ditemukan')
                ->danger()
                ->send();
            return response()->streamDownload(fn() => null, $filename);
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
