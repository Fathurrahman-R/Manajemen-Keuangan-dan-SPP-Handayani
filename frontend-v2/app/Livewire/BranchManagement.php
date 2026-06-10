<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Illuminate\Http\Client\ConnectionException;
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

class BranchManagement extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;
    use HandlesApiErrors;

    protected function hasPermission(string $permission): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array($permission, $permissions);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, ?string $sortColumn = null, ?string $sortDirection = null): array {
                    try {
                        $response = ApiService::client()->get('/branches');

                        if (!$response->ok()) {
                            $this->handleApiError($response);
                            return [];
                        }

                        return $response->collect('data')
                            ->when(
                                filled($search),
                                fn(Collection $data): Collection => $data->filter(
                                    fn(array $record): bool => str_contains(Str::lower($record['location']), Str::lower($search))
                                )
                            )
                            ->when(
                                filled($sortColumn),
                                fn(Collection $data): Collection => $data->sortBy(
                                    fn(array $record) => data_get($record, $sortColumn),
                                    SORT_REGULAR,
                                    ($sortDirection ?? 'asc') === 'desc'
                                )->values()
                            )
                            ->toArray();
                    } catch (ConnectionException $e) {
                        $this->notifyConnectionError();
                        return [];
                    } catch (\Throwable $e) {
                        $this->notifyUnexpectedError();
                        return [];
                    }
                }
            )
            ->columns([
                TextColumn::make('location')->label('Nama Cabang')->sortable()->searchable(),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Cabang')
            ->emptyStateDescription('Silahkan menambahkan cabang baru')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordActions([
                Action::make('edit')
                    ->label('Ubah')
                    ->tooltip('Ubah Cabang')
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn(): bool => $this->hasPermission('update-branch'))
                    ->modalHeading('Ubah Cabang')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'location' => $record['location'],
                    ])
                    ->schema([
                        TextInput::make('location')
                            ->label('Nama Cabang')
                            ->required()
                            ->maxLength(150),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()->put('/branches/' . $record['id'], [
                            'location' => $data['location'],
                        ]);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Cabang Gagal Diubah')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Cabang Berhasil Diubah')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(fn() => $this->resetTable()),
                Action::make('delete')
                    ->label('Hapus')
                    ->tooltip('Hapus Cabang')
                    ->icon('heroicon-s-trash')
                    ->iconButton()
                    ->color('danger')
                    ->visible(fn(): bool => $this->hasPermission('delete-branch'))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Cabang')
                    ->modalDescription('Apakah kamu yakin untuk menghapus cabang ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()->delete('/branches/' . $record['id']);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Cabang Gagal Dihapus')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Cabang Berhasil Dihapus')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(fn() => $this->resetTable()),
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Tambah')
                    ->color('primary')
                    ->button()
                    ->visible(fn(): bool => $this->hasPermission('create-branch'))
                    ->modalHeading('Tambah Cabang')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->schema([
                        TextInput::make('location')
                            ->label('Nama Cabang')
                            ->required()
                            ->maxLength(150),
                    ])
                    ->action(function (array $data): void {
                        $response = ApiService::client()->post('/branches', [
                            'location' => $data['location'],
                        ]);

                        if ($response->status() !== 201) {
                            Notification::make()
                                ->title('Cabang Gagal Ditambahkan')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Cabang Berhasil Ditambahkan')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(fn() => $this->resetTable())
                    ->extraAttributes([
                        'class' => 'text-white font-semibold',
                        'id' => 'add-branch',
                    ]),
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => $this->hasPermission('delete-branch'))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Cabang Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua cabang yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/branches/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} cabang berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.branch-management');
    }
}
