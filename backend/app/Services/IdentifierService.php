<?php

namespace App\Services;

use App\Models\User;

class IdentifierService
{
    /**
     * Check if the identifier looks like an email (contains "@").
     */
    public function isEmail(string $identifier): bool
    {
        return str_contains($identifier, '@');
    }

    /**
     * Find a user by identifier with automatic routing.
     *
     * - If identifier contains "@": query by email (case-insensitive)
     * - If not: query by username
     * - For admin/operator with email set: username login is disabled
     *
     * Deliberately does NOT filter by is_active here — callers (AuthController)
     * need to distinguish "no such account" from "account deactivated" so they
     * can surface the specific "Akun tidak aktif" message instead of a
     * misleading "username/password salah" for a disabled account.
     */
    public function findUserByIdentifier(string $identifier): ?User
    {
        if ($this->isEmail($identifier)) {
            return User::where('email', strtolower(trim($identifier)))
                ->first();
        }

        // Non-email identifier — query by username
        $user = User::where('username', $identifier)
            ->first();

        if (! $user) {
            return null;
        }

        // If admin/operator already has email, username login is disabled for them
        if ($user->email !== null && ! $user->hasRole('siswa')) {
            return null;
        }

        return $user;
    }
}
