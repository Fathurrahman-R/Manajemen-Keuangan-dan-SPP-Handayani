<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;

class TransaksiMidtransDetailPage extends Page
{
    protected string $view = 'filament.pages.transaksi-midtrans-detail';

    protected static ?string $title = 'Detail Transaksi Midtrans';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'transaksi-midtrans/{orderId}';

    public string $orderId;

    public static function canAccess(): bool
    {
        if (! config('handayani.features.midtrans_enabled')) {
            return false;
        }

        return PermissionHelper::hasResource('midtrans.admin');
    }

    public function mount(string $orderId): void
    {
        if (! config('handayani.features.midtrans_enabled')) {
            abort(404);
        }

        abort_if(! PermissionHelper::hasResource('midtrans.admin'), 403);

        $this->orderId = $orderId;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('transaksi-midtrans') => 'Transaksi Midtrans',
            '' => 'Detail - '.$this->orderId,
        ];
    }
}
