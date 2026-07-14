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
     * - If identifier contains "@": query by email (case-insensitive, is_active=true)
     * - If not: query by username (is_active=true)
     * - For admin/operator with email set: username login is disabled
     */
    public function findUserByIdentifier(string $identifier): ?User
    {
        if ($this->isEmail($identifier)) {
            return User::where('email', strtolower(trim($identifier)))
                ->where('is_active', true)
                ->first();
        }

        // Non-email identifier — query by username
        $user = User::where('username', $identifier)
            ->where('is_active', true)
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
