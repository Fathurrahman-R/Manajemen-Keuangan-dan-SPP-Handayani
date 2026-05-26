<?php

namespace App\Services\ImportExport;

use App\Models\ExportJob;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KasExportService
{
    /**
     * Threshold for dispatching queue job instead of synchronous export.
     */
    private const QUEUE_THRESHOLD = 1000;

    /**
     * Export kas harian (daily cash report) for a given month and year.
     *
     * Generates a file containing:
     * - Rincian pemasukan (Pembayaran records) per date in the specified month
     * - Rincian pengeluaran (Pengeluaran records) per date in the specified month
     *
     * Scoped to branch_id. If total records > 1000, dispatches queue job.
     *
     * @param int $bulan Month (1-12)
     * @param int $tahun Year (e.g., 2024)
     * @param string $format Export format: 'xlsx' or 'csv'
     * @param int $branchId Branch ID scope
     * @return BinaryFileResponse|array Returns file response if sync, or job reference array if queued
     */
    public function exportKasHarian(int $bulan, int $tahun, string $format, int $branchId): BinaryFileResponse|array
    {
        $count = $this->getRecordCount($bulan, $tahun, $branchId);

        if ($count > self::QUEUE_THRESHOLD) {
            return $this->dispatchExportJob('kas_harian', [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ], $format, $branchId);
        }

        return $this->generateKasHarianFile($bulan, $tahun, $format, $branchId);
    }

    /**
     * Export rekap bulanan (monthly summary) for a given year.
     *
     * Generates a file containing:
     * - Monthly summary (bulan, total_pemasukan, total_pengeluaran, saldo)
     * - Rincian pemasukan aggregated per month
     * - Rincian pengeluaran aggregated per month
     *
     * Scoped to branch_id. If total records > 1000, dispatches queue job.
     *
     * @param int $tahun Year (e.g., 2024)
     * @param string $format Export format: 'xlsx' or 'csv'
     * @param int $branchId Branch ID scope
     * @return BinaryFileResponse|array Returns file response if sync, or job reference array if queued
     */
    public function exportRekapBulanan(int $tahun, string $format, int $branchId): BinaryFileResponse|array
    {
        $count = $this->getRekapRecordCount($tahun, $branchId);

        if ($count > self::QUEUE_THRESHOLD) {
            return $this->dispatchExportJob('rekap_bulanan', [
                'tahun' => $tahun,
            ], $format, $branchId);
        }

        return $this->generateRekapBulananFile($tahun, $format, $branchId);
    }

    /**
     * Count total records (pemasukan + pengeluaran) for a given month and year.
     *
     * @param int $bulan Month (1-12)
     * @param int $tahun Year (e.g., 2024)
     * @param int $branchId Branch ID scope
     * @return int Total record count
     */
    public function getRecordCount(int $bulan, int $tahun, int $branchId): int
    {
        $pemasukanCount = $this->buildPemasukanQuery($bulan, $tahun, $branchId)->count();
        $pengeluaranCount = $this->buildPengeluaranQuery($bulan, $tahun, $branchId)->count();

        return $pemasukanCount + $pengeluaranCount;
    }

    /**
     * Count total records (pemasukan + pengeluaran) for an entire year.
     *
     * @param int $tahun Year (e.g., 2024)
     * @param int $branchId Branch ID scope
     * @return int Total record count
     */
    public function getRekapRecordCount(int $tahun, int $branchId): int
    {
        $pemasukanCount = Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->count();

        $pengeluaranCount = Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->count();

        return $pemasukanCount + $pengeluaranCount;
    }

    /**
     * Build query for pemasukan (Pembayaran) records in a given month/year, scoped to branch.
     */
    public function buildPemasukanQuery(int $bulan, int $tahun, int $branchId)
    {
        return Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->orderBy('tanggal');
    }

    /**
     * Build query for pengeluaran records in a given month/year, scoped to branch.
     */
    public function buildPengeluaranQuery(int $bulan, int $tahun, int $branchId)
    {
        return Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->orderBy('tanggal');
    }

    /**
     * Generate kas harian export file synchronously.
     */
    private function generateKasHarianFile(int $bulan, int $tahun, string $format, int $branchId): BinaryFileResponse
    {
        $pemasukanQuery = $this->buildPemasukanQuery($bulan, $tahun, $branchId);
        $pengeluaranQuery = $this->buildPengeluaranQuery($bulan, $tahun, $branchId);

        $fileName = 'export_kas_harian_' . $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '_' . now()->format('His') . '.' . $format;

        if ($format === 'xlsx') {
            $export = new \App\Exports\KasHarianExport($pemasukanQuery, $pengeluaranQuery, $bulan, $tahun);
            return Excel::download($export, $fileName);
        }

        // CSV: single file with "tipe" column
        $export = new \App\Exports\KasHarianCsvExport($pemasukanQuery, $pengeluaranQuery, $bulan, $tahun);
        return Excel::download($export, $fileName);
    }

    /**
     * Generate rekap bulanan export file synchronously.
     */
    private function generateRekapBulananFile(int $tahun, string $format, int $branchId): BinaryFileResponse
    {
        $fileName = 'export_rekap_bulanan_' . $tahun . '_' . now()->format('His') . '.' . $format;

        // Build summary data per month
        $pemasukanPerBulan = Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total_pemasukan')
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total_pemasukan', 'bulan')
            ->toArray();

        $pengeluaranPerBulan = Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total_pengeluaran')
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total_pengeluaran', 'bulan')
            ->toArray();

        $summary = [];
        for ($m = 1; $m <= 12; $m++) {
            $pemasukan = $pemasukanPerBulan[$m] ?? 0;
            $pengeluaran = $pengeluaranPerBulan[$m] ?? 0;
            $summary[] = [
                'bulan' => $m,
                'total_pemasukan' => $pemasukan,
                'total_pengeluaran' => $pengeluaran,
                'saldo' => $pemasukan - $pengeluaran,
            ];
        }

        if ($format === 'xlsx') {
            // Build queries for all months for detail sheets
            $pemasukanQuery = Pembayaran::query()
                ->where('branch_id', $branchId)
                ->whereYear('tanggal', $tahun)
                ->orderBy('tanggal');

            $pengeluaranQuery = Pengeluaran::query()
                ->where('branch_id', $branchId)
                ->whereYear('tanggal', $tahun)
                ->orderBy('tanggal');

            $export = new \App\Exports\RekapBulananExport($summary, $pemasukanQuery, $pengeluaranQuery, $tahun);
            return Excel::download($export, $fileName);
        }

        // CSV: single file with "tipe" column
        $pemasukanQuery = Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal');

        $pengeluaranQuery = Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal');

        $export = new \App\Exports\RekapBulananCsvExport($summary, $pemasukanQuery, $pengeluaranQuery, $tahun);
        return Excel::download($export, $fileName);
    }

    /**
     * Build monthly summary data for a given year and branch.
     * Used by ProcessExportJob for background processing.
     */
    public function buildMonthlySummary(int $tahun, int $branchId): array
    {
        $pemasukanPerBulan = Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total_pemasukan')
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total_pemasukan', 'bulan')
            ->toArray();

        $pengeluaranPerBulan = Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total_pengeluaran')
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total_pengeluaran', 'bulan')
            ->toArray();

        $summary = [];
        for ($m = 1; $m <= 12; $m++) {
            $pemasukan = $pemasukanPerBulan[$m] ?? 0;
            $pengeluaran = $pengeluaranPerBulan[$m] ?? 0;
            $summary[] = [
                'bulan' => $m,
                'total_pemasukan' => $pemasukan,
                'total_pengeluaran' => $pengeluaran,
                'saldo' => $pemasukan - $pengeluaran,
            ];
        }

        return $summary;
    }

    /**
     * Build query for yearly pemasukan records. Used by ProcessExportJob.
     */
    public function buildYearlyPemasukanQuery(int $tahun, int $branchId)
    {
        return Pembayaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal');
    }

    /**
     * Build query for yearly pengeluaran records. Used by ProcessExportJob.
     */
    public function buildYearlyPengeluaranQuery(int $tahun, int $branchId)
    {
        return Pengeluaran::query()
            ->where('branch_id', $branchId)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal');
    }

    /**
     * Dispatch an export job for large datasets and return job reference.
     */
    private function dispatchExportJob(string $exportType, array $filters, string $format, int $branchId): array
    {
        $jobReference = Str::uuid()->toString();

        $exportJob = ExportJob::create([
            'job_reference' => $jobReference,
            'user_id' => auth()->id(),
            'export_type' => $exportType,
            'filters' => $filters,
            'format' => $format,
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
            'branch_id' => $branchId,
        ]);

        dispatch(new \App\Jobs\ProcessExportJob(
            exportType: $exportType,
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
