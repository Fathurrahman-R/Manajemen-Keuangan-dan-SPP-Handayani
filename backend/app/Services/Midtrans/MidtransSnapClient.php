<?php

namespace App\Services\Midtrans;

use App\Exceptions\Midtrans\MidtransStatusUnavailableException;
use App\Exceptions\Midtrans\MidtransUnavailableException;
use App\Services\Midtrans\Dto\MidtransStatusResponse;
use App\Services\Midtrans\Dto\SnapPayload;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransSnapClient implements MidtransClient
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.environment') === 'production';
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Point Midtrans SDK's cURL calls at a known-good CA bundle. PHP on
        // Windows ships without `curl.cainfo`/`openssl.cafile` configured, so
        // HTTPS handshakes against Midtrans fail with
        // "SSL certificate problem: unable to get local issuer certificate".
        // Laravel already pulls in `composer/ca-bundle`, which exposes the
        // Mozilla CA root list in a portable way.
        if (class_exists(\Composer\CaBundle\CaBundle::class)) {
            $caPath = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();

            if (is_string($caPath) && $caPath !== '') {
                $option = is_dir($caPath) ? CURLOPT_CAPATH : CURLOPT_CAINFO;

                // The Midtrans SDK accesses Config::$curlOptions[CURLOPT_HTTPHEADER]
                // unconditionally when merging cURL options, so we always
                // include an empty header array to avoid an "Undefined array
                // key" warning being promoted to an exception on PHP 8.4+.
                Config::$curlOptions = [
                    $option => $caPath,
                    CURLOPT_HTTPHEADER => [],
                ];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function createSnapTransaction(SnapPayload $payload): array
    {
        $params = [
            'transaction_details' => [
                'order_id' => $payload->orderId,
                'gross_amount' => $payload->grossAmount,
            ],
            'item_details' => $payload->itemDetails,
            'customer_details' => $payload->customerDetails,
            'expiry' => $payload->expiry,
        ];

        if (! empty($payload->callbacks)) {
            $params['callbacks'] = $payload->callbacks;
        }

        if (! empty($payload->enabledPayments)) {
            $params['enabled_payments'] = $payload->enabledPayments;
        }

        try {
            $response = Snap::createTransaction($params);

            return [
                'token' => $response->token,
                'redirect_url' => $response->redirect_url,
            ];
        } catch (\Exception $e) {
            throw new MidtransUnavailableException($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getStatus(string $orderId): MidtransStatusResponse
    {
        try {
            $response = Transaction::status($orderId);

            return MidtransStatusResponse::fromArray((array) $response);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Midtrans status API call failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            // Midtrans returns HTTP 404 / "Transaction doesn't exist" until the
            // buyer picks a payment channel on the Snap page — the Snap token
            // alone is not enough to register the transaction in Midtrans's
            // ledger. Surface that as a more actionable error.
            if (str_contains($e->getMessage(), 'HTTP status code: 404')
                || str_contains($e->getMessage(), "Transaction doesn't exist")
            ) {
                throw new \App\Exceptions\Midtrans\TransactionNotYetProcessedException($orderId);
            }

            throw new MidtransStatusUnavailableException($orderId);
        }
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        $serverKey = config('midtrans.server_key');
        $clientKey = config('midtrans.client_key');
        $merchantId = config('midtrans.merchant_id');

        return ! empty($serverKey) && ! empty($clientKey) && ! empty($merchantId);
    }
}
