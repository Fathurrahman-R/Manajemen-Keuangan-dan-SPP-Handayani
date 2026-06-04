<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    /**
     * Send password reset link.
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $this->passwordResetService->sendResetLink($request->input('email'));

        // Anti-enumeration: always return same response
        return response()->json([
            'message' => 'Jika email terdaftar, kami telah mengirimkan link reset password.',
        ]);
    }

    /**
     * Validate a reset token.
     */
    public function validateToken(string $token): JsonResponse
    {
        $resetToken = $this->passwordResetService->validateToken($token);

        if (!$resetToken) {
            return response()->json([
                'valid' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'email' => $resetToken->email,
        ]);
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'password' => 'required|string|min:8|max:100|confirmed',
        ]);

        $success = $this->passwordResetService->resetPassword(
            $request->input('token'),
            $request->input('password')
        );

        if (!$success) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kadaluarsa.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password berhasil direset. Silakan login dengan password baru.',
        ]);
    }
}
