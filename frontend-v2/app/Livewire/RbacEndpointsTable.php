<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Models\PermissionEndpoint;
use App\Services\ApiService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class RbacEndpointsTable extends Component implements HasTable, HasActions, HasSchemas
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
            ->query(PermissionEndpoint::with('permission'))
            ->heading('Endpoint Mapping (independen)')
            ->columns([
                TextColumn::make('resource_key')->label('Resource Key')->badge()->color('primary')->searchable(),
                TextColumn::make('permission.name')->label('Bound Permission')->placeholder('-')->searchable(),
                TextColumn::make('group')->label('Group')->badge()->placeholder('-'),
                TextColumn::make('description')->label('Deskripsi')->limit(40)->placeholder('-'),
                IconColumn::make('is_active')->boolean()->toggleable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('group')
                    ->options(PermissionEndpoint::pluck('group','group')->unique()->filter()),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->groups([
                \Filament\Tables\Grouping\Group::make('group'),
            ])
            ->defaultSort('resource_key')
            ->headerActions([
                CreateAction::make('create')
                    ->label('Tambah Endpoint')
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
                    ->using(function (array $data): PermissionEndpoint {
                        $r = ApiService::client()->post('/rbac/endpoints', $data);
                        if ($r->successful()) {
                            Notification::make()->title('Endpoint dibuat.')->success()->send();
                        } else {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        return PermissionEndpoint::create($data);
                    }),
            ])
            ->actions([
                EditAction::make('edit')
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
                    ->mutateRecordDataUsing(fn(PermissionEndpoint $r): array => $r->toArray())
                    ->using(function (PermissionEndpoint $record, array $data): PermissionEndpoint {
                        ApiService::client()->put("/rbac/endpoints/{$record->id}", $data);
                        Notification::make()->title('Endpoint diperbarui.')->success()->send();
                        return tap($record)->update($data);
                    }),
                DeleteAction::make('delete')
                    ->using(function (PermissionEndpoint $record): void {
                        ApiService::client()->delete("/rbac/endpoints/{$record->id}");
                        $record->delete();
                        Notification::make()->title('Endpoint dihapus.')->success()->send();
                    }),
                Action::make('toggle_active')
                    ->label(fn(PermissionEndpoint $r): string => $r->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->color(fn(PermissionEndpoint $r): string => $r->is_active ? 'warning' : 'success')
                    ->icon(fn(PermissionEndpoint $r): string => $r->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->action(fn(PermissionEndpoint $record): PermissionEndpoint => tap($record)->update(['is_active' => !$record->is_active])),
            ]);
    }

    public function render()
    {
        return <<<'BLADE'
            <div>{{ $this->table }}</div>
        BLADE;
    }
}
