<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class JenisTagihan extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search): array => Http::withHeaders([
                    'Authorization' => session()->get('data')['token']
                ])
                    ->get(env('API_URL') . '/jenis-tagihan')
                    ->collect('data')
                    ->when(filled($search), fn (Collection $data): Collection => $data->filter(fn (array $record): bool => str_contains(Str::lower($record['nama']), Str::lower($search))))
                    ->toArray()
            )
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('jatuh_tempo')->label('Jatuh Tempo'),
                TextColumn::make('jumlah')->label('Jumlah')->money(currency: 'Rp.', decimalPlaces: 0, ),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Jenis Tagihan')
            ->emptyStateDescription('Silahkan menambahkan jenis tagihan')
            ->recordActions([
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Jenis Tagihan')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah Jenis Tagihan')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction()
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nama' => $record['nama'],
                        'jatuh_tempo' => $record['jatuh_tempo'],
                        'jumlah' => $record['jumlah'],
                    ])
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Tagihan')
                            ->required(),
                        DatePicker::make('jatuh_tempo')
                            ->label('Jatuh Tempo')
                            ->native(false)
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->minDate(now())
                            ->required(),
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/jenis-tagihan/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Jenis Tagihan Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Jenis Tagihan')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Jenis Tagihan')
                    ->modalDescription('Apakah kamu yakin untuk menghapus jenis tagihan ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->delete(env('API_URL') . '/jenis-tagihan/' . $record['id']);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Jenis Tagihan Berhasil Dihapus')
                    ->failureNotificationTitle('Jenis Tagihan Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    })
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Jenis Tagihan')
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
                        TextInput::make('nama')
                            ->label('Nama Tagihan')
                            ->required(),
                        DatePicker::make('jatuh_tempo')
                            ->label('Jatuh Tempo')
                            ->native(false)
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->minDate(now())
                            ->required(),
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/jenis-tagihan', $data);

                        if ($response->status() != 201) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Jenis Tagihan Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'text-white font-semibold'
                    ]),
            ]);
    }

    public function render()
    {
        return view('livewire.jenis-tagihan');
    }
}
