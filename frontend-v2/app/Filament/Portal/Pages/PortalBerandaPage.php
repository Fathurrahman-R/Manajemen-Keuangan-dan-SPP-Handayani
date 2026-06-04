<?php

namespace App\Filament\Portal\Pages;

use BackedEnum;
use Filament\Pages\Page;

class PortalBerandaPage extends Page
{
    protected string $view = 'filament.portal.pages.beranda';

    protected static ?string $navigationLabel = 'Beranda';

    protected static ?string $title = 'Beranda';

    protected static ?string $slug = 'beranda';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Navigation handled manually via PortalPanelProvider
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', []);
        if (!in_array('view-own-billing', $permissions)) {
            abort(403);
        }
    }
}
