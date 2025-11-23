<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class TransaksiPembayaran extends Page
{
    protected string $view = 'filament.pages.transaksi-pembayaran';

    protected static string | UnitEnum | null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static ?string $title = 'Pembayaran';

    protected static ?int $navigationSort = 3;
}
