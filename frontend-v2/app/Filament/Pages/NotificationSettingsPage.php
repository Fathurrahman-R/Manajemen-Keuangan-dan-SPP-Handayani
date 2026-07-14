<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class NotificationSettingsPage extends Page
{
    protected string $view = 'filament.pages.notification-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Notifikasi';

    protected static ?string $title = 'Pengaturan Notifikasi Email';

    protected static ?string $slug = 'notification-settings';

    protected static ?int $navigationSort = 9;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('notification-setting');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('notification-setting'), 403);
    }
}
