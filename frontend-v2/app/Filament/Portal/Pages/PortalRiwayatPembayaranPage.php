<?php

namespace App\Filament\Portal\Pages;

use App\Services\ApiService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class PortalRiwayatPembayaranPage extends Page implements HasTable
{
    use InteractsWithTable;

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
        if (!in_array('siswa', $roles) && !in_array('wali', $roles)) {
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
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    try {
                        $response = ApiService::client()
                            ->get('/pembayaran', $params)
                            ->collect();

                        return new LengthAwarePaginator(
                            items: $response['data'] ?? [],
                            total: $response['meta']['total'] ?? 0,
                            perPage: $recordsPerPage,
                            currentPage: $page,
                        );
                    } catch (\Throwable $e) {
                        return new LengthAwarePaginator(
                            items: [],
                            total: 0,
                            perPage: $recordsPerPage,
                            currentPage: $page,
                        );
                    }
                }
            )
            ->columns([
                TextColumn::make('kode_pembayaran')
                    ->label('Kode Pembayaran')
                    ->searchable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->sortable()
                    ->money(currency: 'Rp.', decimalPlaces: 0),
                TextColumn::make('metode')
                    ->label('Metode'),
                TextColumn::make('kode_tagihan.jenis_tagihan.nama')
                    ->label('Jenis Tagihan'),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum Ada Pembayaran')
            ->emptyStateDescription('Riwayat pembayaran Anda akan muncul di sini.');
    }
}
