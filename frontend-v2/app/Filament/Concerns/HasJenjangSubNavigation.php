<?php

namespace App\Filament\Concerns;

use App\Config\NavigationConfig;
use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Log;

trait HasJenjangSubNavigation
{
    public string $activeJenjang = '';

    /**
     * Get the sub-navigation items for jenjang filtering.
     * Returns NavigationItem instances for each permitted jenjang option.
     */
    public function getSubNavigation(): array
    {
        $visibleJenjang = PermissionHelper::visibleJenjang();
        $items = [];

        foreach (NavigationConfig::JENJANG_OPTIONS as $jenjang) {
            if (!in_array($jenjang, $visibleJenjang)) {
                continue;
            }

            $count = $this->getJenjangRecordCount($jenjang);

            $items[] = NavigationItem::make($jenjang)
                ->label($jenjang)
                ->badge((string) $count)
                ->url($this->getJenjangUrl($jenjang))
                ->isActiveWhen(fn () => $this->activeJenjang === $jenjang)
                ->icon($this->getJenjangIcon($jenjang));
        }

        return $items;
    }

    /**
     * Fetch the record count for a given jenjang from the backend API.
     */
    public function getJenjangRecordCount(string $jenjang): int
    {
        try {
            $endpoint = $this->getJenjangCountEndpoint();
            $response = ApiService::client()->get($endpoint, [
                'jenjang' => $jenjang,
            ]);

            if ($response->successful()) {
                return (int) ($response->json('count') ?? $response->json('total') ?? 0);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch jenjang record count for {$jenjang}: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Mount the page with the given jenjang context.
     * Sets activeJenjang from URL parameter or defaults to first available.
     */
    public function mountWithJenjang(?string $jenjang = null): void
    {
        $visibleJenjang = PermissionHelper::visibleJenjang();

        if ($jenjang && in_array($jenjang, $visibleJenjang)) {
            $this->activeJenjang = $jenjang;
        } elseif (!empty($visibleJenjang)) {
            $this->activeJenjang = $visibleJenjang[0];
        }
    }

    /**
     * Get the URL for a specific jenjang sub-navigation item.
     */
    protected function getJenjangUrl(string $jenjang): string
    {
        $baseUrl = static::getUrl();

        return $baseUrl . '?jenjang=' . $jenjang;
    }

    /**
     * Get an icon for a jenjang option.
     */
    protected function getJenjangIcon(string $jenjang): string
    {
        return match ($jenjang) {
            'KB' => 'heroicon-o-face-smile',
            'TK' => 'heroicon-o-puzzle-piece',
            'MI' => 'heroicon-o-book-open',
            default => 'heroicon-o-academic-cap',
        };
    }

    /**
     * Get the API endpoint for fetching record counts.
     * Override in page classes to customize the endpoint.
     */
    protected function getJenjangCountEndpoint(): string
    {
        return '/siswa/count';
    }

    /**
     * Check if the current page supports jenjang sub-navigation.
     */
    public static function hasJenjangSubNavigation(): bool
    {
        return true;
    }

    /**
     * Boot the trait during page mount.
     * Reads jenjang from the request query parameter.
     */
    public function bootHasJenjangSubNavigation(): void
    {
        $jenjang = request()->query('jenjang');
        $this->mountWithJenjang($jenjang);
    }
}
