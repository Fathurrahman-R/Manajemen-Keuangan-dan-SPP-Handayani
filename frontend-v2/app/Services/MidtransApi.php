<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;

class MidtransApi
{
    /**
     * Returns an authenticated HTTP client targeting the backend API.
     */
    private static function client(): PendingRequest
    {
        return ApiService::client();
    }

    /**
     * Initiate a Midtrans payment transaction.
     *
     * @throws MidtransApiException
     */
    public static function initiate(string $kodeTagihan, int $amountPaid, ?string $paymentChannel = null): array
    {
        $payload = [
            'kode_tagihan' => $kodeTagihan,
            'amount_paid' => $amountPaid,
        ];

        if ($paymentChannel !== null && $paymentChannel !== '') {
            $payload['payment_channel'] = $paymentChannel;
        }

        $response = static::client()->post('/midtrans/transactions', $payload);

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: $response->json('data'),
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * Initiate a batch (multi-tagihan) Midtrans payment.
     *
     * @param  list<string>  $kodeTagihanList
     *
     * @throws MidtransApiException
     */
    public static function initiateBatch(array $kodeTagihanList, ?string $paymentChannel = null): array
    {
        $payload = ['kode_tagihan_list' => array_values($kodeTagihanList)];

        if ($paymentChannel !== null && $paymentChannel !== '') {
            $payload['payment_channel'] = $paymentChannel;
        }

        $response = static::client()->post('/midtrans/transactions/batch', $payload);

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: $response->json('data'),
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * Get the available payment fee channels (per-kanal pricing).
     *
     * @return array{data: list<array{key:string,label:string,amount:int}>, default: ?string}
     *
     * @throws MidtransApiException
     */
    public static function feeChannels(): array
    {
        $response = static::client()->get('/midtrans/fee-channels');

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: $response->json('data'),
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * Get the current status of a Midtrans transaction.
     *
     * @throws MidtransApiException
     */
    public static function show(string $orderId): array
    {
        $response = static::client()->get("/midtrans/transactions/{$orderId}");

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: $response->json('data'),
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * Admin: List all Midtrans transactions with optional filters.
     *
     * @throws MidtransApiException
     */
    public static function adminList(array $filters = []): array
    {
        $response = static::client()->get('/midtrans/admin/transactions', $filters);

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: null,
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * Admin: Get transaction details.
     *
     * @throws MidtransApiException
     */
    public static function adminShow(string $orderId): array
    {
        $response = static::client()->get("/midtrans/admin/transactions/{$orderId}");

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: null,
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * Admin: Get transaction logs (audit trail).
     *
     * @throws MidtransApiException
     */
    public static function adminLogs(string $orderId): array
    {
        $response = static::client()->get("/midtrans/admin/transactions/{$orderId}/logs");

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: null,
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * Admin: Trigger manual sync for a transaction.
     *
     * @throws MidtransApiException
     */
    public static function sync(string $orderId): array
    {
        $response = static::client()->post("/midtrans/admin/transactions/{$orderId}/sync");

        if ($response->failed()) {
            throw new MidtransApiException(
                errorCode: $response->json('error_code'),
                data: $response->json('data'),
                httpStatus: $response->status(),
            );
        }

        return $response->json();
    }
}
