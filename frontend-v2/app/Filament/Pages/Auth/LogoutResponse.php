<?php

namespace App\Filament\Pages\Auth;

use App\Services\ApiService;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as Responsable;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        // Revoke the backend Sanctum token first (while session still has the token)
        try {
            ApiService::client()->delete('/logout');
        } catch (\Throwable $e) {
            // Continue even if API call fails
        }

        // Clear all session data
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->to(filament()->getLoginUrl());
    }
}
