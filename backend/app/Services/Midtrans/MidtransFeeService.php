<?php

namespace App\Services\Midtrans;

use App\Exceptions\Midtrans\AmountInternalInconsistentException;

class MidtransFeeService
{
    /**
     * Compute the admin fee for a Midtrans transaction.
     *
     * Tipe fee per channel mengikuti dokumentasi resmi Midtrans:
     * - flat   : Rp X tetap, terlepas dari nominal
     * - percent: persentase dari `amount_paid` (mis. 0.7 untuk QRIS).
     *            Optional ditambah komponen flat tambahan via `flat`.
     *
     * Contoh konfigurasi (config/midtrans.php → fee_channels):
     *   'qris' => ['type' => 'percent', 'percent' => 0.7]
     *   'credit_card' => ['type' => 'percent', 'percent' => 2.9, 'flat' => 2000]
     *   'bank_transfer' => ['type' => 'flat', 'amount' => 4000]
     *
     * Format lama (`amount` saja) tetap didukung dan diperlakukan sebagai
     * fee flat untuk kompatibilitas mundur.
     *
     * Selalu membaca config saat dipanggil supaya perubahan runtime tidak
     * tertahan oleh container singleton.
     */
    public function computeFee(int $amountPaid, ?string $channel = null): int
    {
        $config = $this->resolveChannelConfig($channel);

        if ($config === null) {
            return (int) config('midtrans.fee_flat');
        }

        return $this->calculateFromConfig($amountPaid, $config);
    }

    /**
     * Get available channels metadata for frontend selection. Sertakan fee
     * yang sudah dihitung untuk nominal tertentu agar siswa tahu nilai
     * pasti sebelum melanjutkan ke Snap.
     */
    public function availableChannels(?int $amountPaidPreview = null): array
    {
        $channels = (array) config('midtrans.fee_channels', []);
        $items = [];

        foreach ($channels as $key => $cfg) {
            $type = $cfg['type'] ?? 'flat';
            $entry = [
                'key' => $key,
                'label' => $cfg['label'] ?? ucfirst($key),
                'type' => $type,
            ];

            if ($type === 'percent') {
                $entry['percent'] = (float) ($cfg['percent'] ?? 0);
                $entry['flat'] = (int) ($cfg['flat'] ?? 0);
                $entry['description'] = $this->formatPercentDescription($entry['percent'], $entry['flat']);
            } else {
                // flat / legacy
                $entry['amount'] = (int) ($cfg['amount'] ?? $cfg['flat'] ?? 0);
                $entry['description'] = 'Rp ' . number_format($entry['amount'], 0, ',', '.') . ' per transaksi';
            }

            if ($amountPaidPreview !== null) {
                $entry['fee_preview'] = $this->calculateFromConfig($amountPaidPreview, $cfg);
                $entry['gross_preview'] = $amountPaidPreview + $entry['fee_preview'];
            }

            $items[] = $entry;
        }

        return $items;
    }

    public function isValidChannel(?string $channel): bool
    {
        if ($channel === null || $channel === '') {
            return true;
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

    /**
     * Ambil config channel — return null kalau channel tidak diberikan
     * atau tidak terdaftar (caller pakai fee_flat sebagai fallback).
     *
     * @return array<string, mixed>|null
     */
    private function resolveChannelConfig(?string $channel): ?array
    {
        if ($channel === null || $channel === '') {
            return null;
        }

        $channels = (array) config('midtrans.fee_channels', []);
        return $channels[$channel] ?? null;
    }

    /**
     * Hitung fee dari config channel, mendukung format flat & percent.
     *
     * @param array<string, mixed> $config
     */
    private function calculateFromConfig(int $amountPaid, array $config): int
    {
        $type = $config['type'] ?? 'flat';

        if ($type === 'percent') {
            $percent = (float) ($config['percent'] ?? 0);
            $flat = (int) ($config['flat'] ?? 0);
            // Round ke integer Rupiah (Midtrans tidak menerima desimal IDR).
            return (int) round(($amountPaid * $percent / 100) + $flat);
        }

        // type=flat atau legacy `amount` saja
        return (int) ($config['amount'] ?? $config['flat'] ?? 0);
    }

    private function formatPercentDescription(float $percent, int $flat): string
    {
        $desc = rtrim(rtrim(number_format($percent, 2, ',', '.'), '0'), ',') . '% dari nominal';
        if ($flat > 0) {
            $desc .= ' + Rp ' . number_format($flat, 0, ',', '.');
        }
        return $desc;
    }
}
