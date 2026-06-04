<?php

use App\Filament\Pages\Auth\ForgotPassword;
use App\Filament\Pages\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
Route::get('/reset-password', ResetPassword::class)->name('password.reset');

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
