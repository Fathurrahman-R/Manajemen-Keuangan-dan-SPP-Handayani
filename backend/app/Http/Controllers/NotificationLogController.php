<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationLogController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;

        $query = NotificationLog::where('branch_id', $branchId)
            ->orderBy('created_at', 'desc');

        if ($type = $request->query('type')) {
            $query->where('notification_type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $logs = $query->paginate($request->query('per_page', 15));

        return response()->json($logs);
    }

    public function retry(Request $request): JsonResponse
    {
        $request->validate([
            'log_ids' => 'required|array|min:1',
            'log_ids.*' => 'integer|exists:notification_logs,id',
        ]);

        $retriedCount = $this->notificationService->retryFailed($request->input('log_ids'));

        return response()->json([
            'message' => "{$retriedCount} notifikasi berhasil di-retry.",
            'retried_count' => $retriedCount,
        ]);
    }
}
