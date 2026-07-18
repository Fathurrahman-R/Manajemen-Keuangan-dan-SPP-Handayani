<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
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
        $hasAccess = PermissionHelper::hasResource('pengeluaran.view')
            || PermissionHelper::hasResource('pengeluaran.create')
            || PermissionHelper::hasResource('pengeluaran.approve')
            || PermissionHelper::hasResource('pengeluaran.disburse');

        abort_if(! $hasAccess, 403);
    }
}
