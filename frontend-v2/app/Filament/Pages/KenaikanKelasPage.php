<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class KenaikanKelasPage extends Page
{
    protected string $view = 'filament.pages.kenaikan-kelas';

    protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kenaikan Kelas';

    protected static ?string $title = 'Kenaikan Kelas & Kelulusan';

    protected static ?string $slug = 'kenaikan-kelas';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('manage-kenaikan-kelas', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('manage-kenaikan-kelas', $permissions)) {
            abort(403);
        }
    }
}
