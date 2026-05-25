<?php

use Illuminate\Support\Facades\Route;

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
