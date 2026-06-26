<?php

namespace App\Filament\Widgets;

use App\Services\ApiService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget stat di portal siswa/wali — total tagihan, terbayar, tunggakan.
 */
class PortalSiswaStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public ?int $selectedSiswaId = null;
    public ?int $selectedTahunAjaranId = null;

    protected function getStats(): array
    {
        // selectedSiswaId can be passed via URL query (?siswa=X) or property.
        $siswaId = $this->selectedSiswaId ?? (int) request()->query('siswa', 0) ?: null;

        $params = [];
        if ($siswaId) {
            $params['siswa_id'] = $siswaId;
        }

        if ($this->selectedTahunAjaranId !== null) {
            $params['tahun_ajaran_id'] = $this->selectedTahunAjaranId;
        } else {
            $params['all_periods'] = true;
        }

        try {
            $response = ApiService::client()->get('/dashboard/siswa', $params);
            $data = $response->ok() ? ($response->json('data') ?? []) : [];
        } catch (\Throwable $e) {
            $data = [];
        }

        $totalTagihan = (int) ($data['total_tagihan'] ?? 0);
        $totalTerbayar = (int) ($data['total_terbayar'] ?? 0);
        $totalTunggakan = (int) ($data['total_tunggakan'] ?? 0);

        return [
            Stat::make('Total Tagihan', 'Rp ' . number_format($totalTagihan, 0, ',', '.'))
                ->description('Seluruh tagihan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            Stat::make('Total Terbayar', 'Rp ' . number_format($totalTerbayar, 0, ',', '.'))
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Total Tunggakan', 'Rp ' . number_format($totalTunggakan, 0, ',', '.'))
                ->description('Belum dibayar')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($totalTunggakan > 0 ? 'danger' : 'gray'),
        ];
    }
}
