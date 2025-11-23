<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
Use UnitEnum;

class DataMasterSiswa extends Page
{
    protected string $view = 'filament.pages.data-master-siswa';

    protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Siswa';

    protected static ?string $title = 'Data Siswa';

    protected static ?int $navigationSort = 1;
}
