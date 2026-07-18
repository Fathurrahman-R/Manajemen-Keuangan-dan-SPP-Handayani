<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class BranchManagement extends Page
{
    protected string $view = 'filament.pages.branch-management';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Manajemen Cabang';

    protected static ?string $title = 'Manajemen Cabang';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('branch.view');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('branch.view'), 403);
    }
}
