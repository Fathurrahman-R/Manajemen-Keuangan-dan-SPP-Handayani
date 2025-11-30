<?php

namespace App\Filament\Pages;

use UnitEnum;
use Filament\Pages\Page;

class DetailSiswa extends Page
{
    public $id;
    public $jenjang;

    protected string $view = 'filament.pages.detail-siswa';

    protected static ?string $title = 'Lihat Siswa';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'detail-siswa/{jenjang}/{id}';

    public function mount($jenjang, $id): void
    {
        $this->jenjang = $jenjang;
        $this->id = $id;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/admin/data-master-siswa' => 'Data Siswa'
        ];
    }
}
