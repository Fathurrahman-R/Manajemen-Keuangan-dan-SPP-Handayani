<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use App\Livewire\Concerns\HasImportExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class KasHarian extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable, HasImportExport, HandlesApiErrors;

    public $currentMonthYear;
    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (int $page, int $recordsPerPage, array $filters, ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                    $params = [
                        'bulan' => (int) explode('-', $this->currentMonthYear)[1],
                        'tahun' => (int) explode('-', $this->currentMonthYear)[0],
                    ];

                    if(filled($filters['date']['bulan'] ?? null)) {
                        $params['bulan'] = $filters['date']['bulan'];
                    }

                    if(filled($filters['date']['tahun'] ?? null)) {
                        $params['tahun'] = $filters['date']['tahun'];
                    }

                    if (filled($sortColumn)) {
                        $params['sort'] = $sortColumn;
                        $params['direction'] = $sortDirection ?? 'asc';
                    }

                    try {
                        $response = ApiService::client()->get('/laporan/kas', $params);

                        if (!$response->ok()) {
                            $this->handleApiError($response);
                            return new LengthAwarePaginator(items: [], total: 0, perPage: $recordsPerPage, currentPage: $page);
                        }

                        $collected = $response->collect();
                        $items = collect($collected['data'] ?? [])
                            ->when(
                                filled($sortColumn),
                                fn(Collection $data): Collection => $data->sortBy(
                                    fn(array $record) => data_get($record, $sortColumn),
                                    SORT_REGULAR,
                                    ($sortDirection ?? 'asc') === 'desc'
                                )->values()
                            )
                            ->toArray();

                        return new LengthAwarePaginator(
                            items: $items,
                            total: $collected['meta']['total'] ?? count($items),
                            perPage: $recordsPerPage,
                            currentPage: $page,
                        );
                    } catch (ConnectionException $e) {
                        $this->notifyConnectionError();
                        return new LengthAwarePaginator(items: [], total: 0, perPage: $recordsPerPage, currentPage: $page);
                    } catch (\Throwable $e) {
                        $this->notifyUnexpectedError();
                        return new LengthAwarePaginator(items: [], total: 0, perPage: $recordsPerPage, currentPage: $page);
                    }
                }
            )
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->sortable(),
                TextColumn::make('total_masuk')->label('Total Masuk')->sortable()->money(currency: 'Rp.', decimalPlaces: 0,),
                TextColumn::make('total_keluar')->label('Total Keluar')->sortable()->money(currency: 'Rp.', decimalPlaces: 0,),
                TextColumn::make('saldo')->label('Saldo')->sortable()->money(currency: 'Rp.', decimalPlaces: 0,),
            ])
            ->filters([
                Filter::make('date')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('bulan')
                                    ->label('Bulan')
                                    ->numeric(),
                                TextInput::make('tahun')
                                    ->label('Tahun')
                                    ->numeric()
                                    ->maxValue(Carbon::now()->year()),
                            ])
                    ])
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Kas Harian')
            ->emptyStateDescription('Silahkan menambahkan data pembayaran atau pengeluaran')
            ->emptyStateIcon('heroicon-o-document-text')
            ->headerActions([
                Action::make('Export')
                    ->label('Export PDF')
                    ->action(function () {
                        $filters = $this->getTableFilterState('date');
                        $params = [
                            'bulan' => (int) explode('-', $this->currentMonthYear)[1],
                            'tahun' => (int) explode('-', $this->currentMonthYear)[0],
                        ];

                        if(filled($filters['bulan'])) {
                            $params['bulan'] = $filters['bulan'];
                        }

                        if(filled($filters['tahun'])) {
                            $params['tahun'] = $filters['tahun'];
                        }

                        $filename = 'Kas harian-' . $params['bulan'] . '.pdf';
                        $response = ApiService::client()
                            ->withHeaders(['Accept' => 'application/pdf'])
                            ->get('/laporan/export/kas', $params);

                        Storage::disk('local')->put($filename, $response->body());
                        $path = Storage::disk('local')->path($filename);

                        // Return a response that prompts the file download
                        return response()
                            ->streamDownload(function () use ($path) {
                            echo file_get_contents($path);
                            // Clean up the temporary file after streaming
                            unlink($path);
                        }, $filename, [
                            'Content-Type' => 'application/pdf', // Set the correct MIME type
                        ]);
                    }),
                Action::make('export_excel')
                    ->label('Export Excel/CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->button()
                    ->visible(fn(): bool => in_array('export-data', session()->get('data.permissions', [])))
                    ->modalHeading('Export Kas Harian')
                    ->modalSubmitActionLabel('Export')
                    ->schema([
                        \Filament\Forms\Components\Select::make('format')
                            ->label('Format')
                            ->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'])
                            ->default('xlsx')
                            ->required(),
                        \Filament\Forms\Components\Select::make('bulan')
                            ->label('Bulan')
                            ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => \Carbon\Carbon::create(null, $m)->translatedFormat('F')])->toArray())
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return $this->doExportAction('kas_harian', $data);
                    }),
            ]);
    }

    public function render()
    {
        $this->currentMonthYear = Carbon::now()->format('Y-m-d');

        return view('livewire.kas-harian');
    }
}
