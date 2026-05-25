<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;

class KenaikanKelas extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;

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
    public ?array $selectedBatchDetail = null;

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('manage-kenaikan-kelas', $permissions)) {
            abort(403);
        }

        $this->loadTahunAjaranOptions();
        $this->selectedSourcePeriodId = $this->getAktifPeriodId();
        $this->activeJenjangTab = 'MI';
        $this->loadKelasList();
        $this->loadHistory();
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
            $response = ApiService::client()->get('/kenaikan-kelas/hierarki-kelas', [
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
        if (!$this->selectedKelasId || !$this->selectedSourcePeriodId) {
            $this->students = [];
            $this->studentActions = [];
            $this->studentTargetKelas = [];
            $this->isKelasTertinggi = false;
            $this->targetJenjangKelasList = [];
            $this->computeSummary();
            return;
        }

        // Check if selected kelas is the highest in its jenjang
        $this->checkIsKelasTertinggi();

        // Load target jenjang kelas list for pindah_jenjang
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
     * If kelas is tertinggi, default to 'lulus'; otherwise default to 'naik_kelas'.
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
    public function updateStudentAction(int $siswaId, string $action, ?int $targetKelasId = null): void
    {
        $this->studentActions[$siswaId] = $action;

        // Store target kelas for pindah_jenjang actions
        if ($action === 'pindah_jenjang') {
            $this->studentTargetKelas[$siswaId] = $targetKelasId;
        } else {
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
     * Calls the class hierarchy API and compares levels.
     */
    protected function checkIsKelasTertinggi(): void
    {
        $this->isKelasTertinggi = false;

        if (!$this->selectedKelasId) {
            return;
        }

        // Find the selected kelas in the kelasList to get its level
        $selectedKelas = null;
        foreach ($this->kelasList as $kelas) {
            if ($kelas['id'] == $this->selectedKelasId) {
                $selectedKelas = $kelas;
                break;
            }
        }

        if (!$selectedKelas || !isset($selectedKelas['level'])) {
            return;
        }

        // Check if any other kelas in the same jenjang has a higher level
        $hasHigherLevel = false;
        foreach ($this->kelasList as $kelas) {
            if ($kelas['id'] != $this->selectedKelasId && isset($kelas['level']) && $kelas['level'] > $selectedKelas['level']) {
                $hasHigherLevel = true;
                break;
            }
        }

        $this->isKelasTertinggi = !$hasHigherLevel;
    }

    /**
     * Get available actions based on whether the kelas is tertinggi.
     *
     * @return array<string, string>
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
     * Load target kelas options for pindah_jenjang (next jenjang's kelas list).
     */
    protected function loadTargetJenjangKelas(): void
    {
        $this->targetJenjangKelasList = [];

        // Determine the next jenjang based on current active tab
        $nextJenjang = $this->getNextJenjang($this->activeJenjangTab);

        if (!$nextJenjang) {
            return;
        }

        try {
            $response = ApiService::client()->get('/kenaikan-kelas/hierarki-kelas', [
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
     * Allowed transitions: KB → TK, TK → MI
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
     * Get target jenjang kelas options for pindah_jenjang.
     *
     * @return array
     */
    public function getTargetJenjangKelas(): array
    {
        return $this->targetJenjangKelasList;
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
     * Load batch history from the API, sorted by processed_at desc.
     */
    public function loadHistory(): void
    {
        try {
            $response = ApiService::client()->get('/kenaikan-kelas/batches');

            if ($response->ok()) {
                $this->history = $response->json()['data'] ?? [];
            } else {
                $this->history = [];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->history = [];
        } catch (\Throwable $e) {
            $this->history = [];
        }
    }

    /**
     * Show batch detail by fetching from the API.
     */
    public function showBatchDetail(string $batchId): void
    {
        try {
            $response = ApiService::client()->get("/kenaikan-kelas/batches/{$batchId}");

            if ($response->ok()) {
                $this->selectedBatchDetail = $response->json()['data'] ?? null;
            } else {
                $this->selectedBatchDetail = null;
                Notification::make()
                    ->title('Gagal memuat detail batch.')
                    ->danger()
                    ->send();
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->selectedBatchDetail = null;
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            $this->selectedBatchDetail = null;
            Notification::make()
                ->title('Terjadi kesalahan saat memuat detail batch.')
                ->danger()
                ->send();
        }
    }

    /**
     * Close the batch detail view.
     */
    public function closeBatchDetail(): void
    {
        $this->selectedBatchDetail = null;
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

                if (!empty($skipped)) {
                    $skippedMessages = collect($skipped)
                        ->map(fn($item) => ($item['nama'] ?? 'Siswa') . ': ' . ($item['reason'] ?? 'Alasan tidak diketahui'))
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
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
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
     * Process all student actions by sending batch requests to the API.
     */
    public function processAll(): void
    {
        $this->processing = true;

        try {
            // Validate target period is selected
            if (!$this->selectedTargetPeriodId) {
                Notification::make()
                    ->title('Periode tujuan harus dipilih sebelum memproses.')
                    ->danger()
                    ->send();
                $this->processing = false;
                return;
            }

            // Group students by action type
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

            // Process naik_kelas: bulk promotion
            if (!empty($grouped['naik_kelas'])) {
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

            // Process tinggal_kelas: retention
            if (!empty($grouped['tinggal_kelas'])) {
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

            // Process lulus: graduation
            if (!empty($grouped['lulus'])) {
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

            // Process pindah_jenjang: cross-level transfer (individual per student)
            if (!empty($grouped['pindah_jenjang'])) {
                foreach ($grouped['pindah_jenjang'] as $siswaId) {
                    $targetKelasId = $this->studentTargetKelas[$siswaId] ?? null;

                    if (!$targetKelasId) {
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

            // Show results
            if (!empty($errors)) {
                Notification::make()
                    ->title('Terjadi kesalahan saat memproses')
                    ->body(implode("\n", $errors))
                    ->danger()
                    ->persistent()
                    ->send();
            }

            if (!empty($results)) {
                $summaryParts = [];
                if (isset($results['naik_kelas'])) {
                    $summaryParts[] = 'Naik Kelas: ' . ($results['naik_kelas']['total_success'] ?? 0) . ' siswa';
                }
                if (isset($results['tinggal_kelas'])) {
                    $summaryParts[] = 'Tinggal Kelas: ' . ($results['tinggal_kelas']['total_success'] ?? 0) . ' siswa';
                }
                if (isset($results['lulus'])) {
                    $summaryParts[] = 'Lulus: ' . ($results['lulus']['total_success'] ?? 0) . ' siswa';
                }
                if (isset($results['pindah_jenjang'])) {
                    $summaryParts[] = 'Pindah Jenjang: ' . count($results['pindah_jenjang']) . ' siswa';
                }

                Notification::make()
                    ->title('Proses kenaikan kelas berhasil')
                    ->body(implode(', ', $summaryParts))
                    ->success()
                    ->persistent()
                    ->send();

                // Reload students to reflect changes
                $this->loadStudents();
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
     * Extract error message from an API response for display.
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

    /**
     * Handle API error responses with Filament notification.
     */
    protected function handleApiError($response): void
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

            Notification::make()
                ->title($message)
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan yang tidak terduga.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.kenaikan-kelas');
    }
}
