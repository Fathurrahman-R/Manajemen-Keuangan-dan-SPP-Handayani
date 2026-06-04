<?php

namespace App\Livewire;

use App\Services\ApiService;
use Exception;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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

    public $activeTab = 'KB';

    public function mount(string $jenjang = 'KB'): void
    {
        $this->activeTab = $jenjang;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search, ?string $sortColumn = null, ?string $sortDirection = null): array => ApiService::client()
                    ->get('/kelas/' . $this->activeTab)
                    ->collect('data')
                    ->when(filled($search), fn(Collection $data): Collection => $data->filter(fn(array $record): bool => str_contains(Str::lower($record['nama']), Str::lower($search))))
                    ->when(
                        filled($sortColumn),
                        fn(Collection $data): Collection => $data->sortBy(
                            fn(array $record) => data_get($record, $sortColumn),
                            SORT_REGULAR,
                            ($sortDirection ?? 'asc') === 'desc'
                        )->values()
                    )
                    ->toArray()
            )
            ->columns([
                TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
                TextColumn::make('level')->label('Level')->sortable()->placeholder('-'),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Kelas')
            ->emptyStateDescription('Silahkan menambahkan kelas')
            ->recordActions([
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Kelas')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn(): bool => in_array('update-kelas', session()->get('data.permissions', [])))
                    ->modalHeading('Ubah Kelas')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primary')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold'
                                ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction()
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nama' => $record['nama'],
                        'level' => $record['level'] ?? null,
                    ])
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Kelas')
                            ->required(),
                        TextInput::make('level')
                            ->label('Urutan Level')
                            ->placeholder('Opsional - urutan kelas dalam jenjang')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->action(function (array $data, $record): void {
                        $payload = [
                            'nama' => $data['nama'],
                            'level' => $data['level'] !== '' && $data['level'] !== null ? (int) $data['level'] : null,
                        ];

                        $response = ApiService::client()
                            ->put('/kelas/' . $this->activeTab .'/' . $record['id'], $payload);

                        if (!$response->ok()) {
                            $errors = $response->json()['errors'] ?? [];
                            $errorKeys = array_keys($errors);
                            $message = !empty($errorKeys) ? $errors[$errorKeys[0]][0] : 'Kelas Gagal Diubah';

                            Notification::make()
                                ->title($message)
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Kelas Berhasil Diubah')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Kelas')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->visible(fn(): bool => in_array('delete-kelas', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kelas')
                    ->modalDescription('Apakah kamu yakin untuk menghapus kelas ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->delete('/kelas' . '/' . $record['id']);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Kelas Gagal Dihapus')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Kelas Berhasil Dihapus')
                                ->success()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('Kelas Berhasil Dihapus')
                    ->failureNotificationTitle('Kelas Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => in_array('delete-kelas', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kelas Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua kelas yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/kelas/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} kelas berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primary') // Optional color
                    ->button()
                    ->visible(fn(): bool => in_array('create-kelas', session()->get('data.permissions', [])))
                    ->modalHeading('Tambah Kelas')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primary')
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
                        TextInput::make('level')
                            ->label('Urutan Level')
                            ->placeholder('Opsional - urutan kelas dalam jenjang')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->action(function (array $data, $record): void {
                        $payload = [
                            'nama' => $data['nama'],
                            'level' => $data['level'] !== '' && $data['level'] !== null ? (int) $data['level'] : null,
                        ];

                        $response = ApiService::client()
                            ->post('/kelas/' . $this->activeTab, $payload);

                        if ($response->status() != 201) {
                            $errors = $response->json()['errors'] ?? [];
                            $errorKeys = array_keys($errors);
                            $message = !empty($errorKeys) ? $errors[$errorKeys[0]][0] : 'Kelas Gagal Ditambahkan';

                            Notification::make()
                                ->title($message)
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Kelas Berhasil Ditambahkan')
                                ->success()
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
        return view('livewire.data-kelas', [
            'activeTab' => $this->activeTab
        ]);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
}
