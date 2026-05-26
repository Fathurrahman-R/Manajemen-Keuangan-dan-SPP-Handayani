<?php

namespace App\Livewire;

use App\Services\ApiService;
use App\Livewire\Concerns\HasImportExport;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
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
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable, HasImportExport;

    public $currentMonthYear;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (int $page, int $recordsPerPage, array $filters): LengthAwarePaginator {
                    $params = [
                        'tahun' => (int) explode('-', $this->currentMonthYear)[0],
                    ];

                    if(filled($filters['date']['tahun'])) {
                        $params['tahun'] = $filters['date']['tahun'];
                    }

                    $response = ApiService::client()
                        ->get('/laporan/rekap', $params)
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
                TextColumn::make('total_masuk')->label('Total Masuk')->money(currency: 'Rp.', decimalPlaces: 0,),
                TextColumn::make('total_keluar')->label('Total Keluar')->money(currency: 'Rp.', decimalPlaces: 0,),
                TextColumn::make('saldo')->label('Saldo')->money(currency: 'Rp.', decimalPlaces: 0,),
            ])
            ->filters([
                Filter::make('date')
                    ->schema([
                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->maxValue(Carbon::now()->year()),
                    ])
            ])
            ->deferLoading()
            ->striped()
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Rekap Bulanan')
            ->emptyStateDescription('Silahkan menambahkan data pembayaran atau pengeluaran')
            ->headerActions([
                Action::make('Export')
                    ->label('Export PDF')
                    ->action(function () {
                        $filters = $this->getTableFilterState('date');
                        $params = [
                            'tahun' => (int) explode('-', $this->currentMonthYear)[0],
                        ];

                        if(filled($filters['tahun'])) {
                            $params['tahun'] = $filters['tahun'];
                        }

                        $filename = 'Rekap bulanan -' . $params['tahun'] . '.pdf';
                        $response = ApiService::client()
                            ->withHeaders(['Accept' => 'application/pdf'])
                            ->get('/laporan/export/rekap', $params);

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
}
