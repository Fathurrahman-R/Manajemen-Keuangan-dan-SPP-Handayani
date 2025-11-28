<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Filament\Pages\Auth\LoginResponse;
use App\Filament\Pages\Auth\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Colors\Color;

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
        //
    }
}
