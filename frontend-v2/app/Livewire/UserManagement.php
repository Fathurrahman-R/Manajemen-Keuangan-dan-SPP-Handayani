<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
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
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;

class UserManagement extends Component implements HasActions, HasSchemas, HasTable
{
    use HandlesApiErrors;
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected function getBranchOptions(): array
    {
        $response = ApiService::client()->get('/users');
        $users = $response->collect('data');

        return $users
            ->filter(fn (array $user): bool => isset($user['branch']['id'], $user['branch']['location']))
            ->pluck('branch')
            ->unique('id')
            ->mapWithKeys(fn (array $branch): array => [$branch['id'] => $branch['location']])
            ->toArray();
    }

    protected function getRoleOptions(): array
    {
        $response = ApiService::client()->get('/rbac/roles');
        $roles = $response->collect('data');

        return $roles
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getUserFormSchema(bool $isEdit = false): array
    {
        $schema = [
            TextInput::make('username')
                ->label('Username')
                ->required()
                ->minLength(1)
                ->maxLength(100),
            TextInput::make('name')
                ->label('Nama Lengkap')
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255)
                ->helperText('Wajib diisi. User akan diminta verifikasi email saat login pertama kali.'),
            Select::make('branch_id')
                ->label('Cabang')
                ->options(fn (): array => $this->getBranchOptions())
                ->required()
                ->searchable(),
            CheckboxList::make('roles')
                ->label('Role')
                ->options(fn (): array => collect($this->getRoleOptions())
                    ->reject(fn ($label, $key) => $key === 'superadmin')
                    ->toArray())
                ->required()
                ->columns(2),
        ];

        if ($isEdit) {
            // Insert password field after email for edit mode
            array_splice($schema, 3, 0, [
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(100)
                    ->helperText('Kosongkan jika tidak ingin mengubah password'),
            ]);
        }

        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage, array $filters = [], ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                    try {
                        $params = [
                            'page' => $page,
                            'per_page' => $recordsPerPage,
                        ];

                        if (filled($search)) {
                            $params['search'] = $search;
                        }

                        if (! empty($filters['role']['value'])) {
                            $params['role'] = $filters['role']['value'];
                        }

                        if (filled($sortColumn)) {
                            $params['sort'] = $sortColumn;
                            $params['direction'] = $sortDirection ?? 'asc';
                        }

                        $response = ApiService::client()->get('/users', $params);

                        if (! $response->ok()) {
                            $this->handleApiError($response);

                            return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
                        }

                        $data = $response->json('data') ?? [];
                        $total = $response->json('meta.total') ?? count($data);

                        return new LengthAwarePaginator($data, $total, $recordsPerPage, $page);
                    } catch (ConnectionException $e) {
                        $this->notifyConnectionError();

                        return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
                    } catch (\Throwable $e) {
                        $this->notifyUnexpectedError();

                        return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
                    }
                },
            )
            ->columns([
                TextColumn::make('username')
                    ->label('Username')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('branch.location')
                    ->label('Cabang')
                    ->formatStateUsing(fn ($state, $record) => $record['branch']['location'] ?? '-'),
                TextColumn::make('roles')
                    ->label('Role')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function ($state, $record) {
                        if (isset($record['roles']) && is_array($record['roles'])) {
                            return implode(', ', $record['roles']);
                        }

                        return '-';
                    }),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn (): array => $this->getRoleOptions()),
            ])
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada User')
            ->emptyStateDescription('Silahkan menambahkan user')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordActions([
                Action::make('update')
                    ->tooltip('Ubah User')
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah User')
                    ->modalWidth('2xl')
                    ->visible(fn (): bool => PermissionHelper::hasResource('user-management.update'))
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primary')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold',
                                ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->fillForm(fn (array $record): array => [
                        'username' => $record['username'] ?? '',
                        'name' => $record['name'] ?? '',
                        'email' => $record['email'] ?? '',
                        'password' => '',
                        'branch_id' => $record['branch']['id'] ?? null,
                        'roles' => $record['roles'] ?? [],
                    ])
                    ->schema($this->getUserFormSchema(isEdit: true))
                    ->action(function (array $data, $record): void {
                        $payload = [
                            'username' => $data['username'],
                            'name' => $data['name'] ?? null,
                            'email' => $data['email'] ?: null,
                            'branch_id' => (int) $data['branch_id'],
                            'roles' => $data['roles'],
                        ];

                        // Only include password if it's not empty
                        if (! empty($data['password'])) {
                            $payload['password'] = $data['password'];
                        }

                        $response = ApiService::client()
                            ->put('/users/'.$record['id'], $payload);

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

                        if (! $response->ok()) {
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
                Action::make('toggleActive')
                    ->tooltip(fn ($record) => ($record['is_active'] ?? false) ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn ($record) => ($record['is_active'] ?? false) ? 'heroicon-s-x-circle' : 'heroicon-s-check-circle')
                    ->iconButton()
                    ->color(fn ($record) => ($record['is_active'] ?? false) ? 'danger' : 'success')
                    ->visible(fn (): bool => PermissionHelper::hasResource('user-management.update'))
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record['is_active'] ?? false) ? 'Nonaktifkan User' : 'Aktifkan User')
                    ->modalDescription(fn ($record) => ($record['is_active'] ?? false)
                        ? 'User yang dinonaktifkan tidak akan bisa login. Token aktif akan dicabut.'
                        : 'User akan diaktifkan kembali dan bisa login.')
                    ->modalSubmitActionLabel('Ya')
                    ->action(function ($record): void {
                        $response = ApiService::client()->patch('/users/'.$record['id'].'/toggle-active');

                        if (! $response->ok()) {
                            Notification::make()->title('Gagal')->body('Terjadi kesalahan pada server.')->danger()->send();

                            return;
                        }

                        $data = $response->json('data');
                        $status = ($data['is_active'] ?? false) ? 'diaktifkan' : 'dinonaktifkan';
                        Notification::make()->title('Berhasil')->body("User berhasil {$status}.")->success()->send();
                    })
                    ->after(fn () => $this->resetTable()),
                Action::make('delete')
                    ->tooltip('Hapus User')
                    ->icon('heroicon-s-trash')
                    ->iconButton()
                    ->color('danger')
                    ->visible(fn ($record): bool => PermissionHelper::hasResource('user-management.delete')
                        && ! in_array('superadmin', $record['roles'] ?? []))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus User')
                    ->modalDescription('Apakah Anda yakin ingin menghapus user ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->action(function ($record): void {
                        $response = ApiService::client()
                            ->delete('/users/'.$record['id']);

                        if ($response->status() === 404) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('User tidak ditemukan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (! $response->ok()) {
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
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => PermissionHelper::hasResource('user-management.delete'))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus User Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua user yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/users/'.$record['id']);
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
                    ->visible(fn (): bool => PermissionHelper::hasResource('user-management.create'))
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primary')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold',
                                ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->schema($this->getUserFormSchema(isEdit: false))
                    ->action(function (array $data): void {
                        $payload = [
                            'username' => $data['username'],
                            'name' => $data['name'] ?? null,
                            'email' => $data['email'] ?: null,
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

                        if (! $response->ok() && $response->status() !== 201) {
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
