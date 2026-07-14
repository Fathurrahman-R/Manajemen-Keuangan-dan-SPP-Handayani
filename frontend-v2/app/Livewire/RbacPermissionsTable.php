<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component;

class RbacPermissionsTable extends Component implements HasActions, HasSchemas, HasTable
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
                $r = ApiService::client()->get('/rbac/permissions');
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
                        str_contains(strtolower($item['name'] ?? ''), $search)
                        || str_contains(strtolower($item['label'] ?? ''), $search)
                        || str_contains(strtolower($item['group'] ?? ''), $search)
                        || str_contains(strtolower($item['audience'] ?? ''), $search)
                    );
                }

                // Manual filter — SelectFilter state is ['value' => 'selected_string']
                $filters = $this->tableFilters ?? [];
                $groupValue = $filters['group']['value'] ?? null;
                if (filled($groupValue)) {
                    $records = $records->where('group', $groupValue);
                }
                $audienceValue = $filters['audience']['value'] ?? null;
                if (filled($audienceValue)) {
                    $records = $records->where('audience', $audienceValue);
                }

                return $records->values()->toArray();
            })
            ->heading('Daftar Permission')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->toggleable(),
                TextColumn::make('label')->label('Label')->searchable()->toggleable()->default('—'),
                TextColumn::make('guard_name')->label('Guard')->badge()->toggleable(),
                TextColumn::make('group')->label('Grup')->searchable()->badge()->color('gray')->toggleable(),
                TextColumn::make('audience')->label('Section')->searchable()->badge()->color('gray')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn (): array => $this->getColumnFilterOptions('group')),
                SelectFilter::make('audience')
                    ->options(fn (): array => $this->getColumnFilterOptions('audience')),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make('create')
                    ->label('Permission Baru')
                    ->visible(fn () => PermissionHelper::hasResource('rbac.create'))
                    ->form([
                        TextInput::make('name')->label('Nama Permission')->rules(['required', 'string', 'max:255'])->placeholder('contoh: view-laporan-keuangan'),
                        TextInput::make('label')->label('Label Tampilan')->placeholder('Lihat Laporan Keuangan')->hint('Akan ditampilkan di checkbox pada Role Management'),
                        TextInput::make('group')->label('Nama Grup')->placeholder('contoh: Laporan Keuangan')->hint('Buat grup baru atau pakai grup yang sudah ada'),
                        TextInput::make('audience')->label('Section / Audience')->placeholder('kosongkan untuk Admin / Karyawan, atau isi misal: siswa'),
                    ])
                    ->using(function (array $data): void {
                        $r = ApiService::client()->post('/rbac/permissions', $data);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Permission created.')->success()->send();
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->visible(fn () => PermissionHelper::hasResource('rbac.edit'))
                    ->form([
                        TextInput::make('name')->label('Nama Permission')->rules(['required', 'string']),
                        TextInput::make('label')->label('Label Tampilan')->placeholder('Lihat Laporan Keuangan'),
                        TextInput::make('group')->label('Nama Grup')->placeholder('contoh: Laporan Keuangan'),
                        TextInput::make('audience')->label('Section / Audience')->placeholder('kosongkan untuk Admin / Karyawan, atau isi misal: siswa'),
                    ])
                    ->fillForm(fn (array $record): array => [
                        'name' => $record['name'],
                        'label' => $record['label'] ?? '',
                        'group' => $record['group'] ?? '',
                        'audience' => $record['audience'] ?? '',
                    ])
                    ->action(function (array $data): void {
                        $record = $this->getMountedAction()?->getRecord();
                        $r = ApiService::client()->put("/rbac/permissions/{$record['id']}", $data);
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Permission updated.')->success()->send();
                    }),
                Action::make('hapus')
                    ->color('danger')
                    ->visible(fn () => PermissionHelper::hasResource('rbac.delete'))
                    ->action(function (array $record): void {
                        $r = ApiService::client()->delete("/rbac/permissions/{$record['id']}");
                        if (! $r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Permission deleted.')->success()->send();
                    }),
            ]);
    }

    protected function getColumnFilterOptions(string $column): array
    {
        $r = ApiService::client()->get('/rbac/permissions');
        if (! $r->successful()) {
            return [];
        }

        return collect($r->json()['data'] ?? [])
            ->pluck($column)
            ->filter(fn ($v) => filled($v))
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn (string $v) => [$v => $v])
            ->toArray();
    }

    public function render()
    {
        return <<<'BLADE'
            <div>{{ $this->table }}</div>
        BLADE;
    }
}
