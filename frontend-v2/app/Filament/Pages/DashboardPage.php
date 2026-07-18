<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HasPeriodFilter;
use App\Services\ApiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;

class DashboardPage extends Page
{
    use HasPeriodFilter;

    /**
     * Dashboard wajib menampilkan periode aktif sebagai default — tidak
     * mengizinkan pseudo-option "Semua Periode" karena beberapa metrik
     * (tunggakan, status tagihan) tidak bermakna lintas periode.
     */
    public bool $allowAllPeriodsOption = false;

    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('dashboard'), 403);

        $this->mountHasPeriodFilter();

        $this->prefetchWidgetData();
    }

    public function updatedSelectedTahunAjaranId($value): void
    {
        session(['selected_tahun_ajaran_id' => $value ? (int) $value : null]);

        $this->prefetchWidgetData();
    }

    /**
     * Warm Redis for the combined `/dashboard/overview` payload before widgets
     * render. Every widget's own `ApiService::dashboardOverviewSlice()` call
     * uses the SAME endpoint+params, so this single fetch is what all 9 widgets
     * end up reading from — 1 HTTP call total instead of 9 (see
     * DashboardController::overview()).
     */
    protected function prefetchWidgetData(): void
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : ['all_periods' => true];

        ApiService::cachedGet('/dashboard/overview', $params);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    // Bump cache version so every widget's next fetch bypasses
                    // Redis regardless of TTL, then remount the page so all
                    // widgets re-run their getStats()/getData() immediately.
                    ApiService::bustDashboardCache();

                    $this->redirect(static::getUrl(), navigate: true);
                }),
        ];
    }
}
