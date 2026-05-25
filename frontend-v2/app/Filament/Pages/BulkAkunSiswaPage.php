<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class BulkAkunSiswaPage extends Page
{
    protected string $view = 'filament.pages.bulk-akun-siswa';

    protected static string | UnitEnum | null $navigationGroup = 'Manajemen Akses';

    protected static ?string $navigationLabel = 'Bulk Akun Siswa';

    protected static ?string $title = 'Pembuatan Akun Siswa (Bulk)';

    protected static ?string $slug = 'bulk-akun-siswa';

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
