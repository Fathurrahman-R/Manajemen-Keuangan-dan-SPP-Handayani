<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;

class TransaksiMidtransPage extends Page
{
    protected string $view = 'filament.pages.transaksi-midtrans';

    protected static ?string $navigationLabel = 'Transaksi Midtrans';

    protected static ?string $title = 'Transaksi Midtrans';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'transaksi-midtrans';

    public static function canAccess(): bool
    {
        if (! config('handayani.features.midtrans_enabled')) {
            return false;
        }

        return PermissionHelper::hasResource('midtrans.admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! config('handayani.features.midtrans_enabled')) {
            return false;
        }

        return static::canAccess();
    }

    public function mount(): void
    {
        if (! config('handayani.features.midtrans_enabled')) {
            abort(404);
        }

        abort_if(! PermissionHelper::hasResource('midtrans.admin'), 403);
    }
}
