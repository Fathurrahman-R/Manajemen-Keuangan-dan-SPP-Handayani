<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class KenaikanKelasPage extends Page
{
    protected string $view = 'filament.pages.kenaikan-kelas';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kenaikan Kelas';

    protected static ?string $title = 'Kenaikan Kelas & Kelulusan';

    protected static ?string $slug = 'kenaikan-kelas';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('kenaikan-kelas');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('kenaikan-kelas'), 403);
    }
}
