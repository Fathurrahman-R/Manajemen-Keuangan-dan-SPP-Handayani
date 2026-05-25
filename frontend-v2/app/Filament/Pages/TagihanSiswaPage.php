<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TagihanSiswaPage extends Page
{
    protected string $view = 'filament.pages.tagihan-siswa';

    protected static ?string $navigationLabel = 'Tagihan Saya';

    protected static ?string $title = 'Tagihan Saya';

    protected static ?string $slug = 'tagihan-siswa';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $roles = session()->get('data.roles', []);
        return in_array('siswa', $roles);
    }

    public function mount(): void
    {
        $roles = session()->get('data.roles', []);
        if (!in_array('siswa', $roles)) {
            abort(403);
        }
    }
}
