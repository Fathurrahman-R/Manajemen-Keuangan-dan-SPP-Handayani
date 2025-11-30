<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Str;

class DataKelas extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $activeTab = 'TK';

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search): array => Http::withHeaders([
                    'Authorization' => session()->get('data')['token']
                ])
                    ->get(env('API_URL') . '/kelas/' . $this->activeTab)
                    ->collect('data')
                    ->when(filled($search), fn (Collection $data): Collection => $data->filter(fn (array $record): bool => str_contains(Str::lower($record['nama']), Str::lower($search))))
                    ->toArray()
            )
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable(),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Kelas')
            ->emptyStateDescription('Silahkan menambahkan kelas')
            ->recordActions([
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Kelas')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah Kelas')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction()
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nama' => $record['nama']
                    ])
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Kelas')
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/kelas/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Kelas Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Kelas')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kelas')
                    ->modalDescription('Apakah kamu yakin untuk menghapus kelas ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->delete(env('API_URL') . '/kelas' . '/' . $record['id']);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Kelas Berhasil Dihapus')
                    ->failureNotificationTitle('Kelas Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    })
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Kelas')
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
                            ->label('Nama Kelas')
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/kelas/' . $this->activeTab, $data);

                        if ($response->status() != 201) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Kelas Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'text-white font-semibold'
                    ]),
            ]);
    }

    public function render()
    {
        return view('livewire.data-kelas', [
            'activeTab' => $this->activeTab
        ]);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
}
