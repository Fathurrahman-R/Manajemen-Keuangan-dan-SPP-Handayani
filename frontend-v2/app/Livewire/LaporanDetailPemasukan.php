<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
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

class LaporanDetailPemasukan extends Component implements HasActions, HasSchemas, HasTable
{
    use HandlesApiErrors;
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?string $tanggal = null;

    public ?int $bulan = null;

    public ?int $tahun = null;

    public function mount(?string $tanggal = null, ?int $bulan = null, ?int $tahun = null): void
    {
        $this->tanggal = $tanggal;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (int $page, int $recordsPerPage): LengthAwarePaginator {
                    $rows = $this->fetchRows();

                    return new LengthAwarePaginator(
                        items: array_slice($rows, ($page - 1) * $recordsPerPage, $recordsPerPage),
                        total: count($rows),
                        perPage: $recordsPerPage,
                        currentPage: $page,
                    );
                }
            )
            ->columns([
                TextColumn::make('nis')->label('NIS/NISN')->fontFamily('mono'),
                TextColumn::make('nama')->label('Nama')->wrap(),
                TextColumn::make('nama_tagihan')->label('Nama Tagihan')->wrap(),
                TextColumn::make('jumlah')->label('Jumlah')->money(currency: 'Rp.', decimalPlaces: 0)->alignRight(),
            ])
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Tidak ada pemasukan')
            ->emptyStateDescription('Tidak ada transaksi pemasukan pada periode ini.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(): array
    {
        try {
            if (! empty($this->tanggal)) {
                $response = ApiService::client()->get('/laporan/kas/detail', ['tanggal' => $this->tanggal]);
            } else {
                $response = ApiService::client()->get('/laporan/rekap/detail', [
                    'bulan' => $this->bulan,
                    'tahun' => $this->tahun,
                ]);
            }

            if (! $response->ok()) {
                return [];
            }

            return $response->json('data.pemasukan') ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function render()
    {
        return view('livewire.laporan-detail-table');
    }
}
