<?php

namespace App\Http\Controllers;

use App\Models\MidtransTransaction;
use App\Models\MidtransTransactionLog;
use App\Services\Midtrans\MidtransStatusSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransAdminController extends Controller
{
    /**
     * Paginated list of Midtrans transactions with optional filters.
     *
     * GET /api/midtrans/admin/transactions
     */
    public function index(Request $request): JsonResponse
    {
        $query = MidtransTransaction::query()
            ->where('branch_id', $request->user()->branch_id)
            ->with(['tagihan.siswa:id,nis,nama'])
            ->select([
                'id',
                'order_id',
                'kode_tagihan',
                'nis',
                'amount_paid',
                'fee_amount',
                'gross_amount',
                'status',
                'payment_type',
                'branch_id',
                'created_at',
                'updated_at',
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 15), 100);
        $transactions = $query->paginate($perPage);

        // Append nama_siswa from relation
        $transactions->getCollection()->transform(function ($trx) {
            $trx->nama_siswa = $trx->tagihan?->siswa?->nama;
            unset($trx->tagihan);

            return $trx;
        });

        return response()->json($transactions);
    }

    /**
     * Show a single Midtrans transaction detail.
     *
     * GET /api/midtrans/admin/transactions/{order_id}
     */
    public function show(Request $request, string $orderId): JsonResponse
    {
        $trx = MidtransTransaction::where('order_id', $orderId)
            ->where('branch_id', $request->user()->branch_id)
            ->with(['tagihan.siswa:id,nis,nama', 'initiator:id,name'])
            ->firstOrFail();

        return response()->json([
            'order_id' => $trx->order_id,
            'kode_tagihan' => $trx->kode_tagihan,
            'nis' => $trx->nis,
            'nama_siswa' => $trx->tagihan?->siswa?->nama,
            'amount_paid' => $trx->amount_paid,
            'fee_amount' => $trx->fee_amount,
            'gross_amount' => $trx->gross_amount,
            'currency' => $trx->currency,
            'status' => $trx->status,
            'payment_type' => $trx->payment_type,
            'snap_token' => $trx->snap_token,
            'snap_redirect_url' => $trx->snap_redirect_url,
            'expired_at' => $trx->expired_at?->toIso8601String(),
            'paid_at' => $trx->paid_at?->toIso8601String(),
            'initiator' => $trx->initiator?->name,
            'branch_id' => $trx->branch_id,
            'created_at' => $trx->created_at?->toIso8601String(),
            'updated_at' => $trx->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Show transaction logs with masked sensitive fields.
     *
     * GET /api/midtrans/admin/transactions/{order_id}/logs
     */
    public function logs(Request $request, string $orderId): JsonResponse
    {
        // Verify transaction exists and belongs to the caller's branch
        MidtransTransaction::where('order_id', $orderId)
            ->where('branch_id', $request->user()->branch_id)
            ->firstOrFail();

        $logs = MidtransTransactionLog::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'order_id' => $log->order_id,
                    'direction' => $log->direction,
                    'http_status' => $log->http_status,
                    'raw_payload' => $this->maskLogPayload($log->raw_payload),
                    'remote_ip' => $log->remote_ip,
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            });

        return response()->json(['data' => $logs]);
    }

    /**
     * Manually sync a transaction's status by querying Midtrans Status API.
     *
     * POST /api/midtrans/admin/transactions/{order_id}/sync
     */
    public function sync(Request $request, string $orderId, MidtransStatusSyncService $service): JsonResponse
    {
        $trx = MidtransTransaction::where('order_id', $orderId)
            ->where('branch_id', $request->user()->branch_id)
            ->firstOrFail();

        try {
            $service->syncManual($trx);
        } catch (\App\Exceptions\Midtrans\TransactionAlreadyFinalException $e) {
            // Ignore if it's already final, just return the current status
        } catch (\App\Exceptions\Midtrans\TransactionNotYetProcessedException $e) {
            // User hasn't opened Snap or picked a payment method yet.
            // Midtrans returns 404, but we know it's still pending.
        } catch (\App\Exceptions\Midtrans\MidtransStatusUnavailableException $e) {
            // Midtrans API is unreachable.
            return response()->json(['error_code' => 'API_UNAVAILABLE', 'message' => 'Layanan Midtrans sedang tidak tersedia'], 503);
        } catch (\App\Exceptions\Midtrans\MidtransException $e) {
            // Any other known Midtrans domain error (e.g. overpayment blocked) —
            // let the global renderable handler in bootstrap/app.php format it.
            throw $e;
        } catch (\Throwable $e) {
            // Unexpected failure (enum/mapping edge case, DB contention, etc).
            // Never let this fall through as a bare 500 with no error_code —
            // that renders as an unhelpful "Unknown Midtrans API error" on the
            // frontend and hides whether the status actually changed.
            Log::error('MidtransAdminController::sync unexpected failure', [
                'order_id' => $orderId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error_code' => 'SYNC_FAILED',
                'message' => 'Sinkronisasi gagal karena kesalahan tak terduga.',
                'status' => $trx->fresh()->status,
            ], 500);
        }

        return response()->json(['status' => $trx->fresh()->status]);
    }

    /**
     * Mask sensitive fields (server_key, signature_key) in log payloads for UI display.
     */
    private function maskLogPayload(?string $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sensitiveKeys = ['server_key', 'signature_key'];

            array_walk_recursive($decoded, function (&$value, $key) use ($sensitiveKeys) {
                if (in_array($key, $sensitiveKeys, true)) {
                    $value = '***';
                }
            });

            return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Fallback: string-level masking
        $masked = preg_replace('/"server_key"\s*:\s*"[^"]*"/', '"server_key":"***"', $payload);
        $masked = preg_replace('/"signature_key"\s*:\s*"[^"]*"/', '"signature_key":"***"', $masked);

        return $masked;
    }
}
