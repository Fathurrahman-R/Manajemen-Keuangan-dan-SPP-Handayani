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
                TextColumn::make('wali.nama')
                    ->label('Nama Wali')
                    ->visible(fn($livewire) => $livewire->activeTab !== 'MI'),
                TextColumn::make('ayah.nama')
                    ->label('Nama Ayah')
                    ->state(fn(array $record) => $record['ayah']['nama'] ?? '-')
                    ->hidden(fn($livewire) => $livewire->activeTab !== 'MI'),
                TextColumn::make('ibu.nama')
                    ->label('Nama Ibu')
                    ->state(fn(array $record) => $record['ibu']['nama'] ?? '-')
                    ->hidden(fn($livewire) => $livewire->activeTab !== 'MI'),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Siswa')
            ->emptyStateDescription('Silahkan menambahkan siswa')
            ->recordActions([
                Action::make('view') // Unique name for your action
                    ->tooltip('Lihat Siswa')
                    ->icon('heroicon-s-eye') // Optional icon
                    ->iconButton()
                    ->url(fn(array $record): string => 'detail-siswa/' . Str::lower($this->activeTab) . '/' . $record['id'])
                    ->color('gray'),
                // Update Siswa MI
                Action::make('update_detail') // Unique name for your action
                    ->tooltip('Ubah Siswa')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->visible(fn($livewire) => $livewire->activeTab === 'MI')
                    ->color('warning')
                    ->modalHeading('Ubah Siswa')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nis' => $record['nis'],
                        'nisn' => $record['nisn'],
                        'nama' => $record['nama'],
                        'tempat_lahir' => $record['tempat_lahir'],
                        'tanggal_lahir' => $record['tanggal_lahir'],
                        'agama' => $record['agama'],
                        'jenis_kelamin' => $record['jenis_kelamin'],
                        'kelas_id' => $record['kelas']['id'],
                        'kategori_id' => $record['kategori']['id'],
                        'alamat' => $record['alamat'],
                        'asal_sekolah' => $record['asal_sekolah'] ?? null,
                        'kelas_diterima' => $record['kelas_diterima'] ?? null,
                        'tahun_diterima' => $record['tahun_diterima'] ?? null,
                        'keterangan' => $record['keterangan'],
                        'status' => $record['status'],
                        'ayah_nama' => $record['ayah']['nama'] ?? null,
                        'ayah_pendidikan' => $record['ayah']['pendidikan_terakhir'] ?? null,
                        'ayah_pekerjaan' => $record['ayah']['pekerjaan'] ?? null,
                        'ibu_nama' => $record['ibu']['nama'] ?? null,
                        'ibu_pendidikan' => $record['ibu']['pendidikan_terakhir'] ?? null,
                        'ibu_pekerjaan' => $record['ibu']['pekerjaan'] ?? null,
                    ])
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->description('Informasi Detail Siswa')
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
                                            Select::make('kelas_id')
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
                                            Select::make('kategori_id')
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
                                            TextInput::make('tahun_diterima')
                                                ->label('Tahun Diterima')
                                                ->numeric()
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
                                ->description('Informasi Detail Ayah')
                                ->schema([
                                    TextInput::make('ayah_nama')
                                        ->label('Nama Ayah'),
                                    TextInput::make('ayah_pendidikan_terakhir')
                                        ->label('Pendidikan Terakhir'),
                                    TextInput::make('ayah_pekerjaan')
                                        ->label('Pekerjaan'),
                                ]),
                            Step::make('Data Ibu')
                                ->description('Informasi Detail Ibu')
                                ->schema([
                                    TextInput::make('ibu_nama')
                                        ->label('Nama Ibu'),
                                    TextInput::make('ibu_pendidikan_terakhir')
                                        ->label('Pendidikan Terakhir'),
                                    TextInput::make('ibu_pekerjaan')
                                        ->label('Pekerjaan'),
                                ]),
                        ])
                            ->nextAction(
                                fn(Action $action) => $action->label('Selanjutnya')->color('primaryMain')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('submit')
                                    ->label('Simpan')
                                    ->color('primaryMain')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save')
                            ),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/siswa/' . $this->activeTab . '/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }),
                // Update Siswa TK, KB
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Siswa')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah Siswa')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nis' => $record['nis'],
                        'nama' => $record['nama'],
                        'tempat_lahir' => $record['tempat_lahir'],
                        'tanggal_lahir' => $record['tanggal_lahir'],
                        'agama' => $record['agama'],
                        'jenis_kelamin' => $record['jenis_kelamin'],
                        'kelas_id' => $record['kelas']['id'],
                        'kategori_id' => $record['kategori']['id'],
                        'alamat' => $record['alamat'],
                        'keterangan' => $record['keterangan'],
                        'status' => $record['status'],
                        'wali_nama' => $record['wali']['nama'],
                        'wali_agama' => $record['wali']['agama'],
                        'wali_jenis_kelamin' => $record['wali']['jenis_kelamin'],
                        'wali_pendidikan_terakhir' => $record['wali']['pendidikan_terakhir'],
                        'wali_pekerjaan' => $record['wali']['pekerjaan'],
                        'wali_no_hp' => $record['wali']['no_hp'],
                        'wali_alamat' => $record['wali']['alamat'],
                        'wali_keterangan' => $record['wali']['keterangan'],
                    ])
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->description('Informasi Detail Siswa')
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
                                            Select::make('kelas_id')
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
                                            Select::make('kategori_id')
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
                                ->description('Informasi Detail Wali')
                                ->schema([
                                    TextInput::make('wali_nama')
                                        ->label('Nama Lengkap')
                                        ->required(),
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('wali_agama')
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
                                            Select::make('wali_jenis_kelamin')
                                                ->label('Jenis Kelamin')
                                                ->options([
                                                    'Laki-laki' => 'Laki-laki',
                                                    'Perempuan' => 'Perempuan'
                                                ])
                                                ->required(),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('wali_pendidikan_terakhir')
                                                ->label('Pendidikan Terakhir')
                                                ->required(),
                                            TextInput::make('wali_pekerjaan')
                                                ->label('Pekerjaan')
                                                ->required(),
                                        ]),
                                    TextInput::make('wali_no_hp')
                                        ->label('No. HP')
                                        ->required(),
                                    Textarea::make('wali_alamat')
                                        ->label('Alamat')
                                        ->required(),
                                    Textarea::make('wali_keterangan')
                                        ->label('Keterangan'),
                                ]),
                        ])
                            ->nextAction(
                                fn(Action $action) => $action->label('Selanjutnya')->color('primaryMain')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('save')
                                    ->label('Simpan')
                                    ->color('primaryMain')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save'),
                            )
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/siswa/' . $this->activeTab . '/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    })
                    ->visible(fn($livewire) => $livewire->activeTab !== 'MI'),
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
                // Tambah Siswa MI
                Action::make('add_detail') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Siswa')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->description('Informasi Detail Siswa')
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
                                            TextInput::make('tahun_diterima')
                                                ->label('Tahun Diterima')
                                                ->numeric()
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
                                ->description('Informasi Detail Ayah')
                                ->schema([
                                    TextInput::make('ayah_nama')
                                        ->label('Nama Ayah'),
                                    TextInput::make('ayah_pendidikan_terakhir')
                                        ->label('Pendidikan Terakhir'),
                                    TextInput::make('ayah_pekerjaan')
                                        ->label('Pekerjaan'),
                                ]),
                            Step::make('Data Ibu')
                                ->description('Informasi Detail Ibu')
                                ->schema([
                                    TextInput::make('ibu_nama')
                                        ->label('Nama Ibu'),
                                    TextInput::make('ibu_pendidikan_terakhir')
                                        ->label('Pendidikan Terakhir'),
                                    TextInput::make('ibu_pekerjaan')
                                        ->label('Pekerjaan'),
                                ]),
                        ])
                            ->nextAction(
                                fn(Action $action) => $action->label('Selanjutnya')->color('primaryMain')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('submit')
                                    ->label('Simpan')
                                    ->color('primaryMain')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save')
                            ),
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
                // Tambah Siswa TK, KB
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain')
                    ->button()
                    ->modalHeading('Tambah Siswa')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->description('Informasi Detail Siswa')
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
                                            Select::make('kelas_id')
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
                                            Select::make('kategori_id')
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
                                ->description('Informasi Detail Wali')
                                ->schema([
                                    TextInput::make('wali_nama')
                                        ->label('Nama Lengkap')
                                        ->required(),
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('wali_agama')
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
                                            Select::make('wali_jenis_kelamin')
                                                ->label('Jenis Kelamin')
                                                ->options([
                                                    'Laki-laki' => 'Laki-laki',
                                                    'Perempuan' => 'Perempuan'
                                                ])
                                                ->required(),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('wali_pendidikan_terakhir')
                                                ->label('Pendidikan Terakhir')
                                                ->required(),
                                            TextInput::make('wali_pekerjaan')
                                                ->label('Pekerjaan')
                                                ->required(),
                                        ]),
                                    TextInput::make('wali_no_hp')
                                        ->label('No. HP')
                                        ->required(),
                                    Textarea::make('wali_alamat')
                                        ->label('Alamat')
                                        ->required(),
                                    Textarea::make('wali_keterangan')
                                        ->label('Keterangan'),
                                ]),
                        ])
                            ->nextAction(
                                fn(Action $action) => $action->label('Selanjutnya')->color('primaryMain')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('save')
                                    ->label('Simpan')
                                    ->color('primaryMain')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save'),
                            )
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
        $params = [
            'per_page' => $this->perPage,
        ];

        $response = Http::withHeaders([
            'Authorization' => session()->get('data')['token']
        ])
            ->get(env('API_URL') . '/siswa/MI', $params);

        // dd($response->json());

        // dd(session()->get('data')['token']);

        return view('livewire.data-siswa');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }
}
