<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget stat halaman Pengeluaran — selalu branch-wide (lintas semua tahun
 * ajaran), sama seperti perhitungan yang menentukan lolos-tidaknya validasi
 * saldo minus di WorkflowService::assertSaldoMencukupi(). Tidak reaktif
 * terhadap filter periode di halaman Pengeluaran karena saldo cabang
 * memang bukan konsep per-periode.
 */
class PengeluaranStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        try {
            $data = ApiService::client()->get('/pengeluaran-request/stats')->json('data') ?? [];
        } catch (\Throwable $e) {
            $data = [];
        }

        $totalSaldoCabang = (int) ($data['total_saldo_cabang'] ?? 0);
        $totalOutstanding = (int) ($data['total_outstanding'] ?? 0);
        $saldoTersedia = (int) ($data['saldo_tersedia'] ?? 0);

        return [
            Stat::make('Total Saldo Cabang', 'Rp '.number_format($totalSaldoCabang, 0, ',', '.'))
                ->description('Pemasukan dikurangi pengeluaran, seluruh periode')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalSaldoCabang < 0 ? 'danger' : 'info'),
            Stat::make('Total Request Pengeluaran', 'Rp '.number_format($totalOutstanding, 0, ',', '.'))
                ->description('Nominal request submitted/approved, belum dicairkan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Saldo Tersedia', 'Rp '.number_format($saldoTersedia, 0, ',', '.'))
                ->description('Untuk membuat request pengeluaran baru')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($saldoTersedia < 0 ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        return PermissionHelper::hasResource('pengeluaran.view');
    }
}
