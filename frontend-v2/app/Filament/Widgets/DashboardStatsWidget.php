<?php

namespace App\Filament\Widgets;

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
            : [];

        try {
            $data = ApiService::client()->get('/dashboard/summary', $params)->json('data') ?? [];
        } catch (\Throwable $e) {
            $data = [];
        }

        return [
            Stat::make('Total Tagihan', 'Rp ' . number_format($data['total_tagihan'] ?? 0, 0, ',', '.'))
                ->description('Seluruh tagihan periode ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total Terbayar', 'Rp ' . number_format($data['total_terbayar'] ?? 0, 0, ',', '.'))
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Tunggakan', 'Rp ' . number_format($data['total_tunggakan'] ?? 0, 0, ',', '.'))
                ->description('Belum dibayar')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Siswa Aktif', number_format($data['jumlah_siswa_aktif'] ?? 0))
                ->description('Total siswa aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Siswa Menunggak', number_format($data['jumlah_siswa_menunggak'] ?? 0))
                ->description('Memiliki tunggakan')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('warning'),

            Stat::make('Pelunasan', ($data['persentase_pelunasan'] ?? 0) . '%')
                ->description('Persentase pelunasan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return in_array('view-dashboard', session()->get('data.permissions', []));
    }
}
