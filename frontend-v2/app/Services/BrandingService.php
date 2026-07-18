<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BrandingService
{
    private const SESSION_KEY = 'data.branding';

    /**
     * Get the current branding configuration.
     * Falls back: session cache → API fetch → default.
     */
    public static function get(): BrandingConfig
    {
        $cached = session()->get(self::SESSION_KEY);

        if ($cached instanceof BrandingConfig) {
            return $cached;
        }

        // If cached as array (session serialization), reconstruct
        if (is_array($cached)) {
            $config = BrandingConfig::fromApiResponse($cached);
            session()->put(self::SESSION_KEY, $config);

            return $config;
        }

        return static::fetchAndCache();
    }

    /**
     * Force refresh branding from the backend API.
     */
    public static function refresh(): void
    {
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
