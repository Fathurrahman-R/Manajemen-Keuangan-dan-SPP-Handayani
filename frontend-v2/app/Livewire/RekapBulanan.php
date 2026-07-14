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

class RekapBulanan extends Component implements HasActions, HasSchemas, HasTable
{
    use HandlesApiErrors, HasImportExport, InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $currentMonthYear;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (int $page, int $recordsPerPage, array $filters, ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                    $params = [
                        'tahun' => (int) explode('-', $this->currentMonthYear)[0],
                    ];

                    if (filled($filters['date']['tahun'])) {
                        $params['tahun'] = $filters['date']['tahun'];
                    }

                    try {
                        $response = ApiService::client()->get('/laporan/rekap', $params);

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
                    ->visible(fn () => PermissionHelper::hasResource('laporan.rekap-detail'))
                    ->color('gray')
                    ->size('sm')
                    ->modalHeading(fn (array $record) => 'Detail Rekap — '.($record['tanggal'] ?? '-'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('5xl')
                    ->modalContent(function (array $record) {
                        [$bulan, $tahun] = $this->resolveBulanTahun((string) ($record['tanggal'] ?? ''));

                        return view('livewire.partials.detail-laporan', [
                            'bulan' => $bulan,
                            'tahun' => $tahun,
                        ]);
                    }),
            ])
            ->filters([
                Filter::make('date')
                    ->schema([
                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->maxValue(Carbon::now()->year()),
                    ]),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Rekap Bulanan')
            ->emptyStateDescription('Silahkan menambahkan data pembayaran atau pengeluaran')
            ->emptyStateIcon('heroicon-o-document-text')
            ->headerActions([
                Action::make('Export')
                    ->label('Export PDF')
                    ->visible(fn (): bool => PermissionHelper::hasResource('laporan.export'))
                    ->color('danger')
                    ->icon('heroicon-o-document-arrow-down')
                    ->modalHeading('Export PDF Rekap Bulanan')
                    ->modalSubmitActionLabel('Export')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->minValue(2000)
                            ->maxValue(now()->year + 1)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $params = ['tahun' => (int) $data['tahun']];

                        $filename = 'Rekap Bulanan-'.$params['tahun'].'.pdf';
                        $response = ApiService::client()
                            ->withHeaders(['Accept' => 'application/pdf'])
                            ->get('/laporan/export/rekap', $params);

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
                    ->modalHeading('Export Rekap Bulanan')
                    ->modalSubmitActionLabel('Export')
                    ->schema([
                        \Filament\Forms\Components\Select::make('format')
                            ->label('Format')
                            ->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'])
                            ->default('xlsx')
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return $this->doExportAction('rekap_bulanan', $data);
                    }),
            ]);
    }

    public function render()
    {
        $this->currentMonthYear = Carbon::now()->format('Y-m-d');

        return view('livewire.rekap-bulanan');
    }

    /**
     * Map the localised month name shown in the row back to (bulan, tahun).
     * The backend service emits localised Indonesian month names like "Januari".
     */
    private function resolveBulanTahun(string $bulanView): array
    {
        $tahun = (int) (explode('-', $this->currentMonthYear)[0] ?? Carbon::now()->year);
        try {
            $bulan = (int) Carbon::createFromLocaleFormat('F', 'id', $bulanView)->month;
        } catch (\Throwable $e) {
            $bulan = (int) Carbon::now()->month;
        }

        return [$bulan, $tahun];
    }
}
