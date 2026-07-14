<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public ?int $selectedTahunAjaranId = null;

    protected function getStats(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : ['all_periods' => true];

        try {
            $response = ApiService::client()->get('/dashboard/summary', $params);

            if (! $response->ok()) {
                return $this->fallbackStats();
            }

            $data = $response->json('data') ?? [];
        } catch (\Throwable $e) {
            return $this->fallbackStats();
        }

        return [
            Stat::make('Total Tagihan', 'Rp '.number_format($data['total_tagihan'] ?? 0, 0, ',', '.'))
                ->description('Seluruh tagihan periode ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total Terbayar', 'Rp '.number_format($data['total_terbayar'] ?? 0, 0, ',', '.'))
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Tunggakan', 'Rp '.number_format($data['total_tunggakan'] ?? 0, 0, ',', '.'))
                ->description('Belum dibayar')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Siswa Punya Tagihan', number_format($data['jumlah_siswa_punya_tagihan'] ?? 0))
                ->description('Siswa dengan tagihan di periode ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Siswa Menunggak', number_format($data['jumlah_siswa_menunggak'] ?? 0))
                ->description('Memiliki tunggakan')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('warning'),

            Stat::make('Pelunasan', ($data['persentase_pelunasan'] ?? 0).'%')
                ->description('Persentase pelunasan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }

    protected function fallbackStats(): array
    {
        return [
            Stat::make('Total Tagihan', 'Rp 0')
                ->description('Seluruh tagihan periode ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total Terbayar', 'Rp 0')
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Tunggakan', 'Rp 0')
                ->description('Belum dibayar')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Siswa Punya Tagihan', '0')
                ->description('Siswa dengan tagihan di periode ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Siswa Menunggak', '0')
                ->description('Memiliki tunggakan')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('warning'),

            Stat::make('Pelunasan', '0%')
                ->description('Persentase pelunasan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return PermissionHelper::hasResource('dashboard');
    }
}
