<?php

namespace App\Providers;

use App\Filament\Pages\Auth\LoginResponse;
use App\Filament\Pages\Auth\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);

        FilamentColor::register([
            'primaryMain' => Color::hex('#0B56A7'),
            'primaryMainActive' => Color::hex('#09488B'),
            'primaryMainThin' => Color::hex('#CEDDED'),
            'primaryLight' => Color::hex('#BB3C11'),
            'neutral' => Color::hex('#F7F7F7'),
            'sidebarActive' => Color::hex('#3472B6'),
            'disabled' => Color::hex('#E3E3E3'),
            'textDisabled' => Color::hex('#A4A4A4'),
            'info' => Color::hex('#CEEAF6'),
            'warning' => Color::hex('#FDEECC'),
            'borderSuccess' => Color::hex('#61BF62'),
            'textSuccess' => Color::hex('#379438'),
            'bgSuccess' => Color::hex('#D9F0D9'),
            'textWarning' => Color::hex('#CD8E00'),
            'bgWarning' => Color::hex('#FDEECC'),
            'secondary' => Color::hex('#C8C8C8'),
            'danger' => Color::hex('#DD2828'),
            'borderError' => Color::hex('#E6251C'),
            'error' => Color::hex('#EA4942'),
            'textPrimaryBlack' => Color::hex('#071437'),
            'textSecondaryBlack' => Color::hex('#4B5675'),
            'textThirdBlack' => Color::hex('#99A1B7'),
            'alertIconColor' => Color::hex('#E6251C'),
            'warningIconColor' => Color::hex('#F6AA00'),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // If the backend rejects our session's bearer token (401 — e.g. the
        // account was deactivated and its Sanctum tokens were revoked server-side),
        // the frontend session otherwise keeps looking "logged in" forever with no
        // way to regain access, since reactivating the account doesn't reissue a
        // token. There are two parallel "logged in" states to tear down here:
        // our own `data.token` bearer flag, AND Filament's own Laravel Auth guard
        // session (set via `Filament::auth()->loginUsingId()` at login time) —
        // clearing only the former still leaves Filament's login page redirecting
        // away as if the user were fine. Mirrors LogoutResponse's teardown.
        Event::listen(ResponseReceived::class, function (ResponseReceived $event): void {
            if ($event->response->status() !== 401) {
                return;
            }

            if (! str_starts_with((string) $event->request->url(), (string) config('handayani.api_url'))) {
                return;
            }

            if (! session()->has('data.token')) {
                return;
            }

            Auth::logout();
            session()->flush();
            session()->invalidate();
            session()->regenerateToken();
        });
    }
}
