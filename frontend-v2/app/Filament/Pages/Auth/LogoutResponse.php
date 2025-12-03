<?php

namespace App\Filament\Pages\Auth;

use Illuminate\Http\RedirectResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as Responsable;
use Illuminate\Support\Facades\Http;

class LogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        Http::withHeaders([
            'Authorization' => session()->get('data')['token']
        ])
        ->delete(env('API_URL') . '/users/login');

        session()->flush();

        return redirect()->route('/admin/login');
    }
}
