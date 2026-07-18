<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;

class KenaikanKelas extends Component implements HasActions, HasSchemas, HasTable
{
    use \App\Livewire\Concerns\HandlesApiErrors;
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public ?int $selectedSourcePeriodId = null;

    public ?int $selectedTargetPeriodId = null;

    public ?int $selectedKelasId = null;

    public string $activeJenjangTab = 'MI';

    public array $studentActions = [];

    public array $studentTargetKelas = [];

    public array $kelasList = [];

    public array $tahunAjaranOptions = [];

    public array $students = [];

    public array $summary = [];

    public bool $isKelasTertinggi = false;

    public array $targetJenjangKelasList = [];

    public bool $processing = false;

    public array $history = [];

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (! in_array('view-kenaikan-kelas', $permissions)) {
            abort(403);
        }

        $this->loadTahunAjaranOptions();
        $this->selectedSourcePeriodId = $this->getAktifPeriodId();
        $this->activeJenjangTab = 'MI';
        $this->loadKelasList();
        $this->loadHistory();
    }

    /**
     * Filament Action for "Proses Kenaikan Kelas" confirmation modal.
     */
    public function processAction(): Action
    {
        return Action::make('process')
            ->label('Proses Kenaikan Kelas')
            ->icon('heroicon-o-check-circle')
            ->color('primary')
            ->visible(fn (): bool => in_array('process-kenaikan-kelas', session()->get('data.permissions', [])))
            ->disabled(fn () => $this->processing || count($this->students) === 0 || ! $this->selectedTargetPeriodId)
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Proses Kenaikan Kelas')
            ->modalDescription(fn () => 'Apakah Anda yakin ingin memproses kenaikan kelas untuk '.array_sum($this->summary).' siswa? Tindakan ini akan membuat perubahan pada data siswa.')
            ->modalSubmitActionLabel('Ya, Proses')
            ->modalCancelActionLabel('Batal')
            ->action(fn () => $this->processAll());
    }

    /**
     * Filament Table for history (Riwayat Proses).
     */
    public function table(Table $table): Table
    {
        return $table
            ->records(function (): LengthAwarePaginator {
                return new LengthAwarePaginator(
                    items: $this->history,
                    total: count($this->history),
                    perPage: 10,
                    currentPage: 1,
                );
            })
            ->columns([
                TextColumn::make('processed_at')
                    ->label('Tanggal')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-'),
                TextColumn::make('batch_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => $this->translateBatchType($state ?? '')),
                TextColumn::make('kelas_asal')
                    ->label('Kelas Asal')
                    ->state(fn (array $record) => $record['kelas']['nama'] ?? $record['kelas_nama'] ?? '-'),
                TextColumn::make('dari_periode')
                    ->label('Dari Periode')
                    ->state(fn (array $record) => $record['source_tahun_ajaran']['nama'] ?? $record['source_tahun_ajaran_nama'] ?? '-'),
                TextColumn::make('ke_periode')
                    ->label('Ke Periode')
                    ->state(fn (array $record) => $record['target_tahun_ajaran']['nama'] ?? $record['target_tahun_ajaran_nama'] ?? '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('details_count')
                    ->label('Jumlah Siswa')
                    ->state(fn (array $record) => $record['details_count'] ?? count($record['details'] ?? []))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state === 'completed' ? 'Completed' : 'Undone')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processed_by_user')
                    ->label('User')
                    ->state(fn (array $record) => $record['processed_by_user']['name'] ?? '-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->visible(fn (): bool => PermissionHelper::hasResource('kenaikan-kelas.detail'))
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Lihat Detail')
                    ->color('info')
                    ->modalHeading(fn (array $record) => 'Detail: '.$this->translateBatchType($record['batch_type'] ?? ''))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (array $record): \Illuminate\Contracts\View\View {
                        try {
                            $response = ApiService::client()->get("/kenaikan-kelas/batches/{$record['id']}");
                            $detail = $response->ok() ? ($response->json()['data'] ?? null) : null;
                        } catch (\Throwable $e) {
                            $detail = null;
                        }

                        return view('livewire.partials.batch-detail', ['detail' => $detail]);
                    }),
                Action::make('undo')
                    ->label('Undo')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->iconButton()
                    ->tooltip('Batalkan')
                    ->color('danger')
                    ->visible(fn (array $record): bool => ($record['status'] ?? '') === 'completed' && in_array('undo-kenaikan-kelas', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Batch')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan batch ini? Semua perubahan yang dilakukan akan dikembalikan.')
                    ->modalSubmitActionLabel('Ya, Batalkan')
                    ->modalCancelActionLabel('Batal')
                    ->action(fn (array $record) => $this->undoBatch($record['id'])),
            ])
            ->emptyStateHeading('Belum Ada Riwayat')
            ->emptyStateDescription('Belum ada riwayat proses kenaikan kelas.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    /**
     * Load tahun ajaran options from the API.
     */
    protected function loadTahunAjaranOptions(): void
    {
        try {
            $response = ApiService::client()->get('/tahun-ajaran');

            if ($response->ok()) {
                $this->tahunAjaranOptions = $response->json()['data'] ?? [];
            } else {
                $this->tahunAjaranOptions = [];
                $this->handleApiError($response);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->tahunAjaranOptions = [];
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            $this->tahunAjaranOptions = [];
            Notification::make()
                ->title('Terjadi kesalahan saat memuat data tahun ajaran.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Get the ID of the active period from loaded options.
     */
    protected function getAktifPeriodId(): ?int
    {
        foreach ($this->tahunAjaranOptions as $option) {
            if (($option['status'] ?? '') === 'Aktif') {
                return (int) $option['id'];
            }
        }

        return null;
    }

    /**
     * Load kelas list grouped by jenjang from the API.
     */
    public function loadKelasList(): void
    {
        try {
            $response = ApiService::client()->get('/kenaikan-kelas/class-hierarchy', [
                'jenjang' => $this->activeJenjangTab,
            ]);

            if ($response->ok()) {
                $this->kelasList = $response->json()['data'] ?? [];
            } else {
                $this->kelasList = [];
                $this->handleApiError($response);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->kelasList = [];
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            $this->kelasList = [];
            Notification::make()
                ->title('Terjadi kesalahan saat memuat data kelas.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Load students for the selected kelas and source period.
     */
    public function loadStudents(): void
    {
        if (! $this->selectedKelasId || ! $this->selectedSourcePeriodId) {
            $this->students = [];
            $this->studentActions = [];
            $this->studentTargetKelas = [];
            $this->isKelasTertinggi = false;
            $this->targetJenjangKelasList = [];
            $this->computeSummary();

            return;
        }

        $this->checkIsKelasTertinggi();

        if ($this->isKelasTertinggi) {
            $this->loadTargetJenjangKelas();
        } else {
            $this->targetJenjangKelasList = [];
        }

        try {
            $response = ApiService::client()->get('/kenaikan-kelas/eligible-students', [
                'kelas_id' => $this->selectedKelasId,
                'tahun_ajaran_id' => $this->selectedSourcePeriodId,
            ]);

            if ($response->ok()) {
                $this->students = $response->json()['data'] ?? [];
                $this->initializeStudentActions();
            } else {
                $this->students = [];
                $this->studentActions = [];
                $this->studentTargetKelas = [];
                $this->handleApiError($response);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->students = [];
            $this->studentActions = [];
            $this->studentTargetKelas = [];
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            $this->students = [];
            $this->studentActions = [];
            $this->studentTargetKelas = [];
            Notification::make()
                ->title('Terjadi kesalahan saat memuat data siswa.')
                ->danger()
                ->persistent()
                ->send();
        }

        $this->computeSummary();
    }

    /**
     * Initialize student actions with default action for each student.
     */
    protected function initializeStudentActions(): void
    {
        $this->studentActions = [];
        $this->studentTargetKelas = [];

        $defaultAction = $this->isKelasTertinggi ? 'lulus' : 'naik_kelas';

        foreach ($this->students as $student) {
            $siswaId = $student['id'];
            $this->studentActions[$siswaId] = $defaultAction;
            $this->studentTargetKelas[$siswaId] = null;
        }
    }

    /**
     * Update the action for a specific student.
     */
    public function updateStudentAction(int $siswaId, string $action): void
    {
        $this->studentActions[$siswaId] = $action;

        if ($action !== 'pindah_jenjang') {
            $this->studentTargetKelas[$siswaId] = null;
        }

        $this->computeSummary();
    }

    /**
     * Update the target kelas for a specific student's pindah_jenjang action.
     */
    public function updateStudentTargetKelas(int $siswaId, ?int $targetKelasId): void
    {
        $this->studentTargetKelas[$siswaId] = $targetKelasId;
    }

    /**
     * Compute summary counts per action type.
     */
    public function computeSummary(): void
    {
        $this->summary = [
            'naik_kelas' => 0,
            'tinggal_kelas' => 0,
            'lulus' => 0,
            'pindah_jenjang' => 0,
        ];

        foreach ($this->studentActions as $action) {
            if (isset($this->summary[$action])) {
                $this->summary[$action]++;
            }
        }
    }

    /**
     * Check if the selected kelas is the highest in its jenjang.
     */
    protected function checkIsKelasTertinggi(): void
    {
        $this->isKelasTertinggi = false;

        if (! $this->selectedKelasId) {
            return;
        }

        $selectedKelas = null;
        foreach ($this->kelasList as $kelas) {
            if ($kelas['id'] == $this->selectedKelasId) {
                $selectedKelas = $kelas;
                break;
            }
        }

        if (! $selectedKelas || ! isset($selectedKelas['level'])) {
            return;
        }

        $hasHigherLevel = false;
        foreach ($this->kelasList as $kelas) {
            if ($kelas['id'] != $this->selectedKelasId && isset($kelas['level']) && $kelas['level'] > $selectedKelas['level']) {
                $hasHigherLevel = true;
                break;
            }
        }

        $this->isKelasTertinggi = ! $hasHigherLevel;
    }

    /**
     * Get available actions based on whether the kelas is tertinggi.
     */
    public function getAvailableActions(): array
    {
        $actions = [
            'naik_kelas' => 'Naik Kelas',
            'tinggal_kelas' => 'Tinggal Kelas',
        ];

        if ($this->isKelasTertinggi) {
            $actions['lulus'] = 'Lulus';
            $actions['pindah_jenjang'] = 'Pindah Jenjang';
        }

        return $actions;
    }

    /**
     * Load target kelas options for pindah_jenjang.
     */
    protected function loadTargetJenjangKelas(): void
    {
        $this->targetJenjangKelasList = [];

        $nextJenjang = $this->getNextJenjang($this->activeJenjangTab);

        if (! $nextJenjang) {
            return;
        }

        try {
            $response = ApiService::client()->get('/kenaikan-kelas/class-hierarchy', [
                'jenjang' => $nextJenjang,
            ]);

            if ($response->ok()) {
                $this->targetJenjangKelasList = $response->json()['data'] ?? [];
            }
        } catch (\Throwable $e) {
            $this->targetJenjangKelasList = [];
        }
    }

    /**
     * Get the next jenjang for pindah_jenjang transitions.
     */
    protected function getNextJenjang(string $currentJenjang): ?string
    {
        return match ($currentJenjang) {
            'KB' => 'TK',
            'TK' => 'MI',
            default => null,
        };
    }

    /**
     * Handle when the active jenjang tab changes.
     */
    public function updatedActiveJenjangTab(): void
    {
        $this->selectedKelasId = null;
        $this->students = [];
        $this->studentActions = [];
        $this->studentTargetKelas = [];
        $this->isKelasTertinggi = false;
        $this->targetJenjangKelasList = [];
        $this->computeSummary();
        $this->loadKelasList();
    }

    /**
     * Handle when the selected source period changes.
     */
    public function updatedSelectedSourcePeriodId(): void
    {
        $this->selectedKelasId = null;
        $this->students = [];
        $this->studentActions = [];
        $this->studentTargetKelas = [];
        $this->isKelasTertinggi = false;
        $this->targetJenjangKelasList = [];
        $this->computeSummary();
        $this->loadKelasList();
    }

    /**
     * Handle when the selected kelas changes.
     */
    public function updatedSelectedKelasId(): void
    {
        $this->loadStudents();
    }

    /**
     * Load batch history from the API.
     */
    public function loadHistory(): void
    {
        try {
            $response = ApiService::client()->get('/kenaikan-kelas/batches');

            if ($response->ok()) {
                $json = $response->json();
                // The backend returns paginated data, so 'data' is the items array
                $this->history = $json['data'] ?? [];
            } else {
                $this->history = [];
            }
        } catch (\Throwable $e) {
            $this->history = [];
        }
    }

    /**
     * Undo a batch operation.
     */
    public function undoBatch(string $batchId): void
    {
        try {
            $response = ApiService::client()->post("/kenaikan-kelas/{$batchId}/undo");

            if ($response->ok()) {
                $data = $response->json()['data'] ?? [];
                $totalRestored = $data['total_restored'] ?? 0;
                $skipped = $data['skipped'] ?? [];

                Notification::make()
                    ->title("Berhasil membatalkan batch. {$totalRestored} siswa dikembalikan.")
                    ->success()
                    ->send();

                if (! empty($skipped)) {
                    $skippedMessages = collect($skipped)
                        ->map(fn ($item) => ($item['nama'] ?? 'Siswa').': '.($item['reason'] ?? 'Alasan tidak diketahui'))
                        ->implode('; ');

                    Notification::make()
                        ->title('Beberapa siswa dilewati saat undo')
                        ->body($skippedMessages)
                        ->warning()
                        ->persistent()
                        ->send();
                }

                $this->loadHistory();
            } else {
                $this->handleApiError($response);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan saat membatalkan batch.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Translate batch_type to human-readable label.
     */
    public function translateBatchType(string $batchType): string
    {
        return match ($batchType) {
            'bulk_promotion' => 'Kenaikan Kelas (Bulk)',
            'individual_promotion' => 'Kenaikan Kelas (Individual)',
            'kelulusan' => 'Kelulusan',
            'tinggal_kelas' => 'Tinggal Kelas',
            'pindah_jenjang' => 'Pindah Jenjang',
            default => ucfirst(str_replace('_', ' ', $batchType)),
        };
    }

    /**
     * Process all student actions.
     */
    public function processAll(): void
    {
        $this->processing = true;

        try {
            if (! $this->selectedTargetPeriodId) {
                Notification::make()
                    ->title('Periode tujuan harus dipilih sebelum memproses.')
                    ->danger()
                    ->send();
                $this->processing = false;

                return;
            }

            $grouped = [
                'naik_kelas' => [],
                'tinggal_kelas' => [],
                'lulus' => [],
                'pindah_jenjang' => [],
            ];

            foreach ($this->studentActions as $siswaId => $action) {
                $grouped[$action][] = (int) $siswaId;
            }

            $results = [];
            $errors = [];

            // Process naik_kelas
            if (! empty($grouped['naik_kelas'])) {
                try {
                    $response = ApiService::client()->post('/kenaikan-kelas/bulk-promotion', [
                        'kelas_id' => $this->selectedKelasId,
                        'tahun_ajaran_id' => $this->selectedTargetPeriodId,
                    ]);

                    if ($response->ok()) {
                        $results['naik_kelas'] = $response->json()['data'] ?? [];
                    } else {
                        $errors[] = $this->extractErrorMessage($response, 'Naik Kelas');
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Naik Kelas: Gagal menghubungi server.';
                }
            }

            // Process tinggal_kelas
            if (! empty($grouped['tinggal_kelas'])) {
                try {
                    $response = ApiService::client()->post('/kenaikan-kelas/retention', [
                        'siswa_ids' => $grouped['tinggal_kelas'],
                        'tahun_ajaran_id' => $this->selectedTargetPeriodId,
                    ]);

                    if ($response->ok()) {
                        $results['tinggal_kelas'] = $response->json()['data'] ?? [];
                    } else {
                        $errors[] = $this->extractErrorMessage($response, 'Tinggal Kelas');
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Tinggal Kelas: Gagal menghubungi server.';
                }
            }

            // Process lulus
            if (! empty($grouped['lulus'])) {
                try {
                    $response = ApiService::client()->post('/kenaikan-kelas/graduation', [
                        'siswa_ids' => $grouped['lulus'],
                        'tahun_ajaran_id' => $this->selectedTargetPeriodId,
                    ]);

                    if ($response->ok()) {
                        $results['lulus'] = $response->json()['data'] ?? [];
                    } else {
                        $errors[] = $this->extractErrorMessage($response, 'Kelulusan');
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Kelulusan: Gagal menghubungi server.';
                }
            }

            // Process pindah_jenjang
            if (! empty($grouped['pindah_jenjang'])) {
                foreach ($grouped['pindah_jenjang'] as $siswaId) {
                    $targetKelasId = $this->studentTargetKelas[$siswaId] ?? null;

                    if (! $targetKelasId) {
                        $errors[] = "Pindah Jenjang: Kelas tujuan belum dipilih untuk siswa ID {$siswaId}.";

                        continue;
                    }

                    try {
                        $response = ApiService::client()->post('/kenaikan-kelas/cross-level-transfer', [
                            'siswa_id' => $siswaId,
                            'target_kelas_id' => $targetKelasId,
                            'tahun_ajaran_id' => $this->selectedTargetPeriodId,
                        ]);

                        if ($response->ok()) {
                            $results['pindah_jenjang'][] = $response->json()['data'] ?? [];
                        } else {
                            $errors[] = $this->extractErrorMessage($response, 'Pindah Jenjang');
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Pindah Jenjang: Gagal menghubungi server.';
                    }
                }
            }

            if (! empty($errors)) {
                Notification::make()
                    ->title('Terjadi kesalahan saat memproses')
                    ->body(implode("\n", $errors))
                    ->danger()
                    ->persistent()
                    ->send();
            }

            if (! empty($results)) {
                $summaryParts = [];
                if (isset($results['naik_kelas'])) {
                    $summaryParts[] = 'Naik Kelas: '.($results['naik_kelas']['total_success'] ?? 0).' siswa';
                }
                if (isset($results['tinggal_kelas'])) {
                    $summaryParts[] = 'Tinggal Kelas: '.($results['tinggal_kelas']['total_success'] ?? 0).' siswa';
                }
                if (isset($results['lulus'])) {
                    $summaryParts[] = 'Lulus: '.($results['lulus']['total_graduated'] ?? $results['lulus']['total_success'] ?? 0).' siswa';
                }
                if (isset($results['pindah_jenjang'])) {
                    $summaryParts[] = 'Pindah Jenjang: '.count($results['pindah_jenjang']).' siswa';
                }

                Notification::make()
                    ->title('Proses kenaikan kelas berhasil')
                    ->body(implode(', ', $summaryParts))
                    ->success()
                    ->persistent()
                    ->send();

                $this->loadStudents();
                $this->loadHistory();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan yang tidak terduga.')
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->processing = false;
        }
    }

    /**
     * Extract error message from an API response.
     */
    protected function extractErrorMessage($response, string $prefix = ''): string
    {
        try {
            $json = $response->json();
            $errors = $json['errors'] ?? [];

            if (isset($errors['message'])) {
                $message = is_array($errors['message']) ? $errors['message'][0] : $errors['message'];
            } else {
                $firstKey = array_key_first($errors);
                $message = $firstKey
                    ? (is_array($errors[$firstKey]) ? $errors[$firstKey][0] : $errors[$firstKey])
                    : 'Terjadi kesalahan.';
            }

            return $prefix ? "{$prefix}: {$message}" : $message;
        } catch (\Throwable $e) {
            return $prefix ? "{$prefix}: Terjadi kesalahan." : 'Terjadi kesalahan.';
        }
    }

    public function render()
    {
        return view('livewire.kenaikan-kelas');
    }
}
