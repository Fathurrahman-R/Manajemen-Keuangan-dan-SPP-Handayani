<?php

namespace App\Filament\Widgets;

use App\Services\ApiService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class TopTunggakanWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Tunggakan Terbesar';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public ?int $selectedTahunAjaranId = null;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): Collection {
                $params = $this->selectedTahunAjaranId
                    ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
                    : [];

                try {
                    $data = ApiService::client()->get('/dashboard/top-tunggakan', $params)->json('data') ?? [];
                } catch (\Throwable $e) {
                    $data = [];
                }

                return collect($data);
            })
            ->columns([
                TextColumn::make('nis')->label('NIS'),
                TextColumn::make('nama')->label('Nama'),
                TextColumn::make('kelas')->label('Kelas'),
                TextColumn::make('jenjang')->label('Jenjang')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'TK' => 'info',
                        'MI' => 'success',
                        'KB' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total_tunggakan')->label('Tunggakan')
                    ->money(currency: 'IDR', locale: 'id')
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Tidak ada siswa menunggak')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function canView(): bool
    {
        return in_array('view-dashboard', session()->get('data.permissions', []));
    }
}
