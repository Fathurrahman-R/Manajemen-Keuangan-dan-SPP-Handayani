<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class DataMasterCategory extends Page
{
    protected string $view = 'filament.pages.data-master-category';

    protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $title = 'Data Kategori';

    protected static ?int $navigationSort = 3;
}
