<?php

namespace App\Providers\Filament;

use App\Filament\Portal\Pages\PortalBerandaPage;
use App\Filament\Portal\Pages\PortalProfilPage;
use App\Filament\Portal\Pages\PortalRiwayatPembayaranPage;
use App\Filament\Portal\Pages\PortalStatusPembayaranPage;
use App\Filament\Portal\Pages\PortalTagihanPage;
use App\Http\Middleware\CustomAuthentication;
use App\Services\BrandingService;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use function Filament\Support\original_request;

class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $path = config('handayani.portal.path', 'portal');
        $breadcrumbs = config('handayani.portal.breadcrumbs', false);

        return $panel
            ->id('portal')
            ->path($path)
            ->homeUrl($path.'/beranda')
            ->darkMode(true)
            ->spa(false)
            ->breadcrumbs($breadcrumbs)
            ->sidebarCollapsibleOnDesktop(false)
            ->sidebarFullyCollapsibleOnDesktop(false)
            ->topNavigation(true)
            ->pages([
                PortalBerandaPage::class,
                PortalTagihanPage::class,
                PortalRiwayatPembayaranPage::class,
                PortalStatusPembayaranPage::class,
                PortalProfilPage::class,
                \App\Filament\Pages\ChangePassword::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->items([
                        NavigationItem::make()
                            ->label('Beranda')
                            ->icon('heroicon-o-home')
                            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament.portal.pages.portal-beranda-page'))
                            ->url(fn (): string => PortalBerandaPage::getUrl()),
                        NavigationItem::make()
                            ->label('Tagihan')
                            ->icon('heroicon-o-credit-card')
                            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament.portal.pages.portal-tagihan-page'))
                            ->url(fn (): string => PortalTagihanPage::getUrl()),
                        NavigationItem::make()
                            ->label('Riwayat')
                            ->icon('heroicon-o-clock')
                            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament.portal.pages.portal-riwayat-pembayaran-page'))
                            ->url(fn (): string => PortalRiwayatPembayaranPage::getUrl()),
                        NavigationItem::make()
                            ->label('Profil')
                            ->icon('heroicon-o-user')
                            ->isActiveWhen(fn (): bool => original_request()->routeIs('filament.portal.pages.portal-profil-page'))
                            ->url(fn (): string => PortalProfilPage::getUrl()),
                    ]);
            })
            ->userMenuItems([
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

                        redirect()->to('/login')->send();
                    }),
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

    protected function resolveBrandName(): string
    {
        $branding = BrandingService::get();

        return $branding->branchName ? $branding->branchName.' Portal' : config('app.name', 'Handayani').' Portal';
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
