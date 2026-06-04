<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class DataMasterSiswa extends Page
{
    protected string $view = 'filament.pages.data-master-siswa';

    protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Siswa';

    protected static ?string $title = 'Data Siswa';

    protected static ?int $navigationSort = 1;

    public string $activeJenjang = '';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Handled manually in AdminPanelProvider with flat jenjang items
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-siswa', $permissions)) {
            abort(403);
        }

        // Read jenjang from query parameter
        $this->activeJenjang = request()->query('jenjang', 'KB');
    }
}
