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

        // ────────────────────────────────────────────────────────────────
        // Best practice spatie/laravel-permission untuk superadmin:
        //
        // Gate::before() memberikan superadmin akses ke SEMUA gate &
        // policy tanpa harus mendaftarkan permission satu per satu.
        // Ini dipakai oleh:
        //   - $user->can('view-anything')        → selalu true untuk superadmin
        //   - Filament canViewPanel/canAccess    → bypass otomatis
        //   - Policy ($this->authorize)          → bypass otomatis
        //
        // CATATAN: Middleware `permission:foo` dari Spatie tetap akan
        // memeriksa `permissions.has(foo)`, jadi superadmin juga di-grant
        // semua permission di RoleAndPermissionSeeder/SyncPermissionsCommand
        // sebagai backup eksplisit. Dengan dua lapis ini, superadmin tidak
        // akan pernah ter-blok dari halaman atau aksi apa pun.
        // ────────────────────────────────────────────────────────────────
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
