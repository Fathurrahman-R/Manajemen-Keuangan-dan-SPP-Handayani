<?php

namespace App\Livewire;

use App\Services\ApiService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;

class PortalSiswaPembayaranTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public ?int $selectedSiswaId = null;

    public ?int $selectedTahunAjaranId = null;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): LengthAwarePaginator {
                $params = [];
                if ($this->selectedSiswaId) {
                    $params['siswa_id'] = $this->selectedSiswaId;
                }

                if ($this->selectedTahunAjaranId !== null) {
                    $params['tahun_ajaran_id'] = $this->selectedTahunAjaranId;
                } else {
                    $params['all_periods'] = true;
                }

                try {
                    $response = ApiService::client()->get('/dashboard/siswa', $params);
                    $rows = $response->ok() ? (collect($response->json('data.pembayaran_terbaru') ?? [])->all()) : [];
                } catch (\Throwable $e) {
                    $rows = [];
                }

                return new LengthAwarePaginator(
                    items: $rows,
                    total: count($rows),
                    perPage: max(count($rows), 1),
                    currentPage: 1,
                );
            })
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y'),
                TextColumn::make('nama_jenis_tagihan')->label('Jenis Tagihan')->wrap(),
                TextColumn::make('metode')->label('Metode')->badge(),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR', divideBy: 1, decimalPlaces: 0)
                    ->color('success'),
            ])
            ->striped()
            ->paginated(false)
            ->emptyStateHeading('Belum ada pembayaran')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public function render()
    {
        return view('livewire.portal-siswa-pembayaran-table');
    }
}
