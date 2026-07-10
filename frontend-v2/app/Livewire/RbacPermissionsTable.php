<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Models\Permission;
use App\Services\ApiService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class RbacPermissionsTable extends Component implements HasTable, HasActions, HasSchemas
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
            ->query(Permission::query()->orderBy('name'))
            ->heading('Daftar Permission')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->toggleable(),
                TextColumn::make('label')->label('Label')->searchable()->toggleable()->default('—'),
                TextColumn::make('guard_name')->label('Guard')->badge()->toggleable(),
                TextColumn::make('group')->label('Grup')->badge()->color('gray')->toggleable(),
                TextColumn::make('audience')->label('Section')->badge()->color('gray')->toggleable(),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make('create')
                    ->label('Permission Baru')
                    ->visible(fn() => PermissionHelper::hasResource('rbac.create'))
                    ->form([
                        TextInput::make('name')->label('Nama Permission')->rules(['required', 'string', 'max:255'])->placeholder('contoh: view-laporan-keuangan'),
                        TextInput::make('label')->label('Label Tampilan')->placeholder('Lihat Laporan Keuangan')->hint('Akan ditampilkan di checkbox pada Role Management'),
                        TextInput::make('group')->label('Nama Grup')->placeholder('contoh: Laporan Keuangan')->hint('Buat grup baru atau pakai grup yang sudah ada'),
                        TextInput::make('audience')->label('Section / Audience')->placeholder('kosongkan untuk Admin / Karyawan, atau isi misal: siswa'),
                    ])
                    ->using(function (array $data): Permission {
                        $r = ApiService::client()->post('/rbac/permissions', $data);
                        if (!$r->successful()) {
                            Notification::make()->title($r->json('message') ?? 'Gagal')->danger()->send();
                            $this->halt();
                        }
                        Notification::make()->title('Permission created.')->success()->send();
                        return Permission::firstOrCreate(['name' => $data['name']], $data);
                    }),
            ])
            ->actions([
                EditAction::make('edit')
                    ->visible(fn() => PermissionHelper::hasResource('rbac.edit'))
                    ->form([
                        TextInput::make('name')->label('Nama Permission')->rules(['required', 'string']),
                        TextInput::make('label')->label('Label Tampilan')->placeholder('Lihat Laporan Keuangan'),
                        TextInput::make('group')->label('Nama Grup')->placeholder('contoh: Laporan Keuangan'),
                        TextInput::make('audience')->label('Section / Audience')->placeholder('kosongkan untuk Admin / Karyawan, atau isi misal: siswa'),
                    ])
                    ->mutateRecordDataUsing(fn(Permission $r): array => [
                        'name' => $r->name,
                        'label' => $r->label,
                        'group' => $r->group,
                        'audience' => $r->audience,
                    ])
                    ->using(function (Permission $record, array $data): Permission {
                        ApiService::client()->put("/rbac/permissions/{$record->id}", $data);
                        Notification::make()->title('Permission updated.')->success()->send();
                        return tap($record)->update($data);
                    }),
                DeleteAction::make('delete')
                    ->visible(fn() => PermissionHelper::hasResource('rbac.delete'))
                    ->using(function (Permission $record): void {
                        ApiService::client()->delete("/rbac/permissions/{$record->id}");
                        $record->delete();
                        Notification::make()->title('Permission deleted.')->success()->send();
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
