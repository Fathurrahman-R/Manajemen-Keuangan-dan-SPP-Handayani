<?php

namespace App\Jobs;

use App\Models\ExportJob;
use App\Services\ImportExport\KasExportService;
use App\Services\ImportExport\PembayaranExportService;
use App\Services\ImportExport\SiswaExportService;
use App\Services\ImportExport\TagihanExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600; // 10 minutes

    public function __construct(
        private string $exportType,
        private array $filters,
        private string $format,
        private int $branchId,
        private string $jobReferenceId,
    ) {}

    public function handle(): void
    {
        $fileName = "export_{$this->exportType}_".now()->format('Y-m-d_His').".{$this->format}";
        $filePath = "exports/{$fileName}";

        try {
            $this->generateFile($filePath);

            // Update ExportJob record
            $exportJob = ExportJob::where('job_reference', $this->jobReferenceId)->first();
            if ($exportJob) {
                $exportJob->update([
                    'status' => 'completed',
                    'file_path' => $filePath,
                    'expires_at' => now()->addHours(24),
                ]);
            }
        } catch (\Throwable $e) {
            $this->markFailed($e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }

    private function generateFile(string $filePath): void
    {
        switch ($this->exportType) {
            case 'siswa':
                $service = app(SiswaExportService::class);
                $query = $service->buildQuery($this->filters, $this->branchId);
                $tahunAjaranId = $this->filters['tahun_ajaran_id'] ?? null;
                if (! $tahunAjaranId) {
                    $periodeAktif = \App\Models\TahunAjaran::getAktif($this->branchId);
                    $tahunAjaranId = $periodeAktif?->id;
                }
                $export = new \App\Exports\SiswaExport($query, $tahunAjaranId);
                Excel::store($export, $filePath, 'local');
                break;

            case 'tagihan':
                $service = app(TagihanExportService::class);
                $query = $service->buildQuery($this->filters, $this->branchId);
                $export = new \App\Exports\TagihanExport($query);
                Excel::store($export, $filePath, 'local');
                break;

            case 'pembayaran':
                $service = app(PembayaranExportService::class);
                $query = $service->buildQuery($this->filters, $this->branchId);
                $export = new \App\Exports\PembayaranExport($query);
                Excel::store($export, $filePath, 'local');
                break;

            case 'kas_harian':
                $service = app(KasExportService::class);
                $bulan = $this->filters['bulan'] ?? now()->month;
                $tahun = $this->filters['tahun'] ?? now()->year;
                // Build queries and export
                $pemasukanQuery = $service->buildPemasukanQuery($bulan, $tahun, $this->branchId);
                $pengeluaranQuery = $service->buildPengeluaranQuery($bulan, $tahun, $this->branchId);

                if ($this->format === 'csv') {
                    $export = new \App\Exports\KasHarianCsvExport($pemasukanQuery, $pengeluaranQuery, $bulan, $tahun);
                } else {
                    $export = new \App\Exports\KasHarianExport($pemasukanQuery, $pengeluaranQuery, $bulan, $tahun);
                }
                Excel::store($export, $filePath, 'local');
                break;

            case 'rekap_bulanan':
                $service = app(KasExportService::class);
                $tahun = $this->filters['tahun'] ?? now()->year;
                $summary = $service->buildMonthlySummary($tahun, $this->branchId);
                $pemasukanQuery = $service->buildYearlyPemasukanQuery($tahun, $this->branchId);
                $pengeluaranQuery = $service->buildYearlyPengeluaranQuery($tahun, $this->branchId);

                if ($this->format === 'csv') {
                    $export = new \App\Exports\RekapBulananCsvExport($summary, $pemasukanQuery, $pengeluaranQuery);
                } else {
                    $export = new \App\Exports\RekapBulananExport($summary, $pemasukanQuery, $pengeluaranQuery, $tahun);
                }
                Excel::store($export, $filePath, 'local');
                break;

            default:
                throw new \InvalidArgumentException("Unknown export type: {$this->exportType}");
        }
    }

    private function markFailed(string $message): void
    {
        $exportJob = ExportJob::where('job_reference', $this->jobReferenceId)->first();
        if ($exportJob) {
            $exportJob->update([
                'status' => 'failed',
                'error_message' => $message,
            ]);
        }
    }
}
