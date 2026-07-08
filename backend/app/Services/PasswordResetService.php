<?php

namespace App\Services;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
     * Send reset link (anti-enumeration: same response for valid/invalid emails).
     */
    public function sendResetLink(string $email): void
    {
        $user = User::where('email', strtolower(trim($email)))
            ->where('is_active', true)
            ->first();

        // Even if user doesn't exist, we don't reveal that
        if (!$user) {
            return;
        }

        $token = Str::random(64);

        PasswordResetToken::create([
            'email' => strtolower(trim($email)),
            'token' => $token,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(60),
        ]);

        // Send email with reset link
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://127.0.0.1:8000'));
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token;

        Mail::send(
            'emails.reset-password',
            ['resetUrl' => $resetUrl],
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('Reset Password - ' . config('app.name'));
            }
        );
    }

    /**
     * Validate a reset token.
     */
    public function validateToken(string $token): ?PasswordResetToken
    {
        $resetToken = PasswordResetToken::where('token', $token)->first();

        if (!$resetToken || !$resetToken->isValid()) {
            return null;
        }

        return $resetToken;
    }

    /**
     * Reset password using a valid token.
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $resetToken = $this->validateToken($token);

        if (!$resetToken) {
            return false;
        }

        $user = User::where('email', $resetToken->email)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        $user->must_change_password = false;
        $user->save();

        // Mark token as used
        $resetToken->used = true;
        $resetToken->save();

        // Revoke all existing tokens
        $user->tokens()->delete();

        return true;
    }
}
