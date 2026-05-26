<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportKasRequest;
use App\Http\Requests\ExportPembayaranRequest;
use App\Http\Requests\ExportRekapRequest;
use App\Http\Requests\ExportSiswaRequest;
use App\Http\Requests\ExportTagihanRequest;
use App\Http\Requests\ImportConfirmRequest;
use App\Http\Requests\ImportUploadRequest;
use App\Models\ExportJob;
use App\Models\ImportBatch;
use App\Services\ImportExport\ImportBatchService;
use App\Services\ImportExport\KasExportService;
use App\Services\ImportExport\PembayaranExportService;
use App\Services\ImportExport\SiswaExportService;
use App\Services\ImportExport\SiswaImportService;
use App\Services\ImportExport\TagihanExportService;
use App\Services\ImportExport\TagihanImportService;
use App\Services\ImportExport\TemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExportController extends Controller
{
    public function __construct(
        private SiswaExportService $siswaExportService,
        private TagihanExportService $tagihanExportService,
        private PembayaranExportService $pembayaranExportService,
        private KasExportService $kasExportService,
        private SiswaImportService $siswaImportService,
        private TagihanImportService $tagihanImportService,
        private TemplateService $templateService,
        private ImportBatchService $importBatchService,
    ) {}

    // ==================== EXPORT ====================

    public function exportSiswa(ExportSiswaRequest $request): JsonResponse|BinaryFileResponse
    {
        $filters = $request->validated();
        $format = $filters['format'];
        unset($filters['format']);
        $branchId = auth()->user()->branch_id;

        $result = $this->siswaExportService->export($filters, $format, $branchId);

        if (is_array($result)) {
            return response()->json($result, 202);
        }

        return $result;
    }

    public function exportTagihan(ExportTagihanRequest $request): JsonResponse|BinaryFileResponse
    {
        $filters = $request->validated();
        $format = $filters['format'];
        unset($filters['format']);
        $branchId = auth()->user()->branch_id;

        $result = $this->tagihanExportService->export($filters, $format, $branchId);

        if (is_array($result)) {
            return response()->json($result, 202);
        }

        return $result;
    }

    public function exportPembayaran(ExportPembayaranRequest $request): JsonResponse|BinaryFileResponse
    {
        $filters = $request->validated();
        $format = $filters['format'];
        unset($filters['format']);
        $branchId = auth()->user()->branch_id;

        $result = $this->pembayaranExportService->export($filters, $format, $branchId);

        if (is_array($result)) {
            return response()->json($result, 202);
        }

        return $result;
    }

    public function exportKasHarian(ExportKasRequest $request): JsonResponse|BinaryFileResponse
    {
        $validated = $request->validated();
        $branchId = auth()->user()->branch_id;

        $result = $this->kasExportService->exportKasHarian(
            $validated['bulan'],
            $validated['tahun'],
            $validated['format'],
            $branchId
        );

        if (is_array($result)) {
            return response()->json($result, 202);
        }

        return $result;
    }

    public function exportRekapBulanan(ExportRekapRequest $request): JsonResponse|BinaryFileResponse
    {
        $validated = $request->validated();
        $branchId = auth()->user()->branch_id;

        $result = $this->kasExportService->exportRekapBulanan(
            $validated['tahun'],
            $validated['format'],
            $branchId
        );

        if (is_array($result)) {
            return response()->json($result, 202);
        }

        return $result;
    }

    // ==================== IMPORT ====================

    public function uploadSiswa(ImportUploadRequest $request): JsonResponse
    {
        $branchId = auth()->user()->branch_id;

        try {
            $preview = $this->siswaImportService->validate($request->file('file'), $branchId);

            return response()->json([
                'preview_id' => $preview->previewId,
                'total_rows' => $preview->totalRows,
                'valid_rows' => $preview->validRows,
                'error_rows' => $preview->errorRows,
                'errors' => $preview->errors,
                'requires_queue' => $preview->requiresQueue,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }
    }

    public function confirmSiswa(ImportConfirmRequest $request): JsonResponse
    {
        $branchId = auth()->user()->branch_id;
        $userId = auth()->id();

        try {
            $batch = $this->siswaImportService->confirm(
                $request->validated('preview_id'),
                $branchId,
                $userId
            );

            $statusCode = $batch->status === 'processing' ? 202 : 200;

            return response()->json([
                'batch_reference' => $batch->batch_reference,
                'status' => $batch->status,
                'success_count' => $batch->success_count,
                'error_count' => $batch->error_count,
                'message' => $batch->status === 'processing'
                    ? 'Import sedang diproses di background.'
                    : 'Import berhasil.',
            ], $statusCode);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }
    }

    public function uploadTagihan(ImportUploadRequest $request): JsonResponse
    {
        $branchId = auth()->user()->branch_id;

        try {
            $preview = $this->tagihanImportService->validate($request->file('file'), $branchId);

            return response()->json([
                'preview_id' => $preview->previewId,
                'total_rows' => $preview->totalRows,
                'valid_rows' => $preview->validRows,
                'error_rows' => $preview->errorRows,
                'errors' => $preview->errors,
                'requires_queue' => $preview->requiresQueue,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }
    }

    public function confirmTagihan(ImportConfirmRequest $request): JsonResponse
    {
        $branchId = auth()->user()->branch_id;
        $userId = auth()->id();

        try {
            $batch = $this->tagihanImportService->confirm(
                $request->validated('preview_id'),
                $branchId,
                $userId
            );

            $statusCode = $batch->status === 'processing' ? 202 : 200;

            return response()->json([
                'batch_reference' => $batch->batch_reference,
                'status' => $batch->status,
                'success_count' => $batch->success_count,
                'error_count' => $batch->error_count,
                'message' => $batch->status === 'processing'
                    ? 'Import sedang diproses di background.'
                    : 'Import berhasil.',
            ], $statusCode);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }
    }

    // ==================== TEMPLATES ====================

    public function templateSiswa(): BinaryFileResponse
    {
        $branchId = auth()->user()->branch_id;
        return $this->templateService->generateSiswaTemplate($branchId);
    }

    public function templateTagihan(): BinaryFileResponse
    {
        $branchId = auth()->user()->branch_id;
        return $this->templateService->generateTagihanTemplate($branchId);
    }

    // ==================== HISTORY & ROLLBACK ====================

    public function importHistory(Request $request): JsonResponse
    {
        $branchId = auth()->user()->branch_id;
        $perPage = $request->input('per_page', 15);

        $history = $this->importBatchService->getHistory($branchId, $perPage);

        return response()->json($history);
    }

    public function rollbackImport(string $batchId): JsonResponse
    {
        $branchId = auth()->user()->branch_id;
        $userId = auth()->id();

        try {
            $this->importBatchService->rollback($batchId, $branchId, $userId);

            return response()->json([
                'message' => 'Rollback berhasil. Data import telah dihapus.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }
    }

    // ==================== JOB STATUS ====================

    public function jobStatus(string $jobId): JsonResponse
    {
        // Check export jobs
        $exportJob = ExportJob::where('job_reference', $jobId)->first();
        if ($exportJob) {
            $response = [
                'type' => 'export',
                'status' => $exportJob->status,
                'export_type' => $exportJob->export_type,
            ];

            if ($exportJob->status === 'completed') {
                $response['download_url'] = $exportJob->getSignedUrl();
            } elseif ($exportJob->status === 'failed') {
                $response['error_message'] = $exportJob->error_message;
            }

            return response()->json($response);
        }

        // Check import batches
        $importBatch = ImportBatch::where('batch_reference', $jobId)->first();
        if ($importBatch) {
            return response()->json([
                'type' => 'import',
                'status' => $importBatch->status,
                'import_type' => $importBatch->import_type,
                'success_count' => $importBatch->success_count,
                'error_count' => $importBatch->error_count,
                'error_message' => $importBatch->error_message,
            ]);
        }

        return response()->json([
            'errors' => ['message' => ['Job tidak ditemukan']],
        ], 404);
    }
}
