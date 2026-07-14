<?php

namespace App\Filament\Widgets;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Widgets\ChartWidget;

class KasBulananChart extends ChartWidget
{
    protected ?string $heading = 'Pemasukan vs Pengeluaran';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '380px';

    public ?int $selectedTahunAjaranId = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : ['all_periods' => true];

        try {
            $response = ApiService::client()->get('/dashboard/charts/kas-bulanan', $params);

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
                    'label' => 'Pemasukan',
                    'data' => collect($data)->pluck('pemasukan')->toArray(),
                    'borderColor' => '#10B981',
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => collect($data)->pluck('pengeluaran')->toArray(),
                    'borderColor' => '#EF4444',
                    'tension' => 0.3,
                    'fill' => false,
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
