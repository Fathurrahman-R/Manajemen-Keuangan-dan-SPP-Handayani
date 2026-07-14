<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class RbacPagePermissionsTable extends Component implements HasActions, HasSchemas, HasTable
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
                $r = ApiService::client()->get('/rbac/page-permissions');
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
                        || str_contains(strtolower($item['permission_name'] ?? ''), $search)
                        || str_contains(strtolower($item['group'] ?? ''), $search)
                        || str_contains(strtolower($item['description'] ?? ''), $search)
                    );
                }

                // Manual filter — SelectFilter / TernaryFilter state is ['value' => 'selected']
                $filters = $this->tableFilters ?? [];
                $groupValue = $filters['group']['value'] ?? null;
                if (filled($groupValue)) {
                    $records = $records->where('group', $groupValue);
                }
                $permissionNameValue = $filters['permission_name']['value'] ?? null;
                if (filled($permissionNameValue)) {
                    $records = $records->where('permission_name', $permissionNameValue);
                }
                $isActiveValue = $filters['is_active']['value'] ?? null;
                if (filled($isActiveValue)) {
                    $records = $records->where('is_active', (bool) $isActiveValue);
                }

                return $records->values()->toArray();
            })
            ->heading('Resource & Page Registry (merged)')
            ->columns([
                TextColumn::make('resource_key')->label('Resource Key')->badge()->color('info')->searchable(),
                TextColumn::make('permission_name')->label('Bound Permission')->badge()->placeholder('-')->searchable()->toggleable(),
                TextColumn::make('group')->label('Group')->badge()->placeholder('-')->searchable()->toggleable(),
                TextColumn::make('description')->label('Deskripsi')->limit(40)->placeholder('-')->searchable()->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->updateStateUsing(function ($state, $record): ?bool {
                        $r = ApiService::client()->put("/rbac/page-permissions/{$record['id']}", [
                            'is_active' => $state,
                        ]);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal mengubah status')
                                ->danger()->send();

                            return null; // revert toggle
                        }
                        Notification::make()->title('Status resource diubah.')->success()->send();

                        return $state;
                    }),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn (): array => $this->getGroupOptions()),
                SelectFilter::make('permission_name')
                    ->label('Bound Permission')
                    ->options(fn (): array => $this->getPermissionNameOptions()),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->defaultSort('resource_key')
            ->headerActions([
                CreateAction::make('create')
                    ->label('Tambah Resource')
                    ->visible(fn () => PermissionHelper::hasResource('rbac.create'))
                    ->form([
                        TextInput::make('resource_key')
                            ->label('Resource Key')
                            ->required()
                            ->placeholder('contoh: akademik.siswa atau akademik.siswa.create'),
                        Select::make('permission_name')
                            ->label('Bind Permission')
                            ->options(function () {
                                $r = ApiService::client()->get('/rbac/permissions');

                                return $r->successful()
                                    ? collect($r->json()['data'])->pluck('name', 'name')
                                    : [];
                            })
                            ->searchable()
                            ->nullable(),
                        TextInput::make('group')->label('Group')->placeholder('akademik, keuangan, ...'),
                        TextInput::make('description')->label('Deskripsi'),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ])
                    ->using(function (array $data): void {
                        $data['guard_name'] ??= 'web';
                        $r = ApiService::client()->post('/rbac/page-permissions', $data);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Resource dibuat.')->success()->send();
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->visible(fn () => PermissionHelper::hasResource('rbac.edit'))
                    ->form([
                        TextInput::make('resource_key')
                            ->label('Resource Key')
                            ->required()->rules(['string', 'max:255']),
                        Select::make('permission_name')
                            ->label('Bind Permission')
                            ->options(function () {
                                $r = ApiService::client()->get('/rbac/permissions');

                                return $r->successful()
                                    ? collect($r->json()['data'])->pluck('name', 'name')
                                    : [];
                            })
                            ->searchable()
                            ->nullable(),
                        TextInput::make('group')->label('Group'),
                        TextInput::make('description')->label('Deskripsi'),
                        Toggle::make('is_active')->label('Aktif'),
                    ])
                    ->fillForm(fn (array $record): array => $record)
                    ->action(function (array $data): void {
                        $record = $this->getMountedAction()?->getRecord();
                        $r = ApiService::client()->put("/rbac/page-permissions/{$record['id']}", $data);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Resource diperbarui.')->success()->send();
                    }),
                Action::make('hapus')
                    ->color('danger')
                    ->visible(fn () => PermissionHelper::hasResource('rbac.delete'))
                    ->action(function (array $record): void {
                        $r = ApiService::client()->delete("/rbac/page-permissions/{$record['id']}");
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Resource dihapus.')->success()->send();
                    }),
            ]);
    }

    protected function getGroupOptions(): array
    {
        $r = ApiService::client()->get('/rbac/page-permissions');
        if (! $r->successful()) {
            return [];
        }

        return collect($r->json()['data'])
            ->where('group', '!=', null)
            ->pluck('group')
            ->unique()
            ->values()
            ->mapWithKeys(fn (string $g) => [$g => $g])
            ->toArray();
    }

    protected function getPermissionNameOptions(): array
    {
        $r = ApiService::client()->get('/rbac/permissions');
        if (! $r->successful()) {
            return [];
        }

        return collect($r->json()['data'] ?? [])
            ->pluck('name', 'name')
            ->toArray();
    }

    public function render()
    {
        return <<<'BLADE'
            <div>{{ $this->table }}</div>
        BLADE;
    }
}
