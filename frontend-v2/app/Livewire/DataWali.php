<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DataWali extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable, HandlesApiErrors;

    public $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage, ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                    try {
                        $params = [
                            'per_page' => $this->perPage,
                            'page' => $page
                        ];

                        if (filled($search)) {
                            $params['search'] = $search;
                        }

                        if (filled($sortColumn)) {
                            $params['sort'] = $sortColumn;
                            $params['direction'] = $sortDirection ?? 'asc';
                        }

                        $response = ApiService::client()->get('/wali', $params);

                        if (!$response->ok()) {
                            $this->handleApiError($response);
                            return new LengthAwarePaginator(
                                items: [],
                                total: 0,
                                perPage: $recordsPerPage,
                                currentPage: $page,
                            );
                        }

                        $data = $response->collect();

                        return new LengthAwarePaginator(
                            items: $data['data'] ?? [],
                            total: $data['meta']['total'] ?? 0,
                            perPage: $recordsPerPage,
                            currentPage: $page,
                        );
                    } catch (ConnectionException $e) {
                        $this->notifyConnectionError();
                        return new LengthAwarePaginator(
                            items: [],
                            total: 0,
                            perPage: $recordsPerPage,
                            currentPage: $page,
                        );
                    } catch (\Throwable $e) {
                        $this->notifyUnexpectedError();
                        return new LengthAwarePaginator(
                            items: [],
                            total: 0,
                            perPage: $recordsPerPage,
                            currentPage: $page,
                        );
                    }
                }
            )
            ->columns([
                TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
                TextColumn::make('jenis_kelamin')->label('Jenis Kelamin'),
                TextColumn::make('agama')->label('Agama'),
                TextColumn::make('pendidikan_terakhir')->label('Pendidikan Terakhir'),
                TextColumn::make('pekerjaan')->label('Pekerjaan')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Wali')
            ->emptyStateDescription('Silahkan menambahkan wali')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordActions([
                Action::make('view') // Unique name for your action
                    ->tooltip('Lihat Wali')
                    ->icon('heroicon-s-eye') // Optional icon
                    ->iconButton()
                    ->fillForm(fn (array $record): array => [
                        'id' => $record['id'],
                        'nama' => $record['nama'],
                        'jenis_kelamin' => $record['jenis_kelamin'],
                        'agama' => $record['agama'],
                        'pendidikan_terakhir' => $record['pendidikan_terakhir'],
                        'pekerjaan' => $record['pekerjaan'],
                        'no_hp' => $record['no_hp'],
                        'alamat' => $record['alamat'],
                        'keterangan' => $record['keterangan'],
                    ])
                    ->url(fn (array $record): string => 'detail-wali/' . $record['id'])
                    ->color('gray'),
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Wali')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn(): bool => in_array('update-siswa', session()->get('data.permissions', [])))
                    ->modalHeading('Ubah Wali')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction()
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nama' => $record['nama'],
                        'jenis_kelamin' => $record['jenis_kelamin'],
                        'agama' => $record['agama'],
                        'pendidikan_terakhir' => $record['pendidikan_terakhir'],
                        'pekerjaan' => $record['pekerjaan'],
                        'no_hp' => $record['no_hp'],
                        'alamat' => $record['alamat'],
                        'keterangan' => $record['keterangan'],
                    ])
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required(),
                        Grid::make(2)
                            ->schema([
                                Select::make('agama')
                                    ->label('Agama')
                                    ->options([
                                        'Islam' => 'Islam',
                                        'Kristen' => 'Kristen',
                                        'Katolik' => 'Katolik',
                                        'Hindu' => 'Hindu',
                                        'Buddha' => 'Buddha',
                                        'Konghucu' => 'Konghucu',
                                    ])
                                    ->required(),
                                Select::make('jenis_kelamin')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'Laki-laki' => 'Laki-laki',
                                        'Perempuan' => 'Perempuan'
                                    ])
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('pendidikan_terakhir')
                                    ->label('Pendidikan Terakhir')
                                    ->required(),
                                TextInput::make('pekerjaan')
                                    ->label('Pekerjaan')
                                    ->required(),
                            ]),
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required(),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->required(),
                        Textarea::make('keterangan')
                            ->label('Keterangan'),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->put('/wali/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Wali Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Wali')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->visible(fn(): bool => in_array('delete-siswa', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Wali')
                    ->modalDescription('Apakah kamu yakin untuk menghapus wali ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->delete('/wali/' . $record['id']);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Wali Berhasil Dihapus')
                    ->failureNotificationTitle('Wali Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => in_array('delete-siswa', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Wali Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua wali yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/wali/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            \Filament\Notifications\Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            \Filament\Notifications\Notification::make()->title("{$success} wali berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primary') // Optional color
                    ->button()
                    ->visible(fn(): bool => in_array('create-siswa', session()->get('data.permissions', [])))
                    ->modalHeading('Tambah Kelas')
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
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required(),
                        Grid::make(2)
                            ->schema([
                                Select::make('agama')
                                    ->label('Agama')
                                    ->options([
                                        'Islam' => 'Islam',
                                        'Kristen' => 'Kristen',
                                        'Katolik' => 'Katolik',
                                        'Hindu' => 'Hindu',
                                        'Buddha' => 'Buddha',
                                        'Konghucu' => 'Konghucu',
                                    ])
                                    ->required(),
                                Select::make('jenis_kelamin')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'Laki-laki' => 'Laki-laki',
                                        'Perempuan' => 'Perempuan'
                                    ])
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('pendidikan_terakhir')
                                    ->label('Pendidikan Terakhir')
                                    ->required(),
                                TextInput::make('pekerjaan')
                                    ->label('Pekerjaan')
                                    ->required(),
                            ]),
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required(),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->required(),
                        Textarea::make('keterangan')
                            ->label('Keterangan'),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->post('/wali', $data);

                        if ($response->status() != 201) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Wali Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'text-white font-semibold'
                    ]),
            ]);
    }

    public function render()
    {
        return view('livewire.data-wali');
    }
}
