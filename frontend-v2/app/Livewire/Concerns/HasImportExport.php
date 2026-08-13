<?php

namespace App\Livewire\Concerns;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

trait HasImportExport
{
    // Action names below use Str::camel() because Filament's mountAction()
    // resolves an action by calling method_exists($this, "{name}Action") — a
    // snake_case name here (e.g. "import_tagihan") never matches its camelCase
    // wrapper method (importTagihanAction()), so the action silently fails to
    // resolve: no modal, no notification, no error, just a no-op (bug IE-006).
    // The same applies to importPreviewSiswaAction()/importPreviewTagihanAction()
    // below — each import type needs its own concrete wrapper method.
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

        return Action::make(Str::camel("export_{$exportType}"))
            ->label('Export')
            ->color('success')
            ->icon('heroicon-o-arrow-down-tray')
            ->button()
            ->visible(fn (): bool => PermissionHelper::hasResource('export-data'))
            ->modalHeading('Export Data')
            ->modalSubmitActionLabel('Export')
            ->schema($schema)
            ->action(function (array $data) use ($exportType) {
                if ($exportType === 'siswa' && property_exists($this, 'activeTab')) {
                    $data['jenjang'] = strtoupper($this->activeTab);
                }

                return $this->doExportAction($exportType, $data);
            });
    }

    /**
     * Create an Import action for the table header.
     */
    protected function makeImportAction(string $importType): Action
    {
        return Action::make(Str::camel("import_{$importType}"))
            ->label('Import')
            ->color('warning')
            ->icon('heroicon-o-arrow-up-tray')
            ->button()
            ->visible(fn (): bool => PermissionHelper::hasResource('import-data'))
            ->modalHeading('Import Data '.ucfirst($importType))
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
                    ->storeFiles(false)
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

        if (PermissionHelper::hasResource('export-data')) {
            $actions[] = $this->makeExportAction($type, $exportFilterSchema);
        }

        if (PermissionHelper::hasResource('import-data')) {
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
        return Action::make(Str::camel("template_{$importType}"))
            ->label('Template')
            ->color('gray')
            ->icon('heroicon-o-document-arrow-down')
            ->button()
            ->visible(fn (): bool => PermissionHelper::hasResource('import-data'))
            ->action(function () use ($importType) {
                return $this->downloadImportTemplate($importType);
            });
    }

    /**
     * Create Import History action. Modal content is the ImportHistoryTable
     * Livewire component — a genuine Filament table (bug IE-002), instead of
     * a custom HTML table.
     */
    protected function makeImportHistoryAction(string $importType): Action
    {
        return Action::make(Str::camel("import_history_{$importType}"))
            ->label('Riwayat Import')
            ->color('gray')
            ->icon('heroicon-o-clock')
            ->button()
            ->modalHeading('Riwayat Import '.ucfirst($importType))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalContent(fn () => view('livewire.partials.import-history-modal', ['importType' => $importType]));
    }

    /**
     * Preview action for the siswa import — see makeImportPreviewAction().
     */
    public function importPreviewSiswaAction(): Action
    {
        return $this->makeImportPreviewAction('siswa');
    }

    /**
     * Preview action for the tagihan import — see makeImportPreviewAction().
     */
    public function importPreviewTagihanAction(): Action
    {
        return $this->makeImportPreviewAction('tagihan');
    }

    /**
     * Modal shown right after upload with the per-row validation report
     * (see uploadImportFile()). Not added to makeImportExportActions() —
     * it is only ever mounted programmatically via replaceMountedAction(),
     * never shown as a header button.
     */
    protected function makeImportPreviewAction(string $importType): Action
    {
        return Action::make(Str::camel("import_preview_{$importType}"))
            ->modalHeading('Hasil Pemeriksaan Data Import')
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalContent(fn () => view('livewire.partials.import-preview-modal', [
                'importType' => $importType,
                'previewId' => $this->importPreviewId,
                'preview' => $this->importPreviewData ?? [],
            ]));
    }

    /**
     * Fired by ImportPreviewTable's importSekarangAction() once the import is
     * actually committed, so the page that triggered the upload refreshes.
     */
    #[On('import-selesai')]
    public function handleImportSelesai(): void
    {
        $this->importPreviewId = null;
        $this->importPreviewData = null;

        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
        if (method_exists($this, 'loadData')) {
            $this->loadData();
        }
    }

    /**
     * Perform the export action.
     */
    protected function doExportAction(string $exportType, array $data): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $endpoint = match ($exportType) {
            'siswa' => '/import-export/export/siswa',
            'tagihan' => '/import-export/export/tagihan',
            'pembayaran' => '/import-export/export/pembayaran',
            'kas_harian' => '/import-export/export/kas-harian',
            'rekap_bulanan' => '/import-export/export/rekap-bulanan',
            default => null,
        };

        if (! $endpoint) {
            return null;
        }

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
                $filename = "export_{$exportType}_".now()->format('Y-m-d_His').".{$format}";
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
    protected function downloadImportTemplate(string $importType): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $params = [];
            if ($importType === 'siswa' && property_exists($this, 'activeTab')) {
                $params['jenjang'] = strtoupper($this->activeTab);
            }

            $response = ApiService::client()->get("/import-export/import/template/{$importType}", $params);

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
            $file = $data['import_file'];
            if (! $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                // Fallback in case it's an array of files (e.g. multiple=true was somehow used)
                $file = is_array($file) ? collect($file)->first() : $file;
            }

            if (! $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                throw new \Exception('File upload tidak valid.');
            }

            $response = ApiService::client()
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("/import-export/import/{$importType}/upload");

            if ($response->successful()) {
                $preview = $response->json();
                $this->importPreviewData = $preview;
                $this->importPreviewId = $preview['preview_id'] ?? null;

                // All-or-nothing: no row is ever committed from here. The
                // preview modal reports every invalid row (nomor baris +
                // NIS/NISN + penyebab) and lets the user fix rows inline or
                // upload a corrected file — confirm only happens from
                // ImportPreviewTable::importSekarangAction() once every row
                // is valid.
                $this->replaceMountedAction(Str::camel("import_preview_{$importType}"));
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
}
