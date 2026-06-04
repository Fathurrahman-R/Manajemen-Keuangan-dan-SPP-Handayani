<?php

namespace App\Livewire;

use App\Services\ApiService;
use Exception;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagihanCardView extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;
    use \App\Livewire\Concerns\HasPeriodFilter;
    use \App\Livewire\Concerns\HandlesApiErrors;

    public string $search = '';
    public string $filterJenjang = '';
    public string $filterStatus = '';
    public int $perPage = 5;
    public int $page = 1;

    public array $siswaData = [];
    public array $meta = [];

    public array $selectedTagihanForPayment = [];

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

        if ($this->selectedTahunAjaranId) {
            $params['tahun_ajaran_id'] = $this->selectedTahunAjaranId;
        }

        if (filled($this->search)) {
            $params['search'] = $this->search;
        }

        if (filled($this->filterJenjang)) {
            $params['jenjang'] = $this->filterJenjang;
        }

        if (filled($this->filterStatus)) {
            $params['status'] = $this->filterStatus;
        }

        try {
            $response = ApiService::client()->get('/tagihan/grouped', $params);

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
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
            $this->siswaData = [];
            $this->meta = [];
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan yang tidak terduga. Silakan coba lagi atau hubungi support.')
                ->danger()
                ->persistent()
                ->send();
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

    public function updatedFilterStatus(): void
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

    public function isAdmin(): bool
    {
        $permissions = session()->get('data.permissions', []);
        return in_array('create-tagihan', $permissions) || in_array('delete-tagihan', $permissions);
    }

    public function canCreate(): bool
    {
        return in_array('create-tagihan', session()->get('data.permissions', []));
    }

    public function canDelete(): bool
    {
        return in_array('delete-tagihan', session()->get('data.permissions', []));
    }

    public function deleteTagihan(string $kodeTagihan): void
    {
        try {
            $response = ApiService::client()->delete('/tagihan/' . $kodeTagihan);

            if ($response->ok()) {
                Notification::make()
                    ->title('Tagihan Berhasil Dihapus')
                    ->success()
                    ->send();
                $this->loadData();
            } else {
                $this->handleApiError($response);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function batchPay(array $kodeTagihan, string $metode, string $pembayar): void
    {
        try {
            $response = ApiService::client()->post('/pembayaran/batch', [
                'kode_tagihan' => $kodeTagihan,
                'metode' => $metode,
                'pembayar' => $pembayar,
            ]);

            if ($response->ok()) {
                Notification::make()
                    ->title('Pembayaran Batch Berhasil')
                    ->success()
                    ->send();

                // Dispatch event for kwitansi download
                $pembayaranData = $response->json()['data'] ?? [];
                $kodePembayaran = collect($pembayaranData)->pluck('kode_pembayaran')->toArray();
                $this->dispatch('batch-payment-success', kodePembayaran: $kodePembayaran);

                $this->loadData();
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
                ->title('Terjadi kesalahan saat memproses pembayaran.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function payAction(): Action
    {
        return Action::make('pay')
            ->label('Bayar')
            ->icon('heroicon-o-banknotes')
            ->color('primary')
            ->form([
                Select::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options([
                        'Tunai' => 'Tunai',
                        'Non-Tunai' => 'Non-Tunai',
                    ])
                    ->required(),
                TextInput::make('pembayar')
                    ->label('Nama Pembayar')
                    ->required()
                    ->maxLength(100),
                TextInput::make('jumlah')
                    ->label('Jumlah Bayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->processPayment($data);
            })
            ->modalHeading('Pembayaran Tagihan')
            ->modalWidth('md');
    }

    public function processPayment(array $data): void
    {
        if (empty($this->selectedTagihanForPayment)) {
            Notification::make()
                ->title('Tidak ada tagihan yang dipilih.')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = ApiService::client()->post('/pembayaran/batch', [
                'kode_tagihan' => $this->selectedTagihanForPayment,
                'metode' => $data['metode_pembayaran'],
                'pembayar' => $data['pembayar'],
            ]);

            if ($response->ok()) {
                Notification::make()
                    ->title('Pembayaran Berhasil')
                    ->success()
                    ->send();

                // Dispatch event for kwitansi download
                $pembayaranData = $response->json()['data'] ?? [];
                $kodePembayaran = collect($pembayaranData)->pluck('kode_pembayaran')->toArray();
                $this->dispatch('batch-payment-success', kodePembayaran: $kodePembayaran);

                $this->selectedTagihanForPayment = [];
                $this->loadData();
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
                ->title('Terjadi kesalahan saat memproses pembayaran.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function openPayModal(array $selectedTagihan): void
    {
        $this->selectedTagihanForPayment = $selectedTagihan;
        $this->mountAction('pay');
    }

    public function downloadKwitansi(string $kodePembayaran): ?StreamedResponse
    {
        try {
            $filename = 'kwitansi-' . $kodePembayaran . '.pdf';

            $response = ApiService::client()
                ->withHeaders(['Accept' => 'application/pdf'])
                ->get('/pembayaran/kwitansi/' . $kodePembayaran);

            if (!$response->ok()) {
                Notification::make()
                    ->title('Kwitansi gagal diunduh')
                    ->danger()
                    ->persistent()
                    ->send();
                return null;
            }

            Storage::disk('local')->put($filename, $response->body());
            $path = Storage::disk('local')->path($filename);

            return response()->streamDownload(function () use ($path) {
                echo file_get_contents($path);
                unlink($path);
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Kwitansi gagal diunduh')
                ->danger()
                ->persistent()
                ->send();
            return null;
        }
    }

    public function addTagihanAction(): Action
    {
        return Action::make('addTagihan')
            ->label('Tambah Tagihan')
            ->color('primary')
            ->button()
            ->visible(fn(): bool => $this->canCreate())
            ->modalHeading('Tambah Tagihan')
            ->modalFooterActions(function (Action $action) {
                return [
                    $action->getModalSubmitAction()
                        ->label('Simpan')
                        ->color('primary')
                        ->extraAttributes(['class' => 'text-white font-semibold']),
                    $action->getModalCancelAction()->label('Batal'),
                ];
            })
            ->modalFooterActionsAlignment(Alignment::End)
            ->schema([
                Select::make('jenis_tagihan_id')
                    ->label('Jenis Tagihan')
                    ->searchable()
                    ->searchPrompt('Cari Jenis Tagihan')
                    ->options(function () {
                        $response = ApiService::client()->get('/jenis-tagihan');
                        if (!$response->ok()) return [];
                        $data = $response->json();
                        return collect($data['data'])->mapWithKeys(function ($item) {
                            $jumlah = 'Rp. ' . number_format($item['jumlah'], 0, '', ',');
                            return [$item['id'] => $item['nama'] . ' - ' . $jumlah];
                        })->toArray();
                    })
                    ->required(),
                Select::make('jenjang')
                    ->label('Jenjang')
                    ->options(['TK' => 'TK', 'KB' => 'KB', 'MI' => 'MI'])
                    ->reactive()
                    ->required(),
                Select::make('kelas_id')
                    ->label('Kelas')
                    ->searchable()
                    ->searchPrompt('Cari Kelas')
                    ->options(function ($get) {
                        $jenjang = $get('jenjang');
                        if (!$jenjang) return [];
                        $response = ApiService::client()->get('/kelas/' . $jenjang);
                        if (!$response->ok()) return [];
                        $data = $response->json();
                        return collect($data['data'])->mapWithKeys(fn($item) => [$item['id'] => $item['nama']])->toArray();
                    })
                    ->required(),
                Select::make('kategori_id')
                    ->label('Kategori')
                    ->searchable()
                    ->searchPrompt('Cari Kategori')
                    ->options(function () {
                        $response = ApiService::client()->get('/kategori');
                        if (!$response->ok()) return [];
                        $data = $response->json();
                        return collect($data['data'])->mapWithKeys(fn($item) => [$item['id'] => $item['nama']])->toArray();
                    })
                    ->required(),
            ])
            ->action(function (array $data): void {
                try {
                    $response = ApiService::client()->post('/tagihan', $data);
                    if ($response->status() === 201) {
                        Notification::make()
                            ->title('Tagihan Berhasil Ditambahkan')
                            ->success()
                            ->send();
                        $this->loadData();
                    } else {
                        $this->handleApiError($response);
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Notification::make()
                        ->title('Server tidak dapat dihubungi')
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }



    public function render()
    {
        return view('livewire.tagihan-card-view');
    }
}
