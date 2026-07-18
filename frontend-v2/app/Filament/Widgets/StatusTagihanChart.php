<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\ChartWidget;

class StatusTagihanChart extends ChartWidget
{
    protected ?string $heading = 'Status Tagihan';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '300px';

    public ?int $selectedTahunAjaranId = null;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : ['all_periods' => true];

        try {
            $response = ApiService::client()->get('/dashboard/charts/status-tagihan', $params);

            if (! $response->ok()) {
                return ['datasets' => [], 'labels' => []];
            }

            $data = $response->json('data') ?? [];
        } catch (\Throwable $e) {
            return ['datasets' => [], 'labels' => []];
        }

        return [
            'datasets' => [
                [
                    'data' => collect($data)->pluck('jumlah')->toArray(),
                    'backgroundColor' => ['#10B981', '#F59E0B', '#EF4444'],
                ],
            ],
            'labels' => collect($data)->pluck('status')->toArray(),
        ];
    }

    public static function canView(): bool
    {
        return PermissionHelper::hasResource('dashboard');
    }
}
