<?php

namespace App\Filament\Pages;

use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;
use Livewire\Attributes\Url;

/**
 * Halaman Manajemen Akun Siswa.
 *
 * Menggabungkan dua mode:
 * - tab "terdaftar"     : daftar akun siswa yang sudah dibuat (ex-ManajemenAkunSiswa)
 * - tab "belum-terdaftar": siswa yang belum punya akun + bulk-create (ex-BulkAkunSiswaPage)
 */
class ManajemenAkunSiswa extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Manajemen Akun Siswa';

    protected static ?string $title = 'Manajemen Akun Siswa';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manajemen-akun-siswa';

    public const TAB_TERDAFTAR = 'terdaftar';
    public const TAB_BELUM_TERDAFTAR = 'belum-terdaftar';

    #[Url(as: 'tab')]
    public string $tab = self::TAB_TERDAFTAR;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('manage-akun-siswa', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('manage-akun-siswa', $permissions)) {
            abort(403);
        }

        if (!in_array($this->tab, [self::TAB_TERDAFTAR, self::TAB_BELUM_TERDAFTAR], true)) {
            $this->tab = self::TAB_TERDAFTAR;
        }
    }

    public function setTab(string $tab): void
    {
        if (!in_array($tab, [self::TAB_TERDAFTAR, self::TAB_BELUM_TERDAFTAR], true)) {
            return;
        }

        $this->tab = $tab;
        $this->resetTable();
    }

    public function getTabs(): array
    {
        return [
            self::TAB_TERDAFTAR => 'Sudah Terdaftar',
            self::TAB_BELUM_TERDAFTAR => 'Belum Terdaftar',
        ];
    }

    public function table(Table $table): Table
    {
        if ($this->tab === self::TAB_BELUM_TERDAFTAR) {
            return $this->buildUnregisteredTable($table);
        }

        return $this->buildRegisteredTable($table);
    }

    // ==== Tab: terdaftar ====

    protected function buildRegisteredTable(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search, int $page, int $recordsPerPage, array $filters = []): LengthAwarePaginator
                    => $this->fetchRegistered($search, $page, $recordsPerPage, $filters),
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable()
                    ->state(fn(array $record): string => $record['name'] ?? $record['siswa']['nama'] ?? '-'),
                TextColumn::make('username')
                    ->label('Username')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kelas')
                    ->label('Kelas')
                    ->state(fn(array $record): string => $record['siswa']['kelas']['nama'] ?? '-'),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->state(fn(array $record): string => $record['siswa']['jenjang'] ?? '-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->state(fn(array $record): bool => $record['is_active'] ?? false),
            ])
            ->filters([
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->options(['KB' => 'KB', 'TK' => 'TK', 'MI' => 'MI']),
                SelectFilter::make('kelas')
                    ->label('Kelas')
                    ->options(fn() => $this->kelasOptionsForRegistered()),
            ])
            ->bulkActions([
                BulkAction::make('toggleActive')
                    ->label('Toggle Status Aktif')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Toggle Status Aktif')
                    ->modalDescription('Apakah Anda yakin ingin mengubah status aktif akun yang dipilih?')
                    ->action(fn(Collection $records) => $this->bulkToggleActive($records))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('Apakah Anda yakin ingin mereset password akun yang dipilih ke default (tanggal lahir DDMMYYYY)?')
                    ->action(fn(Collection $records) => $this->bulkResetPassword($records))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('viewCredentials')
                    ->label('Lihat Kredensial')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Kredensial Akun Siswa')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (Collection $records): \Illuminate\Contracts\View\View {
                        $ids = $records->pluck('id')->toArray();
                        $response = ApiService::client()
                            ->get('/akun-siswa/credentials', ['ids' => implode(',', $ids)]);
                        $credentials = $response->ok() ? $response->json('data') : [];

                        return view('livewire.partials.credentials-list', [
                            'credentials' => $credentials,
                        ]);
                    }),
                BulkAction::make('printPdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        $ids = $records->pluck('id')->toArray();
                        $token = session()->get('data.token');
                        $url = env('API_URL') . '/akun-siswa/credentials-pdf?ids=' . implode(',', $ids) . '&token=' . $token;
                        $this->dispatch('open-url', url: $url);
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->recordActions([
                Action::make('resetPassword')
                    ->tooltip('Reset Password')
                    ->icon('heroicon-s-key')
                    ->iconButton()
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('Apakah Anda yakin ingin mereset password akun ini ke default (tanggal lahir DDMMYYYY)?')
                    ->modalSubmitActionLabel('Reset')
                    ->action(function ($record): void {
                        $response = ApiService::client()->post('/akun-siswa/' . $record['id'] . '/reset-password');

                        if ($response->status() === 404) {
                            Notification::make()->title('Gagal')->body('Akun tidak ditemukan.')->danger()->send();
                            return;
                        }

                        if (!$response->ok()) {
                            Notification::make()->title('Gagal')->body('Terjadi kesalahan pada server.')->danger()->send();
                            return;
                        }

                        Notification::make()
                            ->title('Password Berhasil Direset')
                            ->body('Password telah direset ke default (tanggal lahir DDMMYYYY).')
                            ->success()
                            ->send();
                    })
                    ->after(fn() => $this->resetTable()),
                Action::make('toggleActive')
                    ->tooltip(fn($record) => ($record['is_active'] ?? false) ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn($record) => ($record['is_active'] ?? false) ? 'heroicon-s-x-circle' : 'heroicon-s-check-circle')
                    ->iconButton()
                    ->color(fn($record) => ($record['is_active'] ?? false) ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => ($record['is_active'] ?? false) ? 'Nonaktifkan Akun' : 'Aktifkan Akun')
                    ->modalDescription(fn($record) => ($record['is_active'] ?? false)
                        ? 'Apakah Anda yakin ingin menonaktifkan akun ini?'
                        : 'Apakah Anda yakin ingin mengaktifkan akun ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->action(function ($record): void {
                        $response = ApiService::client()->patch('/akun-siswa/' . $record['id'] . '/toggle-active');

                        if ($response->status() === 404) {
                            Notification::make()->title('Gagal')->body('Akun tidak ditemukan.')->danger()->send();
                            return;
                        }

                        if (!$response->ok()) {
                            Notification::make()->title('Gagal')->body('Terjadi kesalahan pada server.')->danger()->send();
                            return;
                        }

                        $data = $response->json('data');
                        $status = ($data['is_active'] ?? false) ? 'diaktifkan' : 'dinonaktifkan';

                        Notification::make()->title('Berhasil')->body("Akun berhasil {$status}.")->success()->send();
                    })
                    ->after(fn() => $this->resetTable()),
            ])
            ->headerActions([])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Tidak Ada Akun Siswa')
            ->emptyStateDescription('Belum ada akun siswa yang terdaftar.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected function fetchRegistered(?string $search, int $page, int $recordsPerPage, ?array $filters): LengthAwarePaginator
    {
        try {
            $response = ApiService::client()->get('/akun-siswa', ['per_page' => 200]);

            if (!$response->ok()) {
                return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
            }

            $collection = collect($response->json('data') ?? []);

            if (!empty($filters['jenjang']['value'] ?? null)) {
                $jenjang = $filters['jenjang']['value'];
                $collection = $collection->filter(fn(array $r) => ($r['siswa']['jenjang'] ?? '') === $jenjang);
            }

            if (!empty($filters['kelas']['value'] ?? null)) {
                $kelas = $filters['kelas']['value'];
                $collection = $collection->filter(fn(array $r) => ($r['siswa']['kelas']['nama'] ?? '') === $kelas);
            }

            if (filled($search)) {
                $needle = Str::lower($search);
                $collection = $collection->filter(
                    fn(array $r): bool => str_contains(Str::lower($r['name'] ?? ''), $needle)
                        || str_contains(Str::lower($r['username'] ?? ''), $needle)
                        || str_contains(Str::lower($r['siswa']['nama'] ?? ''), $needle)
                        || str_contains(Str::lower($r['siswa']['jenjang'] ?? ''), $needle)
                        || str_contains(Str::lower($r['siswa']['kelas']['nama'] ?? ''), $needle)
                );
            }

            $total = $collection->count();
            $items = $collection->slice(($page - 1) * $recordsPerPage, $recordsPerPage)->values()->toArray();

            return new LengthAwarePaginator($items, $total, $recordsPerPage, $page);
        } catch (\Throwable $e) {
            return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
        }
    }

    protected function kelasOptionsForRegistered(): array
    {
        try {
            $response = ApiService::client()->get('/akun-siswa', ['per_page' => 200]);
            if (!$response->ok()) {
                return [];
            }

            return collect($response->json('data') ?? [])
                ->map(fn($r) => $r['siswa']['kelas']['nama'] ?? null)
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn($kelas) => [$kelas => $kelas])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function bulkToggleActive(Collection $records): void
    {
        $success = 0;
        $fail = 0;

        foreach ($records as $r) {
            $response = ApiService::client()->patch('/akun-siswa/' . $r['id'] . '/toggle-active');
            $response->ok() ? $success++ : $fail++;
        }

        if ($success > 0) {
            Notification::make()->title('Berhasil')->body("{$success} akun berhasil diubah statusnya.")->success()->send();
        }
        if ($fail > 0) {
            Notification::make()->title('Sebagian Gagal')->body("{$fail} akun gagal diubah statusnya.")->warning()->send();
        }

        $this->resetTable();
    }

    protected function bulkResetPassword(Collection $records): void
    {
        $success = 0;
        $fail = 0;

        foreach ($records as $r) {
            $response = ApiService::client()->post('/akun-siswa/' . $r['id'] . '/reset-password');
            $response->ok() ? $success++ : $fail++;
        }

        if ($success > 0) {
            Notification::make()->title('Password Berhasil Direset')->body("{$success} akun berhasil direset passwordnya ke default.")->success()->send();
        }
        if ($fail > 0) {
            Notification::make()->title('Sebagian Gagal')->body("{$fail} akun gagal direset passwordnya.")->warning()->send();
        }

        $this->resetTable();
    }

    // ==== Tab: belum-terdaftar ====

    protected function buildUnregisteredTable(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search, int $page, int $recordsPerPage, array $filters = []): LengthAwarePaginator
                    => $this->fetchUnregistered($search, $page, $recordsPerPage, $filters),
            )
            ->columns([
                TextColumn::make('nis')->label('NIS')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('jenjang')->label('Jenjang')->sortable(),
                TextColumn::make('kelas_nama')
                    ->label('Kelas')
                    ->state(fn(array $record): string => $record['kelas']['nama'] ?? $record['kelas_nama'] ?? '-'),
            ])
            ->filters([
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->options(['KB' => 'KB', 'TK' => 'TK', 'MI' => 'MI']),
                SelectFilter::make('kelas')
                    ->label('Kelas')
                    ->options(fn() => $this->kelasOptionsForUnregistered()),
            ])
            ->bulkActions([
                BulkAction::make('buatAkun')
                    ->label('Buat Akun')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Buat Akun Siswa')
                    ->modalDescription(fn(Collection $records) => "Apakah Anda yakin ingin membuat akun untuk {$records->count()} siswa yang dipilih?")
                    ->action(fn(Collection $records) => $this->bulkCreateAccounts($records))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Tidak Ada Siswa Tanpa Akun')
            ->emptyStateDescription('Semua siswa sudah memiliki akun.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected function fetchUnregistered(?string $search, int $page, int $recordsPerPage, ?array $filters): LengthAwarePaginator
    {
        try {
            $params = ['per_page' => 200];

            if (!empty($filters['jenjang']['value'] ?? null)) {
                $params['jenjang'] = $filters['jenjang']['value'];
            }

            $response = ApiService::client()->get('/akun-siswa/unregistered', $params);

            if (!$response->ok()) {
                return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
            }

            $collection = collect($response->json('data') ?? []);

            if (!empty($filters['kelas']['value'] ?? null)) {
                $kelas = $filters['kelas']['value'];
                $collection = $collection->filter(fn(array $r) => ($r['kelas']['nama'] ?? '') === $kelas);
            }

            if (filled($search)) {
                $needle = Str::lower($search);
                $collection = $collection->filter(
                    fn(array $r): bool => str_contains(Str::lower($r['nis'] ?? ''), $needle)
                        || str_contains(Str::lower($r['nama'] ?? ''), $needle)
                        || str_contains(Str::lower($r['jenjang'] ?? ''), $needle)
                        || str_contains(Str::lower($r['kelas']['nama'] ?? ''), $needle)
                );
            }

            $total = $collection->count();
            $items = $collection->slice(($page - 1) * $recordsPerPage, $recordsPerPage)->values()->toArray();

            return new LengthAwarePaginator($items, $total, $recordsPerPage, $page);
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal memuat data siswa')->danger()->send();
            return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
        }
    }

    protected function kelasOptionsForUnregistered(): array
    {
        try {
            $response = ApiService::client()->get('/akun-siswa/unregistered', ['per_page' => 100]);
            if (!$response->ok()) {
                return [];
            }

            return collect($response->json('data') ?? [])
                ->map(fn($r) => $r['kelas']['nama'] ?? null)
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn($kelas) => [$kelas => $kelas])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function bulkCreateAccounts(Collection $records): void
    {
        $ids = $records->pluck('id')->toArray();

        if (empty($ids)) {
            Notification::make()->title('Pilih minimal satu siswa untuk membuat akun.')->warning()->send();
            return;
        }

        try {
            $response = ApiService::client()->post('/akun-siswa/bulk', ['siswa_ids' => $ids]);

            if ($response->ok()) {
                $data = $response->json('data') ?? [];
                $created = $data['created'] ?? 0;
                $errors = $data['errors'] ?? [];

                if ($created > 0) {
                    Notification::make()
                        ->title('Akun Berhasil Dibuat')
                        ->body("{$created} akun siswa berhasil dibuat.")
                        ->success()
                        ->send();
                }

                if (!empty($errors)) {
                    Notification::make()
                        ->title('Sebagian Gagal')
                        ->body(count($errors) . ' akun gagal dibuat.')
                        ->warning()
                        ->send();
                }
            } else {
                Notification::make()->title('Gagal')->body('Terjadi kesalahan saat membuat akun.')->danger()->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal')->body('Terjadi kesalahan saat membuat akun.')->danger()->send();
        }

        $this->resetTable();
    }
}
