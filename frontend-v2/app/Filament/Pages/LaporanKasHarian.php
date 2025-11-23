<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class LaporanKasHarian extends Page
{
    protected string $view = 'filament.pages.laporan-kas-harian';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Kas Harian';

    protected static ?string $title = 'Kas Harian';

    protected static ?int $navigationSort = 1;
}
