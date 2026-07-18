<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class TransaksiPembayaran extends Page
{
    protected string $view = 'filament.pages.transaksi-pembayaran';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static ?string $title = 'Pembayaran';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Handled manually in AdminPanelProvider with flat jenjang items
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('pembayaran.view'), 403);
    }
}
