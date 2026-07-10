<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Models\PagePermission;
use App\Services\ApiService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Livewire\Component;

class RbacPagePermissionsTable extends Component implements HasTable, HasActions, HasSchemas
{
    use InteractsWithTable;
    use InteractsWithSchemas;
    use InteractsWithActions;

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PagePermission::query()->orderBy('resource_key'))
            ->heading('Resource & Page Registry')
            ->columns([
                TextColumn::make('resource_key')->label('Resource Key')->badge()->color('info')->searchable(),
                TextColumn::make('permission_name')->label('Bound Permission')->badge()->placeholder('-')->toggleable(),
                TextColumn::make('group')->label('Group')->badge()->placeholder('-')->toggleable(),
                TextColumn::make('description')->label('Deskripsi')->limit(40)->placeholder('-')->toggleable(),
                IconColumn::make('is_active')->label('Aktif')->boolean()->toggleable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('group')
                    ->options(PagePermission::whereNotNull('group')->pluck('group', 'group')->unique()),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->defaultSort('resource_key')
            ->groups([
                Group::make('group'),
            ])
            ->headerActions([
                CreateAction::make('create')
                    ->label('Tambah Resource')
                    ->visible(fn() => PermissionHelper::hasResource('rbac.create'))
                    ->form([
                        TextInput::make('resource_key')
                            ->label('Resource Key')
                            ->required()
                            ->unique('page_permissions', 'resource_key')
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
                    ->using(function (array $data): PagePermission {
                        $data['guard_name'] ??= 'web';
                        $r = ApiService::client()->post('/rbac/page-permissions', $data);
                        if (!$r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Resource dibuat.')->success()->send();
                        return PagePermission::create($data);
                    }),
            ])
            ->actions([
                EditAction::make('edit')
                    ->visible(fn() => PermissionHelper::hasResource('rbac.edit'))
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
                    ->mutateRecordDataUsing(fn(PagePermission $r): array => $r->toArray())
                    ->using(function (PagePermission $record, array $data): PagePermission {
                        ApiService::client()->put("/rbac/page-permissions/{$record->id}", $data);
                        Notification::make()->title('Resource diperbarui.')->success()->send();
                        return tap($record)->update($data);
                    }),
                DeleteAction::make('delete')
                    ->visible(fn() => PermissionHelper::hasResource('rbac.delete'))
                    ->using(function (PagePermission $record): void {
                        ApiService::client()->delete("/rbac/page-permissions/{$record->id}");
                        $record->delete();
                        Notification::make()->title('Resource dihapus.')->success()->send();
                    }),
            ]);
    }

    public function render()
    {
        return <<<'BLADE'
            <div>{{ $this->table }}</div>
        BLADE;
    }
}
