<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class DetailWali extends Page
{
    public ?int $id;

    protected string $view = 'filament.pages.detail-wali';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'detail-wali/{id}';

    // protected static string | UnitEnum | null $navigationGroup = 'Data Master';

    // protected static ?string $navigationLabel = 'Wali';

    protected static ?string $title = 'Lihat Wali';

    protected static ?int $navigationSort = 2;

    public function mount(int $id): void
    {
        $this->id = $id;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/admin/data-master-wali' => 'Data Wali'
        ];
    }
}
