<?php

namespace App\Services\Midtrans;

use App\Exceptions\Midtrans\AmountInternalInconsistentException;

class MidtransFeeService
{
    /**
     * Compute the admin fee for a Midtrans transaction.
     *
     * If a payment channel is supplied and exists in `fee_channels`, the
     * channel-specific amount is returned; otherwise the flat fallback is
     * used. Always reads config at call time so changes don't leak into
     * already-persisted transactions (snapshot is the responsibility of the
     * caller).
     */
    public function computeFee(int $amountPaid, ?string $channel = null): int
    {
        if ($channel !== null && $channel !== '') {
            $channels = (array) config('midtrans.fee_channels', []);
            if (isset($channels[$channel]['amount'])) {
                return (int) $channels[$channel]['amount'];
            }
        }

        return (int) config('midtrans.fee_flat');
    }

    /**
     * List of available fee channels suitable for the Portal modal dropdown.
     *
     * @return list<array{key:string,label:string,amount:int}>
     */
    public function availableChannels(): array
    {
        $channels = (array) config('midtrans.fee_channels', []);

        $items = [];
        foreach ($channels as $key => $cfg) {
            $items[] = [
                'key' => (string) $key,
                'label' => (string) ($cfg['label'] ?? ucfirst((string) $key)),
                'amount' => (int) ($cfg['amount'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * Whether the channel key is a known fee channel.
     */
    public function isKnownChannel(?string $channel): bool
    {
        if ($channel === null || $channel === '') {
            return false;
        }

        return array_key_exists($channel, (array) config('midtrans.fee_channels', []));
    }

    /**
     * Assert the gross amount invariant: gross_amount == amount_paid + fee_amount.
     *
     * @throws AmountInternalInconsistentException
     */
    public function assertGrossInvariant(int $amountPaid, int $feeAmount, int $grossAmount): void
    {
        if ($grossAmount !== $amountPaid + $feeAmount) {
            throw new AmountInternalInconsistentException($amountPaid, $feeAmount, $grossAmount);
        }
    }
}
