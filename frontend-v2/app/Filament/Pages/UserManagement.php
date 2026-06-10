<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class UserManagement extends Page
{
    protected string $view = 'filament.pages.user-management';

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Manajemen User';

    protected static ?string $title = 'Manajemen User';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('view-user', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-user', $permissions)) {
            abort(403);
        }
    }
}
