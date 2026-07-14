<?php

namespace App\Http\Controllers;

use App\Models\PengeluaranRequest;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengeluaranRequestController extends Controller
{
    use Traits\Sortable;

    public function __construct(
        private readonly WorkflowService $workflowService
    ) {}

    #[QueryParameter('sort', description: 'Column to sort by (created_at, jumlah, tanggal_kebutuhan, status)', required: false, example: 'created_at')]
    #[QueryParameter('direction', description: 'Sort direction (asc or desc)', required: false, example: 'desc')]
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->user()->branch_id;
        $userId = $request->user()->id;

        $query = PengeluaranRequest::where('branch_id', $branchId)
            ->with(['requester:id,name,username', 'approvalLogs:id,pengeluaran_request_id,new_status,note,user_id,created_at', 'approvalLogs.user:id,name'])
            // Draft requests are only visible to the requester
            ->where(function ($q) use ($userId) {
                $q->where('status', '!=', 'draft')
                    ->orWhere('requester_id', $userId);
            })
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter periode ajaran berbasis tanggal_kebutuhan ke range tahun_ajarans.
        // all_periods=1 atau tahun_ajaran_id=0 = semua periode.
        $tahunAjaranId = $request->query('tahun_ajaran_id');
        $allPeriods = $request->boolean('all_periods')
            || ($tahunAjaranId !== null && $tahunAjaranId !== '' && (int) $tahunAjaranId === 0);

        if (! $allPeriods && $tahunAjaranId !== null && $tahunAjaranId !== '') {
            $ta = \App\Models\TahunAjaran::where('id', (int) $tahunAjaranId)
                ->where('branch_id', $branchId)
                ->first();
            if ($ta) {
                $query->whereBetween('tanggal_kebutuhan', [$ta->tanggal_mulai, $ta->tanggal_selesai]);
            }
        }

        $this->applySorting(
            $query,
            ['created_at', 'jumlah', 'tanggal_kebutuhan', 'status'],
            'created_at',
            'desc'
        );

        $data = $query->paginate($request->query('per_page', 15));

        return response()->json($data);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pengeluaranRequest = PengeluaranRequest::with(['requester:id,name,username', 'approvalLogs.user:id,name,username'])
            ->find($id);

        if (! $pengeluaranRequest) {
            return response()->json(['message' => 'Request tidak ditemukan.'], 404);
        }

        if ($pengeluaranRequest->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => $pengeluaranRequest]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uraian' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:1',
            'tanggal_kebutuhan' => 'required|date',
            'kategori_pengeluaran' => 'nullable|string|max:100',
            'lampiran' => 'nullable|file|max:2048|mimes:pdf,jpg,png',
        ]);

        if ($request->hasFile('lampiran')) {
            $data['lampiran'] = $request->file('lampiran')->store('lampiran', 'public');
        }

        $pengeluaranRequest = $this->workflowService->create($data, $request->user());

        return response()->json(['data' => $pengeluaranRequest], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pengeluaranRequest = PengeluaranRequest::where('branch_id', $request->user()->branch_id)->find($id);

        if (! $pengeluaranRequest) {
            return response()->json(['message' => 'Request tidak ditemukan.'], 404);
        }

        if ($pengeluaranRequest->requester_id !== $request->user()->id) {
            return response()->json(['message' => 'Hanya pembuat request yang bisa mengubah.'], 403);
        }

        $data = $request->validate([
            'uraian' => 'sometimes|string|max:255',
            'jumlah' => 'sometimes|numeric|min:1',
            'tanggal_kebutuhan' => 'sometimes|date',
            'kategori_pengeluaran' => 'nullable|string|max:100',
            'lampiran' => 'nullable|file|max:2048|mimes:pdf,jpg,png',
        ]);

        if ($request->hasFile('lampiran')) {
            $data['lampiran'] = $request->file('lampiran')->store('lampiran', 'public');
        }

        $pengeluaranRequest = $this->workflowService->update($pengeluaranRequest, $data);

        return response()->json(['data' => $pengeluaranRequest]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $pengeluaranRequest = PengeluaranRequest::where('branch_id', $request->user()->branch_id)->find($id);

        if (! $pengeluaranRequest) {
            return response()->json(['message' => 'Request tidak ditemukan.'], 404);
        }

        if (! $pengeluaranRequest->isDeletable()) {
            return response()->json(['errors' => ['status' => ['Request hanya bisa dihapus saat status draft.']]], 422);
        }

        $pengeluaranRequest->delete();

        return response()->json(['message' => 'Request berhasil dihapus.']);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $pengeluaranRequest = PengeluaranRequest::where('branch_id', $request->user()->branch_id)->find($id);

        if (! $pengeluaranRequest) {
            return response()->json(['message' => 'Request tidak ditemukan.'], 404);
        }

        // Only the requester can submit their own request
        if ($pengeluaranRequest->requester_id !== $request->user()->id) {
            return response()->json(['message' => 'Hanya pengaju yang dapat submit request ini.'], 403);
        }

        $result = $this->workflowService->submit($pengeluaranRequest, $request->user());

        return response()->json(['data' => $result]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $pengeluaranRequest = PengeluaranRequest::where('branch_id', $request->user()->branch_id)->find($id);

        if (! $pengeluaranRequest) {
            return response()->json(['message' => 'Request tidak ditemukan.'], 404);
        }

        $note = $request->input('note');
        $result = $this->workflowService->approve($pengeluaranRequest, $request->user(), $note);

        return response()->json(['data' => $result]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $pengeluaranRequest = PengeluaranRequest::where('branch_id', $request->user()->branch_id)->find($id);

        if (! $pengeluaranRequest) {
            return response()->json(['message' => 'Request tidak ditemukan.'], 404);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $result = $this->workflowService->reject($pengeluaranRequest, $request->user(), $request->input('reason'));

        return response()->json(['data' => $result]);
    }

    public function disburse(Request $request, int $id): JsonResponse
    {
        $pengeluaranRequest = PengeluaranRequest::where('branch_id', $request->user()->branch_id)->find($id);

        if (! $pengeluaranRequest) {
            return response()->json(['message' => 'Request tidak ditemukan.'], 404);
        }

        // Only the requester can disburse their own approved request
        if ($pengeluaranRequest->requester_id !== $request->user()->id) {
            return response()->json(['message' => 'Hanya pengaju yang dapat mencairkan request ini.'], 403);
        }

        $result = $this->workflowService->disburse($pengeluaranRequest, $request->user());

        return response()->json(['data' => $result]);
    }
}
