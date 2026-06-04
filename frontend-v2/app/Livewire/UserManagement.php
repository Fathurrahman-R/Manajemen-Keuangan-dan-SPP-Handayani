<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserManagement extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected function hasPermission(string $permission): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array($permission, $permissions);
    }

    protected function getBranchOptions(): array
    {
        $response = ApiService::client()->get('/users');
        $users = $response->collect('data');

        return $users
            ->filter(fn(array $user): bool => isset($user['branch']['id'], $user['branch']['location']))
            ->pluck('branch')
            ->unique('id')
            ->mapWithKeys(fn(array $branch): array => [$branch['id'] => $branch['location']])
            ->toArray();
    }

    protected function getRoleOptions(): array
    {
        $response = ApiService::client()->get('/roles');
        $roles = $response->collect('data');

        return $roles
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getUserFormSchema(bool $isEdit = false): array
    {
        return [
            TextInput::make('username')
                ->label('Username')
                ->required()
                ->minLength(1)
                ->maxLength(100),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required(!$isEdit)
                ->minLength(8)
                ->maxLength(100)
                ->helperText($isEdit ? 'Kosongkan jika tidak ingin mengubah password' : null),
            Select::make('branch_id')
                ->label('Cabang')
                ->options(fn(): array => $this->getBranchOptions())
                ->required()
                ->searchable(),
            CheckboxList::make('roles')
                ->label('Role')
                ->options(fn(): array => $this->getRoleOptions())
                ->required()
                ->columns(2),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search, ?string $sortColumn = null, ?string $sortDirection = null): array => ApiService::client()
                    ->get('/users')
                    ->collect('data')
                    ->when(filled($search), fn(Collection $data): Collection => $data->filter(
                        fn(array $record): bool => str_contains(Str::lower($record['username'] ?? ''), Str::lower($search))
                            || str_contains(Str::lower($record['branch']['location'] ?? ''), Str::lower($search))
                    ))
                    ->when(
                        filled($sortColumn),
                        fn(Collection $data): Collection => $data->sortBy(
                            fn(array $record) => data_get($record, $sortColumn),
                            SORT_REGULAR,
                            ($sortDirection ?? 'asc') === 'desc'
                        )->values()
                    )
                    ->toArray(),
            )
            ->columns([
                TextColumn::make('username')
                    ->label('Username')
                    ->sortable(),
                TextColumn::make('branch.location')
                    ->label('Lokasi Cabang')
                    ->formatStateUsing(function ($state, $record) {
                        return $record['branch']['location'] ?? '-';
                    }),
                TextColumn::make('roles')
                    ->label('Role')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if (isset($record['roles']) && is_array($record['roles'])) {
                            return implode(', ', $record['roles']);
                        }
                        return '-';
                    }),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn(): array => $this->getRoleOptions()),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada User')
            ->emptyStateDescription('Silahkan menambahkan user')
            ->recordActions([
                Action::make('update')
                    ->tooltip('Ubah User')
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah User')
                    ->modalWidth('2xl')
                    ->visible(fn(): bool => $this->hasPermission('update-user'))
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
                    ->fillForm(fn(array $record): array => [
                        'username' => $record['username'] ?? '',
                        'password' => '',
                        'branch_id' => $record['branch']['id'] ?? null,
                        'roles' => $record['roles'] ?? [],
                    ])
                    ->schema($this->getUserFormSchema(isEdit: true))
                    ->action(function (array $data, $record): void {
                        $payload = [
                            'username' => $data['username'],
                            'branch_id' => (int) $data['branch_id'],
                            'roles' => $data['roles'],
                        ];

                        // Only include password if it's not empty
                        if (!empty($data['password'])) {
                            $payload['password'] = $data['password'];
                        }

                        $response = ApiService::client()
                            ->put('/users/' . $record['id'], $payload);

                        if ($response->status() === 404) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('User tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($response->status() === 422) {
                            $body = $response->json();
                            $errorMessage = 'User gagal diubah.';

                            if (isset($body['errors'])) {
                                $firstError = collect($body['errors'])->flatten()->first();
                                if ($firstError) {
                                    $errorMessage = $firstError;
                                }
                            }

                            Notification::make()
                                ->title('Gagal')
                                ->body($errorMessage)
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Terjadi kesalahan pada server.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('User Berhasil Diubah')
                            ->success()
                            ->send();
                    })
                    ->after(function () {
                        $this->resetTable();
                    }),
                Action::make('delete')
                    ->tooltip('Hapus User')
                    ->icon('heroicon-s-trash')
                    ->iconButton()
                    ->color('danger')
                    ->visible(fn(): bool => $this->hasPermission('delete-user'))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus User')
                    ->modalDescription('Apakah Anda yakin ingin menghapus user ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->action(function ($record): void {
                        $response = ApiService::client()
                            ->delete('/users/' . $record['id']);

                        if ($response->status() === 404) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('User tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Terjadi kesalahan pada server.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('User Berhasil Dihapus')
                            ->success()
                            ->send();
                    })
                    ->after(function () {
                        $this->resetTable();
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => in_array('delete-user', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus User Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua user yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/users/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} user berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Tambah User')
                    ->color('primary')
                    ->button()
                    ->modalHeading('Tambah User')
                    ->modalWidth('2xl')
                    ->visible(fn(): bool => $this->hasPermission('create-user'))
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
                    ->schema($this->getUserFormSchema(isEdit: false))
                    ->action(function (array $data): void {
                        $payload = [
                            'username' => $data['username'],
                            'password' => $data['password'],
                            'branch_id' => (int) $data['branch_id'],
                            'roles' => $data['roles'],
                        ];

                        $response = ApiService::client()
                            ->post('/users', $payload);

                        if ($response->status() === 422) {
                            $body = $response->json();
                            $errorMessage = 'User gagal ditambahkan.';

                            if (isset($body['errors'])) {
                                $firstError = collect($body['errors'])->flatten()->first();
                                if ($firstError) {
                                    $errorMessage = $firstError;
                                }
                            }

                            Notification::make()
                                ->title('Gagal')
                                ->body($errorMessage)
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$response->ok() && $response->status() !== 201) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Terjadi kesalahan pada server.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('User Berhasil Ditambahkan')
                            ->success()
                            ->send();
                    })
                    ->after(function () {
                        $this->resetTable();
                    })
                    ->extraAttributes([
                        'class' => 'text-white font-semibold',
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.user-management');
    }
}
