<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class TahunAjaranManagement extends Page
{
    protected string $view = 'filament.pages.tahun-ajaran-management';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Tahun Ajaran';

    protected static ?string $title = 'Tahun Ajaran';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return PermissionHelper::hasResource('tahun-ajaran.view');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('tahun-ajaran.view'), 403);
    }
}
