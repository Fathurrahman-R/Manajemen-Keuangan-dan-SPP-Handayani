<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;

class PengeluaranRequest extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable, HandlesApiErrors;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage, array $filters, ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                $params = ['per_page' => $recordsPerPage, 'page' => $page];

                if (!empty($filters['status']['value'] ?? null)) {
                    $params['status'] = $filters['status']['value'];
                }

                if (filled($sortColumn)) {
                    $params['sort'] = $sortColumn;
                    $params['direction'] = $sortDirection ?? 'asc';
                }

                try {
                    $response = ApiService::client()->get('/pengeluaran-request', $params);

                    if (!$response->ok()) {
                        $this->handleApiError($response);
                        return new LengthAwarePaginator(items: [], total: 0, perPage: $recordsPerPage, currentPage: $page);
                    }

                    $data = $response->json();

                    return new LengthAwarePaginator(
                        items: $data['data'] ?? [],
                        total: $data['total'] ?? 0,
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
            })
            ->columns([
                TextColumn::make('uraian')->label('Uraian')->limit(40),
                TextColumn::make('jumlah')->label('Jumlah')->sortable()->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('tanggal_kebutuhan')->label('Tgl Kebutuhan')->sortable()->date('d/m/Y'),
                TextColumn::make('requester.name')->label('Pengaju'),
                TextColumn::make('status')->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'disbursed' => 'purple',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'disbursed' => 'Disbursed',
                    ]),
            ])
            ->recordActions([
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn($record) => in_array($record['status'], ['draft', 'rejected'])
                        && in_array('create-pengeluaran-request', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $response = ApiService::client()->post("/pengeluaran-request/{$record['id']}/submit");
                        if ($response->ok()) {
                            Notification::make()->title('Request berhasil disubmit')->success()->send();
                            $this->resetTable();
                        } else {
                            Notification::make()->title('Gagal')->danger()->body($response->json('errors.status.0') ?? 'Error')->send();
                        }
                    }),
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record['status'] === 'submitted'
                        && in_array('approve-pengeluaran', session()->get('data.permissions', [])))
                    ->schema([
                        Textarea::make('note')->label('Catatan (opsional)'),
                    ])
                    ->action(function ($record, array $data) {
                        $response = ApiService::client()->post("/pengeluaran-request/{$record['id']}/approve", ['note' => $data['note'] ?? null]);
                        if ($response->ok()) {
                            Notification::make()->title('Request disetujui')->success()->send();
                            $this->resetTable();
                        }
                    }),
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => $record['status'] === 'submitted'
                        && in_array('approve-pengeluaran', session()->get('data.permissions', [])))
                    ->schema([
                        Textarea::make('reason')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $response = ApiService::client()->post("/pengeluaran-request/{$record['id']}/reject", ['reason' => $data['reason']]);
                        if ($response->ok()) {
                            Notification::make()->title('Request ditolak')->success()->send();
                            $this->resetTable();
                        }
                    }),
                \Filament\Actions\Action::make('disburse')
                    ->label('Cairkan')
                    ->icon('heroicon-o-banknotes')
                    ->color('purple')
                    ->visible(fn($record) => $record['status'] === 'approved'
                        && in_array('disburse-pengeluaran', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Cairkan Dana')
                    ->modalDescription('Yakin ingin mencairkan pengeluaran ini? Pengeluaran akan dicatat di kas.')
                    ->action(function ($record) {
                        $response = ApiService::client()->post("/pengeluaran-request/{$record['id']}/disburse");
                        if ($response->ok()) {
                            Notification::make()->title('Pencairan berhasil')->success()->send();
                            $this->resetTable();
                        }
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Buat Request')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->visible(fn() => in_array('create-pengeluaran-request', session()->get('data.permissions', [])))
                    ->schema([
                        TextInput::make('uraian')->label('Uraian')->required()->maxLength(255),
                        TextInput::make('jumlah')->label('Jumlah (Rp)')->numeric()->required()->minValue(1),
                        DatePicker::make('tanggal_kebutuhan')->label('Tanggal Kebutuhan')->required(),
                        TextInput::make('kategori_pengeluaran')->label('Kategori (opsional)'),
                    ])
                    ->modalHeading('Buat Request Pengeluaran')
                    ->action(function (array $data) {
                        $response = ApiService::client()->post('/pengeluaran-request', $data);
                        if ($response->status() === 201) {
                            Notification::make()->title('Request berhasil dibuat')->success()->send();
                            $this->resetTable();
                        } else {
                            Notification::make()->title('Gagal')->danger()->body('Gagal membuat request.')->send();
                        }
                    }),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada request')
            ->emptyStateDescription('Buat request pengeluaran baru dengan tombol di atas.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public function render()
    {
        return view('livewire.pengeluaran-request');
    }
}
