<?php

namespace App\Filament\Portal\Pages;

use App\Filament\Widgets\PortalSiswaStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class PortalBerandaPage extends Page
{
    protected string $view = 'filament.portal.pages.beranda';

    protected static ?string $navigationLabel = 'Beranda';

    protected static ?string $title = 'Beranda';

    protected static ?string $slug = 'beranda';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    /**
     * Wali dengan beberapa anak dapat memilih siswa via dropdown ini.
     */
    #[Url(as: 'siswa')]
    public ?int $selectedSiswaId = null;

    public array $childOptions = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Navigation handled manually via PortalPanelProvider
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', []);
        if (!in_array('view-own-billing', $permissions)) {
            abort(403);
        }

        $roles = session()->get('data.roles', []);
        if (in_array('wali', $roles)) {
            $this->childOptions = session()->get('data.children', []);
        }
    }

    public function updatedSelectedSiswaId(): void
    {
        // Trigger re-render so widget & tables receive new selectedSiswaId.
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PortalSiswaStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    /**
     * Pass selectedSiswaId to the stats widget.
     */
    public function getWidgetData(): array
    {
        return [
            'selectedSiswaId' => $this->selectedSiswaId,
        ];
    }
}
