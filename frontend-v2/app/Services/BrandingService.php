<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BrandingService
{
    private const SESSION_KEY = 'data.branding';

    /**
     * Request-scoped memoization — Admin + Portal panel providers each call
     * get()/logoUrl()/primaryColor()/faviconUrl() multiple times per request
     * (Filament boots both panels regardless of which one matches the URL),
     * so without this a single page load fires several redundant API calls
     * to the backend before the session write from the first call is read
     * back by the others.
     */
    private static ?BrandingConfig $memoized = null;

    /**
     * Get the current branding configuration.
     * Falls back: request memo → session cache → API fetch → default.
     */
    public static function get(): BrandingConfig
    {
        if (static::$memoized instanceof BrandingConfig) {
            return static::$memoized;
        }

        $cached = session()->get(self::SESSION_KEY);

        if ($cached instanceof BrandingConfig) {
            return static::$memoized = $cached;
        }

        // If cached as array (session serialization), reconstruct
        if (is_array($cached)) {
            $config = BrandingConfig::fromApiResponse($cached);
            session()->put(self::SESSION_KEY, $config);

            return static::$memoized = $config;
        }

        return static::$memoized = static::fetchAndCache();
    }

    /**
     * Force refresh branding from the backend API.
     */
    public static function refresh(): void
    {
        static::$memoized = null;
        static::fetchAndCache();
    }

    /**
     * Get the branch logo URL, or null if not configured.
     */
    public static function logoUrl(): ?string
    {
        return static::get()->logoUrl;
    }

    /**
     * Get the branch primary color (hex), or null if not configured.
     */
    public static function primaryColor(): ?string
    {
        return static::get()->primaryColor;
    }

    /**
     * Get the branch favicon URL, or null if not configured.
     */
    public static function faviconUrl(): ?string
    {
        return static::get()->faviconUrl;
    }

    /**
     * Fetch branding from backend API and cache in session.
     * Returns default branding on failure.
     */
    private static function fetchAndCache(): BrandingConfig
    {
        try {
            $response = ApiService::client()->get('/app-settings/branding');

            if ($response->successful()) {
                $data = $response->json('data', $response->json());
                $config = BrandingConfig::fromApiResponse(is_array($data) ? $data : []);
                session()->put(self::SESSION_KEY, $config);

                return $config;
            }
        } catch (\Throwable $e) {
            Log::warning('BrandingService: Failed to fetch branding from API', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to default branding
        $config = BrandingConfig::default();
        session()->put(self::SESSION_KEY, $config);

        return $config;
    }
}
