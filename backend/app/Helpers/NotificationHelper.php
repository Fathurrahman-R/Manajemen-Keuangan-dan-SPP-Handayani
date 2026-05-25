<?php

namespace App\Helpers;

class NotificationHelper
{
    /**
     * Validate if a string is a valid email address.
     */
    public static function isValidEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Format a numeric amount as Indonesian Rupiah.
     */
    public static function formatRupiah(int|float $amount): string
    {
        return 'Rp. ' . number_format($amount, 0, '', '.');
    }
}
