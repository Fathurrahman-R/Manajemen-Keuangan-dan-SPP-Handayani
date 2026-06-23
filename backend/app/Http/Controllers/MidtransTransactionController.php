<?php

namespace App\Http\Controllers;

use App\Models\MidtransTransaction;
use App\Services\Midtrans\MidtransInitiationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransTransactionController extends Controller
{
    /**
     * Initiate a Midtrans Snap payment transaction.
     *
     * POST /api/midtrans/transactions
     */
    public function initiate(Request $request, MidtransInitiationService $service): JsonResponse
    {
        $request->validate([
            'kode_tagihan' => 'required|string',
            'amount_paid' => 'required|integer|min:1',
            'payment_channel' => 'nullable|string|max:32',
        ]);

        $result = $service->initiate(
            $request->user(),
            $request->kode_tagihan,
            $request->integer('amount_paid'),
            $request->input('payment_channel'),
        );

        return response()->json([
            'order_id' => $result->orderId,
            'snap_token' => $result->snapToken,
            'redirect_url' => $result->redirectUrl,
            'amount_paid' => $result->amountPaid,
            'fee_amount' => $result->feeAmount,
            'gross_amount' => $result->grossAmount,
            'client_key' => config('midtrans.client_key'),
        ]);
    }

    /**
     * List of available fee channels for the Portal "Bayar Online" modal.
     *
     * GET /api/midtrans/fee-channels
     */
    public function feeChannels(\App\Services\Midtrans\MidtransFeeService $feeService): JsonResponse
    {
        return response()->json([
            'data' => $feeService->availableChannels(),
            'default' => config('midtrans.default_channel', 'qris'),
        ]);
    }

    /**
     * Initiate a Midtrans Snap payment that settles MULTIPLE tagihan in one
     * Snap session (siswa pays all chosen bills in full).
     *
     * POST /api/midtrans/transactions/batch
     */
    public function initiateBatch(Request $request, MidtransInitiationService $service): JsonResponse
    {
        $request->validate([
            'kode_tagihan_list' => 'required|array|min:1|max:50',
            'kode_tagihan_list.*' => 'required|string',
            'payment_channel' => 'nullable|string|max:32',
        ]);

        $result = $service->initiateBatch(
            $request->user(),
            $request->input('kode_tagihan_list', []),
            $request->input('payment_channel'),
        );

        return response()->json([
            'order_id' => $result->orderId,
            'snap_token' => $result->snapToken,
            'redirect_url' => $result->redirectUrl,
            'amount_paid' => $result->amountPaid,
            'fee_amount' => $result->feeAmount,
            'gross_amount' => $result->grossAmount,
            'client_key' => config('midtrans.client_key'),
        ]);
    }

    /**
     * Show the current status of a Midtrans transaction (for portal polling).
     *
     * GET /api/midtrans/transactions/{order_id}
     */
    public function show(Request $request, string $orderId): JsonResponse
    {
        $trx = MidtransTransaction::where('order_id', $orderId)->first();

        if (! $trx) {
            return response()->json(['error_code' => 'ORDER_NOT_FOUND', 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        // Ownership check: user's siswa NIS must match the transaction NIS
        $userNis = $request->user()->siswa->nis ?? null;
        if ($userNis === null || $userNis !== $trx->nis) {
            return response()->json(['error_code' => 'FORBIDDEN', 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'order_id' => $trx->order_id,
            'kode_tagihan' => $trx->kode_tagihan,
            'status' => $trx->status,
            'amount_paid' => $trx->amount_paid,
            'fee_amount' => $trx->fee_amount,
            'gross_amount' => $trx->gross_amount,
            'payment_type' => $trx->payment_type,
            'snap_token' => $trx->snap_token,
            'redirect_url' => $trx->snap_redirect_url,
            'expired_at' => $trx->expired_at?->toIso8601String(),
            'paid_at' => $trx->paid_at?->toIso8601String(),
            'created_at' => $trx->created_at?->toIso8601String(),
        ]);
    }
}
