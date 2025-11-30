<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
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
use Illuminate\Support\Str;

class DataSiswa extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $activeTab = 'TK';
    public $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
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
                        ->get(env('API_URL') . '/siswa/' . $this->activeTab, $params)
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
                TextColumn::make('nis')->label('NIS')->searchable(),
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->hidden(fn($livewire) => $livewire->activeTab !== 'MI'),
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('jenis_kelamin')->label('Jenis Kelamin')->searchable(),
                TextColumn::make('tanggal_lahir')->label('Tanggal Lahir')->searchable(),
                TextColumn::make('agama')->label('Agama')->searchable(),
                TextColumn::make('wali.nama')->label('Wali')->searchable(),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Siswa')
            ->emptyStateDescription('Silahkan menambahkan siswa')
            ->recordActions([
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Siswa')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah Siswa')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction()
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nama' => $record['nama']
                    ])
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Siswa')
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/siswa/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Siswa')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Siswa')
                    ->modalDescription('Apakah kamu yakin untuk menghapus siswa ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->delete(env('API_URL') . '/siswa' . '/' . $record['id']);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Dihapus')
                    ->failureNotificationTitle('Siswa Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    })
            ])
            ->headerActions([
                Action::make('add_detail') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Siswa')
                    // ->modalFooterActions(function (Action $action) {
                    //     return [
                    //         $action->getModalSubmitAction()
                    //             ->label('Simpan')
                    //             ->color('primaryMain')
                    //             ->extraAttributes([
                    //                 'class' => 'text-white font-semibold'
                    //             ]),
                    //         $action->getModalCancelAction()->label('Batal'),
                    //     ];
                    // })
                    // ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->schema([
                                    TextInput::make('nis')
                                        ->label('NIS')
                                        ->required(),
                                    TextInput::make('nisn')
                                        ->label('NISN')
                                        ->required(),
                                    TextInput::make('nama')
                                        ->label('Nama Siswa')
                                        ->required(),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('tempate_lahir')
                                                ->label('Tempat Lahir')
                                                ->required(),
                                            DatePicker::make('tanggal_lahir')
                                                ->label('Tanggal Lahir')
                                                ->native(false)
                                                ->timezone('Asia/Jakarta')
                                                ->format('Y-m-d')
                                                ->displayFormat('d-m-Y')
                                                ->required(),
                                        ]),
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
                                            Select::make('kelas')
                                                ->label('Kelas')
                                                ->searchable()
                                                ->searchPrompt('Cari Kelas')
                                                ->options(function () {
                                                    $response = Http::withHeaders([
                                                        'Authorization' => session()->get('data')['token']
                                                    ])
                                                        ->get(env('API_URL') . '/kelas/' . $this->activeTab);

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required(),
                                            Select::make('kategori')
                                                ->label('Kategori')
                                                ->searchable()
                                                ->searchPrompt('Cari Kategori')
                                                ->options(function () {
                                                    $response = Http::withHeaders([
                                                        'Authorization' => session()->get('data')['token']
                                                    ])
                                                        ->get(env('API_URL') . '/kategori');

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required(),
                                        ]),
                                    Textarea::make('alamat')
                                        ->label('Alamat')
                                        ->required(),
                                    // Asal Sekolah
                                    TextInput::make('asal_sekolah')
                                        ->label('Asal Sekolah')
                                        ->required(),
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('kelas_diterima')
                                                ->label('Kelas Diterima')
                                                ->options([
                                                    'I' => 'I',
                                                    'II' => 'II',
                                                    'III' => 'III',
                                                    'IV' => 'IV',
                                                    'V' => 'V',
                                                    'VI' => 'VI',
                                                ])
                                                ->required(),
                                            DatePicker::make('tahun_diterima')
                                                ->label('Tahun Diterima')
                                                ->native(false)
                                                ->timezone('Asia/Jakarta')
                                                ->format('Y')
                                                ->displayFormat('Y')
                                                ->required(),
                                        ]),
                                    Textarea::make('keterangan')
                                        ->label('Keterangan'),
                                    Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'Aktif' => 'Aktif',
                                            'Lulus' => 'Lulus',
                                            'Pindah' => 'Pindah',
                                            'Keluar' => 'Keluar',
                                        ])
                                        ->required(),
                                ]),
                            Step::make('Data Ayah')
                                ->schema([
                                    TextInput::make('nama_ayah')
                                        ->label('Nama Ayah')
                                        ->required(),
                                    TextInput::make('pendidikan_terakhir_ayah')
                                        ->label('Pendidikan Terakhir')
                                        ->required(),
                                    TextInput::make('pekerjaan_ayah')
                                        ->label('Pekerjaan')
                                        ->required(),
                                ]),
                            Step::make('Data Ibu')
                                ->schema([
                                    TextInput::make('nama_ibu')
                                        ->label('Nama Ibu')
                                        ->required(),
                                    TextInput::make('pendidikan_terakhir_ibu')
                                        ->label('Pendidikan Terakhir')
                                        ->required(),
                                    TextInput::make('pekerjaan_ibu')
                                        ->label('Pekerjaan')
                                        ->required(),
                                ]),
                        ])
                            ->skippable()
                            ->submitAction(Action::make('submit')->label('Simpan')),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/siswa/' . $this->activeTab, $data);

                        if ($response->status() != 201) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'font-semibold text-white'
                    ])
                    ->visible(fn($livewire) => $livewire->activeTab === 'MI'),
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Siswa')
                    // ->modalFooterActions(function (Action $action) {
                    //     return [
                    //         $action->getModalSubmitAction()
                    //             ->label('Simpan')
                    //             ->color('primaryMain')
                    //             ->extraAttributes([
                    //                 'class' => 'text-white font-semibold'
                    //             ]),
                    //         $action->getModalCancelAction()->label('Batal'),
                    //     ];
                    // })
                    // ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->schema([
                                    TextInput::make('nis')
                                        ->label('NIS')
                                        ->required(),
                                    TextInput::make('nama')
                                        ->label('Nama Siswa')
                                        ->required(),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('tempat_lahir')
                                                ->label('Tempat Lahir')
                                                ->required(),
                                            DatePicker::make('tanggal_lahir')
                                                ->label('Tanggal Lahir')
                                                ->native(false)
                                                ->timezone('Asia/Jakarta')
                                                ->format('Y-m-d')
                                                ->displayFormat('d-m-Y')
                                                ->required(),
                                        ]),
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
                                            Select::make('kelas')
                                                ->label('Kelas')
                                                ->searchable()
                                                ->searchPrompt('Cari Kelas')
                                                ->options(function () {
                                                    $response = Http::withHeaders([
                                                        'Authorization' => session()->get('data')['token']
                                                    ])
                                                        ->get(env('API_URL') . '/kelas/' . $this->activeTab);

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required(),
                                            Select::make('kategori')
                                                ->label('Kategori')
                                                ->searchable()
                                                ->searchPrompt('Cari Kategori')
                                                ->options(function () {
                                                    $response = Http::withHeaders([
                                                        'Authorization' => session()->get('data')['token']
                                                    ])
                                                        ->get(env('API_URL') . '/kategori');

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required(),
                                        ]),
                                    Textarea::make('alamat')
                                        ->label('Alamat')
                                        ->required(),
                                    Textarea::make('keterangan')
                                        ->label('Keterangan'),
                                    Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'Aktif' => 'Aktif',
                                            'Lulus' => 'Lulus',
                                            'Pindah' => 'Pindah',
                                            'Keluar' => 'Keluar',
                                        ])
                                        ->required(),
                                ]),
                            Step::make('Data Wali')
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
                                ]),
                        ])
                            ->skippable()
                            ->submitAction(Action::make('submit')->label('Simpan')),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/siswa/' . $this->activeTab, $data);

                        if ($response->status() != 201) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'font-semibold text-white'
                    ])
                    ->visible(fn($livewire) => $livewire->activeTab !== 'MI'),
            ]);
    }

    public function render(): View
    {
        return view('livewire.data-siswa');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }
}
