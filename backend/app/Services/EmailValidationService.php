<?php

namespace App\Services;

use App\Models\User;

class EmailValidationService
{
    /**
     * Validate email format (RFC 5322 basic).
     */
    public function isValidFormat(?string $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if email is unique within a branch, optionally excluding a user ID.
     */
    public function isUniqueInBranch(string $email, int $branchId, ?int $excludeUserId = null): bool
    {
        $query = User::where('email', $this->normalize($email))
            ->where('branch_id', $branchId);

        if ($excludeUserId !== null) {
            $query->where('id', '!=', $excludeUserId);
        }

        return ! $query->exists();
    }

    /**
     * Normalize email — lowercase and trim.
     */
    public function normalize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        return strtolower(trim($email));
    }
}
