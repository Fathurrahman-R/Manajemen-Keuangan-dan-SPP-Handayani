<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget stat untuk pemasukan/pengeluaran/saldo PADA periode yang dipilih.
 */
class DashboardKasStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public ?int $selectedTahunAjaranId = null;

    protected function getStats(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : ['all_periods' => true];

        try {
            $data = ApiService::dashboardOverviewSlice('kas_summary', $params) ?? [];
        } catch (\Throwable $e) {
            $data = [];
        }

        $pemasukan = (int) ($data['total_pemasukan'] ?? 0);
        $pengeluaran = (int) ($data['total_pengeluaran'] ?? 0);
        $saldo = (int) ($data['saldo'] ?? ($pemasukan - $pengeluaran));

        return [
            Stat::make('Pemasukan Periode', 'Rp '.number_format($pemasukan, 0, ',', '.'))
                ->description('Total pembayaran pada periode ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Pengeluaran Periode', 'Rp '.number_format($pengeluaran, 0, ',', '.'))
                ->description('Total pengeluaran pada periode ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Saldo Periode', 'Rp '.number_format($saldo, 0, ',', '.'))
                ->description('Pemasukan dikurangi pengeluaran')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldo >= 0 ? 'success' : 'danger'),
        ];
    }

    public static function canView(): bool
    {
        return PermissionHelper::hasResource('dashboard');
    }
}
