<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DetailWali extends Page
{
    public $id;

    protected string $view = 'filament.pages.detail-wali';

    protected static ?string $title = 'Detail Wali';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'detail-wali/{id}';

    public function mount($id): void
    {
        $this->id = $id;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/admin/data-master-wali' => 'Data Wali',
        ];
    }
}
