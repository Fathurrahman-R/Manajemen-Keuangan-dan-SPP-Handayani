<?php

namespace App\Livewire;

use App\Services\ApiService;
use Exception;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DataCategory extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search, ?string $sortColumn = null, ?string $sortDirection = null): array => ApiService::client()
                    ->get('/kategori')
                    ->collect('data')
                    ->when(filled($search), fn (Collection $data): Collection => $data->filter(fn (array $record): bool => str_contains(Str::lower($record['nama']), Str::lower($search))))
                    ->when(
                        filled($sortColumn),
                        fn(Collection $data): Collection => $data->sortBy(
                            $sortColumn,
                            SORT_REGULAR,
                            ($sortDirection ?? 'asc') === 'desc'
                        )->values()
                    )
                    ->toArray(),
            )
            ->columns([
                TextColumn::make('nama')->label('Nama')->sortable(),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Kategori')
            ->emptyStateDescription('Silahkan menambahkan kategori')
            ->recordActions([
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Kategori')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn(): bool => in_array('update-kategori', session()->get('data.permissions', [])))
                    ->modalHeading('Ubah Kategori')
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
                        'nama' => $record['nama']
                    ])
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Kategori')
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->put('/kategori/' . $record['id'], $data);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Kategori Gagal Diubah')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Kategori Berhasil Diubah')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Kategori')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->visible(fn(): bool => in_array('delete-kategori', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kategori')
                    ->modalDescription('Apakah kamu yakin untuk menghapus kategori ini?')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Ya')
                                ->color('danger')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold'
                                ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->delete('/kategori' . '/' . $record['id']);

                        if ($response->status() != 201) {
                            Notification::make()
                                ->title('Kategori Gagal Dihapus')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Kategori Berhasil Dihapus')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(function () {
                        $this->resetTable();
                    })
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => in_array('delete-kategori', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kategori Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua kategori yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/kategori/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} kategori berhasil dihapus")->success()->send();
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
                    ->visible(fn(): bool => in_array('create-kategori', session()->get('data.permissions', [])))
                    ->modalHeading('Tambah Kategori')
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
                            ->label('Nama Kategori')
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->post('/kategori', $data);

                        if ($response->status() != 201) {
                            Notification::make()
                                ->title('Kategori Gagal Ditambahkan')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Kategori Berhasil Ditambahkan')
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

    public function render(): View
    {
        try {
            return view('livewire.data-category');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
