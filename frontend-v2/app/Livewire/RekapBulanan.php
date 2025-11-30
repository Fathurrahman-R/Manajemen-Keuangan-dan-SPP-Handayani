<?php

namespace App\Livewire;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RekapBulanan extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;
    
    public $currentMonthYear;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (int $page, int $recordsPerPage, array $filters): LengthAwarePaginator {
                    $params = [
                        'tahun' => (int) explode('-', $this->currentMonthYear)[0],
                    ];

                    $response = Http::withHeaders([
                        'Authorization' => session()->get('data')['token']
                    ])
                        ->get(env('API_URL') . '/laporan/rekap', $params)
                        ->collect();

                    return new LengthAwarePaginator(
                        items: $response['data'] ?? [],
                        total: $response['meta']['total'] ?? 0,
                        perPage: $recordsPerPage,
                        currentPage: $page,
                    );
                }
            )
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal'),
                TextColumn::make('total_masuk')->label('Total Masuk')->money(currency: 'Rp.', decimalPlaces: 0, ),
                TextColumn::make('total_keluar')->label('Total Keluar')->money(currency: 'Rp.', decimalPlaces: 0, ),
                TextColumn::make('saldo')->label('Saldo')->money(currency: 'Rp.', decimalPlaces: 0, ),
            ])
            ->filters([
                Filter::make('date')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label('Tanggal Berakhir')
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->native(false),
                    ])
            ])
            ->deferLoading()
            ->striped()
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Rekap Bulanan')
            ->emptyStateDescription('Silahkan menambahkan data pembayaran atau pengeluaran');
    }

    public function render()
    {
        $this->currentMonthYear = Carbon::now()->format('Y-m-d');

        return view('livewire.rekap-bulanan');
    }
}
