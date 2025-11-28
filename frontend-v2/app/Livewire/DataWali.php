<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class DataWali extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
                    $skip = ($page - 1) * $recordsPerPage;
                    
                    $params = [
                        'per_page' => $this->perPage,
                        'page' => $page
                    ];
                    
                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    $response = Http::withHeaders([
                        'Authorization' => session()->get('data')['token']
                    ])
                    ->get(env('API_URL') . '/wali', $params)
                    ->collect();

                    return new LengthAwarePaginator(
                        items: $response['data'] ?? [],
                        total: $response['meta']['total'] ?? 0,
                        perPage: $recordsPerPage,
                        currentPage: $page,
                    );
                }
            )
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('jenis_kelamin')->label('Jenis Kelamin'),
                TextColumn::make('agama')->label('Agama'),
                TextColumn::make('pendidikan_terakhir')->label('Pendidikan Terakhir'),
                TextColumn::make('pekerjaan')->label('Pekerjaan'),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(2)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Wali')
            ->emptyStateDescription('Silahkan menambahkan wali')
            ->recordActions([
                Action::make('view') // Unique name for your action
                    ->tooltip('View Wali')
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
                    ->tooltip('Edit Kelas')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Edit Kelas')
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
                                        'Protestan' => 'Protestan',
                                        'Katolik' => 'Katolik',
                                        'Hindu' => 'Hindu',
                                        'Budha' => 'Budha',
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
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/wali/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Wali Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Delete Wali')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Wali')
                    ->modalDescription('Apakah kamu yakin untuk menghapus wali ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->delete(env('API_URL') . '/wali/' . $record['id']);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Wali Berhasil Dihapus')
                    ->failureNotificationTitle('Wali Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    })
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Kelas')
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
                                        'Protestan' => 'Protestan',
                                        'Katolik' => 'Katolik',
                                        'Hindu' => 'Hindu',
                                        'Budha' => 'Budha',
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
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/wali', $data);

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
