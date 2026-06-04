<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class EmailPopulationPage extends Page
{
    protected string $view = 'filament.pages.email-population';

    protected static ?string $navigationLabel = 'Migrasi Email';

    protected static ?string $title = 'Migrasi Email Akun';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    public static function shouldRegisterNavigation(): bool
    {
        // Only visible to admins
        $roles = session()->get('data.roles', []);
        return in_array('admin', $roles) || in_array('superadmin', $roles);
    }

    public function mount(): void
    {
        $roles = session()->get('data.roles', []);
        if (!in_array('admin', $roles) && !in_array('superadmin', $roles)) {
            abort(403);
        }
    }
}
