<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\ChartWidget;

class TunggakanJenjangChart extends ChartWidget
{
    protected ?string $heading = 'Tunggakan per Jenjang';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '300px';

    public ?int $selectedTahunAjaranId = null;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : ['all_periods' => true];

        try {
            $data = ApiService::dashboardOverviewSlice('chart_tunggakan_jenjang', $params);

            if ($data === null) {
                return ['datasets' => [], 'labels' => []];
            }
        } catch (\Throwable $e) {
            return ['datasets' => [], 'labels' => []];
        }

        return [
            'datasets' => [
                [
                    'data' => collect($data)->pluck('total_tunggakan')->toArray(),
                    'backgroundColor' => ['#3B82F6', '#10B981', '#F59E0B'],
                ],
            ],
            'labels' => collect($data)->pluck('jenjang')->toArray(),
        ];
    }

    public static function canView(): bool
    {
        return PermissionHelper::hasResource('dashboard');
    }
}
