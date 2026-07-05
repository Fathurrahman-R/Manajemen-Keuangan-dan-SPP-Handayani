<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class NotificationLogTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable, HandlesApiErrors;

    public string $filterType = '';
    public string $filterStatus = '';

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search): array {
                    try {
                        $params = ['per_page' => 100];

                        if (filled($this->filterType)) {
                            $params['type'] = $this->filterType;
                        }
                        if (filled($this->filterStatus)) {
                            $params['status'] = $this->filterStatus;
                        }

                        $response = ApiService::client()->get('/notification-logs', $params);

                        if (!$response->ok()) {
                            $this->handleApiError($response);
                            return [];
                        }

                        $data = collect($response->json('data', []));

                        // Client-side search
                        if (filled($search)) {
                            $searchLower = strtolower($search);
                            $data = $data->filter(function (array $record) use ($searchLower): bool {
                                return str_contains(strtolower($record['recipient_email'] ?? ''), $searchLower)
                                    || str_contains(strtolower($record['tagihan_kode'] ?? ''), $searchLower)
                                    || str_contains(strtolower($record['notification_type'] ?? ''), $searchLower)
                                    || str_contains(strtolower($record['reason'] ?? ''), $searchLower);
                            });
                        }

                        return $data->values()->toArray();
                    } catch (ConnectionException $e) {
                        $this->notifyConnectionError();
                        return [];
                    } catch (\Throwable $e) {
                        $this->notifyUnexpectedError();
                        return [];
                    }
                }
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->sortable()
                    ->formatStateUsing(fn(?string $state) => $state
                        ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i')
                        : '-'
                    ),
                TextColumn::make('notification_type')
                    ->label('Tipe')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'tagihan_baru' => 'Tagihan Baru',
                        'kwitansi' => 'Kwitansi',
                        'reminder' => 'Pengingat',
                        'overdue' => 'Jatuh Tempo',
                        default => $state ?? '-',
                    })
                    ->color(fn(?string $state) => match ($state) {
                        'tagihan_baru' => 'primary',
                        'kwitansi' => 'success',
                        'reminder' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tagihan_kode')
                    ->label('Kode Tagihan')
                    ->sortable()
                    ->copyable()
                    ->formatStateUsing(fn($state) => $state ?? '-'),
                TextColumn::make('recipient_email')
                    ->label('Email Tujuan')
                    ->sortable()
                    ->formatStateUsing(fn(?string $state) => filled($state) ? $state : '(tidak ada)'),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn(?string $state) => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'skipped' => 'Dilewati',
                        default => $state ?? '-',
                    }),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->sortable()
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'no_email_available' => 'Email belum diatur',
                        'invalid_email' => 'Email tidak valid',
                        'opted_out' => 'Berhenti langganan',
                        'rate_limited' => 'Batas pengiriman tercapai',
                        'disabled' => 'Notifikasi dinonaktifkan',
                        null, '' => '-',
                        default => $state,
                    }),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Tidak Ada Log Notifikasi')
            ->emptyStateDescription('Belum ada riwayat pengiriman notifikasi email.')
            ->emptyStateIcon('heroicon-o-envelope')
            ->bulkActions([
                BulkAction::make('retry')
                    ->label('Kirim Ulang Terpilih')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang Notifikasi')
                    ->modalDescription('Notifikasi yang berstatus "Gagal" atau "Dilewati" akan dikirim ulang. Pastikan email siswa/wali sudah diatur. Lanjutkan?')
                    ->modalSubmitActionLabel('Ya, Kirim Ulang')
                    ->action(function (Collection $records): void {
                        $logIds = $records->pluck('id')->filter()->values()->toArray();

                        if (empty($logIds)) {
                            Notification::make()
                                ->title('Tidak ada log yang dipilih')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            $response = ApiService::client()->post('/notification-logs/retry', [
                                'log_ids' => $logIds,
                            ]);

                            if ($response->ok()) {
                                $count = $response->json('retried_count', 0);
                                Notification::make()
                                    ->title("{$count} notifikasi berhasil dikirim ulang")
                                    ->success()
                                    ->send();
                            } else {
                                $this->handleApiError($response);
                            }
                        } catch (ConnectionException $e) {
                            $this->notifyConnectionError();
                        } catch (\Throwable $e) {
                            $this->notifyUnexpectedError();
                        }

                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ]);
    }

    public function updatedFilterType(): void
    {
        $this->resetTable();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.notification-log-table');
    }
}
