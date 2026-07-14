<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class UserManagement extends Page
{
    protected string $view = 'filament.pages.user-management';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Manajemen User';

    protected static ?string $title = 'Manajemen User';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('user-management');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('user-management'), 403);
    }
}
