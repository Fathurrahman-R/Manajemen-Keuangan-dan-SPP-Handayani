<?php

namespace App\Services\ImportExport;

use App\Exports\PembayaranExport;
use App\Models\ExportJob;
use App\Models\Pembayaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PembayaranExportService
{
    /**
     * Threshold for dispatching queue job instead of synchronous export.
     */
    private const QUEUE_THRESHOLD = 1000;

    /**
     * Export pembayaran data to file (sync) or dispatch queue job (async).
     *
     * @param  array  $filters  Filter parameters (tahun_ajaran_id, tanggal_mulai, tanggal_selesai)
     * @param  string  $format  Export format: 'xlsx' or 'csv'
     * @param  int  $branchId  Branch ID scope
     * @return BinaryFileResponse|array Returns file response if sync, or job reference array if queued
     */
    public function export(array $filters, string $format, int $branchId): BinaryFileResponse|array
    {
        $count = $this->getRecordCount($filters, $branchId);

        if ($count > self::QUEUE_THRESHOLD) {
            return $this->dispatchExportJob($filters, $format, $branchId);
        }

        return $this->generateFile($filters, $format, $branchId);
    }

    /**
     * Count records matching the given filters and branch.
     */
    public function getRecordCount(array $filters, int $branchId): int
    {
        return $this->buildQuery($filters, $branchId)->count();
    }

    /**
     * Build the query for exporting pembayaran data.
     *
     * Scopes to branch_id, applies optional filters:
     * - tahun_ajaran_id: filter through related tagihan's tahun_ajaran_id
     * - tanggal_mulai/tanggal_selesai: date range on pembayaran.tanggal
     *
     * If no filters specified, defaults to Periode_Aktif.
     */
    public function buildQuery(array $filters, int $branchId): Builder
    {
        $query = Pembayaran::query()
            ->where('pembayarans.branch_id', $branchId);

        $hasTahunAjaranFilter = ! empty($filters['tahun_ajaran_id']);
        $hasDateFilter = ! empty($filters['tanggal_mulai']) || ! empty($filters['tanggal_selesai']);

        // Apply date range filter
        if (! empty($filters['tanggal_mulai'])) {
            $query->where('pembayarans.tanggal', '>=', $filters['tanggal_mulai']);
        }

        if (! empty($filters['tanggal_selesai'])) {
            $query->where('pembayarans.tanggal', '<=', $filters['tanggal_selesai']);
        }

        // Apply tahun_ajaran_id filter through related tagihan
        if ($hasTahunAjaranFilter) {
            $query->whereHas('tagihan', function (Builder $q) use ($filters) {
                $q->where('tahun_ajaran_id', $filters['tahun_ajaran_id']);
            });
        } elseif (! $hasDateFilter) {
            // Default to Periode_Aktif if no tahun_ajaran_id and no date range filter
            $periodeAktif = TahunAjaran::getAktif($branchId);

            if ($periodeAktif) {
                $query->whereHas('tagihan', function (Builder $q) use ($periodeAktif) {
                    $q->where('tahun_ajaran_id', $periodeAktif->id);
                });
            }
        }

        return $query;
    }

    /**
     * Generate the export file synchronously and return a download response.
     */
    private function generateFile(array $filters, string $format, int $branchId): BinaryFileResponse
    {
        $query = $this->buildQuery($filters, $branchId);

        $export = new PembayaranExport($query);
        $fileName = 'export_pembayaran_'.now()->format('Y-m-d_His').'.'.$format;

        return Excel::download($export, $fileName);
    }

    /**
     * Dispatch an export job for large datasets and return job reference.
     */
    private function dispatchExportJob(array $filters, string $format, int $branchId): array
    {
        $jobReference = Str::uuid()->toString();

        $exportJob = ExportJob::create([
            'job_reference' => $jobReference,
            'user_id' => auth()->id(),
            'export_type' => 'pembayaran',
            'filters' => $filters,
            'format' => $format,
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
            'branch_id' => $branchId,
        ]);

        // Dispatch the queue job
        dispatch(new \App\Jobs\ProcessExportJob(
            exportType: 'pembayaran',
            filters: $filters,
            format: $format,
            branchId: $branchId,
            jobReferenceId: $jobReference,
        ));

        return [
            'queued' => true,
            'job_reference' => $jobReference,
            'export_job_id' => $exportJob->id,
            'message' => 'Export sedang diproses. Silakan cek status secara berkala.',
        ];
    }
}
