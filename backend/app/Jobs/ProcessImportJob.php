<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\TahunAjaran;
use App\Services\ImportExport\SiswaImportService;
use App\Services\ImportExport\TagihanImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public function __construct(
        private string $previewId,
        private string $importType,
        private int $branchId,
        private int $userId,
        private string $batchId,
    ) {}

    public function handle(): void
    {
        $cached = Cache::get("import_preview:{$this->previewId}");

        if (! $cached) {
            $this->markFailed('Sesi preview telah kedaluwarsa.');

            return;
        }

        $periodeAktif = TahunAjaran::getAktif($this->branchId);
        if (! $periodeAktif) {
            $this->markFailed('Periode aktif belum diatur untuk cabang ini.');

            return;
        }

        $validData = $cached['validData'];
        $successCount = 0;

        try {
            if ($this->importType === 'siswa') {
                $service = app(SiswaImportService::class);
                $successCount = $service->processRows($validData, $this->branchId, $this->batchId, $periodeAktif->id);
            } elseif ($this->importType === 'tagihan') {
                $service = app(TagihanImportService::class);
                $successCount = $service->processRows($validData, $this->branchId, $this->batchId, $periodeAktif->id);
            }

            // Update batch to completed
            $batch = ImportBatch::where('batch_reference', $this->batchId)->first();
            if ($batch) {
                $batch->update([
                    'success_count' => $successCount,
                    'status' => 'completed',
                ]);
            }

            // Clear cache
            Cache::forget("import_preview:{$this->previewId}");
        } catch (\Throwable $e) {
            $this->markFailed($e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }

    private function markFailed(string $message): void
    {
        $batch = ImportBatch::where('batch_reference', $this->batchId)->first();
        if ($batch) {
            $batch->update([
                'status' => 'failed',
                'error_message' => $message,
            ]);
        }
    }
}
