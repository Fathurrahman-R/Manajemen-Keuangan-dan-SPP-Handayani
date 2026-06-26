<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
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
    use HandlesApiErrors;

    /**
     * Cached permission groups fetched from the backend.
     * Shape: ['Audience Label' => ['Group Label' => ['permission-name' => 'Human Label', ...], ...], ...]
     */
    protected ?array $permissionAudiencesCache = null;

    /**
     * Fetch permission audiences from the backend `/roles/permissions` endpoint.
     * Result is cached per-request to avoid repeated API calls when the
     * modal is rendered alongside the table.
     */
    protected function getPermissionAudiences(): array
    {
        if ($this->permissionAudiencesCache !== null) {
            return $this->permissionAudiencesCache;
        }

        try {
            $response = ApiService::client()->get('/roles/permissions');

            if (!$response->ok()) {
                $this->handleApiError($response);
                return $this->permissionAudiencesCache = [];
            }

            $raw = $response->json('data') ?? [];
            $audiences = $raw['audiences'] ?? null;

            if (!is_array($audiences)) {
                // Backward compat: treat the whole flat payload as a single "admin" audience
                $audiences = [
                    'admin' => [
                        'label' => 'Admin / Karyawan',
                        'groups' => array_filter(
                            $raw,
                            fn($v, $k) => is_array($v) && $k !== 'audiences',
                            ARRAY_FILTER_USE_BOTH
                        ),
                    ],
                ];
            }

            $normalised = [];
            foreach ($audiences as $key => $audience) {
                $label = $audience['label'] ?? $key;
                $groups = $audience['groups'] ?? [];

                $normalisedGroups = [];
                foreach ($groups as $groupName => $permissions) {
                    if (!is_array($permissions)) {
                        continue;
                    }
                    $map = [];
                    foreach ($permissions as $perm) {
                        if (!isset($perm['name'])) {
                            continue;
                        }
                        $map[$perm['name']] = $perm['label'] ?? $perm['name'];
                    }
                    if ($map !== []) {
                        $normalisedGroups[$groupName] = $map;
                    }
                }
                if ($normalisedGroups !== []) {
                    $normalised[$key] = [
                        'label'  => $label,
                        'groups' => $normalisedGroups,
                    ];
                }
            }

            return $this->permissionAudiencesCache = $normalised;
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
            return $this->permissionAudiencesCache = [];
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
            return $this->permissionAudiencesCache = [];
        }
    }

    /**
     * Flat helper: returns ['group_label' => [...permissions...]] across all audiences.
     * Useful for collecting/distributing permissions back into form fields.
     */
    protected function getPermissionGroups(): array
    {
        $flat = [];
        foreach ($this->getPermissionAudiences() as $audience) {
            foreach ($audience['groups'] ?? [] as $group => $perms) {
                $flat[$group] = $perms;
            }
        }
        return $flat;
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

        foreach ($this->getPermissionAudiences() as $audience) {
            $audienceLabel = $audience['label'] ?? '';
            $audienceGroups = $audience['groups'] ?? [];

            $children = [];
            foreach ($audienceGroups as $group => $permissions) {
                $children[] = Section::make($group)
                    ->schema([
                        CheckboxList::make('permissions_' . Str::snake($group))
                            ->label('Permissions ' . Str::lower($group))
                            ->options($permissions)
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->collapsible();
            }

            if ($children !== []) {
                $schema[] = Section::make($audienceLabel)
                    ->description('Permissions yang relevan untuk audiens ini.')
                    ->schema($children)
                    ->collapsible()
                    ->compact();
            }
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
                function (?string $search, ?string $sortColumn = null, ?string $sortDirection = null): array {
                    try {
                        $response = ApiService::client()->get('/roles');

                        if (!$response->ok()) {
                            $this->handleApiError($response);
                            return [];
                        }

                        return $response->collect('data')
                            ->when(filled($search), fn(Collection $data): Collection => $data->filter(fn(array $record): bool => str_contains(Str::lower($record['name']), Str::lower($search))))
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
                },
            )
            ->columns([
                TextColumn::make('name')->label('Nama Role')->sortable(),
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
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Role')
            ->emptyStateDescription('Silahkan menambahkan role')
            ->emptyStateIcon('heroicon-o-document-text')
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
                                ->color('primary')
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
                Action::make('delete')
                    ->tooltip('Hapus Role')
                    ->icon('heroicon-s-trash')
                    ->iconButton()
                    ->color('danger')
                    ->visible(fn(): bool => $this->hasPermission('delete-role'))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Role')
                    ->modalDescription(fn(array $record) => "Apakah kamu yakin ingin menghapus role \"{$record['name']}\"? Tindakan ini tidak dapat dibatalkan.")
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (array $record): void {
                        $response = ApiService::client()->delete('/roles/' . $record['id']);

                        if ($response->ok()) {
                            Notification::make()
                                ->title('Role Berhasil Dihapus')
                                ->success()
                                ->send();
                        } else {
                            $body = $response->json();
                            $errorMessage = 'Role gagal dihapus.';
                            if (isset($body['errors']['message'])) {
                                $messages = $body['errors']['message'];
                                $errorMessage = is_array($messages) ? implode(' ', $messages) : $messages;
                            }
                            Notification::make()
                                ->title('Gagal')
                                ->body($errorMessage)
                                ->danger()
                                ->send();
                        }
                    })
                    ->after(fn() => $this->resetTable()),
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => in_array('delete-role', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Role Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua role yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/roles/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} role berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Tambah Role')
                    ->color('primary')
                    ->button()
                    ->modalHeading('Tambah Role')
                    ->modalWidth('4xl')
                    ->visible(fn(): bool => $this->hasPermission('create-role'))
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
