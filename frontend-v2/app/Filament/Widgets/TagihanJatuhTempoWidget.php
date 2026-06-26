<?php

namespace App\Filament\Widgets;

use App\Services\ApiService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class TagihanJatuhTempoWidget extends BaseWidget
{
    protected static ?string $heading = 'Tagihan Jatuh Tempo 7 Hari';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public ?int $selectedTahunAjaranId = null;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): Collection {
                $params = $this->selectedTahunAjaranId
                    ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
                    : ['all_periods' => true];

                try {
                    $response = ApiService::client()->get('/dashboard/tagihan-jatuh-tempo', $params);

                    if (!$response->ok()) {
                        return collect([]);
                    }

                    $data = $response->json('data') ?? [];
                } catch (\Throwable $e) {
                    $data = [];
                }

                return collect($data);
            })
            ->columns([
                TextColumn::make('nama_siswa')->label('Siswa'),
                TextColumn::make('nama_jenis_tagihan')->label('Jenis Tagihan'),
                TextColumn::make('jatuh_tempo')->label('Jatuh Tempo')
                    ->date('d/m/Y'),
                TextColumn::make('jumlah')->label('Jumlah')
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Belum Dibayar' => 'danger',
                        'Belum Lunas' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Belum ada data yang tersedia.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function canView(): bool
    {
        return in_array('view-dashboard', session()->get('data.permissions', []));
    }
}
