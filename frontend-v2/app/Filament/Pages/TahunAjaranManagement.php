<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class TahunAjaranManagement extends Page
{
    protected string $view = 'filament.pages.tahun-ajaran-management';

    protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Tahun Ajaran';

    protected static ?string $title = 'Tahun Ajaran';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        $role = session()->get('data.role', session()->get('data')['role'] ?? '');

        return in_array('view-tahun-ajaran', $permissions) || $role === 'admin';
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', []);
        $role = session()->get('data.role', '');
        if (!in_array('view-tahun-ajaran', $permissions) && $role !== 'admin') {
            abort(403);
        }
    }
}
