<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NumberFormatter;

class Tagihan extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage, array $filters): LengthAwarePaginator {
                    $params = [
                        'per_page' => $this->perPage,
                        'page' => $page,
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    if (filled($filters['status']['value'])) {
                        $params['status'] = $filters['status']['value'];
                    }

                    if (filled($filters['jenjang']['value'])) {
                        $params['jenjang'] = $filters['jenjang']['value'];
                    }

                    $response = Http::withHeaders([
                        'Authorization' => session()->get('data')['token']
                    ])
                        ->get(env('API_URL') . '/tagihan', $params)
                        ->collect();

                    return new LengthAwarePaginator(
                        items: $response['data'] ?? [],
                        total: $response['meta']['total'] ?? 0,
                        perPage: $recordsPerPage,
                        currentPage: $page,
                    );
                }
            )
            ->columns([
                TextColumn::make('kode_tagihan')->label('Kode Tagihan')->searchable(),
                TextColumn::make('siswa.nama')->label('Nama Siswa'),
                TextColumn::make('siswa.nis')->label('NIS'),
                TextColumn::make('siswa.kelas.nama')->label('Kelas'),
                TextColumn::make('jenis_tagihan.jatuh_tempo')->label('Jatuh Tempo'),
                TextColumn::make('jenis_tagihan.nama')->label('Jenis Tagihan'),
                TextColumn::make('jenis_tagihan.jumlah')->label('Jumlah Tagihan')->money(currency: 'Rp.', decimalPlaces: 0,),
                TextColumn::make('tmp')->label('Jumlah Yang Telah Dibayarkan')->money(currency: 'Rp.', decimalPlaces: 0,),
                TextColumn::make('sisa')->state(fn(array $record) => $record['jenis_tagihan']['jumlah'] - $record['tmp'])->label('Sisa')->money(currency: 'Rp.', decimalPlaces: 0,),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Lunas' => 'success',
                        'Belum Lunas' => 'warning',
                        'Belum Dibayar' => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Lunas' => 'Lunas',
                        'Belum Lunas' => 'Belum Lunas',
                        'Belum Dibayar' => 'Belum Dibayar',
                    ]),
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->options([
                        'TK' => 'TK',
                        'SD' => 'SD',
                        'MI' => 'MI',
                    ]),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(2)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Tagihan')
            ->emptyStateDescription('Silahkan menambahkan tagihan')
            ->recordActions([
                ActionGroup::make([
                    Action::make('installments')
                        ->label('Bayar')
                        ->tooltip('Bayar')
                        ->hidden(fn(array $record): bool => $record['status'] === 'Lunas')
                        ->modalHeading('Bayar Tagihan')
                        ->modalFooterActions(function (Action $action) {
                            return [
                                $action->getModalSubmitAction()
                                    ->label('Simpan')
                                    ->color('primaryMain')
                                    ->extraAttributes([
                                        'class' => 'text-white font-semibold'
                                    ]),
                                $action->getModalCancelAction()->label('Batal'),
                            ];
                        })
                        ->fillForm(fn(array $record): array => [
                            'kode_tagihan' => $record['kode_tagihan'],
                            'total' => $record['jenis_tagihan']['jumlah'] - $record['tmp'],
                        ])
                        ->schema([
                            TextInput::make('total')
                                ->label('Total Tagihan')
                                ->readOnly()
                                ->disabled(),
                            Select::make('metode')
                                ->label('Metode Pembayaran')
                                ->options([
                                    'Tunai' => 'Tunai',
                                    'Non-Tunai' => 'Non-Tunai',
                                ])
                                ->required(),
                            Select::make('jenis_pembayaran')
                                ->label('Jenis Pembayaran')
                                ->options([
                                    'Cicil' => 'Cicil',
                                    'Lunas' => 'Lunas',
                                ])
                                ->live()
                                ->afterStateUpdated(function ($state, $set, array $record): void {
                                    if ($state == 'Lunas') {
                                        $set('jumlah', $record['jenis_tagihan']['jumlah'] - $record['tmp']);
                                    } else {
                                        $set('jumlah', null);
                                    }
                                })
                                ->required(),
                            TextInput::make('jumlah')
                                ->label('Jumlah')
                                ->disabled(fn($get) => $get('jenis_pembayaran') === 'Lunas')
                                ->required(),
                            TextInput::make('pembayar')
                                ->label('Dibayar Oleh')
                                ->required(),
                        ])
                        ->action(function (array $data, $record) {
                            $url = env('API_URL') . '/pembayaran';
                            $payload = [
                                'metode' => $data['metode'],
                                'pembayar' => $data['pembayar']
                            ];

                            if ($data['jenis_pembayaran'] === 'Lunas') {
                                $url = $url . '/lunas/' . $record['kode_tagihan'];
                            } else {
                                $url = $url . '/bayar/' . $record['kode_tagihan'];

                                $payload['jumlah'] = $data['jumlah'];
                            }

                            $response = Http::withHeaders([
                                'Authorization' => session()->get('data')['token']
                            ])
                                ->post($url, $payload);

                            if (!$response->ok()) {
                                Notification::make()
                                    ->title('Tagihan Gagal Dibayar')
                                    ->danger()
                                    ->send();
                            } else {
                                try {
                                    Notification::make()
                                        ->title('Tagihan Berhasil Dibayar')
                                        ->success()
                                        ->send();
    
                                    $filename = 'kwitansi-' . $response->json()['data']['kode_pembayaran'] . '.pdf';
    
                                    $responseDownload = Http::withHeaders([
                                        'Authorization' => session()->get('data')['token'],
                                        'Accept' => 'application/pdf'
                                    ])
                                        ->get(env('API_URL') . '/pembayaran/kwitansi/' . $response->json()['data']['kode_pembayaran']);
        
                                    if (!$responseDownload->ok()) {
                                        $errorKeys = array_keys($response->json()['errors']);
                                        $message = $response->json()['errors'][$errorKeys[0]][0];

                                        throw new Exception($message, $responseDownload->status());
                                    }
    
                                    // Store the file temporarily (optional, but good practice for larger files)
                                    Storage::disk('local')->put($filename, $responseDownload->body());
                                    $path = Storage::disk('local')->path($filename);
    
                                    // Return a response that prompts the file download
                                    return response()->streamDownload(function () use ($path) {
                                        echo file_get_contents($path);
                                        // Clean up the temporary file after streaming
                                        unlink($path);
                                    }, $filename, [
                                        'Content-Type' => 'application/pdf', // Set the correct MIME type
                                    ]);
                                } catch (\Throwable $th) {
                                    Notification::make()
                                        ->title('Tagihan Berhasil Dibayar')
                                        ->danger()
                                        ->send();
                                }
                            }
                        })
                        ->after(function () {
                            $this->resetTable();
                        }),
                    Action::make('delete')
                        ->label('Hapus')
                        ->tooltip('Hapus Tagihan')
                        ->color('danger') // Optional color
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Tagihan')
                        ->modalDescription('Apakah kamu yakin untuk menghapus tagihan ini?')
                        ->modalSubmitActionLabel('Ya')
                        ->modalCancelActionLabel('Batal')
                        ->modalFooterActionsAlignment(Alignment::End)
                        ->action(function (array $data, $record): void {
                            $response = Http::withHeaders([
                                'Authorization' => session()->get('data')['token']
                            ])
                                ->delete(env('API_URL') . '/tagihan/' . $record['id']);

                            if (!$response->ok()) {
                                Notification::make()
                                    ->title('Tagihan Gagal Dihapus')
                                    ->danger()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Tagihan Berhasil Dihapus')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->after(function () {
                            $this->resetTable();
                        })
                ])
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Tagihan')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primaryMain')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold'
                                ]),
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
                                $response = Http::withHeaders([
                                    'Authorization' => session()->get('data')['token']
                                ])
                                    ->get(env('API_URL') . '/jenis-tagihan');

                                if (!$response->ok()) {
                                    return [];
                                }

                                $data = $response->json();

                                $options = collect($data['data'])->mapWithKeys(function ($item) {
                                    $jumlah = 'Rp. ' . number_format($item['jumlah'], 0, '', ',');

                                    return [$item['id'] => $item['nama'] . ' - ' . $jumlah];
                                })->toArray();

                                return $options;
                            })
                            ->required(),
                        Select::make('jenjang')
                            ->label('Jenjang')
                            ->options([
                                'TK' => 'TK',
                                'KB' => 'KB',
                                'MI' => 'MI',
                            ])
                            ->reactive()
                            ->required(),
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->searchable()
                            ->searchPrompt('Cari Kelas')
                            ->options(function ($get) {
                                $response = Http::withHeaders([
                                    'Authorization' => session()->get('data')['token']
                                ])
                                    ->get(env('API_URL') . '/kelas/' . $get('jenjang'));

                                if (!$response->ok()) {
                                    return [];
                                }

                                $data = $response->json();

                                $options = collect($data['data'])->mapWithKeys(function ($item) {
                                    return [$item['id'] => $item['nama']];
                                })->toArray();

                                return $options;
                            })
                            ->required(),
                        Select::make('kategori_id')
                            ->label('Kategori')
                            ->searchable()
                            ->searchPrompt('Cari Kategori')
                            ->options(function () {
                                $response = Http::withHeaders([
                                    'Authorization' => session()->get('data')['token']
                                ])
                                    ->get(env('API_URL') . '/kategori');

                                if (!$response->ok()) {
                                    return [];
                                }

                                $data = $response->json();

                                $options = collect($data['data'])->mapWithKeys(function ($item) {
                                    return [$item['id'] => $item['nama']];
                                })->toArray();

                                return $options;
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/tagihan', $data);

                        if ($response->status() != 201) {
                            Notification::make()
                                ->title('Tagihan Gagal Ditambahkan')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tagihan Berhasil Ditambahkan')
                                ->danger()
                                ->send();
                        }
                    })
                    ->extraAttributes([
                        'class' => 'text-white font-semibold',
                        'id' => 'add',
                    ]),
            ]);
    }

    public function render()
    {
        return view('livewire.tagihan');
    }
}
