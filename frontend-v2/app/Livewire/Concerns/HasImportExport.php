<?php

namespace App\Livewire\Concerns;

use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

trait HasImportExport
{
    public ?array $importPreviewData = null;
    public ?string $importPreviewId = null;

    /**
     * Create an Export action for the table header.
     */
    protected function makeExportAction(string $exportType, array $filterSchema = []): Action
    {
        $schema = array_merge([
            Select::make('format')
                ->label('Format')
                ->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'])
                ->default('xlsx')
                ->required(),
        ], $filterSchema);

        return Action::make("export_{$exportType}")
            ->label('Export')
            ->color('success')
            ->icon('heroicon-o-arrow-down-tray')
            ->button()
            ->visible(fn(): bool => in_array('export-data', session()->get('data.permissions', [])))
            ->modalHeading('Export Data')
            ->modalSubmitActionLabel('Export')
            ->schema($schema)
            ->action(function (array $data) use ($exportType) {
                return $this->doExportAction($exportType, $data);
            });
    }

    /**
     * Create an Import action for the table header.
     */
    protected function makeImportAction(string $importType): Action
    {
        return Action::make("import_{$importType}")
            ->label('Import')
            ->color('warning')
            ->icon('heroicon-o-arrow-up-tray')
            ->button()
            ->visible(fn(): bool => in_array('import-data', session()->get('data.permissions', [])))
            ->modalHeading("Import Data " . ucfirst($importType))
            ->modalSubmitActionLabel('Upload & Import')
            ->modalDescription('Upload file .xlsx atau .csv (maks 5MB). Download template terlebih dahulu jika belum punya.')
            ->schema([
                FileUpload::make('import_file')
                    ->label('Pilih File')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/csv',
                    ])
                    ->maxSize(5120)
                    ->required(),
            ])
            ->action(function (array $data) use ($importType): void {
                $this->uploadImportFile($importType, $data);
            });
    }

    /**
     * Create a combined Import + Export action group for the table header.
     */
    protected function makeImportExportActions(string $type, array $exportFilterSchema = []): array
    {
        $actions = [];

        $permissions = session()->get('data.permissions', []);

        if (in_array('export-data', $permissions)) {
            $actions[] = $this->makeExportAction($type, $exportFilterSchema);
        }

        if (in_array('import-data', $permissions)) {
            $actions[] = $this->makeDownloadTemplateAction($type);
            $actions[] = $this->makeImportAction($type);
            $actions[] = $this->makeImportHistoryAction($type);
        }

        return $actions;
    }

    /**
     * Create Download Template action.
     */
    protected function makeDownloadTemplateAction(string $importType): Action
    {
        return Action::make("template_{$importType}")
            ->label('Template')
            ->color('gray')
            ->icon('heroicon-o-document-arrow-down')
            ->button()
            ->visible(fn(): bool => in_array('import-data', session()->get('data.permissions', [])))
            ->action(function () use ($importType) {
                return $this->downloadImportTemplate($importType);
            });
    }

    /**
     * Create Import History action.
     */
    protected function makeImportHistoryAction(string $importType): Action
    {
        return Action::make("import_history_{$importType}")
            ->label('Riwayat Import')
            ->color('gray')
            ->icon('heroicon-o-clock')
            ->button()
            ->modalHeading('Riwayat Import ' . ucfirst($importType))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalContent(function () use ($importType) {
                $response = ApiService::client()->get('/import-export/import/history', [
                    'per_page' => 10,
                ]);

                $history = [];
                if ($response->successful()) {
                    $data = $response->json();
                    // Filter by import type
                    $history = collect($data['data'] ?? [])
                        ->filter(fn($item) => ($item['import_type'] ?? '') === $importType)
                        ->values()
                        ->toArray();
                }

                return view('livewire.partials.import-history', ['history' => $history]);
            });
    }

    /**
     * Perform the export action.
     */
    protected function doExportAction(string $exportType, array $data): \Symfony\Component\HttpFoundation\StreamedResponse|null
    {
        $endpoint = match ($exportType) {
            'siswa' => '/import-export/export/siswa',
            'tagihan' => '/import-export/export/tagihan',
            'pembayaran' => '/import-export/export/pembayaran',
            'kas_harian' => '/import-export/export/kas-harian',
            'rekap_bulanan' => '/import-export/export/rekap-bulanan',
            default => null,
        };

        if (!$endpoint) return null;

        try {
            $response = ApiService::client()->post($endpoint, $data);

            if ($response->status() === 202) {
                $result = $response->json();
                Notification::make()
                    ->title('Export Diproses')
                    ->body($result['message'] ?? 'Export sedang diproses di background.')
                    ->info()
                    ->send();
                return null;
            } elseif ($response->successful()) {
                $format = $data['format'] ?? 'xlsx';
                $filename = "export_{$exportType}_" . now()->format('Y-m-d_His') . ".{$format}";
                $content = $response->body();
                $mimeType = $format === 'xlsx'
                    ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    : 'text/csv';

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $filename, ['Content-Type' => $mimeType]);
            } else {
                $errors = $response->json('errors', []);
                Notification::make()
                    ->title('Export Gagal')
                    ->body(is_array($errors) ? implode(', ', \Illuminate\Support\Arr::flatten($errors)) : 'Terjadi kesalahan.')
                    ->danger()
                    ->send();
                return null;
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return null;
        }
    }

    /**
     * Download import template.
     */
    protected function downloadImportTemplate(string $importType): \Symfony\Component\HttpFoundation\StreamedResponse|null
    {
        try {
            $response = ApiService::client()->get("/import-export/import/template/{$importType}");

            if ($response->successful()) {
                $filename = "template_import_{$importType}.xlsx";
                $content = $response->body();

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            } else {
                Notification::make()
                    ->title('Gagal')
                    ->body('Gagal mengunduh template.')
                    ->danger()
                    ->send();
                return null;
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return null;
        }
    }

    /**
     * Upload and validate import file.
     */
    protected function uploadImportFile(string $importType, array $data): void
    {
        if (empty($data['import_file'])) {
            Notification::make()
                ->title('Error')
                ->body('Pilih file terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        try {
            $filePath = storage_path('app/livewire-tmp/' . $data['import_file']);

            $response = ApiService::client()
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("/import-export/import/{$importType}/upload");

            if ($response->successful()) {
                $preview = $response->json();
                $this->importPreviewData = $preview;
                $this->importPreviewId = $preview['preview_id'] ?? null;

                $validRows = $preview['valid_rows'] ?? 0;
                $errorRows = $preview['error_rows'] ?? 0;

                if ($errorRows > 0) {
                    Notification::make()
                        ->title("Validasi: {$validRows} valid, {$errorRows} error")
                        ->body('Ada baris yang tidak valid. Hanya baris valid yang akan diimport.')
                        ->warning()
                        ->send();
                }

                if ($validRows > 0) {
                    // Auto-confirm for simplicity
                    $this->confirmImportAction($importType);
                } else {
                    Notification::make()
                        ->title('Import Gagal')
                        ->body('Tidak ada baris valid untuk diimport.')
                        ->danger()
                        ->send();
                }
            } else {
                $errors = $response->json('errors', []);
                Notification::make()
                    ->title('Upload Gagal')
                    ->body(is_array($errors) ? implode(', ', \Illuminate\Support\Arr::flatten($errors)) : 'Terjadi kesalahan.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Confirm import after preview.
     */
    protected function confirmImportAction(string $importType): void
    {
        if (!$this->importPreviewId) return;

        try {
            $response = ApiService::client()->post("/import-export/import/{$importType}/confirm", [
                'preview_id' => $this->importPreviewId,
            ]);

            if ($response->successful() || $response->status() === 202) {
                $result = $response->json();
                $status = $result['status'] ?? 'completed';

                if ($status === 'processing') {
                    Notification::make()
                        ->title('Import Diproses')
                        ->body('File besar sedang diproses di background.')
                        ->info()
                        ->send();
                } else {
                    $successCount = $result['success_count'] ?? 0;
                    Notification::make()
                        ->title('Import Berhasil')
                        ->body("{$successCount} data berhasil diimport.")
                        ->success()
                        ->send();

                    // Refresh table
                    if (method_exists($this, 'resetTable')) {
                        $this->resetTable();
                    }
                }
            } else {
                $errors = $response->json('errors', []);
                Notification::make()
                    ->title('Import Gagal')
                    ->body(is_array($errors) ? implode(', ', \Illuminate\Support\Arr::flatten($errors)) : 'Konfirmasi gagal.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->importPreviewId = null;
        $this->importPreviewData = null;
    }

    /**
     * Rollback an import batch.
     */
    public function rollbackImport(string $batchId): void
    {
        try {
            $response = ApiService::client()->post("/import-export/import/{$batchId}/rollback");

            if ($response->successful()) {
                Notification::make()
                    ->title('Rollback Berhasil')
                    ->body('Data import telah dihapus.')
                    ->success()
                    ->send();

                if (method_exists($this, 'resetTable')) {
                    $this->resetTable();
                }
            } else {
                $errors = $response->json('errors', []);
                Notification::make()
                    ->title('Rollback Gagal')
                    ->body(is_array($errors) ? implode(', ', \Illuminate\Support\Arr::flatten($errors)) : 'Rollback gagal.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
