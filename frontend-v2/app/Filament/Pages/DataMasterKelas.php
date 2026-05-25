<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
Use UnitEnum;

class DataMasterKelas extends Page
{
    protected string $view = 'filament.pages.data-master-kelas';

    protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kelas';

    protected static ?string $title = 'Data Kelas';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('view-kelas', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-kelas', $permissions)) {
            abort(403);
        }
    }
}
