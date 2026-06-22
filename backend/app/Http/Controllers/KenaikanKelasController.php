<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkPromotionRequest;
use App\Http\Requests\CrossLevelTransferRequest;
use App\Http\Requests\GraduationRequest;
use App\Http\Requests\IndividualPromotionRequest;
use App\Http\Requests\RetentionRequest;
use App\Models\BatchPromosi;
use App\Models\Kelas;
use App\Services\KenaikanKelasService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KenaikanKelasController extends Controller
{
    public function __construct(
        protected KenaikanKelasService $kenaikanKelasService
    ) {}

    /**
     * Process bulk promotion for all eligible students in a kelas.
     */
    public function bulkPromotion(BulkPromotionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $branchId = Auth::user()->branch_id;
        $userId = Auth::id();

        $result = $this->kenaikanKelasService->processBulkPromotion(
            $data['kelas_id'],
            $data['tahun_ajaran_id'],
            $userId,
            $branchId
        );

        return response()->json([
            'message' => 'Bulk promotion berhasil diproses.',
            'data' => $result,
        ], 200);
    }

    /**
     * Process individual promotion for a specific student.
     */
    public function individualPromotion(IndividualPromotionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $branchId = Auth::user()->branch_id;
        $userId = Auth::id();

        $result = $this->kenaikanKelasService->processIndividualPromotion(
            $data['siswa_id'],
            $data['target_kelas_id'],
            $data['tahun_ajaran_id'],
            $data['is_pindah_jenjang'] ?? false,
            $userId,
            $branchId
        );

        return response()->json([
            'message' => 'Individual promotion berhasil diproses.',
            'data' => $result,
        ], 200);
    }

    /**
     * Process graduation for specified students.
     */
    public function graduation(GraduationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $branchId = Auth::user()->branch_id;
        $userId = Auth::id();

        $result = $this->kenaikanKelasService->processGraduation(
            $data['siswa_ids'],
            $data['tahun_ajaran_id'],
            $userId,
            $branchId
        );

        return response()->json([
            'message' => 'Kelulusan berhasil diproses.',
            'data' => $result,
        ], 200);
    }

    /**
     * Process retention (tinggal kelas) for specified students.
     */
    public function retention(RetentionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $branchId = Auth::user()->branch_id;
        $userId = Auth::id();

        $result = $this->kenaikanKelasService->processRetention(
            $data['siswa_ids'],
            $data['tahun_ajaran_id'],
            $userId,
            $branchId
        );

        return response()->json([
            'message' => 'Tinggal kelas berhasil diproses.',
            'data' => $result,
        ], 200);
    }

    /**
     * Process cross-level transfer (pindah jenjang) for a student.
     */
    public function crossLevelTransfer(CrossLevelTransferRequest $request): JsonResponse
    {
        $data = $request->validated();
        $branchId = Auth::user()->branch_id;
        $userId = Auth::id();

        $result = $this->kenaikanKelasService->processCrossLevelTransfer(
            $data['siswa_id'],
            $data['target_kelas_id'],
            $data['tahun_ajaran_id'],
            $userId,
            $branchId
        );

        return response()->json([
            'message' => 'Pindah jenjang berhasil diproses.',
            'data' => $result,
        ], 200);
    }

    /**
     * Undo a completed batch operation.
     */
    public function undo(string $batchId): JsonResponse
    {
        $branchId = Auth::user()->branch_id;

        $result = $this->kenaikanKelasService->undoBatch($batchId, $branchId);

        return response()->json([
            'message' => 'Batch berhasil dibatalkan.',
            'data' => $result,
        ], 200);
    }

    /**
     * List all batch promosi for the authenticated user's branch.
     */
    public function listBatches(): JsonResponse
    {
        $branchId = Auth::user()->branch_id;

        $batches = BatchPromosi::where('branch_id', $branchId)
            ->with(['kelas:id,nama', 'sourceTahunAjaran:id,nama', 'targetTahunAjaran:id,nama', 'processedBy:id,name'])
            ->withCount('details')
            ->orderBy('processed_at', 'desc')
            ->paginate(15);

        // Add processed_by_user manually to avoid column/relation name conflicts
        // (processed_by is the integer FK column, processedBy is the relation)
        $batches->getCollection()->transform(function ($batch) {
            $batchArray = $batch->toArray();
            // processedBy relation serializes as 'processed_by' which overwrites the FK column
            // so we rename it to 'processed_by_user' for clarity
            $batchArray['processed_by_user'] = $batchArray['processed_by'] ?? null;
            // Restore the original FK integer value
            $batchArray['processed_by'] = $batch->getAttributes()['processed_by'];
            return $batchArray;
        });

        return response()->json($batches, 200);
    }

    /**
     * Show a single batch with its details.
     */
    public function showBatch(string $id): JsonResponse
    {
        $branchId = Auth::user()->branch_id;

        $batch = BatchPromosi::where('id', $id)
            ->where('branch_id', $branchId)
            ->first();

        if (!$batch) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Batch tidak ditemukan.']]
            ], 404));
        }

        $batch->load(['details.siswa', 'details.sourceKelas', 'details.targetKelas', 'kelas:id,nama', 'sourceTahunAjaran:id,nama', 'targetTahunAjaran:id,nama', 'processedBy:id,name']);

        // Build response with explicit key mapping to avoid column/relation naming conflicts
        $batchData = $batch->toArray();
        // processedBy relation serializes as 'processed_by' which overwrites the FK integer
        $batchData['processed_by_user'] = $batchData['processed_by'] ?? null;
        $batchData['processed_by'] = $batch->getAttributes()['processed_by'];

        return response()->json([
            'data' => $batchData,
        ], 200);
    }

    /**
     * Get eligible students for promotion from a specific kelas and tahun ajaran.
     */
    public function eligibleStudents(Request $request): JsonResponse
    {
        $kelasId = $request->query('kelas_id');
        $tahunAjaranId = $request->query('tahun_ajaran_id');

        if (!$kelasId || !$tahunAjaranId) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Parameter kelas_id dan tahun_ajaran_id wajib diisi.']]
            ], 422));
        }

        $students = $this->kenaikanKelasService->getEligibleStudents(
            (int) $kelasId,
            (int) $tahunAjaranId
        );

        return response()->json([
            'data' => $students,
        ], 200);
    }

    /**
     * Get class hierarchy ordered by level for the authenticated user's branch.
     */
    public function classHierarchy(Request $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $jenjang = $request->query('jenjang');

        $query = Kelas::where('branch_id', $branchId)
            ->whereNotNull('level')
            ->orderBy('level', 'asc');

        if ($jenjang) {
            $query->where('jenjang', $jenjang);
        }

        $kelasList = $query->get();

        return response()->json([
            'data' => $kelasList,
        ], 200);
    }
}
