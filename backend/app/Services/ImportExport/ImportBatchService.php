<?php

namespace App\Services\ImportExport;

use App\Models\ImportBatch;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ImportBatchService
{
    /**
     * Get import history for a branch, sorted by newest first.
     */
    public function getHistory(int $branchId, int $perPage = 15): LengthAwarePaginator
    {
        return ImportBatch::where('branch_id', $branchId)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Check if a batch can be rolled back.
     *
     * @return array{allowed: bool, reason: string}
     */
    public function canRollback(ImportBatch $batch): array
    {
        // Must be in 'completed' status
        if ($batch->status !== 'completed') {
            return [
                'allowed' => false,
                'reason' => "Hanya import dengan status 'completed' yang dapat di-rollback",
            ];
        }

        // Must be within 48 hours
        if ($batch->created_at->diffInHours(now()) >= 48) {
            return [
                'allowed' => false,
                'reason' => 'Batas waktu rollback (48 jam) telah terlewati',
            ];
        }

        // Check for dependent records
        if ($batch->import_type === 'siswa') {
            $affectedSiswa = Siswa::where('batch_reference', $batch->batch_reference)
                ->whereHas('tagihan')
                ->pluck('nis')
                ->toArray();

            if (!empty($affectedSiswa)) {
                return [
                    'allowed' => false,
                    'reason' => 'Tidak dapat rollback: siswa dengan NIS berikut memiliki tagihan: ' . implode(', ', $affectedSiswa),
                ];
            }
        }

        if ($batch->import_type === 'tagihan') {
            $affectedTagihan = Tagihan::where('batch_reference', $batch->batch_reference)
                ->whereHas('pembayaran')
                ->pluck('kode_tagihan')
                ->toArray();

            if (!empty($affectedTagihan)) {
                return [
                    'allowed' => false,
                    'reason' => 'Tidak dapat rollback: tagihan berikut memiliki pembayaran: ' . implode(', ', $affectedTagihan),
                ];
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * Rollback an import batch - delete all records created by it.
     */
    public function rollback(string $batchId, int $branchId, int $userId): bool
    {
        $batch = ImportBatch::where('batch_reference', $batchId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$batch) {
            throw new \InvalidArgumentException('Import batch tidak ditemukan');
        }

        $eligibility = $this->canRollback($batch);
        if (!$eligibility['allowed']) {
            throw new \InvalidArgumentException($eligibility['reason']);
        }

        DB::transaction(function () use ($batch, $userId) {
            if ($batch->import_type === 'siswa') {
                // Delete SiswaKelas records for these siswa
                $siswaIds = Siswa::where('batch_reference', $batch->batch_reference)->pluck('id');
                \App\Models\SiswaKelas::whereIn('siswa_id', $siswaIds)->delete();

                // Delete the siswa records
                Siswa::where('batch_reference', $batch->batch_reference)->delete();
            } elseif ($batch->import_type === 'tagihan') {
                // Delete the tagihan records
                Tagihan::where('batch_reference', $batch->batch_reference)->delete();
            }

            // Update batch status
            $batch->update([
                'status' => 'rolled_back',
                'rolled_back_at' => now(),
                'rolled_back_by' => $userId,
            ]);
        });

        return true;
    }

    /**
     * Update the status of an import batch.
     */
    public function updateStatus(string $batchId, string $status, ?string $errorMessage = null): void
    {
        $batch = ImportBatch::where('batch_reference', $batchId)->first();

        if ($batch) {
            $data = ['status' => $status];
            if ($errorMessage) {
                $data['error_message'] = $errorMessage;
            }
            $batch->update($data);
        }
    }
}
