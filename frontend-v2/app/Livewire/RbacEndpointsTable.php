<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Livewire\Component;

class RbacEndpointsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $r = ApiService::client()->get('/rbac/endpoints');
                if (! $r->successful()) {
                    return [];
                }

                $records = collect($r->json()['data'] ?? [])
                    ->map(fn (array $item) => array_merge($item, ['key' => $item['id']]));

                // Manual search — Filament cannot filter array-based records automatically
                $search = $this->getTableSearch();
                if (filled($search)) {
                    $search = strtolower($search);
                    $records = $records->filter(fn ($item) =>
                        str_contains(strtolower($item['resource_key'] ?? ''), $search)
                        || str_contains(strtolower($item['group'] ?? ''), $search)
                        || str_contains(strtolower($item['description'] ?? ''), $search)
                        || str_contains(strtolower($item['permission']['name'] ?? ''), $search)
                    );
                }

                // Manual filter — SelectFilter / TernaryFilter state is ['value' => 'selected']
                $filters = $this->tableFilters ?? [];
                $groupValue = $filters['group']['value'] ?? null;
                if (filled($groupValue)) {
                    $records = $records->where('group', $groupValue);
                }
                $permissionValue = $filters['permission_id']['value'] ?? null;
                if (filled($permissionValue)) {
                    $records = $records->where('permission_id', (int) $permissionValue);
                }
                $isActiveValue = $filters['is_active']['value'] ?? null;
                if (filled($isActiveValue)) {
                    $records = $records->where('is_active', (bool) $isActiveValue);
                }

                return $records->values()->toArray();
            })
            ->heading('Endpoint Mapping (independen)')
            ->columns([
                TextColumn::make('resource_key')->label('Resource Key')->badge()->color('primary')->searchable(),
                TextColumn::make('permission.name')->label('Bound Permission')->placeholder('-')->searchable(),
                TextColumn::make('group')->label('Group')->badge()->placeholder('-')->searchable(),
                TextColumn::make('description')->label('Deskripsi')->limit(40)->placeholder('-')->searchable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->disabled(fn () => !PermissionHelper::hasResource('rbac.toggle'))
                    ->updateStateUsing(function ($state, $record): ?bool {
                        $r = ApiService::client()->put("/rbac/endpoints/{$record['id']}", [
                            'is_active' => $state,
                        ]);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal mengubah status')
                                ->danger()->send();

                            return null; // revert toggle
                        }
                        Notification::make()->title('Status endpoint diubah.')->success()->send();

                        return $state;
                    }),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn (): array => $this->getGroupOptions()),
                SelectFilter::make('permission_id')
                    ->label('Bound Permission')
                    ->options(fn (): array => $this->getPermissionOptions()),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->defaultSort('resource_key')
            ->headerActions([
                CreateAction::make('create')
                    ->label('Tambah Endpoint')
                    ->visible(fn (): bool => PermissionHelper::hasResource('endpoint-mapping.create'))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('resource_key')
                            ->label('Resource Key')
                            ->required()
                            ->placeholder('contoh: api.siswa.index'),
                        \Filament\Forms\Components\Select::make('permission_id')
                            ->label('Bind Permission')
                            ->options(function () {
                                $r = ApiService::client()->get('/rbac/permissions');

                                return $r->successful()
                                    ? collect($r->json()['data'])->pluck('name', 'id')
                                    : [];
                            })
                            ->searchable()
                            ->nullable(),
                        \Filament\Forms\Components\TextInput::make('group')->label('Group'),
                        \Filament\Forms\Components\TextInput::make('description')->label('Deskripsi'),
                        \Filament\Forms\Components\Hidden::make('is_active')->default(1),
                    ])
                    ->using(function (array $data): void {
                        $r = ApiService::client()->post('/rbac/endpoints', $data);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Endpoint dibuat.')->success()->send();
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->visible(fn (): bool => PermissionHelper::hasResource('endpoint-mapping.update'))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('resource_key')
                            ->label('Resource Key')
                            ->required(),
                        \Filament\Forms\Components\Select::make('permission_id')
                            ->label('Bind Permission')
                            ->options(function () {
                                $r = ApiService::client()->get('/rbac/permissions');

                                return $r->successful()
                                    ? collect($r->json()['data'])->pluck('name', 'id')
                                    : [];
                            })
                            ->searchable()
                            ->nullable(),
                        \Filament\Forms\Components\TextInput::make('group'),
                        \Filament\Forms\Components\TextInput::make('description'),
                    ])
                    ->fillForm(fn (array $record): array => $record)
                    ->action(function (array $data): void {
                        $record = $this->getMountedAction()?->getRecord();
                        $r = ApiService::client()->put("/rbac/endpoints/{$record['id']}", $data);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Endpoint diperbarui.')->success()->send();
                    }),
                Action::make('hapus')
                    ->color('danger')
                    ->visible(fn (): bool => PermissionHelper::hasResource('endpoint-mapping.delete'))
                    ->action(function (array $record): void {
                        $r = ApiService::client()->delete("/rbac/endpoints/{$record['id']}");
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Endpoint dihapus.')->success()->send();
                    }),
            ]);
    }

    protected function getGroupOptions(): array
    {
        $r = ApiService::client()->get('/rbac/endpoints');
        if (! $r->successful()) {
            return [];
        }

        return collect($r->json()['data'])
            ->pluck('group')
            ->unique()
            ->filter()
            ->values()
            ->mapWithKeys(fn (string $g) => [$g => $g])
            ->toArray();
    }

    protected function getPermissionOptions(): array
    {
        $r = ApiService::client()->get('/rbac/permissions');
        if (! $r->successful()) {
            return [];
        }

        return collect($r->json()['data'] ?? [])
            ->pluck('name', 'id')
            ->toArray();
    }

    public function render()
    {
        return <<<'BLADE'
            <div>{{ $this->table }}</div>
        BLADE;
    }
}
