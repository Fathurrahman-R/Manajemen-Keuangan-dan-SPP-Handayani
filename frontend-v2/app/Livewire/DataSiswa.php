<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\ApiService;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\HasImportExport;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DataSiswa extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable, HasImportExport, HandlesApiErrors;

    public $activeTab = 'KB';
    public $perPage = 5;
    public $currentPage = 1;
    public ?int $kelasId = null;

    public function mount(string $jenjang = 'KB'): void
    {
        $this->activeTab = $jenjang;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage, array $filters = [], ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                    try {
                        $this->perPage = $recordsPerPage;
                        $this->currentPage = $page;
                        $params = [
                            'per_page' => $this->perPage,
                            'page' => $this->currentPage,
                        ];

                        if (filled($search)) {
                            $params['search'] = $search;
                        }

                        if (!is_null($this->kelasId)) {
                            $params['kelas_id'] = $this->kelasId;
                        }

                        if (!empty($filters['status']['value'] ?? null)) {
                            $params['status'] = $filters['status']['value'];
                        }

                        if (filled($sortColumn)) {
                            $params['sort'] = $sortColumn;
                            $params['direction'] = $sortDirection ?? 'asc';
                        }

                        $response = ApiService::client()
                            ->get('/siswa/' . $this->activeTab, $params);

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
                TextColumn::make('nis')->label(__('NIS'))->searchable(),
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->hidden(fn($livewire) => $livewire->activeTab !== 'MI'),
                TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
                TextColumn::make('kelas.nama')->label(__('Kelas')),
                TextColumn::make('jenis_kelamin')->label('Jenis Kelamin')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_lahir')->label('Tanggal Lahir')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('agama')->label('Agama')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wali.nama')
                    ->label('Nama Wali')
                    ->visible(fn($livewire) => $livewire->activeTab !== 'MI')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ayah.nama')
                    ->label('Nama Ayah')
                    ->state(fn(array $record) => $record['ayah']['nama'] ?? '-')
                    ->hidden(fn($livewire) => $livewire->activeTab !== 'MI')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ibu.nama')
                    ->label('Nama Ibu')
                    ->state(fn(array $record) => $record['ibu']['nama'] ?? '-')
                    ->hidden(fn($livewire) => $livewire->activeTab !== 'MI')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Lulus' => 'Lulus',
                        'Pindah' => 'Pindah',
                        'Keluar' => 'Keluar',
                    ]),
            ])
            ->emptyStateHeading('Tidak Ada Siswa')
            ->emptyStateDescription('Silahkan menambahkan siswa')
            ->emptyStateIcon('heroicon-o-document-text')
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
                    ->visible(fn($livewire) => $livewire->activeTab === 'MI' && in_array('update-siswa', session()->get('data.permissions', [])))
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
                        'ayah_pendidikan_terakhir' => $record['ayah']['pendidikan_terakhir'] ?? null,
                        'ayah_pekerjaan' => $record['ayah']['pekerjaan'] ?? null,
                        'ibu_nama' => $record['ibu']['nama'] ?? null,
                        'ibu_pendidikan_terakhir' => $record['ibu']['pendidikan_terakhir'] ?? null,
                        'ibu_pekerjaan' => $record['ibu']['pekerjaan'] ?? null,
                    ])
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->description('Informasi Detail Siswa')
                                ->schema([
                                    TextInput::make('nis')
                                        ->label(__('NIS'))
                                        ->required(),
                                    TextInput::make('nisn')
                                        ->label(__('NISN'))
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
                                                    $response = ApiService::client()
                        ->get('/kelas/' . $this->activeTab);

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
                                                    $response = ApiService::client()
                        ->get('/kategori');

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
                                fn(Action $action) => $action->label('Selanjutnya')->color('primary')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('submit')
                                    ->label('Simpan')
                                    ->color('primary')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save')
                            ),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->put('/siswa/' . $this->activeTab . '/' . $record['id'], $data);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Siswa Gagal Diubah')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Siswa Berhasil Diubah')
                                ->success()
                                ->send();
                        }
                    })
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
                        'tempat_lahir' => $record['tempat_lahir'] ?? '',
                        'tanggal_lahir' => $record['tanggal_lahir'] ?? '',
                        'agama' => $record['agama'] ?? '',
                        'jenis_kelamin' => $record['jenis_kelamin'] ?? '',
                        'kelas_id' => $record['kelas']['id'] ?? null,
                        'kategori_id' => $record['kategori']['id'] ?? null,
                        'alamat' => $record['alamat'] ?? '',
                        'keterangan' => $record['keterangan'] ?? '',
                        'status' => $record['status'] ?? '',
                        'wali_nama' => $record['wali']['nama'] ?? '',
                        'wali_jenis_kelamin' => $record['wali']['jenis_kelamin'] ?? '',
                        'wali_pendidikan_terakhir' => $record['wali']['pendidikan_terakhir'] ?? '',
                        'wali_pekerjaan' => $record['wali']['pekerjaan'] ?? '',
                        'wali_no_hp' => $record['wali']['no_hp'] ?? '',
                        'wali_alamat' => $record['wali']['alamat'] ?? '',
                        'wali_keterangan' => $record['wali']['keterangan'] ?? '',
                    ])
                    ->schema([
                        Wizard::make([
                            Step::make('Data Siswa')
                                ->description('Informasi Detail Siswa')
                                ->schema([
                                    TextInput::make('nis')
                                        ->label(__('NIS'))
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
                                                    $response = ApiService::client()
                        ->get('/kelas/' . $this->activeTab);

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
                                                    $response = ApiService::client()
                        ->get('/kategori');

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
                                    TextInput::make('wali_pekerjaan')
                                        ->label('Pekerjaan')
                                        ->required(),
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
                                fn(Action $action) => $action->label('Selanjutnya')->color('primary')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('save')
                                    ->label('Simpan')
                                    ->color('primary')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save'),
                            )
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->put('/siswa/' . $this->activeTab . '/' . $record['id'], $data);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Siswa Gagal Diubah')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Siswa Berhasil Diubah')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(function () {
                        $this->resetTable();
                    })
                    ->visible(fn($livewire) => $livewire->activeTab !== 'MI' && in_array('update-siswa', session()->get('data.permissions', []))),
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Siswa')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->visible(fn(): bool => in_array('delete-siswa', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Siswa')
                    ->modalDescription('Apakah kamu yakin untuk menghapus siswa ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->delete('/siswa/' . $this->activeTab . '/' . $record['id']);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Siswa Gagal Dihapus')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Siswa Berhasil Dihapus')
                                ->success()
                                ->send();
                        }
                    })
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
                    ->modalHeading('Hapus Siswa Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua siswa yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/siswa/' . $this->activeTab . '/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} siswa berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                // Kelas filter tied to active tab
                Action::make('filter_kelas')
                    ->label('Filter Kelas')
                    ->color('gray')
                    ->button()
                    ->modalHeading('Filter Kelas')
                    ->modalSubmitActionLabel('Terapkan')
                    ->modalCancelActionLabel('Batal')
                    ->schema([
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->searchable()
                            ->searchPrompt('Cari Kelas')
                            ->options(function () {
                                $response = ApiService::client()->get('/kelas/' . $this->activeTab);

                                if (!$response->ok()) {
                                    return [];
                                }

                                $data = $response->json();

                                return collect($data['data'])->mapWithKeys(fn($item) => [$item['id'] => $item['nama']])->toArray();
                            })
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->kelasId = (int) $data['kelas_id'];
                        $this->resetTable();
                    }),

                // Clear kelas filter
                Action::make('clear_kelas_filter')
                    ->label('Hapus Filter Kelas')
                    ->color('gray')
                    ->button()
                    ->action(function (): void {
                        $this->kelasId = null;
                        $this->resetTable();
                    }),

                // Import/Export Siswa
                ...$this->makeImportExportActions('siswa', [
                    Select::make('jenjang')
                        ->label('Jenjang')
                        ->options(['TK' => 'TK', 'MI' => 'MI', 'KB' => 'KB']),
                    Select::make('status')
                        ->label('Status')
                        ->options(['Aktif' => 'Aktif', 'Lulus' => 'Lulus', 'Pindah' => 'Pindah', 'Keluar' => 'Keluar']),
                ]),

                // Tambah Siswa MI
                Action::make('add_detail') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primary') // Optional color
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
                                        ->label(__('NIS'))
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'NIS Tidak Boleh Kosong'
                                        ]),
                                    TextInput::make('nisn')
                                        ->label('NISN')
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'NISN Tidak Boleh Kosong'
                                        ]),
                                    TextInput::make('nama')
                                        ->label('Nama Siswa')
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Nama Tidak Boleh Kosong'
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('tempat_lahir')
                                                ->label('Tempat Lahir')
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Tempat Lahir Tidak Boleh Kosong'
                                                ]),
                                            DatePicker::make('tanggal_lahir')
                                                ->label('Tanggal Lahir')
                                                ->native(false)
                                                ->timezone('Asia/Jakarta')
                                                ->format('Y-m-d')
                                                ->displayFormat('d-m-Y')
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Tanggal Lahir Tidak Boleh Kosong'
                                                ]),
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
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Agama Tidak Boleh Kosong'
                                                ]),
                                            Select::make('jenis_kelamin')
                                                ->label('Jenis Kelamin')
                                                ->options([
                                                    'Laki-laki' => 'Laki-laki',
                                                    'Perempuan' => 'Perempuan'
                                                ])
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Jenis Kelamin Tidak Boleh Kosong'
                                                ]),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('kelas_id')
                                                ->label('Kelas')
                                                ->searchable()
                                                ->searchPrompt('Cari Kelas')
                                                ->options(function () {
                                                    $response = ApiService::client()
                        ->get('/kelas/' . $this->activeTab);

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Kelas Tidak Boleh Kosong'
                                                ]),
                                            Select::make('kategori_id')
                                                ->label('Kategori')
                                                ->searchable()
                                                ->searchPrompt('Cari Kategori')
                                                ->options(function () {
                                                    $response = ApiService::client()
                        ->get('/kategori');

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Kategori Tidak Boleh Kosong'
                                                ]),
                                        ]),
                                    Textarea::make('alamat')
                                        ->label('Alamat')
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Alamat Tidak Boleh Kosong'
                                        ]),
                                    // Asal Sekolah
                                    TextInput::make('asal_sekolah')
                                        ->label('Asal Sekolah')
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Asal Sekolah Tidak Boleh Kosong'
                                        ]),
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
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Kelas Diterima Tidak Boleh Kosong'
                                                ]),
                                            TextInput::make('tahun_diterima')
                                                ->label('Tahun Diterima')
                                                ->numeric()
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Tahun Diterima Tidak Boleh Kosong'
                                                ]),
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
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Status Tidak Boleh Kosong'
                                        ]),
                                ]),
                            Step::make('Data Ayah')
                                ->description('Informasi Detail Ayah')
                                ->schema([
                                    Select::make('ayah_id')
                                        ->label('Pilih Ayah yang Sudah Ada')
                                        ->searchable()
                                        ->searchPrompt('Cari nama ayah...')
                                        ->getSearchResultsUsing(function (string $search): array {
                                            if (strlen($search) < 2) {
                                                return [];
                                            }
                                            $response = ApiService::client()
                                                ->get('/ayah', ['search' => $search]);
                                            if (!$response->ok()) {
                                                return [];
                                            }
                                            $data = $response->json('data') ?? [];
                                            return collect($data)->mapWithKeys(function ($item) {
                                                $label = $item['nama'];
                                                if (!empty($item['pekerjaan'])) {
                                                    $label .= ' - ' . $item['pekerjaan'];
                                                }
                                                return [$item['id'] => $label];
                                            })->toArray();
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            if ($state) {
                                                // Clear manual fields when an existing ayah is selected
                                                $set('ayah_nama', null);
                                                $set('ayah_pendidikan_terakhir', null);
                                                $set('ayah_pekerjaan', null);
                                            }
                                        })
                                        ->helperText('Pilih ayah yang sudah ada di sistem, atau kosongkan untuk input manual di bawah.')
                                        ->placeholder('Ketik nama ayah untuk mencari...'),
                                    TextInput::make('ayah_nama')
                                        ->label('Nama Ayah')
                                        ->hidden(fn ($get) => filled($get('ayah_id'))),
                                    TextInput::make('ayah_pendidikan_terakhir')
                                        ->label('Pendidikan Terakhir')
                                        ->hidden(fn ($get) => filled($get('ayah_id'))),
                                    TextInput::make('ayah_pekerjaan')
                                        ->label('Pekerjaan')
                                        ->hidden(fn ($get) => filled($get('ayah_id'))),
                                ]),
                            Step::make('Data Ibu')
                                ->description('Informasi Detail Ibu')
                                ->schema([
                                    Select::make('ibu_id')
                                        ->label('Pilih Ibu yang Sudah Ada')
                                        ->searchable()
                                        ->searchPrompt('Cari nama ibu...')
                                        ->getSearchResultsUsing(function (string $search): array {
                                            if (strlen($search) < 2) {
                                                return [];
                                            }
                                            $response = ApiService::client()
                                                ->get('/ibu', ['search' => $search]);
                                            if (!$response->ok()) {
                                                return [];
                                            }
                                            $data = $response->json('data') ?? [];
                                            return collect($data)->mapWithKeys(function ($item) {
                                                $label = $item['nama'];
                                                if (!empty($item['pekerjaan'])) {
                                                    $label .= ' - ' . $item['pekerjaan'];
                                                }
                                                return [$item['id'] => $label];
                                            })->toArray();
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            if ($state) {
                                                // Clear manual fields when an existing ibu is selected
                                                $set('ibu_nama', null);
                                                $set('ibu_pendidikan_terakhir', null);
                                                $set('ibu_pekerjaan', null);
                                            }
                                        })
                                        ->helperText('Pilih ibu yang sudah ada di sistem, atau kosongkan untuk input manual di bawah.')
                                        ->placeholder('Ketik nama ibu untuk mencari...'),
                                    TextInput::make('ibu_nama')
                                        ->label('Nama Ibu')
                                        ->hidden(fn ($get) => filled($get('ibu_id'))),
                                    TextInput::make('ibu_pendidikan_terakhir')
                                        ->label('Pendidikan Terakhir')
                                        ->hidden(fn ($get) => filled($get('ibu_id'))),
                                    TextInput::make('ibu_pekerjaan')
                                        ->label('Pekerjaan')
                                        ->hidden(fn ($get) => filled($get('ibu_id'))),
                                ]),
                        ])
                            ->nextAction(
                                fn(Action $action) => $action->label('Selanjutnya')->color('primary')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('submit')
                                    ->label('Simpan')
                                    ->color('primary')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save')
                            ),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->post('/siswa/' . $this->activeTab, $data);

                        if ($response->status() != 201) {
                            $errors = collect($response->json('errors') ?? [])->flatten()->implode(', ');
                            Notification::make()
                                ->title('Siswa Gagal Ditambahkan')
                                ->body($errors ?: ($response->json('message') ?? 'Terjadi kesalahan.'))
                                ->danger()
                                ->send();
                        } else {
                            try {
                                User::create([
                                    'name' =>  $data['nama'],
                                    'username' => $data['nis'],
                                    'password' => Hash::make($data['tanggal_lahir']),
                                ]);
                            } catch (\Throwable $th) {
                            }
                            
                            Notification::make()
                                ->title('Siswa Berhasil Ditambahkan')
                                ->success()
                                ->send();
                        }
                    })
                    ->extraAttributes([
                        'class' => 'font-semibold text-white',
                        'id' => 'add-detail'
                    ])
                    ->visible(fn($livewire) => $livewire->activeTab === 'MI' && in_array('create-siswa', session()->get('data.permissions', []))),
                // Tambah Siswa TK, KB
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primary')
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
                                        ->label(__('NIS'))
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'NIS Tidak Boleh Kosong'
                                        ]),
                                    TextInput::make('nama')
                                        ->label('Nama Siswa')
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Nama Siswa Tidak Boleh Kosong'
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('tempat_lahir')
                                                ->label('Tempat Lahir')
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Tempat Lahir Tidak Boleh Kosong'
                                                ]),
                                            DatePicker::make('tanggal_lahir')
                                                ->label('Tanggal Lahir')
                                                ->native(false)
                                                ->timezone('Asia/Jakarta')
                                                ->format('Y-m-d')
                                                ->displayFormat('d-m-Y')
                                                ->maxDate(now())
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Tanggal Lahir Tidak Boleh Kosong'
                                                ]),
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
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Agama Tidak Boleh Kosong'
                                                ]),
                                            Select::make('jenis_kelamin')
                                                ->label('Jenis Kelamin')
                                                ->options([
                                                    'Laki-laki' => 'Laki-laki',
                                                    'Perempuan' => 'Perempuan'
                                                ])
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Jenis Kelamin Tidak Boleh Kosong'
                                                ]),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('kelas_id')
                                                ->label('Kelas')
                                                ->searchable()
                                                ->searchPrompt('Cari Kelas')
                                                ->options(function () {
                                                    $response = ApiService::client()
                        ->get('/kelas/' . $this->activeTab);

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Kelas Tidak Boleh Kosong'
                                                ]),
                                            Select::make('kategori_id')
                                                ->label('Kategori')
                                                ->searchable()
                                                ->searchPrompt('Cari Kategori')
                                                ->options(function () {
                                                    $response = ApiService::client()
                        ->get('/kategori');

                                                    if (!$response->ok()) {
                                                        return [];
                                                    }

                                                    $data = $response->json();

                                                    $options = collect($data['data'])->mapWithKeys(function ($item) {
                                                        return [$item['id'] => $item['nama']];
                                                    })->toArray();

                                                    return $options;
                                                })
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Kategori Tidak Boleh Kosong'
                                                ]),
                                        ]),
                                    Textarea::make('alamat')
                                        ->label('Alamat')
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Alamat Tidak Boleh Kosong'
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
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Status Tidak Boleh Kosong'
                                        ]),
                                ]),
                            Step::make('Data Wali')
                                ->description('Informasi Detail Wali')
                                ->schema([
                                    Select::make('wali_id')
                                        ->label('Pilih Wali yang Sudah Ada')
                                        ->searchable()
                                        ->searchPrompt('Cari nama wali...')
                                        ->getSearchResultsUsing(function (string $search): array {
                                            if (strlen($search) < 2) {
                                                return [];
                                            }
                                            $response = ApiService::client()
                                                ->get('/wali', ['search' => $search]);
                                            if (!$response->ok()) {
                                                return [];
                                            }
                                            $data = $response->json('data') ?? [];
                                            return collect($data)->mapWithKeys(function ($item) {
                                                $label = $item['nama'];
                                                if (!empty($item['pekerjaan'])) {
                                                    $label .= ' - ' . $item['pekerjaan'];
                                                }
                                                return [$item['id'] => $label];
                                            })->toArray();
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            if ($state) {
                                                // Clear manual fields when an existing wali is selected
                                                $set('wali_nama', null);
                                                $set('wali_pekerjaan', null);
                                                $set('wali_no_hp', null);
                                                $set('wali_alamat', null);
                                                $set('wali_keterangan', null);
                                            }
                                        })
                                        ->helperText('Pilih wali yang sudah ada di sistem, atau kosongkan untuk input manual di bawah.')
                                        ->placeholder('Ketik nama wali untuk mencari...'),
                                    TextInput::make('wali_nama')
                                        ->label('Nama Lengkap')
                                        ->required(fn ($get) => !filled($get('wali_id')))
                                        ->hidden(fn ($get) => filled($get('wali_id')))
                                        ->validationMessages([
                                            'required' => 'Nama Tidak Boleh Kosong'
                                        ]),
                                    TextInput::make('wali_pekerjaan')
                                        ->label('Pekerjaan')
                                        ->required(fn ($get) => !filled($get('wali_id')))
                                        ->hidden(fn ($get) => filled($get('wali_id')))
                                        ->validationMessages([
                                            'required' => 'Pekerjaan Tidak Boleh Kosong'
                                        ]),
                                    TextInput::make('wali_no_hp')
                                        ->label('No. HP')
                                        ->required(fn ($get) => !filled($get('wali_id')))
                                        ->hidden(fn ($get) => filled($get('wali_id')))
                                        ->validationMessages([
                                            'required' => 'No. HP Tidak Boleh Kosong'
                                        ]),
                                    Textarea::make('wali_alamat')
                                        ->label('Alamat')
                                        ->required(fn ($get) => !filled($get('wali_id')))
                                        ->hidden(fn ($get) => filled($get('wali_id')))
                                        ->validationMessages([
                                            'required' => 'Alamat Tidak Boleh Kosong'
                                        ]),
                                    Textarea::make('wali_keterangan')
                                        ->label('Keterangan')
                                        ->hidden(fn ($get) => filled($get('wali_id'))),
                                ]),
                        ])
                            ->nextAction(
                                fn(Action $action) => $action->label('Selanjutnya')->color('primary')->extraAttributes([
                                    'class' => 'font-semibold'
                                ]),
                            )
                            ->previousAction(
                                fn(Action $action) => $action->label('Sebelumnya')
                            )
                            ->submitAction(
                                Action::make('save')
                                    ->label('Simpan')
                                    ->color('primary')
                                    ->extraAttributes([
                                        'class' => 'font-semibold'
                                    ])
                                    ->submit('save'),
                            )
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->post('/siswa/' . $this->activeTab, $data);

                        if (!$response->successful()) {
                            Notification::make()
                                ->title('Siswa Gagal Ditambahkan')
                                ->danger()
                                ->send();
                        } else {
                            try {
                                User::create([
                                    'name' =>  $data['nama'],
                                    'username' => $data['nis'],
                                    'password' => Hash::make($data['tanggal_lahir']),
                                ]);
                            } catch (\Throwable $th) {
                            }

                            Notification::make()
                                ->title('Siswa Berhasil Ditambahkan')
                                ->success()
                                ->send();
                        }
                    })
                    ->extraAttributes([
                        'class' => 'font-semibold text-white',
                        'id' => 'add'
                    ])
                    ->visible(fn($livewire) => $livewire->activeTab !== 'MI' && in_array('create-siswa', session()->get('data.permissions', []))),
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
