<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class PengeluaranRequestPage extends Page
{
    protected string $view = 'filament.pages.pengeluaran-request';

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?string $title = 'Pengeluaran';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Handled manually in AdminPanelProvider
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', []);
        $hasAccess = in_array('view-pengeluaran', $permissions)
            || in_array('create-pengeluaran-request', $permissions)
            || in_array('approve-pengeluaran', $permissions)
            || in_array('disburse-pengeluaran', $permissions);

        if (!$hasAccess) {
            abort(403);
        }
    }
}
