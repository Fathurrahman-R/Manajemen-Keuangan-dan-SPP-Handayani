<?php

namespace App\Filament\Pages\Auth;

use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if (session()->has('intended_url')) {
            $intendedUrl = session()->pull('intended_url');
            return redirect()->to($intendedUrl);
        }

        return redirect()->intended(filament()->getUrl() . '/data-master-siswa');
    }
}