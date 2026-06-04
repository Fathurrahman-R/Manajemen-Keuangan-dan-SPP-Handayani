<?php

namespace App\Filament\Widgets;

use App\Services\ApiService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class PembayaranTerbaruWidget extends BaseWidget
{
    protected static ?string $heading = '5 Pembayaran Terbaru';

    protected static ?int $sort = 8;

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
                    $data = ApiService::client()->get('/dashboard/pembayaran-terbaru', $params)->json('data') ?? [];
                } catch (\Throwable $e) {
                    $data = [];
                }

                return collect($data);
            })
            ->columns([
                TextColumn::make('kode_pembayaran')->label('Kode')
                    ->fontFamily('mono')
                    ->size('sm'),
                TextColumn::make('nama_siswa')->label('Siswa'),
                TextColumn::make('nama_jenis_tagihan')->label('Jenis Tagihan'),
                TextColumn::make('tanggal')->label('Tanggal')
                    ->date('d/m/Y'),
                TextColumn::make('metode')->label('Metode')
                    ->badge()
                    ->color('info'),
                TextColumn::make('jumlah')->label('Jumlah')
                    ->money(currency: 'IDR', locale: 'id')
                    ->color('success')
                    ->weight('bold'),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Belum ada pembayaran')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function canView(): bool
    {
        return in_array('view-dashboard', session()->get('data.permissions', []));
    }
}
