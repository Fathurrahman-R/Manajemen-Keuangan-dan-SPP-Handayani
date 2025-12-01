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

class Pengeluaran extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $perPage = 5;
    public $startDate;
    public $endDate;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage, array $filters): LengthAwarePaginator {
                    $params = [
                        'start_date' => $this->startDate,
                        'end_date' => $this->endDate,
                        'per_page' => $this->perPage,
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }
                    
                    if (filled($filters['date']['start_date'])) {
                        $params['start_date'] = $filters['date']['start_date'];
                    }
                    
                    if (filled($filters['date']['end_date'])) {
                        $params['end_date'] = $filters['date']['end_date'];
                    }

                    $response = Http::withHeaders([
                        'Authorization' => session()->get('data')['token']
                    ])
                        ->get(env('API_URL') . '/pengeluaran', $params)
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
                TextColumn::make('uraian')->label('Uraian')->searchable(),
                TextColumn::make('tanggal')->label('Tanggal Pengeluaran')->date('d-m-Y'),
                TextColumn::make('jumlah')->label('Jumlah')->money(currency: 'Rp.', decimalPlaces: 0, ),
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
            ->persistFiltersInSession()
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(2)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Pengeluaran')
            ->emptyStateDescription('Silahkan menambahkan pengeluaran')
            ->recordActions([
                Action::make('update') // Unique name for your action
                    ->tooltip('Ubah Pengeluaran')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Ubah Pengeluaran')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primaryMain')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold'
                                ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction()
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'uraian' => $record['uraian'],
                        'tanggal' => $record['tanggal'],
                        'jumlah' => $record['jumlah'],
                    ])
                    ->schema([
                        TextInput::make('uraian')
                            ->label('Uraian')
                            ->required(),
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->native(false)
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->required(),
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/pengeluaran/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Pengeluaran Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Hapus Pengeluaran')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Pengeluaran')
                    ->modalDescription('Apakah kamu yakin untuk menghapus pengeluaran ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->delete(env('API_URL') . '/pengeluaran/' . $record['id']);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Pengeluaran Berhasil Dihapus')
                    ->failureNotificationTitle('Pengeluaran Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    })
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Pengeluaran')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primaryMain')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold'
                                ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->schema([
                        TextInput::make('uraian')
                            ->label('Uraian')
                            ->required(),
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->native(false)
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->required(),
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/pengeluaran', $data);

                        if ($response->status() != 201) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Pengeluaran Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'text-white font-semibold'
                    ]),
            ]);
    }

    public function render()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        return view('livewire.pengeluaran');
    }
}
