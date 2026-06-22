<?php

namespace App\Providers\Filament;

use App\Config\NavigationConfig;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\ForgotPassword;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\BulkAkunSiswaPage;
use App\Filament\Pages\DashboardPage;
use App\Filament\Pages\DataMasterCategory;
use App\Filament\Pages\DataMasterKelas;
use App\Filament\Pages\DataMasterSiswa;
use App\Filament\Pages\KenaikanKelasPage;
use App\Filament\Pages\LaporanKasHarian;
use App\Filament\Pages\LaporanRekapBulanan;
use App\Filament\Pages\ManajemenAkunSiswa;
use App\Filament\Pages\PengeluaranRequestPage;
use App\Filament\Pages\RoleManagement;
use App\Filament\Pages\Settings;
use App\Filament\Pages\SiswaDashboardPage;
use App\Filament\Pages\TagihanSiswaPage;
use App\Filament\Pages\TahunAjaranManagement as TahunAjaranPage;
use App\Filament\Pages\UserManagement;
use App\Filament\Pages\TransaksiJenisTagihan;
use App\Filament\Pages\TransaksiPembayaran;
use App\Filament\Pages\TransaksiTagihan;
use App\Helpers\PermissionHelper;
use App\Http\Middleware\CustomAuthentication;
use App\Services\BrandingService;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

use function Filament\Support\original_request;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('')
            ->path('')
            ->homeUrl('data-master-siswa')
            ->login(Login::class)
            ->passwordReset(ForgotPassword::class)
            ->profile(false)
            ->darkMode(true)
            ->spa(config('handayani.features.spa_loading_enabled', true))
            ->breadcrumbs(true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([])
            ->userMenuItems([
                'profile' => fn(Action $action) => $action
                    ->label('Profil Saya')
                    ->icon('heroicon-o-user')
                    ->url(fn(): string => EditProfile::getUrl()),
                'logout' => fn(Action $action) => $action
                    ->label('Logout')
                    ->action(function (): void {
                        try {
                            \App\Services\ApiService::client()->delete('/logout');
                        } catch (\Exception $e) {
                            // Even if the API call fails, we still clear the session
                        }

                        \Filament\Facades\Filament::auth()->logout();
                        session()->flush();
                        session()->invalidate();
                        session()->regenerateToken();

                        redirect()->to(filament()->getLoginUrl())->send();
                    })
            ])
            ->navigation(function (NavigationBuilder $builder) {
                if (config('handayani.features.custom_navigation_enabled', true)) {
                    return $this->buildNavigation($builder);
                }
                return null;
            })
            ->userMenu(true)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName($this->resolveBrandName())
            ->brandLogo($this->resolveBrandLogo())
            ->favicon($this->resolveFavicon())
            ->colors($this->resolvePanelColors())
            ->authMiddleware([
                CustomAuthentication::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling(null)
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => Blade::render('<livewire:notification-poller />')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => Blade::render('@include("components.pagination-loading")')
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }

    /**
     * Build navigation with 4 reorganized groups per NavigationConfig.
     * Groups are hidden entirely when user has no permission to any item within.
     */
    protected function buildNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        $groups = [];

        // Siswa/Wali-specific items (shown only for siswa role without admin dashboard access)
        $siswaItems = $this->buildSiswaNavigationItems();
        if (!empty($siswaItems)) {
            $groups[] = NavigationGroup::make('siswa_portal')
                ->label('')
                ->items($siswaItems);
        }

        if (PermissionHelper::hasAnyInGroup('akademik')) {
            $groups[] = NavigationGroup::make('akademik')
                ->label(NavigationConfig::GROUPS['akademik']['label'])
                ->items($this->buildAkademikItems());
        }

        if (PermissionHelper::hasAnyInGroup('keuangan')) {
            $groups[] = NavigationGroup::make('keuangan')
                ->label(NavigationConfig::GROUPS['keuangan']['label'])
                ->items($this->buildKeuanganItems());
        }

        if (PermissionHelper::hasAnyInGroup('laporan')) {
            $groups[] = NavigationGroup::make('laporan')
                ->label(NavigationConfig::GROUPS['laporan']['label'])
                ->items($this->buildLaporanItems());
        }

        if (PermissionHelper::hasAnyInGroup('pengaturan')) {
            $groups[] = NavigationGroup::make('pengaturan')
                ->label(NavigationConfig::GROUPS['pengaturan']['label'])
                ->items($this->buildPengaturanItems());
        }

        return $builder->groups($groups);
    }

    /**
     * Siswa/Wali navigation items (visible only for siswa role without admin access).
     */
    protected function buildSiswaNavigationItems(): array
    {
        $items = [];

        if (in_array('view-own-billing', session()->get('data.permissions', []))
            && !in_array('view-dashboard', session()->get('data.permissions', []))) {
            $items[] = NavigationItem::make()
                ->label('Dashboard Saya')
                ->icon('heroicon-o-home')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.siswa-dashboard-page'))
                ->url(fn(): string => SiswaDashboardPage::getUrl());
        }

        if (in_array('siswa', session()->get('data.roles', []))) {
            $items[] = NavigationItem::make()
                ->label('Tagihan Saya')
                ->icon('heroicon-o-credit-card')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.tagihan-siswa-page'))
                ->url(fn(): string => TagihanSiswaPage::getUrl());
        }

        return $items;
    }

    /**
     * Akademik group items: Siswa (flat per jenjang), Kategori, Kelas (flat per jenjang), Tahun Ajaran, Kenaikan Kelas.
     */
    protected function buildAkademikItems(): array
    {
        $items = [];

        // Siswa — flat jenjang sub-items in sidebar
        if (PermissionHelper::has('view-siswa')) {
            foreach (NavigationConfig::JENJANG_OPTIONS as $jenjang) {
                $items[] = NavigationItem::make()
                    ->label("Siswa - {$jenjang}")
                    ->icon($this->getJenjangIcon($jenjang))
                    ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.data-master-siswa')
                        && request()->query('jenjang') === $jenjang)
                    ->url(fn(): string => DataMasterSiswa::getUrl() . '?jenjang=' . $jenjang);
            }
        }

        $items[] = NavigationItem::make()
            ->label('Kategori')
            ->icon('heroicon-o-tag')
            ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.data-master-category'))
            ->visible(fn(): bool => PermissionHelper::has('view-kategori'))
            ->url(fn(): string => DataMasterCategory::getUrl());

        // Kelas — flat jenjang sub-items in sidebar
        if (PermissionHelper::has('view-kelas')) {
            foreach (NavigationConfig::JENJANG_OPTIONS as $jenjang) {
                $items[] = NavigationItem::make()
                    ->label("Kelas - {$jenjang}")
                    ->icon($this->getJenjangIcon($jenjang))
                    ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.data-master-kelas')
                        && request()->query('jenjang') === $jenjang)
                    ->url(fn(): string => DataMasterKelas::getUrl() . '?jenjang=' . $jenjang);
            }
        }

        $items[] = NavigationItem::make()
            ->label('Tahun Ajaran')
            ->icon('heroicon-o-calendar')
            ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.tahun-ajaran-management'))
            ->visible(fn(): bool => PermissionHelper::has('manage-tahun-ajaran') || session()->get('data.role') === 'admin')
            ->url(fn(): string => TahunAjaranPage::getUrl());

        $items[] = NavigationItem::make()
            ->label('Kenaikan Kelas')
            ->icon('heroicon-o-arrow-up-circle')
            ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.kenaikan-kelas-page'))
            ->visible(fn(): bool => PermissionHelper::has('manage-kenaikan-kelas'))
            ->url(fn(): string => KenaikanKelasPage::getUrl());

        return $items;
    }

    /**
     * Keuangan group items: Jenis Tagihan, Tagihan (flat per jenjang), Pembayaran (flat per jenjang), Pengeluaran (workflow only).
     */
    protected function buildKeuanganItems(): array
    {
        $items = [];

        $items[] = NavigationItem::make()
            ->label('Jenis Tagihan')
            ->icon('heroicon-o-document-text')
            ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.transaksi-jenis-tagihan'))
            ->visible(fn(): bool => PermissionHelper::has('view-jenis-tagihan'))
            ->url(fn(): string => TransaksiJenisTagihan::getUrl());

        // Tagihan — flat jenjang sub-items
        if (PermissionHelper::has('view-tagihan')) {
            foreach (NavigationConfig::JENJANG_OPTIONS as $jenjang) {
                $items[] = NavigationItem::make()
                    ->label("Tagihan - {$jenjang}")
                    ->icon($this->getJenjangIcon($jenjang))
                    ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.transaksi-tagihan')
                        && request()->query('jenjang') === $jenjang)
                    ->url(fn(): string => TransaksiTagihan::getUrl() . '?jenjang=' . $jenjang);
            }
        }

        // Pembayaran — single page with built-in card view filters
        $items[] = NavigationItem::make()
            ->label('Pembayaran')
            ->icon('heroicon-o-banknotes')
            ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.transaksi-pembayaran'))
            ->visible(fn(): bool => PermissionHelper::has('view-pembayaran'))
            ->url(fn(): string => TransaksiPembayaran::getUrl());

        // Pengeluaran — single page (workflow only, no separate CRUD)
        $items[] = NavigationItem::make()
            ->label('Pengeluaran')
            ->icon('heroicon-o-document-check')
            ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.pengeluaran-request-page'))
            ->visible(fn(): bool => PermissionHelper::has('view-pengeluaran')
                || PermissionHelper::has('create-pengeluaran-request')
                || PermissionHelper::has('approve-pengeluaran')
                || PermissionHelper::has('disburse-pengeluaran'))
            ->url(fn(): string => PengeluaranRequestPage::getUrl());

        return $items;
    }

    /**
     * Laporan group items: Dashboard, Kas Harian, Rekap Bulanan.
     */
    protected function buildLaporanItems(): array
    {
        return [
            NavigationItem::make()
                ->label('Dashboard')
                ->icon('heroicon-o-chart-pie')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.dashboard-page'))
                ->visible(fn(): bool => PermissionHelper::has('view-dashboard'))
                ->url(fn(): string => DashboardPage::getUrl()),
            NavigationItem::make()
                ->label('Kas Harian')
                ->icon('heroicon-o-document-currency-dollar')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.laporan-kas-harian'))
                ->visible(fn(): bool => PermissionHelper::has('view-kas-harian'))
                ->url(fn(): string => LaporanKasHarian::getUrl()),
            NavigationItem::make()
                ->label('Rekap Bulanan')
                ->icon('heroicon-o-calendar-days')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.laporan-rekap-bulanan'))
                ->visible(fn(): bool => PermissionHelper::has('view-rekap-bulanan'))
                ->url(fn(): string => LaporanRekapBulanan::getUrl()),
        ];
    }

    /**
     * Pengaturan group items: User Management, Role Management, Akun Siswa, App Settings.
     */
    protected function buildPengaturanItems(): array
    {
        return [
            NavigationItem::make()
                ->label('Manajemen User')
                ->icon('heroicon-o-users')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.user-management'))
                ->visible(fn(): bool => PermissionHelper::has('view-user'))
                ->url(fn(): string => UserManagement::getUrl()),
            NavigationItem::make()
                ->label('Manajemen Role')
                ->icon('heroicon-o-shield-check')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.role-management'))
                ->visible(fn(): bool => PermissionHelper::has('view-roles'))
                ->url(fn(): string => RoleManagement::getUrl()),
            NavigationItem::make()
                ->label('Manajemen Akun Siswa')
                ->icon('heroicon-o-user-circle')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.manajemen-akun-siswa'))
                ->visible(fn(): bool => PermissionHelper::has('manage-akun-siswa'))
                ->url(fn(): string => ManajemenAkunSiswa::getUrl()),
            NavigationItem::make()
                ->label('Bulk Akun Siswa')
                ->icon('heroicon-o-user-plus')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.bulk-akun-siswa-page'))
                ->visible(fn(): bool => PermissionHelper::has('manage-akun-siswa'))
                ->url(fn(): string => BulkAkunSiswaPage::getUrl()),
            NavigationItem::make()
                ->label('Pengaturan Aplikasi')
                ->icon('heroicon-o-cog-6-tooth')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.settings'))
                ->url(fn(): string => Settings::getUrl()),
            NavigationItem::make()
                ->label('Manajemen Cabang')
                ->icon('heroicon-o-building-office-2')
                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament..pages.branch-management'))
                ->visible(fn(): bool => PermissionHelper::has('view-branch'))
                ->url(fn(): string => \App\Filament\Pages\BranchManagement::getUrl()),
        ];
    }

    /**
     * Get an icon for a jenjang option.
     */
    protected function getJenjangIcon(string $jenjang): string
    {
        return match ($jenjang) {
            'KB' => 'heroicon-o-face-smile',
            'TK' => 'heroicon-o-puzzle-piece',
            'MI' => 'heroicon-o-book-open',
            default => 'heroicon-o-academic-cap',
        };
    }

    /**
     * Apply branch branding (logo, primary color, favicon) to the Filament panel.
     * Only applies custom branding when BrandingService reports branding is configured.
     */

    /**
     * Resolve the brand name from branding config.
     * Falls back to APP_NAME env if no custom branding.
     */
    protected function resolveBrandName(): string
    {
        $branding = BrandingService::get();

        return $branding->branchName ?: env('APP_NAME', 'Handayani');
    }

    /**
     * Resolve the brand logo URL from branding config.
     * Returns null if no custom logo is configured (Filament will show brand name text).
     */
    protected function resolveBrandLogo(): ?string
    {
        $branding = BrandingService::get();

        if ($branding->hasBranding() && $branding->logoUrl) {
            return $branding->logoUrl;
        }

        return null;
    }

    /**
     * Resolve the favicon URL from branding config.
     * Returns null if no custom favicon is configured.
     */
    protected function resolveFavicon(): ?string
    {
        $branding = BrandingService::get();

        if ($branding->hasBranding() && $branding->faviconUrl) {
            return $branding->faviconUrl;
        }

        return null;
    }

    /**
     * Resolve panel colors from branding config.
     * Applies custom primary color when configured, otherwise returns empty array (Filament defaults).
     */
    protected function resolvePanelColors(): array
    {
        $branding = BrandingService::get();

        if ($branding->hasBranding() && $branding->primaryColor) {
            return [
                'primary' => Color::hex($branding->primaryColor),
            ];
        }

        return [];
    }
}
