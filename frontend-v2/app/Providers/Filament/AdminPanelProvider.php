<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\DataMasterCategory;
use App\Filament\Pages\DataMasterKelas;
use App\Filament\Pages\DataMasterSiswa;
use App\Filament\Pages\LaporanKasHarian;
use App\Filament\Pages\LaporanRekapBulanan;
use App\Filament\Pages\Settings;
use App\Filament\Pages\TransaksiJenisTagihan;
use App\Filament\Pages\TransaksiPembayaran;
use App\Filament\Pages\TransaksiPengeluaran;
use App\Filament\Pages\TransaksiTagihan;
use App\Http\Middleware\CustomAuthentication;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

use function Filament\Support\original_request;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl('data-master-siswa')
            ->login(Login::class)
            // ->login()
            ->spa(hasPrefetching: true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                // Dashboard::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder->groups([
                    NavigationGroup::make('data_master')
                        ->label('Data Master')
                        ->items([
                            NavigationItem::make()
                                ->label('Siswa')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.data-master-siswa'))
                                ->url(fn(): string => DataMasterSiswa::getUrl()),
                            NavigationItem::make()
                                ->label('Kategori')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.data-master-category'))
                                ->url(fn(): string => DataMasterCategory::getUrl()),
                            NavigationItem::make()
                                ->label('Kelas')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.data-master-kelas'))
                                ->url(fn(): string => DataMasterKelas::getUrl()),
                        ]),
                    NavigationGroup::make('transaksi')
                        ->label('Transaksi')
                        ->items([
                            NavigationItem::make()
                                ->label('Jenis Tagihan')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.transaksi-jenis-tagihan'))
                                ->url(fn(): string => TransaksiJenisTagihan::getUrl()),
                            NavigationItem::make()
                                ->label('Tagihan')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.transaksi-tagihan'))
                                ->url(fn(): string => TransaksiTagihan::getUrl()),
                            NavigationItem::make()
                                ->label('Pembayaran')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.transaksi-pembayaran'))
                                ->url(fn(): string => TransaksiPembayaran::getUrl()),
                            NavigationItem::make()
                                ->label('Pengeluaran')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.transaksi-pengeluaran'))
                                ->url(fn(): string => TransaksiPengeluaran::getUrl()),
                        ]),
                    NavigationGroup::make('laporan')
                        ->label('Laporan')
                        ->items([
                            NavigationItem::make()
                                ->label('Kas Harian')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.laporan-kas-harian'))
                                ->url(fn(): string => LaporanKasHarian::getUrl()),
                            NavigationItem::make()
                                ->label('Rekap Bulanan')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.laporan-rekap-bulanan'))
                                ->url(fn(): string => LaporanRekapBulanan::getUrl()),
                        ]),
                    NavigationGroup::make()
                        ->items([
                            NavigationItem::make()
                                ->label('Pengaturan')
                                ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.setting'))
                                ->url(fn(): string => Settings::getUrl()),
                        ]),
                ]);
            })
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName(env('APP_NAME', 'Laravel'))
            ->authMiddleware([
                CustomAuthentication::class
                // Authenticate::class,
            ])
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
}
