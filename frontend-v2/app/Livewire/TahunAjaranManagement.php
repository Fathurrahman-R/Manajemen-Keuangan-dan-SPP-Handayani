<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class TahunAjaranManagement extends Component implements HasActions, HasSchemas, HasTable
{
    use HandlesApiErrors;
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, array $filters = [], ?string $sortColumn = null, ?string $sortDirection = null): array {
                    try {
                        $response = ApiService::client()->get('/tahun-ajaran');

                        if (! $response->ok()) {
                            $this->handleApiError($response);

                            return [];
                        }

                        return $response->collect('data')
                            ->when(filled($search), fn (Collection $data): Collection => $data->filter(
                                fn (array $record): bool => str_contains(Str::lower($record['nama']), Str::lower($search))
                            ))
                            ->when(! empty($filters['status']['value'] ?? null), fn (Collection $data) => $data->filter(
                                fn (array $record): bool => ($record['status'] ?? '') === $filters['status']['value']
                            ))
                            ->when(
                                filled($sortColumn),
                                fn (Collection $data): Collection => $data->sortBy(
                                    fn (array $record) => data_get($record, $sortColumn),
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
                TextColumn::make('nama')->label('Tahun Ajaran')->sortable()->searchable(),
                TextColumn::make('tanggal_mulai')->label('Tanggal Mulai')->sortable(),
                TextColumn::make('tanggal_selesai')->label('Tanggal Selesai')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Non-Aktif' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Non-Aktif' => 'Non-Aktif',
                    ]),
            ])
            ->emptyStateHeading('Tidak Ada Tahun Ajaran')
            ->emptyStateDescription('Silahkan menambahkan tahun ajaran')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordActions([
                ActionGroup::make([
                    Action::make('activate')
                        ->label('Aktifkan')
                        ->tooltip('Aktifkan Tahun Ajaran')
                        ->color('success')
                        ->hidden(fn (array $record): bool => $record['status'] === 'Aktif')
                        ->visible(fn (): bool => PermissionHelper::hasResource('tahun-ajaran.toggle'))
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan Tahun Ajaran')
                        ->modalDescription('Apakah kamu yakin ingin mengaktifkan tahun ajaran ini? Tahun ajaran lain akan dinonaktifkan.')
                        ->modalSubmitActionLabel('Ya, Aktifkan')
                        ->modalCancelActionLabel('Batal')
                        ->action(function (array $data, $record): void {
                            $response = ApiService::client()
                                ->patch('/tahun-ajaran/'.$record['id'].'/activate');

                            if (! $response->ok()) {
                                $this->showApiError($response);
                            } else {
                                Notification::make()
                                    ->title('Tahun Ajaran Berhasil Diaktifkan')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->after(fn () => $this->resetTable()),
                    Action::make('edit')
                        ->label('Edit')
                        ->tooltip('Edit Tahun Ajaran')
                        ->color('warning')
                        ->visible(fn (): bool => PermissionHelper::hasResource('tahun-ajaran.update'))
                        ->modalHeading('Edit Tahun Ajaran')
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
                        ->fillForm(fn (array $record): array => [
                            'nama' => $record['nama'],
                            'tanggal_mulai' => $record['tanggal_mulai'],
                            'tanggal_selesai' => $record['tanggal_selesai'],
                        ])
                        ->schema([
                            TextInput::make('nama')
                                ->label('Nama Tahun Ajaran')
                                ->placeholder('2024/2025')
                                ->maxLength(9)
                                ->required(),
                            DatePicker::make('tanggal_mulai')
                                ->label('Tanggal Mulai')
                                ->native(false)
                                ->format('Y-m-d')
                                ->displayFormat('d-m-Y')
                                ->required(),
                            DatePicker::make('tanggal_selesai')
                                ->label('Tanggal Selesai')
                                ->native(false)
                                ->format('Y-m-d')
                                ->displayFormat('d-m-Y')
                                ->required(),
                        ])
                        ->action(function (array $data, $record): void {
                            $response = ApiService::client()
                                ->put('/tahun-ajaran/'.$record['id'], $data);

                            if (! $response->ok()) {
                                $this->showApiError($response);
                            } else {
                                Notification::make()
                                    ->title('Tahun Ajaran Berhasil Diubah')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->after(fn () => $this->resetTable()),
                    Action::make('delete')
                        ->label('Hapus')
                        ->tooltip('Hapus Tahun Ajaran')
                        ->color('danger')
                        ->visible(fn (): bool => PermissionHelper::hasResource('tahun-ajaran.delete'))
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Tahun Ajaran')
                        ->modalDescription('Apakah kamu yakin untuk menghapus tahun ajaran ini?')
                        ->modalSubmitActionLabel('Ya, Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->modalFooterActionsAlignment(Alignment::End)
                        ->action(function (array $data, $record): void {
                            $response = ApiService::client()
                                ->delete('/tahun-ajaran/'.$record['id']);

                            if (! $response->ok()) {
                                $this->showApiError($response);
                            } else {
                                Notification::make()
                                    ->title('Tahun Ajaran Berhasil Dihapus')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->after(fn () => $this->resetTable()),
                ]),
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => PermissionHelper::hasResource('tahun-ajaran.delete'))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Tahun Ajaran Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua tahun ajaran yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/tahun-ajaran/'.$record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} tahun ajaran berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Tambah')
                    ->color('primary')
                    ->button()
                    ->visible(fn (): bool => PermissionHelper::hasResource('tahun-ajaran.create'))
                    ->modalHeading('Tambah Tahun Ajaran')
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
                        TextInput::make('nama')
                            ->label('Nama Tahun Ajaran')
                            ->placeholder('2024/2025')
                            ->maxLength(9)
                            ->required(),
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->native(false)
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->required(),
                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->native(false)
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $response = ApiService::client()
                            ->post('/tahun-ajaran', $data);

                        if ($response->status() !== 201) {
                            $this->showApiError($response);
                        } else {
                            Notification::make()
                                ->title('Tahun Ajaran Berhasil Ditambahkan')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(fn () => $this->resetTable())
                    ->extraAttributes([
                        'class' => 'text-white font-semibold',
                    ]),
            ]);
    }

    private function showApiError($response): void
    {
        try {
            $json = $response->json();
            $errors = $json['errors'] ?? [];

            if (isset($errors['message'])) {
                $message = is_array($errors['message']) ? $errors['message'][0] : $errors['message'];
            } else {
                $firstKey = array_key_first($errors);
                $message = $firstKey ? (is_array($errors[$firstKey]) ? $errors[$firstKey][0] : $errors[$firstKey]) : 'Terjadi kesalahan.';
            }

            Notification::make()
                ->title($message)
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan pada server.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.tahun-ajaran-management');
    }
}
