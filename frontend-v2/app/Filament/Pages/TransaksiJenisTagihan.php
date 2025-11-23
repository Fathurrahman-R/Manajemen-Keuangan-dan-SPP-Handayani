<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class TransaksiJenisTagihan extends Page
{
    protected string $view = 'filament.pages.transaksi-jenis-tagihan';

    protected static string | UnitEnum | null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Jenis Tagihan';

    protected static ?string $title = 'Jenis Tagihan';

    protected static ?int $navigationSort = 1;
}
