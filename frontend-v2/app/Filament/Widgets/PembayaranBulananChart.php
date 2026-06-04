<?php

namespace App\Filament\Widgets;

use App\Services\ApiService;
use Filament\Widgets\ChartWidget;

class PembayaranBulananChart extends ChartWidget
{
    protected ?string $heading = 'Pembayaran per Bulan';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '300px';

    public ?int $selectedTahunAjaranId = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $params = $this->selectedTahunAjaranId
            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
            : [];

        try {
            $data = ApiService::client()->get('/dashboard/charts/pembayaran-bulanan', $params)->json('data') ?? [];
        } catch (\Throwable $e) {
            $data = [];
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
        return in_array('view-dashboard', session()->get('data.permissions', []));
    }
}
