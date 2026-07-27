<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget stat all-time (lintas semua periode ajaran).
 */
class DashboardAllTimeStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        try {
            $data = ApiService::dashboardOverviewSlice('all_time_summary') ?? [];
        } catch (\Throwable $e) {
            $data = [];
        }

        $totalTagihan = (int) ($data['total_tagihan'] ?? 0);
        $totalPemasukan = (int) ($data['total_pemasukan'] ?? 0);
        $totalPengeluaran = (int) ($data['total_pengeluaran'] ?? 0);
        $totalSaldo = (int) ($data['saldo'] ?? 0);

        return [
            Stat::make('Total Tagihan (Semua Periode)', 'Rp '.number_format($totalTagihan, 0, ',', '.'))
                ->description('Akumulasi tagihan dari semua periode')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            Stat::make('Total Pemasukan (Semua Periode)', 'Rp '.number_format($totalPemasukan, 0, ',', '.'))
                ->description('Akumulasi pemasukan dari semua periode')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Pengeluaran (Semua Periode)', 'Rp '.number_format($totalPengeluaran, 0, ',', '.'))
                ->description('Akumulasi pengeluaran dari semua periode')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Total Saldo Cabang', 'Rp '.number_format($totalSaldo, 0, ',', '.'))
                ->description('Pemasukan dikurangi pengeluaran, seluruh periode')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalSaldo < 0 ? 'danger' : 'info'),
        ];
    }

    public static function canView(): bool
    {
        return PermissionHelper::hasResource('dashboard');
    }
}
