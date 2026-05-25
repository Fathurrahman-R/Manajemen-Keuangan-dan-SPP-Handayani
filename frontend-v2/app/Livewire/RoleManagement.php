<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
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

class RoleManagement extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected function getPermissionGroups(): array
    {
        return [
            'Users' => [
                'view-user' => 'View User',
                'create-user' => 'Create User',
                'read-user' => 'Read User',
                'update-user' => 'Update User',
                'delete-user' => 'Delete User',
            ],
            'Siswa' => [
                'view-siswa' => 'View Siswa',
                'create-siswa' => 'Create Siswa',
                'read-siswa' => 'Read Siswa',
                'update-siswa' => 'Update Siswa',
                'delete-siswa' => 'Delete Siswa',
            ],
            'Kelas' => [
                'view-kelas' => 'View Kelas',
                'create-kelas' => 'Create Kelas',
                'read-kelas' => 'Read Kelas',
                'update-kelas' => 'Update Kelas',
                'delete-kelas' => 'Delete Kelas',
            ],
            'Kategori' => [
                'view-kategori' => 'View Kategori',
                'create-kategori' => 'Create Kategori',
                'read-kategori' => 'Read Kategori',
                'update-kategori' => 'Update Kategori',
                'delete-kategori' => 'Delete Kategori',
            ],
            'Pembayaran' => [
                'view-pembayaran' => 'View Pembayaran',
                'delete-pembayaran' => 'Delete Pembayaran',
                'print-kwitansi' => 'Print Kwitansi',
            ],
            'Jenis Tagihan' => [
                'view-jenis-tagihan' => 'View Jenis Tagihan',
                'create-jenis-tagihan' => 'Create Jenis Tagihan',
                'read-jenis-tagihan' => 'Read Jenis Tagihan',
                'update-jenis-tagihan' => 'Update Jenis Tagihan',
                'delete-jenis-tagihan' => 'Delete Jenis Tagihan',
            ],
            'Tagihan' => [
                'view-tagihan' => 'View Tagihan',
                'create-tagihan' => 'Create Tagihan',
                'read-tagihan' => 'Read Tagihan',
                'update-tagihan' => 'Update Tagihan',
                'delete-tagihan' => 'Delete Tagihan',
            ],
            'Pengeluaran' => [
                'view-pengeluaran' => 'View Pengeluaran',
                'create-pengeluaran' => 'Create Pengeluaran',
                'read-pengeluaran' => 'Read Pengeluaran',
                'update-pengeluaran' => 'Update Pengeluaran',
                'delete-pengeluaran' => 'Delete Pengeluaran',
            ],
            'Laporan' => [
                'view-kas-harian' => 'View Kas Harian',
                'view-rekap-bulanan' => 'View Rekap Bulanan',
                'export-laporan' => 'Export Laporan',
            ],
            'Roles' => [
                'view-roles' => 'View Roles',
                'create-role' => 'Create Role',
                'update-role' => 'Update Role',
                'delete-role' => 'Delete Role',
                'attach-role' => 'Attach Role',
                'detach-role' => 'Detach Role',
                'view-permissions' => 'View Permissions',
                'attach-permissions' => 'Attach Permissions',
                'detach-permissions' => 'Detach Permissions',
            ],
        ];
    }

    protected function getPermissionFormSchema(): array
    {
        $schema = [
            TextInput::make('name')
                ->label('Nama Role')
                ->required()
                ->minLength(1)
                ->maxLength(255),
        ];

        foreach ($this->getPermissionGroups() as $group => $permissions) {
            $schema[] = Section::make($group)
                ->schema([
                    CheckboxList::make('permissions_' . Str::snake($group))
                        ->label('Permissions ' . Str::lower($group))
                        ->options($permissions)
                        ->bulkToggleable()
                        ->columns(2),
                ])
                ->collapsible();
        }

        return $schema;
    }

    protected function collectPermissionsFromFormData(array $data): array
    {
        $permissions = [];
        foreach ($this->getPermissionGroups() as $group => $groupPermissions) {
            $key = 'permissions_' . Str::snake($group);
            if (isset($data[$key]) && is_array($data[$key])) {
                $permissions = array_merge($permissions, $data[$key]);
            }
        }
        return $permissions;
    }

    protected function buildFillFormData(array $record): array
    {
        $data = [
            'name' => $record['name'],
        ];

        // Get the permission names from the record
        $recordPermissions = [];
        if (isset($record['permissions']) && is_array($record['permissions'])) {
            $recordPermissions = collect($record['permissions'])->pluck('name')->toArray();
        }

        // Distribute permissions into their respective group fields
        foreach ($this->getPermissionGroups() as $group => $groupPermissions) {
            $key = 'permissions_' . Str::snake($group);
            $data[$key] = array_values(array_intersect($recordPermissions, array_keys($groupPermissions)));
        }

        return $data;
    }

    protected function hasPermission(string $permission): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array($permission, $permissions);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search): array => ApiService::client()
                    ->get('/roles')
                    ->collect('data')
                    ->when(filled($search), fn(Collection $data): Collection => $data->filter(fn(array $record): bool => str_contains(Str::lower($record['name']), Str::lower($search))))
                    ->toArray(),
            )
            ->columns([
                TextColumn::make('name')->label('Nama Role'),
                TextColumn::make('permission_list')
                    ->label('Permissions')
                    ->getStateUsing(function ($record) {
                        if (isset($record['permissions']) && is_array($record['permissions'])) {
                            return collect($record['permissions'])->pluck('name')->toArray();
                        }
                        return [];
                    })
                    ->badge()
                    ->wrap(),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Role')
            ->emptyStateDescription('Silahkan menambahkan role')
            ->recordActions([
                Action::make('update')
                    ->tooltip('Ubah Role')
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah Role')
                    ->modalWidth('4xl')
                    ->visible(fn(): bool => $this->hasPermission('update-role'))
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
                    ->fillForm(fn(array $record): array => $this->buildFillFormData($record))
                    ->schema($this->getPermissionFormSchema())
                    ->action(function (array $data, $record): void {
                        $permissions = $this->collectPermissionsFromFormData($data);

                        $response = ApiService::client()
                            ->put('/roles/' . $record['id'], [
                                'name' => $data['name'],
                                'permissions' => $permissions,
                            ]);

                        if (!$response->ok()) {
                            $body = $response->json();
                            $errorMessage = 'Role gagal diubah.';

                            if (isset($body['errors']['message'])) {
                                $messages = $body['errors']['message'];
                                $errorMessage = is_array($messages) ? implode(' ', $messages) : $messages;
                            }

                            Notification::make()
                                ->title('Gagal')
                                ->body($errorMessage)
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Role Berhasil Diubah')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(function () {
                        $this->resetTable();
                    }),
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Tambah Role')
                    ->color('primaryMain')
                    ->button()
                    ->modalHeading('Tambah Role')
                    ->modalWidth('4xl')
                    ->visible(fn(): bool => $this->hasPermission('create-role'))
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
                    ->schema($this->getPermissionFormSchema())
                    ->action(function (array $data): void {
                        $permissions = $this->collectPermissionsFromFormData($data);

                        if (empty($permissions)) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Pilih minimal satu permission.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $response = ApiService::client()
                            ->post('/roles', [
                                'name' => $data['name'],
                                'permissions' => $permissions,
                            ]);

                        if (!$response->ok() && $response->status() !== 201) {
                            $body = $response->json();
                            $errorMessage = 'Role gagal ditambahkan.';

                            if (isset($body['errors']['message'])) {
                                $messages = $body['errors']['message'];
                                $errorMessage = is_array($messages) ? implode(' ', $messages) : $messages;
                            } elseif (isset($body['errors']['name'])) {
                                $messages = $body['errors']['name'];
                                $errorMessage = is_array($messages) ? implode(' ', $messages) : $messages;
                            }

                            Notification::make()
                                ->title('Gagal')
                                ->body($errorMessage)
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Role Berhasil Ditambahkan')
                                ->success()
                                ->send();
                        }
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
        return view('livewire.role-management');
    }
}
