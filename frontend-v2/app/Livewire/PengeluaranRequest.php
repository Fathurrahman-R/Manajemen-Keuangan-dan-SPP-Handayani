<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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

    /**
     * POST `$data` to `$url`, attaching `lampiran` as multipart when a new
     * file was uploaded. `$overrideMethod` spoofs PUT via `_method` (pattern
     * from Settings.php) since PHP does not populate uploaded files for real
     * PUT requests.
     */
    private function submitWithOptionalLampiran(string $url, array $data, ?string $overrideMethod = null): \Illuminate\Http\Client\Response
    {
        $file = $data['lampiran'] ?? null;
        unset($data['lampiran']);

        if ($overrideMethod) {
            $data['_method'] = $overrideMethod;
        }

        $request = ApiService::client();

        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $request = $request->attach('lampiran', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
        }

        return $request->post($url, $data);
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
                        && PermissionHelper::hasResource('pengeluaran.create'))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $response = ApiService::client()->post("/pengeluaran-request/{$record['id']}/submit");
                        if ($response->ok()) {
                            Notification::make()->title('Request berhasil disubmit')->success()->send();
                            $this->resetTable();
                        } else {
                            $this->handleApiError($response);
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
                        } else {
                            $this->handleApiError($response);
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
                        } else {
                            $this->handleApiError($response);
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
                        } else {
                            $this->handleApiError($response);
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
                ActionGroup::make([
                    \Filament\Actions\Action::make('detail')
                        ->label('Detail')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->visible(fn () => PermissionHelper::hasResource('pengeluaran.view'))
                        ->modalHeading('Detail Request Pengeluaran')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(function ($record): \Illuminate\Contracts\View\View {
                            $timeline = collect($record['approval_logs'] ?? [])
                                ->sortBy('created_at')
                                ->map(fn ($log) => [
                                    'label' => match ($log['new_status'] ?? null) {
                                        'submitted' => 'Diajukan',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        'disbursed' => 'Dicairkan',
                                        default => $log['new_status'] ?? '-',
                                    },
                                    'by' => str_starts_with($log['note'] ?? '', 'Auto-approved') ? 'Sistem (disetujui otomatis)' : ($log['user']['name'] ?? $log['user']['username'] ?? '-'),
                                    'note' => $log['note'] ?? null,
                                    'at' => isset($log['created_at']) ? \Carbon\Carbon::parse($log['created_at'])->format('d M Y, H:i') : '-',
                                ])
                                ->values();

                            return view('livewire.partials.pengeluaran-detail', [
                                'record' => $record,
                                'timeline' => $timeline,
                            ]);
                        }),
                    \Filament\Actions\Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->color('gray')
                        ->visible(fn ($record) => in_array($record['status'], ['draft', 'rejected'])
                            && ($record['requester_id'] ?? null) == session()->get('data.id')
                            && PermissionHelper::hasResource('pengeluaran.update'))
                        ->schema([
                            TextInput::make('uraian')->label('Uraian')->required()->maxLength(255),
                            TextInput::make('jumlah')->label('Jumlah (Rp)')->numeric()->required()->minValue(1),
                            DatePicker::make('tanggal_kebutuhan')->label('Tanggal Kebutuhan')->required(),
                            TextInput::make('kategori_pengeluaran')->label('Kategori (opsional)'),
                            FileUpload::make('lampiran')->label('Ganti Lampiran (opsional)')
                                ->storeFiles(false)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(2048)
                                ->helperText('Kosongkan untuk mempertahankan lampiran yang sudah ada.'),
                        ])
                        ->fillForm(fn (array $record): array => [
                            'uraian' => $record['uraian'],
                            'jumlah' => $record['jumlah'],
                            'tanggal_kebutuhan' => $record['tanggal_kebutuhan'],
                            'kategori_pengeluaran' => $record['kategori_pengeluaran'] ?? '',
                        ])
                        ->modalHeading('Edit Request Pengeluaran')
                        ->action(function (array $data): void {
                            $record = $this->getMountedAction()?->getRecord();
                            $response = $this->submitWithOptionalLampiran("/pengeluaran-request/{$record['id']}", $data, overrideMethod: 'PUT');
                            if ($response->ok()) {
                                Notification::make()->title('Request berhasil diubah')->success()->send();
                                $this->resetTable();
                            } else {
                                $this->handleApiError($response);
                            }
                        }),
                    \Filament\Actions\Action::make('hapus')
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record['status'], ['draft', 'rejected'])
                            && ($record['requester_id'] ?? null) == session()->get('data.id')
                            && PermissionHelper::hasResource('pengeluaran.delete'))
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Request Pengeluaran')
                        ->modalDescription('Yakin ingin menghapus request ini? Tindakan tidak dapat dibatalkan.')
                        ->action(function ($record): void {
                            $response = ApiService::client()->delete("/pengeluaran-request/{$record['id']}");
                            if ($response->ok()) {
                                Notification::make()->title('Request berhasil dihapus')->success()->send();
                                $this->resetTable();
                            } else {
                                $this->handleApiError($response);
                            }
                        }),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm'),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Buat Request')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->visible(fn () => PermissionHelper::hasResource('pengeluaran.create'))
                    ->schema([
                        TextInput::make('uraian')->label('Uraian')->required()->maxLength(255),
                        TextInput::make('jumlah')->label('Jumlah (Rp)')->numeric()->required()->minValue(1),
                        DatePicker::make('tanggal_kebutuhan')->label('Tanggal Kebutuhan')->required(),
                        TextInput::make('kategori_pengeluaran')->label('Kategori (opsional)'),
                        FileUpload::make('lampiran')->label('Lampiran (opsional)')
                            ->storeFiles(false)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(2048),
                    ])
                    ->modalHeading('Buat Request Pengeluaran')
                    ->action(function (array $data) {
                        $response = $this->submitWithOptionalLampiran('/pengeluaran-request', $data);
                        if ($response->status() === 201) {
                            Notification::make()->title('Request berhasil dibuat')->success()->send();
                            $this->resetTable();
                        } else {
                            $this->handleApiError($response);
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
