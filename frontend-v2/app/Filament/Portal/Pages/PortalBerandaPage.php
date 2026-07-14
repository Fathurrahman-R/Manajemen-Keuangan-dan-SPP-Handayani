<?php

namespace App\Filament\Portal\Pages;

use App\Filament\Widgets\PortalSiswaStatsWidget;
use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HasPeriodFilter;
use BackedEnum;
use Filament\Pages\Page;

class PortalBerandaPage extends Page
{
    use HasPeriodFilter;

    /**
     * Beranda portal: default ke "Semua Periode" supaya siswa/wali melihat
     * akumulasi sejak awal masuk sampai sekarang. Tetap bisa di-filter ke
     * periode tertentu via dropdown.
     */
    public bool $allowAllPeriodsOption = true;

    protected string $view = 'filament.portal.pages.beranda';

    protected static ?string $navigationLabel = 'Beranda';

    protected static ?string $title = 'Beranda';

    protected static ?string $slug = 'beranda';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    /**
     * Wali dengan beberapa anak dapat memilih siswa via dropdown ini.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Navigation handled manually via PortalPanelProvider
    }

    public function mount(): void
    {
        if (! PermissionHelper::hasResource('portal-access')) {
            abort(403);
        }

        $this->mountHasPeriodFilter();
    }



    //    protected function getHeaderWidgets(): array
    //    {
    //        return [
    //            PortalSiswaStatsWidget::class,
    //        ];
    //    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
