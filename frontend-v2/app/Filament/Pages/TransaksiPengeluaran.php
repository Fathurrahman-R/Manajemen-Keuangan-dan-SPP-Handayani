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
}
