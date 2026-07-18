<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class NotificationLogPage extends Page
{
    protected string $view = 'filament.pages.notification-log';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Log Notifikasi';

    protected static ?string $title = 'Log Notifikasi Email';

    protected static ?string $slug = 'notification-log';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('notification-logs.view');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('notification-logs.view'), 403);
    }
}
