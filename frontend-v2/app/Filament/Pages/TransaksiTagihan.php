<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class TransaksiTagihan extends Page
{
    protected string $view = 'filament.pages.transaksi-tagihan';

    protected static string | UnitEnum | null $navigationGroup = 'Transaksi';

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
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        if (!in_array('view-tagihan', $permissions)) {
            abort(403);
        }

        $this->activeJenjang = request()->query('jenjang', 'KB');
    }
}
