<?php

namespace App\Filament\Pages\Auth;

use App\Helpers\PermissionHelper;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if (session()->has('intended_url')) {
            $intendedUrl = session()->pull('intended_url');

            return redirect()->to($intendedUrl);
        }

        // Superadmin: selalu redirect ke dashboard
        if (in_array('superadmin', session()->get('data.roles', []))) {
            return redirect()->to(filament()->getUrl().'/dashboard-page');
        }

        // Siswa/Wali role langsung ke portal panel (resource key abstraction)
        if (PermissionHelper::hasResource('portal-access')) {
            return redirect()->to('/'.config('handayani.portal.path', 'portal'));
        }

        // Non-siswa: redirect ke dashboard.
        // mount() gate di tiap halaman akan handle redirect jika user tidak punya akses.
        return redirect()->to(filament()->getUrl().'/dashboard-page');
    }
}
