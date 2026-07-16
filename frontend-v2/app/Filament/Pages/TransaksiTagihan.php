<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use UnitEnum;

class TransaksiTagihan extends Page
{
    protected string $view = 'filament.pages.transaksi-tagihan';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Tagihan';

    protected static ?string $title = 'Tagihan';

    protected static ?int $navigationSort = 2;

    public string $activeJenjang = '';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Handled manually in AdminPanelProvider with flat jenjang items
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('tagihan.view'), 403);

        $this->activeJenjang = request()->query('jenjang', 'KB');
    }

    public function getTitle(): string
    {
        return 'Tagihan - '.($this->activeJenjang ?: 'Semua');
    }
}
