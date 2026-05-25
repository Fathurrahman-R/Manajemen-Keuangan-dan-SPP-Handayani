<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class TransaksiPengeluaran extends Page
{
    protected string $view = 'filament.pages.transaksi-pengeluaran';

    protected static string | UnitEnum | null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?string $title = 'Pengeluaran';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('view-pengeluaran', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-pengeluaran', $permissions)) {
            abort(403);
        }
    }
}
