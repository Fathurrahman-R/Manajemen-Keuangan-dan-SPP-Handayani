<?php

use App\Filament\Pages\Auth\ForgotPassword;
use App\Filament\Pages\Auth\ResetPassword;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'index'])->name('public.index');

Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
Route::get('/reset-password', ResetPassword::class)->name('password.reset');

// Fallback for framework-level auth redirects (e.g. AuthenticateSession logout
// after a password reset mid-session) that resolve the generic 'login' route name.
// Kept off the '/login' URI on purpose: that URI is already owned by Filament's
// own admin panel login route (path '' + default login slug). Registering a
// second GET route on the same URI+method evicts Filament's named route
// ("filament..auth.login") from Laravel's route collection entirely, which
// breaks filament()->getLoginUrl() (used by LogoutResponse) with a
// RouteNotFoundException.
Route::get('/login-redirect', function () {
    return redirect()->to(\Filament\Facades\Filament::getDefaultPanel()->getLoginUrl());
})->name('login');

Route::get('/logout', function () {
    try {
        \App\Services\ApiService::client()->delete('/logout');
    } catch (\Exception $e) {
        // Even if the API call fails, we still clear the session
    }

    \Filament\Facades\Filament::auth()->logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->to('/login');
})->name('custom.logout');
