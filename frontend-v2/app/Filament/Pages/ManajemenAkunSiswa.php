<?php

namespace App\Filament\Pages;

use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Actions\BulkAction;
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

class ManajemenAkunSiswa extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Manajemen Akun Siswa';

    protected static ?string $title = 'Manajemen Akun Siswa';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manajemen-akun-siswa';

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
    }

    protected function hasPermission(string $permission): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array($permission, $permissions);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search, int $page, int $recordsPerPage, array $filters = []): LengthAwarePaginator => $this->fetchRecords($search, $page, $recordsPerPage, $filters),
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
                    ->sortable()
                    ->state(fn(array $record): string => $record['siswa']['kelas']['nama'] ?? '-'),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->sortable()
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
                    ->options(fn() => $this->getKelasOptions()),
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
                        $url = env('API_URL') . '/akun-siswa/credentials/pdf?ids=' . implode(',', $ids) . '&token=' . $token;
                        $this->dispatch('open-url', url: $url);
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('resetPassword')
                    ->tooltip('Reset Password')
                    ->icon('heroicon-s-key')
                    ->iconButton()
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('Apakah Anda yakin ingin mereset password akun ini ke default (tanggal lahir DDMMYYYY)?')
                    ->modalSubmitActionLabel('Reset')
                    ->action(function ($record): void {
                        $response = ApiService::client()
                            ->post('/akun-siswa/' . $record['id'] . '/reset-password');

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
                \Filament\Actions\Action::make('toggleActive')
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
                        $response = ApiService::client()
                            ->patch('/akun-siswa/' . $record['id'] . '/toggle-active');

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

    /**
     * Fetch records from the API with search and filter support.
     */
    protected function fetchRecords(?string $search, int $page, int $recordsPerPage, ?array $filters): LengthAwarePaginator
    {
        try {
            $response = ApiService::client()->get('/akun-siswa', ['per_page' => 200]);

            if (!$response->ok()) {
                return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
            }

            $allRecords = $response->json('data') ?? [];
            $collection = collect($allRecords);

            // Apply jenjang filter
            if (!empty($filters['jenjang']['value'] ?? null)) {
                $jenjang = $filters['jenjang']['value'];
                $collection = $collection->filter(
                    fn(array $record) => ($record['siswa']['jenjang'] ?? '') === $jenjang
                );
            }

            // Apply kelas filter
            if (!empty($filters['kelas']['value'] ?? null)) {
                $kelas = $filters['kelas']['value'];
                $collection = $collection->filter(
                    fn(array $record) => ($record['siswa']['kelas']['nama'] ?? '') === $kelas
                );
            }

            // Apply search
            if (filled($search)) {
                $searchLower = Str::lower($search);
                $collection = $collection->filter(
                    fn(array $record): bool => str_contains(Str::lower($record['name'] ?? ''), $searchLower)
                        || str_contains(Str::lower($record['username'] ?? ''), $searchLower)
                        || str_contains(Str::lower($record['siswa']['nama'] ?? ''), $searchLower)
                        || str_contains(Str::lower($record['siswa']['jenjang'] ?? ''), $searchLower)
                        || str_contains(Str::lower($record['siswa']['kelas']['nama'] ?? ''), $searchLower)
                );
            }

            $total = $collection->count();
            $items = $collection->slice(($page - 1) * $recordsPerPage, $recordsPerPage)->values()->toArray();

            return new LengthAwarePaginator($items, $total, $recordsPerPage, $page);
        } catch (\Throwable $e) {
            return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
        }
    }

    /**
     * Get kelas options for the filter.
     */
    protected function getKelasOptions(): array
    {
        try {
            $response = ApiService::client()->get('/akun-siswa', ['per_page' => 200]);
            if (!$response->ok()) {
                return [];
            }

            $records = collect($response->json('data') ?? []);
            return $records
                ->map(fn($record) => $record['siswa']['kelas']['nama'] ?? null)
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn($kelas) => [$kelas => $kelas])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Bulk toggle active status for selected accounts.
     */
    protected function bulkToggleActive(Collection $records): void
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($records as $record) {
            $response = ApiService::client()->patch('/akun-siswa/' . $record['id'] . '/toggle-active');
            if ($response->ok()) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        if ($successCount > 0) {
            Notification::make()
                ->title('Berhasil')
                ->body("{$successCount} akun berhasil diubah statusnya.")
                ->success()
                ->send();
        }

        if ($failCount > 0) {
            Notification::make()
                ->title('Sebagian Gagal')
                ->body("{$failCount} akun gagal diubah statusnya.")
                ->warning()
                ->send();
        }

        $this->resetTable();
    }

    /**
     * Bulk reset password for selected accounts.
     */
    protected function bulkResetPassword(Collection $records): void
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($records as $record) {
            $response = ApiService::client()->post('/akun-siswa/' . $record['id'] . '/reset-password');
            if ($response->ok()) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        if ($successCount > 0) {
            Notification::make()
                ->title('Password Berhasil Direset')
                ->body("{$successCount} akun berhasil direset passwordnya ke default.")
                ->success()
                ->send();
        }

        if ($failCount > 0) {
            Notification::make()
                ->title('Sebagian Gagal')
                ->body("{$failCount} akun gagal direset passwordnya.")
                ->warning()
                ->send();
        }

        $this->resetTable();
    }

    public function toggleSelection(int $id): void
    {
        // Deprecated — using native Filament table bulk selection
    }
}
