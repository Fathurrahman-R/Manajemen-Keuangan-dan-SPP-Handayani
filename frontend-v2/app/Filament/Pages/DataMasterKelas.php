<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class DataMasterKelas extends Page
{
    protected string $view = 'filament.pages.data-master-kelas';

    protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kelas';

    protected static ?string $title = 'Data Kelas';

    protected static ?int $navigationSort = 3;

    public string $activeJenjang = '';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Handled manually in AdminPanelProvider with flat jenjang items
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-kelas', $permissions)) {
            abort(403);
        }

        $this->activeJenjang = request()->query('jenjang', 'KB');
    }
}
