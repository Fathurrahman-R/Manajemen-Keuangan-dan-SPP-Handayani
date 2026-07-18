<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\ChartWidget;

class PembayaranBulananChart extends ChartWidget
{
    protected ?string $heading = 'Pembayaran per Bulan';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '380px';

    public ?int $selectedTahunAjaranId = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : ['all_periods' => true];

        try {
            $data = ApiService::dashboardOverviewSlice('chart_pembayaran_bulanan', $params);

            if ($data === null) {
                return ['datasets' => [], 'labels' => []];
            }
        } catch (\Throwable $e) {
            return ['datasets' => [], 'labels' => []];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pembayaran',
                    'data' => collect($data)->pluck('total')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => collect($data)->pluck('nama_bulan')->toArray(),
        ];
    }

    public static function canView(): bool
    {
        return PermissionHelper::hasResource('dashboard');
    }
}
