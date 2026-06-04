<?php

namespace App\Filament\Widgets;

use App\Services\ApiService;
use Filament\Widgets\ChartWidget;

class KasBulananChart extends ChartWidget
{
    protected ?string $heading = 'Pemasukan vs Pengeluaran';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '300px';

    public ?int $selectedTahunAjaranId = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : [];

        try {
            $data = ApiService::client()->get('/dashboard/charts/kas-bulanan', $params)->json('data') ?? [];
        } catch (\Throwable $e) {
            $data = [];
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
        return in_array('view-dashboard', session()->get('data.permissions', []));
    }
}
