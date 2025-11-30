<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Str;

class Pembayaran extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
                    $params = [
                        'per_page' => $this->perPage,
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    $response = Http::withHeaders([
                        'Authorization' => session()->get('data')['token']
                    ])
                        ->get(env('API_URL') . '/pembayaran', $params)
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
                TextColumn::make('kode_pembayaran')->label('Kode Pembayaran')->searchable(),
                TextColumn::make('tanggal')->label('Tanggal Pembayaran'),
                TextColumn::make('pembayar')->label('Dibayar Oleh'),
                TextColumn::make('jumlah')->label('Jumlah Pembayaran'),
                TextColumn::make('kode_tagihan.kode_tagihan')->label('Kode Tagihan'),
                TextColumn::make('kode_tagihan.jenis_tagihan.nama')->label('Jenis Tagihan'),
                TextColumn::make('kode_tagihan.jenis_tagihan.jumlah')->label('Jumlah Tagihan'),
                TextColumn::make('kode_tagihan.siswa.nama')->label('Nama Siswa'),
                TextColumn::make('metode')->label('Metode Pembayaran'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'Lunas' => 'Lunas',
                        'Belum Lunas' => 'Belum Lunas',
                        'Belum Dibayar' => 'Belum Dibayar',
                    ]),
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->multiple()
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
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Tagihan')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
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
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Tagihan Berhasil Dihapus')
                    ->failureNotificationTitle('Tagihan Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    })
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
                        Select::make('jenis_tagihan')
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
                                    return [$item['id'] => $item['nama']];
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
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Tagihan Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'text-white font-semibold'
                    ]),
            ]);
    }

    public function render()
    {
        $response = Http::withHeaders([
            'Authorization' => session()->get('data')['token']
        ])
            ->get(env('API_URL') . '/pembayaran', [
                'per_page' => $this->perPage
            ]);

        if (!$response->ok()) {
            throw new Exception($response->json()['errors']['message'][0]);
        }

        $data = $response->json();

        // dd($data['data']);

        return view('livewire.pembayaran');
    }
}
