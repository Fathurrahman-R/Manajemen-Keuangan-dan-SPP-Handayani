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

class PortalSiswaTagihanTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public ?int $selectedSiswaId = null;

    public ?int $selectedTahunAjaranId = null;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search): LengthAwarePaginator {
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
                    $rows = $response->ok() ? (collect($response->json('data.tagihan_list') ?? [])->all()) : [];
                } catch (\Throwable $e) {
                    $rows = [];
                }

                if (filled($search)) {
                    $needle = mb_strtolower($search);
                    $rows = array_values(array_filter($rows, fn (array $r): bool => str_contains(mb_strtolower((string) ($r['nama_jenis_tagihan'] ?? '')), $needle)
                    ));
                }

                return new LengthAwarePaginator(
                    items: $rows,
                    total: count($rows),
                    perPage: max(count($rows), 1),
                    currentPage: 1,
                );
            })
            ->columns([
                TextColumn::make('nama_jenis_tagihan')->label('Jenis Tagihan')->wrap(),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR', divideBy: 1, decimalPlaces: 0),
                TextColumn::make('jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lunas' => 'success',
                        'Belum Lunas' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->striped()
            ->paginated(false)
            ->emptyStateHeading('Tidak ada tagihan')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    public function render()
    {
        return view('livewire.portal-siswa-tagihan-table');
    }
}
