<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class TransaksiTagihan extends Page
{
    protected string $view = 'filament.pages.transaksi-tagihan';

    protected static string | UnitEnum | null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Tagihan';

    protected static ?string $title = 'Tagihan';

    protected static ?int $navigationSort = 2;
}
