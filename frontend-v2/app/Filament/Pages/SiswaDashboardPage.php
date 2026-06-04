<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class SiswaDashboardPage extends Page
{
    protected string $view = 'filament.pages.siswa-dashboard';

    protected static ?string $navigationLabel = 'Dashboard Saya';

    protected static ?string $title = 'Dashboard Saya';

    protected static ?int $navigationSort = -1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public static function shouldRegisterNavigation(): bool
    {
        // Navigation handled manually via AdminPanelProvider
        return false;
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-own-billing', $permissions)) {
            abort(403);
        }
    }
}
