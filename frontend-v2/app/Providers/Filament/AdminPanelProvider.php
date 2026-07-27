<?php

namespace App\Providers\Filament;

use App\Config\NavigationConfig;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\ForgotPassword;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\DashboardPage;
use App\Filament\Pages\DataMasterCategory;
use App\Filament\Pages\DataMasterKelas;
use App\Filament\Pages\DataMasterSiswa;
use App\Filament\Pages\KenaikanKelasPage;
use App\Filament\Pages\LaporanKasHarian;
use App\Filament\Pages\LaporanRekapBulanan;
use App\Filament\Pages\ManajemenAkunSiswa;
use App\Filament\Pages\NotificationLogPage;
use App\Filament\Pages\PengeluaranRequestPage;
use App\Filament\Pages\RbacDashboard;
use App\Filament\Pages\Settings;
use App\Filament\Pages\TahunAjaranManagement as TahunAjaranPage;
use App\Filament\Pages\TransaksiJenisTagihan;
use App\Filament\Pages\TransaksiMidtransPage;
use App\Filament\Pages\TransaksiPembayaran;
use App\Filament\Pages\TransaksiTagihan;
use App\Filament\Pages\UserManagement;
use App\Helpers\PermissionHelper;
use App\Http\Middleware\CustomAuthentication;
use App\Services\BrandingService;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use function Filament\Support\original_request;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('')
            ->path('')
            ->homeUrl('dashboard-page')
            ->login(Login::class)
            ->passwordReset(ForgotPassword::class)
            ->profile(false)
            ->darkMode(true)
            ->spa(false)
            ->breadcrumbs(true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->userMenuItems([
                'profile' => fn (Action $action) => $action
                    ->label('Profil Saya')
                    ->icon('heroicon-o-user')
                    ->url(fn (): string => EditProfile::getUrl()),
                'logout' => fn (Action $action) => $action
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
                    }),
            ])
            ->navigation(function (NavigationBuilder $builder) {
                if (config('handayani.features.custom_navigation_enabled', true)) {
                    return $this->buildNavigation($builder);
                }

                return null;
            })
            ->userMenu(true)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => Blade::render('@livewire("branch-switcher")')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => Blade::render('@include("components.sidebar-scroll-preserve")')
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
     * Build navigation with groups driven by dynamic Resource Registry + fallback static items.
     */
    protected function buildNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        $groups = [];

        // Dashboard (always shown for admin users who have access)
        if (PermissionHelper::hasResource('dashboard')) {
            $groups[] = NavigationGroup::make('dashboard')
                ->label('')
                ->items([
                    NavigationItem::make()
                        ->label('Dashboard')
                        ->icon('heroicon-o-home')
                        ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.dashboard-page'))
                        ->url(fn (): string => DashboardPage::getUrl()),
                ]);
        }

        // Dynamic groups from Resource Registry — build standard items per group
        $groups[] = $this->buildDynamicGroup('akademik', 'Akademik', $this->buildAkademikItems());
        $groups[] = $this->buildDynamicGroup('keuangan', 'Keuangan', $this->buildKeuanganItems());
        $groups[] = $this->buildDynamicGroup('laporan', 'Laporan', $this->buildLaporanItems());
        $groups[] = $this->buildDynamicGroup('pengaturan', 'Pengaturan', $this->buildPengaturanItems());

        // Filter out empty groups
        $groups = array_values(array_filter($groups));

        return $builder->groups($groups);
    }

    /**
     * Build a NavigationGroup only if the user has access via Resource Registry.
     */
    protected function buildDynamicGroup(string $groupKey, string $label, array $items): ?NavigationGroup
    {
        if (! PermissionHelper::hasAnyInGroup($groupKey)) {
            return null;
        }

        return NavigationGroup::make($groupKey)
            ->label($label)
            ->items($items);
    }

    /**
     * Akademik group items — visibility driven by PermissionHelper::hasResource().
     */
    protected function buildAkademikItems(): array
    {
        $items = [];

        // Siswa — flat jenjang sub-items
        if (PermissionHelper::hasResource('siswa.view')) {
            foreach (NavigationConfig::JENJANG_OPTIONS as $jenjang) {
                $items[] = NavigationItem::make()
                    ->label("Siswa - {$jenjang}")
                    ->icon($this->getJenjangIcon($jenjang))
                    ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.data-master-siswa')
                        && request()->query('jenjang') === $jenjang)
                    ->url(fn (): string => DataMasterSiswa::getUrl().'?jenjang='.$jenjang);
            }
        }

        // Kategori
        $items[] = NavigationItem::make()
            ->label('Kategori')
            ->icon('heroicon-o-tag')
            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.data-master-category'))
            ->visible(fn (): bool => PermissionHelper::hasResource('kategori.view'))
            ->url(fn (): string => DataMasterCategory::getUrl());

        // Kelas — flat jenjang sub-items
        if (PermissionHelper::hasResource('kelas.view')) {
            foreach (NavigationConfig::JENJANG_OPTIONS as $jenjang) {
                $items[] = NavigationItem::make()
                    ->label("Kelas - {$jenjang}")
                    ->icon($this->getJenjangIcon($jenjang))
                    ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.data-master-kelas')
                        && request()->query('jenjang') === $jenjang)
                    ->url(fn (): string => DataMasterKelas::getUrl().'?jenjang='.$jenjang);
            }
        }

        // Tahun Ajaran
        $items[] = NavigationItem::make()
            ->label('Tahun Ajaran')
            ->icon('heroicon-o-calendar')
            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.tahun-ajaran-management'))
            ->visible(fn (): bool => PermissionHelper::hasResource('tahun-ajaran.view'))
            ->url(fn (): string => TahunAjaranPage::getUrl());

        // Kenaikan Kelas
        $items[] = NavigationItem::make()
            ->label('Kenaikan Kelas')
            ->icon('heroicon-o-arrow-up-circle')
            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.kenaikan-kelas-page'))
            ->visible(fn (): bool => PermissionHelper::hasResource('kenaikan-kelas.view'))
            ->url(fn (): string => KenaikanKelasPage::getUrl());

        return $items;
    }

    /**
     * Keuangan group items — visibility driven by PermissionHelper::hasResource().
     */
    protected function buildKeuanganItems(): array
    {
        $items = [];

        // Jenis Tagihan
        $items[] = NavigationItem::make()
            ->label('Jenis Tagihan')
            ->icon('heroicon-o-document-text')
            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.transaksi-jenis-tagihan'))
            ->visible(fn (): bool => PermissionHelper::hasResource('jenis-tagihan.view'))
            ->url(fn (): string => TransaksiJenisTagihan::getUrl());

        // Tagihan — flat jenjang sub-items
        if (PermissionHelper::hasResource('tagihan.view')) {
            foreach (NavigationConfig::JENJANG_OPTIONS as $jenjang) {
                $items[] = NavigationItem::make()
                    ->label("Tagihan - {$jenjang}")
                    ->icon($this->getJenjangIcon($jenjang))
                    ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.transaksi-tagihan')
                        && request()->query('jenjang') === $jenjang)
                    ->url(fn (): string => TransaksiTagihan::getUrl().'?jenjang='.$jenjang);
            }
        }

        // Pembayaran
        $items[] = NavigationItem::make()
            ->label('Pembayaran')
            ->icon('heroicon-o-banknotes')
            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.transaksi-pembayaran'))
            ->visible(fn (): bool => PermissionHelper::hasResource('pembayaran.view'))
            ->url(fn (): string => TransaksiPembayaran::getUrl());

        // Pengeluaran
        $items[] = NavigationItem::make()
            ->label('Pengeluaran')
            ->icon('heroicon-o-document-check')
            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.pengeluaran-request-page'))
            ->visible(fn (): bool => PermissionHelper::hasResource('pengeluaran.view'))
            ->url(fn (): string => PengeluaranRequestPage::getUrl());

        // Transaksi Midtrans (feature flag + resource check)
        if (config('handayani.features.midtrans_enabled') && PermissionHelper::hasResource('midtrans.admin')) {
            $items[] = NavigationItem::make()
                ->label('Transaksi Midtrans')
                ->icon('heroicon-o-credit-card')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.transaksi-midtrans'))
                ->url(fn (): string => TransaksiMidtransPage::getUrl());
        }

        return $items;
    }

    /**
     * Laporan group items — visibility driven by PermissionHelper::hasResource().
     */
    protected function buildLaporanItems(): array
    {
        return [
            NavigationItem::make()
                ->label('Kas Harian')
                ->icon('heroicon-o-document-currency-dollar')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.laporan-kas-harian'))
                ->visible(fn (): bool => PermissionHelper::hasResource('laporan.kas'))
                ->url(fn (): string => LaporanKasHarian::getUrl()),
            NavigationItem::make()
                ->label('Rekap Bulanan')
                ->icon('heroicon-o-calendar-days')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.laporan-rekap-bulanan'))
                ->visible(fn (): bool => PermissionHelper::hasResource('laporan.rekap'))
                ->url(fn (): string => LaporanRekapBulanan::getUrl()),
        ];
    }

    /**
     * Pengaturan group items — visibility driven by PermissionHelper::hasResource().
     */
    protected function buildPengaturanItems(): array
    {
        return [
            NavigationItem::make()
                ->label('Manajemen User')
                ->icon('heroicon-o-users')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.user-management'))
                ->visible(fn (): bool => PermissionHelper::hasResource('users.view'))
                ->url(fn (): string => UserManagement::getUrl()),
            NavigationItem::make()
                ->label('Manajemen Akun Siswa')
                ->icon('heroicon-o-user-circle')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.manajemen-akun-siswa'))
                ->visible(fn (): bool => PermissionHelper::hasResource('akun-siswa.view'))
                ->url(fn (): string => ManajemenAkunSiswa::getUrl()),
            NavigationItem::make()
                ->label('Pengaturan Aplikasi')
                ->icon('heroicon-o-cog-6-tooth')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.settings'))
                ->visible(fn (): bool => PermissionHelper::hasResource('pengaturan.view'))
                ->url(fn (): string => Settings::getUrl()),
            NavigationItem::make()
                ->label('Manajemen Cabang')
                ->icon('heroicon-o-building-office-2')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.branch-management'))
                ->visible(fn (): bool => PermissionHelper::hasResource('branch.view'))
                ->url(fn (): string => \App\Filament\Pages\BranchManagement::getUrl()),
            NavigationItem::make()
                ->label('Log Notifikasi')
                ->icon('heroicon-o-envelope')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.notification-log'))
                ->visible(fn (): bool => PermissionHelper::hasResource('notification-logs.view'))
                ->url(fn (): string => NotificationLogPage::getUrl()),
            NavigationItem::make()
                ->label('Pengaturan Notifikasi')
                ->icon('heroicon-o-bell')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.notification-settings'))
                ->visible(fn (): bool => PermissionHelper::hasResource('notification-setting.view'))
                ->url(fn (): string => \App\Filament\Pages\NotificationSettingsPage::getUrl()),
            NavigationItem::make()
                ->label('Pengaturan Approval')
                ->icon('heroicon-o-check-badge')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.branch-approval-settings'))
                ->visible(fn (): bool => PermissionHelper::hasResource('auto-approve.view'))
                ->url(fn (): string => \App\Filament\Pages\BranchApprovalSettingsPage::getUrl()),
            NavigationItem::make()
                ->label('Manajemen RBAC')
                ->icon('heroicon-o-shield-check')
                ->isActiveWhen(fn (): bool => original_request()->routeIs('filament..pages.rbac-dashboard'))
                ->visible(fn (): bool => PermissionHelper::hasResource('rbac'))
                ->url(fn (): string => RbacDashboard::getUrl()),
        ];
    }

    protected function getJenjangIcon(string $jenjang): string
    {
        return match ($jenjang) {
            'KB' => 'heroicon-o-face-smile',
            'TK' => 'heroicon-o-puzzle-piece',
            'MI' => 'heroicon-o-book-open',
            default => 'heroicon-o-academic-cap',
        };
    }

    protected function resolveBrandName(): string
    {
        $branding = BrandingService::get();

        return $branding->branchName ?: config('app.name', 'Handayani');
    }

    protected function resolveBrandLogo(): ?string
    {
        $branding = BrandingService::get();

        if ($branding->hasBranding() && $branding->logoUrl) {
            return $branding->logoUrl;
        }

        return null;
    }

    protected function resolveFavicon(): ?string
    {
        $branding = BrandingService::get();

        if ($branding->hasBranding() && $branding->faviconUrl) {
            return $branding->faviconUrl;
        }

        return asset('images/logo.jpg');
    }

    protected function resolvePanelColors(): array
    {
        $branding = BrandingService::get();

        if ($branding->hasBranding() && $branding->primaryColor) {
            return [
                'primary' => Color::hex($branding->primaryColor),
            ];
        }

        return [
            'primary' => Color::hex('#1B4FBF'),
        ];
    }
}
