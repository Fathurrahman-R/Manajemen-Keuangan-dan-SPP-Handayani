<?php

namespace App\Filament\Pages;

use App\Livewire\Concerns\HasPeriodFilter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;

class DashboardPage extends Page
{
    use HasPeriodFilter;

    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $permissions = session()->get('data.permissions', []);
        if (!in_array('view-dashboard', $permissions)) {
            abort(403);
        }

        $this->mountHasPeriodFilter();
    }

    public function updatedSelectedTahunAjaranId($value): void
    {
        session(['selected_tahun_ajaran_id' => $value ? (int) $value : null]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn() => null),
        ];
    }
}
