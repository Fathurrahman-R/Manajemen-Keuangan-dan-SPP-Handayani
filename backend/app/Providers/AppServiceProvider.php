<?php

namespace App\Providers;

use App\Enum\DefaultRoles;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Observers\DashboardCacheObserver;
use App\Observers\SiswaObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Carbon\Carbon::setLocale(config('app.locale'));
        date_default_timezone_set(config('app.timezone'));

        Gate::before(function ($user, $ability) {
            if ($user === null) {
                return null;
            }
            return $user->hasRole(DefaultRoles::SUPERADMIN->value) ? true : null;
        });

        Siswa::observe(SiswaObserver::class);

        // Dashboard cache invalidation observers
        Pembayaran::observe(DashboardCacheObserver::class);
        Tagihan::observe(DashboardCacheObserver::class);
        Pengeluaran::observe(DashboardCacheObserver::class);
    }
}
