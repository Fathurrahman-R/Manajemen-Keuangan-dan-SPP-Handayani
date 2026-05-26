<?php

namespace App\Services\ImportExport;

use App\Exports\SiswaExport;
use App\Models\ExportJob;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiswaExportService
{
    /**
     * Threshold for dispatching queue job instead of synchronous export.
     */
    private const QUEUE_THRESHOLD = 1000;

    /**
     * Export siswa data to file (sync) or dispatch queue job (async).
     *
     * @param array $filters Filter parameters (jenjang, kelas_id, status, tahun_ajaran_id)
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
     * Build the query for exporting siswa data.
     *
     * Scopes to branch_id, applies optional filters (jenjang, kelas_id, status, tahun_ajaran_id).
     * Resolves kelas from SiswaKelas based on tahun_ajaran_id or Periode_Aktif.
     */
    public function buildQuery(array $filters, int $branchId): Builder
    {
        $query = Siswa::query()
            ->where('siswas.branch_id', $branchId);

        // Determine which tahun_ajaran to use for kelas resolution
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? null;

        if (!$tahunAjaranId) {
            $periodeAktif = TahunAjaran::getAktif($branchId);
            $tahunAjaranId = $periodeAktif?->id;
        }

        // Join with siswa_kelas to resolve kelas for the specified tahun_ajaran
        if ($tahunAjaranId) {
            $query->leftJoin('siswa_kelas', function ($join) use ($tahunAjaranId) {
                $join->on('siswas.id', '=', 'siswa_kelas.siswa_id')
                    ->where('siswa_kelas.tahun_ajaran_id', '=', $tahunAjaranId);
            });

            // Filter by kelas_id from siswa_kelas if specified
            if (!empty($filters['kelas_id'])) {
                $query->where('siswa_kelas.kelas_id', $filters['kelas_id']);
            }
        } else {
            // Fallback: use the kelas_id directly on siswa table
            if (!empty($filters['kelas_id'])) {
                $query->where('siswas.kelas_id', $filters['kelas_id']);
            }
        }

        // Apply jenjang filter
        if (!empty($filters['jenjang'])) {
            $query->where('siswas.jenjang', $filters['jenjang']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('siswas.status', $filters['status']);
        }

        // Select siswa fields and the resolved kelas_id from siswa_kelas
        if ($tahunAjaranId) {
            $query->select('siswas.*')
                ->addSelect('siswa_kelas.kelas_id as resolved_kelas_id');
        } else {
            $query->select('siswas.*');
        }

        return $query;
    }

    /**
     * Generate the export file synchronously and return a download response.
     */
    private function generateFile(array $filters, string $format, int $branchId): BinaryFileResponse
    {
        $query = $this->buildQuery($filters, $branchId);
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? null;

        if (!$tahunAjaranId) {
            $periodeAktif = TahunAjaran::getAktif($branchId);
            $tahunAjaranId = $periodeAktif?->id;
        }

        $export = new SiswaExport($query, $tahunAjaranId);
        $fileName = 'export_siswa_' . now()->format('Y-m-d_His') . '.' . $format;

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
            'export_type' => 'siswa',
            'filters' => $filters,
            'format' => $format,
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
            'branch_id' => $branchId,
        ]);

        // Dispatch the queue job (ProcessExportJob will be created in task 9.2)
        dispatch(new \App\Jobs\ProcessExportJob(
            exportType: 'siswa',
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
