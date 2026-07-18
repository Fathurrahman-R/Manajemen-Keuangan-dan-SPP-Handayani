<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use UnitEnum;

class DataMasterCategory extends Page
{
    protected string $view = 'filament.pages.data-master-category';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $title = 'Data Kategori';

    protected ?Alignment $headerActionsAlignment = Alignment::End;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionHelper::hasResource('kategori.view');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('kategori.view'), 403);
    }
}
