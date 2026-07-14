<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
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
    use \App\Livewire\Concerns\HasPeriodFilter;
    use HandlesApiErrors, InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function mount(): void
    {
        $this->mountHasPeriodFilter();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage, array $filters, ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                $params = ['per_page' => $recordsPerPage, 'page' => $page];

                if (! empty($filters['status']['value'] ?? null)) {
                    $params['status'] = $filters['status']['value'];
                }

                if ($this->selectedTahunAjaranId !== null) {
                    $params['tahun_ajaran_id'] = $this->selectedTahunAjaranId;
                } else {
                    $params['all_periods'] = 1;
                }

                if (filled($sortColumn)) {
                    $params['sort'] = $sortColumn;
                    $params['direction'] = $sortDirection ?? 'asc';
                }

                try {
                    $response = ApiService::client()->get('/pengeluaran-request', $params);

                    if (! $response->ok()) {
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
                    ->color(fn (string $state): string => match ($state) {
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
                    ->visible(fn ($record) => in_array($record['status'], ['draft', 'rejected'])
                        && ($record['requester_id'] ?? null) == session()->get('data.id')
                        && PermissionHelper::hasResource('pengeluaran.request'))
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
                    ->visible(fn ($record) => $record['status'] === 'submitted'
                        && PermissionHelper::hasResource('pengeluaran.approve'))
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
                    ->visible(fn ($record) => $record['status'] === 'submitted'
                        && PermissionHelper::hasResource('pengeluaran.approve'))
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
                \Filament\Actions\Action::make('viewReason')
                    ->label('Alasan Ditolak')
                    ->icon('heroicon-o-information-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record['status'] === 'rejected')
                    ->modalHeading('Alasan Penolakan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function ($record): \Illuminate\Contracts\View\View {
                        $logs = $record['approval_logs'] ?? [];
                        $rejectionLog = collect($logs)->firstWhere('new_status', 'rejected');
                        $reason = $rejectionLog['note'] ?? 'Alasan tidak tersedia.';
                        $rejectedBy = $rejectionLog['user']['name'] ?? null;
                        $rejectedAt = isset($rejectionLog['created_at']) ? \Carbon\Carbon::parse($rejectionLog['created_at'])->format('d M Y, H:i') : null;

                        return view('livewire.partials.rejection-reason', compact('reason', 'rejectedBy', 'rejectedAt'));
                    }),
                \Filament\Actions\Action::make('viewNote')
                    ->label('Catatan Approval')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->visible(function ($record) {
                        if (! in_array($record['status'], ['approved', 'disbursed'])) {
                            return false;
                        }
                        $logs = $record['approval_logs'] ?? [];
                        $approveLog = collect($logs)->firstWhere('new_status', 'approved');

                        return $approveLog && ! empty($approveLog['note']);
                    })
                    ->modalHeading('Catatan Approval')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function ($record): \Illuminate\Contracts\View\View {
                        $logs = $record['approval_logs'] ?? [];
                        $approveLog = collect($logs)->firstWhere('new_status', 'approved');
                        $note = $approveLog['note'] ?? '';
                        $approvedBy = $approveLog['user']['name'] ?? null;
                        $approvedAt = isset($approveLog['created_at']) ? \Carbon\Carbon::parse($approveLog['created_at'])->format('d M Y, H:i') : null;

                        return view('livewire.partials.approval-note', compact('note', 'approvedBy', 'approvedAt'));
                    }),
                \Filament\Actions\Action::make('disburse')
                    ->label('Cairkan')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record['status'] === 'approved'
                        && PermissionHelper::hasResource('pengeluaran.disburse'))
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
                \Filament\Actions\Action::make('viewDisburse')
                    ->label('Info Pencairan')
                    ->icon('heroicon-o-banknotes')
                    ->color('gray')
                    ->visible(fn ($record) => $record['status'] === 'disbursed')
                    ->modalHeading('Informasi Pencairan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function ($record): \Illuminate\Contracts\View\View {
                        $logs = $record['approval_logs'] ?? [];
                        $disburseLog = collect($logs)->firstWhere('new_status', 'disbursed');
                        $disbursedBy = $disburseLog['user']['name'] ?? 'Tidak diketahui';
                        $disbursedAt = isset($disburseLog['created_at']) ? \Carbon\Carbon::parse($disburseLog['created_at'])->format('d M Y, H:i') : '-';

                        return view('livewire.partials.disburse-info', compact('disbursedBy', 'disbursedAt'));
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Buat Request')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->visible(fn () => PermissionHelper::hasResource('pengeluaran.request'))
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
