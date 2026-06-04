<?php

namespace App\Filament\Portal\Pages;

use BackedEnum;
use Filament\Pages\Page;

class PortalTagihanPage extends Page
{
    protected string $view = 'filament.portal.pages.tagihan';

    protected static ?string $navigationLabel = 'Tagihan';

    protected static ?string $title = 'Tagihan Saya';

    protected static ?string $slug = 'tagihan';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $roles = session()->get('data.roles', []);
        if (!in_array('siswa', $roles) && !in_array('wali', $roles)) {
            abort(403);
        }
    }
}
