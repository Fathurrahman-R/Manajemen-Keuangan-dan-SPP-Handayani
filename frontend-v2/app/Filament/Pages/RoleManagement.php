<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class RoleManagement extends Page
{
    protected string $view = 'filament.pages.role-management';

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Manajemen Role';

    protected static ?string $title = 'Manajemen Role';

    // protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('view-roles', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-roles', $permissions)) {
            abort(403);
        }
    }
}
