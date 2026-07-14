<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use Filament\Pages\Page;

class DetailSiswa extends Page
{
    public static function canAccess(): bool
    {
        return PermissionHelper::hasResource('siswa.view');
    }
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
            url('/data-master-siswa?jenjang='.$this->jenjang) => 'Data Siswa - '.$this->jenjang,
            '' => 'Detail Siswa',
        ];
    }
}
