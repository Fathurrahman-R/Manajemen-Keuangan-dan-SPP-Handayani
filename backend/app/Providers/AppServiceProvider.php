<?php

namespace App\Providers;

use App\Enum\DefaultRoles;
use App\Exceptions\Midtrans\InvalidMidtransConfigException;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Observers\DashboardCacheObserver;
use App\Observers\SiswaObserver;
use App\Services\Midtrans\MidtransClient;
use App\Services\Midtrans\MidtransSnapClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MidtransClient::class, MidtransSnapClient::class);
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

        // Midtrans configuration validation
        if (config('midtrans.enabled')) {
            $environment = config('midtrans.environment');

            if (! in_array($environment, ['sandbox', 'production'], true)) {
                throw new InvalidMidtransConfigException('MIDTRANS_ENVIRONMENT', $environment);
            }
        }
    }
}
