<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class LaporanRekapBulanan extends Page
{
    protected string $view = 'filament.pages.laporan-rekap-bulanan';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Rekap Bulanan';

    protected static ?string $title = 'Rekap Bulanan';

    protected static ?int $navigationSort = 3;
}
