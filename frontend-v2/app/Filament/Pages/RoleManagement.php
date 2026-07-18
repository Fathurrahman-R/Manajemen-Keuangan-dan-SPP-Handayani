<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class RoleManagement extends Page
{
    protected string $view = 'filament.pages.role-management';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Manajemen Role';

    protected static ?string $title = 'Manajemen Role';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('role.view');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('role.view'), 403);
    }
}
