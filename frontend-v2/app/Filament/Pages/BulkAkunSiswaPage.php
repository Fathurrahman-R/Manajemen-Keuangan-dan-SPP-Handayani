<?php

namespace App\Filament\Pages;

use App\Services\ApiService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class BulkAkunSiswaPage extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Bulk Akun Siswa';

    protected static ?string $title = 'Pembuatan Akun Siswa (Bulk)';

    protected static ?string $slug = 'bulk-akun-siswa';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.bulk-akun-siswa';

    public bool $processing = false;

    public bool $showSummaryModal = false;

    public int $createdCount = 0;

    public array $errors = [];

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

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search, int $page, int $recordsPerPage, array $filters = []): LengthAwarePaginator => $this->fetchUnregisteredSiswa($search, $page, $recordsPerPage, $filters),
            )
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kelas_nama')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable()
                    ->state(fn(array $record): string => $record['kelas']['nama'] ?? $record['kelas_nama'] ?? '-'),
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

    /**
     * Fetch unregistered siswa from the API with search and filter support.
     */
    protected function fetchUnregisteredSiswa(?string $search, int $page, int $recordsPerPage, ?array $filters): LengthAwarePaginator
    {
        try {
            $params = ['per_page' => 200];

            // Apply jenjang filter at API level
            if (!empty($filters['jenjang']['value'] ?? null)) {
                $params['jenjang'] = $filters['jenjang']['value'];
            }

            $response = ApiService::client()->get('/akun-siswa/unregistered', $params);

            if (!$response->ok()) {
                return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
            }

            $data = $response->json('data') ?? [];
            $collection = collect($data);

            // Apply kelas filter client-side
            if (!empty($filters['kelas']['value'] ?? null)) {
                $kelas = $filters['kelas']['value'];
                $collection = $collection->filter(
                    fn(array $record) => ($record['kelas']['nama'] ?? '') === $kelas
                );
            }

            // Apply search
            if (filled($search)) {
                $searchLower = Str::lower($search);
                $collection = $collection->filter(
                    fn(array $record): bool => str_contains(Str::lower($record['nis'] ?? ''), $searchLower)
                        || str_contains(Str::lower($record['nama'] ?? ''), $searchLower)
                        || str_contains(Str::lower($record['jenjang'] ?? ''), $searchLower)
                        || str_contains(Str::lower($record['kelas']['nama'] ?? ''), $searchLower)
                );
            }

            $total = $collection->count();
            $items = $collection->slice(($page - 1) * $recordsPerPage, $recordsPerPage)->values()->toArray();

            return new LengthAwarePaginator($items, $total, $recordsPerPage, $page);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal memuat data siswa')
                ->danger()
                ->send();
            return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
        }
    }

    /**
     * Get kelas options for the filter dropdown.
     */
    protected function getKelasOptions(): array
    {
        try {
            $response = ApiService::client()->get('/akun-siswa/unregistered', ['per_page' => 100]);
            if (!$response->ok()) {
                return [];
            }

            $data = $response->json('data') ?? [];

            return collect($data)
                ->map(fn($record) => $record['kelas']['nama'] ?? null)
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
     * Bulk create accounts for selected siswa.
     */
    protected function bulkCreateAccounts(Collection $records): void
    {
        $siswaIds = $records->pluck('id')->toArray();

        if (empty($siswaIds)) {
            Notification::make()
                ->title('Pilih minimal satu siswa untuk membuat akun.')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = ApiService::client()->post('/akun-siswa/bulk', [
                'siswa_ids' => $siswaIds,
            ]);

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
                    $errorCount = count($errors);
                    Notification::make()
                        ->title('Sebagian Gagal')
                        ->body("{$errorCount} akun gagal dibuat.")
                        ->warning()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Gagal')
                    ->body('Terjadi kesalahan saat membuat akun.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal')
                ->body('Terjadi kesalahan saat membuat akun.')
                ->danger()
                ->send();
        }

        $this->resetTable();
    }

    public function closeSummaryModal(): void
    {
        $this->showSummaryModal = false;
        $this->createdCount = 0;
        $this->errors = [];
    }
}
