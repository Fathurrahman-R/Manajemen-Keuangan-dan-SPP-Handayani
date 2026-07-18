<?php

namespace App\Filament\Portal\Pages;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalRiwayatPembayaranPage extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected string $view = 'filament.portal.pages.riwayat-pembayaran';

    protected static ?string $navigationLabel = 'Riwayat';

    protected static ?string $title = 'Riwayat Pembayaran';

    protected static ?string $slug = 'riwayat-pembayaran';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $roles = session()->get('data.roles', []);
        if (! PermissionHelper::hasResource('portal-access')) {
            abort(403);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
                    $params = [
                        'per_page' => $recordsPerPage,
                        'page' => $page,
                        'include_pending' => true,
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    try {
                        $response = ApiService::client()->get('/pembayaran/siswa', $params);
                        if (! $response->ok()) {
                            return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
                        }

                        $json = $response->json();
                        $items = $json['data'] ?? [];

                        // Hanya di halaman pertama: prepend list pending Midtrans di atas.
                        if ($page === 1 && ! empty($json['pending'])) {
                            $items = array_merge($json['pending'], $items);
                        }

                        $items = collect($items)->mapWithKeys(function ($item) {
                            $key = $item['order_id'] ?? $item['kode_pembayaran'] ?? uniqid();

                            return [$key => $item];
                        });

                        return new LengthAwarePaginator(
                            items: $items,
                            total: $json['meta']['total'] ?? $items->count(),
                            perPage: $recordsPerPage,
                            currentPage: $page,
                        );
                    } catch (\Throwable $e) {
                        return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
                    }
                }
            )
            ->columns([
                TextColumn::make('kode_pembayaran')
                    ->label('Kode')
                    ->searchable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->sortable()
                    ->money(currency: 'Rp.', decimalPlaces: 0),
                TextColumn::make('metode')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online_midtrans' => __('midtrans.badge_online', [], 'id'),
                        default => __('midtrans.badge_offline', [], 'id'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'online_midtrans' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (array $record): string => ($record['is_pending'] ?? false) ? 'Menunggu Pembayaran' : 'Selesai')
                    ->color(fn (array $record): string => ($record['is_pending'] ?? false) ? 'warning' : 'success'),
                TextColumn::make('kode_tagihan.jenis_tagihan.nama')
                    ->label('Jenis Tagihan')
                    ->state(fn (array $record) => $record['kode_tagihan']['jenis_tagihan']['nama']
                        ?? $record['kode_tagihan_relation']['jenis_tagihan']['nama']
                        ?? '-'),
            ])
            ->recordActions([
                Action::make('lihatStatus')
                    ->label('Lihat Status')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn (array $record): bool => (bool) ($record['is_pending'] ?? false))
                    ->url(fn (array $record): string => '/'.config('handayani.portal.path', 'portal').'/status-pembayaran?order_id='.urlencode($record['order_id'] ?? $record['kode_pembayaran'] ?? ''))
                    ->openUrlInNewTab(false),
                Action::make('downloadKwitansi')
                    ->label('Kwitansi')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconButton()
                    ->color('primary')
                    ->visible(fn (array $record): bool => ! ($record['is_pending'] ?? false)
                        && PermissionHelper::hasResource('pembayaran.kwitansi'))
                    ->action(function (array $record) {
                        return $this->downloadKwitansi($record['kode_pembayaran']);
                    }),
            ])
            ->poll('5s')
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum Ada Pembayaran')
            ->emptyStateDescription('Riwayat pembayaran Anda akan muncul di sini.');
    }

    public function downloadKwitansi(string $kodePembayaran): StreamedResponse
    {
        $filename = 'kwitansi-'.$kodePembayaran.'.pdf';

        $response = ApiService::client()
            ->withHeaders(['Accept' => 'application/pdf'])
            ->get('/pembayaran/kwitansi/'.$kodePembayaran);

        if (! $response->ok()) {
            Notification::make()
                ->title('Kwitansi tidak tersedia')
                ->body('File kwitansi tidak dapat diambil dari server.')
                ->danger()
                ->send();

            return response()->streamDownload(fn () => null, $filename);
        }

        Storage::disk('local')->put($filename, $response->body());
        $path = Storage::disk('local')->path($filename);

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
            unlink($path);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
