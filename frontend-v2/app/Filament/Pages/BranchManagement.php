<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class BranchManagement extends Page
{
    protected string $view = 'filament.pages.branch-management';

    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Manajemen Cabang';

    protected static ?string $title = 'Manajemen Cabang';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('view-branch', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-branch', $permissions)) {
            abort(403);
        }
    }
}
