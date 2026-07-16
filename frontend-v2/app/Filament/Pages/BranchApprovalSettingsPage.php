<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class BranchApprovalSettingsPage extends Page
{
    protected string $view = 'filament.pages.branch-approval-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Approval';

    protected static ?string $title = 'Pengaturan Approval Otomatis';

    protected static ?string $slug = 'branch-approval-settings';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('auto-approve.view');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('auto-approve.view'), 403);
    }
}
