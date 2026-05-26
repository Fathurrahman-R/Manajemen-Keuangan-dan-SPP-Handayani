<?php

namespace App\Services\ImportExport;

use App\Exports\TagihanExport;
use App\Models\ExportJob;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TagihanExportService
{
    /**
     * Threshold for dispatching queue job instead of synchronous export.
     */
    private const QUEUE_THRESHOLD = 1000;

    /**
     * Export tagihan data to file (sync) or dispatch queue job (async).
     *
     * @param array $filters Filter parameters (tahun_ajaran_id, jenjang, kelas_id, status)
     * @param string $format Export format: 'xlsx' or 'csv'
     * @param int $branchId Branch ID scope
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
     * Build the query for exporting tagihan data.
     *
     * Scopes to branch_id, applies optional filters (tahun_ajaran_id, jenjang, kelas_id, status).
     * Defaults to Periode_Aktif if tahun_ajaran_id is not specified.
     * Joins with Siswa and SiswaKelas for jenjang/kelas filtering.
     */
    public function buildQuery(array $filters, int $branchId): Builder
    {
        $query = Tagihan::query()
            ->where('tagihans.branch_id', $branchId);

        // Determine which tahun_ajaran to use (default to Periode_Aktif)
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? null;

        if (!$tahunAjaranId) {
            $periodeAktif = TahunAjaran::getAktif($branchId);
            $tahunAjaranId = $periodeAktif?->id;
        }

        // Filter by tahun_ajaran_id
        if ($tahunAjaranId) {
            $query->where('tagihans.tahun_ajaran_id', $tahunAjaranId);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('tagihans.status', $filters['status']);
        }

        // Join with Siswa for jenjang filtering and name resolution
        $needsSiswaJoin = !empty($filters['jenjang']) || !empty($filters['kelas_id']);

        if ($needsSiswaJoin) {
            $query->join('siswas', 'tagihans.nis', '=', 'siswas.nis');

            // Apply jenjang filter
            if (!empty($filters['jenjang'])) {
                $query->where('siswas.jenjang', $filters['jenjang']);
            }

            // Filter by kelas_id via SiswaKelas for the relevant tahun_ajaran
            if (!empty($filters['kelas_id'])) {
                $query->join('siswa_kelas', function ($join) use ($tahunAjaranId) {
                    $join->on('siswas.id', '=', 'siswa_kelas.siswa_id')
                        ->where('siswa_kelas.tahun_ajaran_id', '=', $tahunAjaranId);
                })
                ->where('siswa_kelas.kelas_id', $filters['kelas_id']);
            }

            $query->select('tagihans.*');
        } else {
            $query->select('tagihans.*');
        }

        return $query;
    }

    /**
     * Generate the export file synchronously and return a download response.
     */
    private function generateFile(array $filters, string $format, int $branchId): BinaryFileResponse
    {
        $query = $this->buildQuery($filters, $branchId);

        $export = new TagihanExport($query);
        $fileName = 'export_tagihan_' . now()->format('Y-m-d_His') . '.' . $format;

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
            'export_type' => 'tagihan',
            'filters' => $filters,
            'format' => $format,
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
            'branch_id' => $branchId,
        ]);

        // Dispatch the queue job (ProcessExportJob will be created in task 9.2)
        dispatch(new \App\Jobs\ProcessExportJob(
            exportType: 'tagihan',
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
