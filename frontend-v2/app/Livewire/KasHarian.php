<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\HasImportExport;
use App\Services\ApiService;
use Carbon\Carbon;
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
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class KasHarian extends Component implements HasActions, HasSchemas, HasTable
{
    use HandlesApiErrors, HasImportExport, InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

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

                    if (filled($filters['date']['bulan'] ?? null)) {
                        $params['bulan'] = $filters['date']['bulan'];
                    }

                    if (filled($filters['date']['tahun'] ?? null)) {
                        $params['tahun'] = $filters['date']['tahun'];
                    }

                    if (filled($sortColumn)) {
                        $params['sort'] = $sortColumn;
                        $params['direction'] = $sortDirection ?? 'asc';
                    }

                    try {
                        $response = ApiService::client()->get('/laporan/kas', $params);

                        if (! $response->ok()) {
                            $this->handleApiError($response);

                            return new LengthAwarePaginator(items: [], total: 0, perPage: $recordsPerPage, currentPage: $page);
                        }

                        $collected = $response->collect();
                        $items = collect($collected['data'] ?? [])
                            ->when(
                                filled($sortColumn),
                                fn (Collection $data): Collection => $data->sortBy(
                                    fn (array $record) => data_get($record, $sortColumn),
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
                TextColumn::make('total_masuk')->label('Total Masuk')->sortable()->money(currency: 'Rp.', decimalPlaces: 0),
                TextColumn::make('total_keluar')->label('Total Keluar')->sortable()->money(currency: 'Rp.', decimalPlaces: 0),
                TextColumn::make('saldo')->label('Saldo')->sortable()->money(currency: 'Rp.', decimalPlaces: 0),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->visible(fn () => PermissionHelper::hasResource('laporan.kas-detail'))
                    ->color('gray')
                    ->size('sm')
                    ->modalHeading(fn (array $record) => 'Detail Kas — '.($record['tanggal'] ?? '-'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('5xl')
                    ->modalContent(function (array $record) {
                        return view('livewire.partials.detail-laporan', [
                            'tanggal' => $this->resolveIsoDate((string) ($record['tanggal'] ?? '')),
                        ]);
                    }),
            ])
            ->filters([
                Filter::make('date')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('bulan')
                                    ->label('Bulan')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(12),
                                TextInput::make('tahun')
                                    ->label('Tahun')
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(Carbon::now()->year()),
                            ]),
                    ]),
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
                    ->visible(fn (): bool => PermissionHelper::hasResource('laporan.export'))
                    ->color('danger')
                    ->icon('heroicon-o-document-arrow-down')
                    ->modalHeading('Export PDF Kas Harian')
                    ->modalSubmitActionLabel('Export')
                    ->schema([
                        \Filament\Forms\Components\Select::make('bulan')
                            ->label('Bulan')
                            ->options(collect(range(1, 12))
                                ->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m)->translatedFormat('F')])
                                ->toArray())
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->minValue(2000)
                            ->maxValue(now()->year + 1)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $params = [
                            'bulan' => (int) $data['bulan'],
                            'tahun' => (int) $data['tahun'],
                        ];

                        $filename = 'Kas harian-'.str_pad((string) $params['bulan'], 2, '0', STR_PAD_LEFT).'-'.$params['tahun'].'.pdf';
                        $response = ApiService::client()
                            ->withHeaders(['Accept' => 'application/pdf'])
                            ->get('/laporan/export/kas', $params);

                        Storage::disk('local')->put($filename, $response->body());
                        $path = Storage::disk('local')->path($filename);

                        return response()
                            ->streamDownload(function () use ($path) {
                                echo file_get_contents($path);
                                unlink($path);
                            }, $filename, [
                                'Content-Type' => 'application/pdf',
                            ]);
                    }),
                Action::make('export_excel')
                    ->label('Export Excel/CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->button()
                    ->visible(fn (): bool => PermissionHelper::hasResource('laporan.export'))
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
                            ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m)->translatedFormat('F')])->toArray())
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->minValue(2000)
                            ->maxValue(now()->year + 1)
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

    /**
     * Convert the localised "DD MMMM YYYY" date used in table rows back to
     * an ISO `Y-m-d` value so the backend detail endpoint can match it.
     */
    private function resolveIsoDate(string $tanggalView): string
    {
        try {
            return Carbon::createFromLocaleFormat('d F Y', 'id', $tanggalView)->format('Y-m-d');
        } catch (\Throwable $e) {
            // Fallback for any other format.
            return Carbon::parse($tanggalView)->format('Y-m-d');
        }
    }
}
