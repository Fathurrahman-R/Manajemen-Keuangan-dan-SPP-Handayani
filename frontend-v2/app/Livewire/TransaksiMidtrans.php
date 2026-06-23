<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\MidtransApi;
use App\Services\MidtransApiException;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class TransaksiMidtrans extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;
    use HandlesApiErrors;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, array $filters = []): array {
                    try {
                        $queryParams = [];

                        // Status filter
                        if (!empty($filters['status']['value'] ?? null)) {
                            $queryParams['status'] = $filters['status']['value'];
                        }

                        // Branch filter
                        if (!empty($filters['branch_id']['value'] ?? null)) {
                            $queryParams['branch_id'] = $filters['branch_id']['value'];
                        }

                        // Date range filters
                        if (!empty($filters['created_from']['created_from'] ?? null)) {
                            $queryParams['created_from'] = $filters['created_from']['created_from'];
                        }
                        if (!empty($filters['created_until']['created_until'] ?? null)) {
                            $queryParams['created_until'] = $filters['created_until']['created_until'];
                        }

                        $response = MidtransApi::adminList($queryParams);

                        $data = collect($response['data'] ?? []);

                        // Client-side search
                        if (filled($search)) {
                            $searchLower = strtolower($search);
                            $data = $data->filter(
                                fn(array $record): bool => str_contains(strtolower($record['order_id'] ?? ''), $searchLower)
                                    || str_contains(strtolower($record['kode_tagihan'] ?? ''), $searchLower)
                                    || str_contains(strtolower($record['nama_siswa'] ?? ''), $searchLower)
                            );
                        }

                        return $data->toArray();
                    } catch (MidtransApiException $e) {
                        $this->notifyUnexpectedError();
                        return [];
                    } catch (ConnectionException $e) {
                        $this->notifyConnectionError();
                        return [];
                    } catch (\Throwable $e) {
                        $this->notifyUnexpectedError();
                        return [];
                    }
                },
            )
            ->columns([
                TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable(),
                TextColumn::make('kode_tagihan')
                    ->label('Kode Tagihan')
                    ->searchable(),
                TextColumn::make('nama_siswa')
                    ->label('Nama Siswa')
                    ->searchable(),
                TextColumn::make('amount_paid')
                    ->label('Jumlah Bayar')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.')),
                TextColumn::make('fee_amount')
                    ->label('Biaya Admin')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.')),
                TextColumn::make('gross_amount')
                    ->label('Total')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.')),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'settlement', 'capture' => 'success',
                        'pending' => 'warning',
                        'deny', 'cancel', 'expire', 'failure' => 'danger',
                        'refund', 'partial_refund' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('payment_type')
                    ->label('Metode Pembayaran')
                    ->formatStateUsing(fn($state) => $state ?? '-'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-'),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'settlement' => 'Settlement',
                        'capture' => 'Capture',
                        'deny' => 'Deny',
                        'cancel' => 'Cancel',
                        'expire' => 'Expire',
                        'failure' => 'Failure',
                        'refund' => 'Refund',
                        'partial_refund' => 'Partial Refund',
                    ]),
                Filter::make('created_from')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                    ]),
                Filter::make('created_until')
                    ->form([
                        DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ]),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(fn(): array => $this->getBranchOptions()),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Tidak Ada Transaksi Midtrans')
            ->emptyStateDescription('Belum ada transaksi pembayaran online.')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->recordUrl(fn(array $record): string => url('transaksi-midtrans/' . $record['order_id']));
    }

    protected function getBranchOptions(): array
    {
        try {
            $response = MidtransApi::adminList(['per_page' => 1]);
            $branches = collect($response['meta']['branches'] ?? []);

            return $branches->mapWithKeys(fn(array $branch): array => [
                $branch['id'] => $branch['location'] ?? $branch['name'] ?? "Cabang #{$branch['id']}",
            ])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function render(): View
    {
        return view('livewire.transaksi-midtrans');
    }
}
