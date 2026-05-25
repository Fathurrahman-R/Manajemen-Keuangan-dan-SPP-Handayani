<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class ManajemenAkunSiswa extends Page
{
    protected string $view = 'filament.pages.manajemen-akun-siswa';

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Manajemen Akun Siswa';

    protected static ?string $title = 'Manajemen Akun Siswa';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array('manage-akun-siswa', $permissions);
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('manage-akun-siswa', $permissions)) {
            abort(403);
        }
    }
}
