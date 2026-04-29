<?php

namespace App\Filament\Pages\Auth;

use Illuminate\Http\RedirectResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as Responsable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class LogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->intended(filament()->getUrl());
    }
}
