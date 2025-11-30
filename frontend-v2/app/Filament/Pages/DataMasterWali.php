<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
Use UnitEnum;

class DataMasterWali extends Page
{
    protected string $view = 'filament.pages.data-master-wali';

    // protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Wali';

    protected static ?string $title = 'Data Wali';

    protected static ?int $navigationSort = 2;
}
