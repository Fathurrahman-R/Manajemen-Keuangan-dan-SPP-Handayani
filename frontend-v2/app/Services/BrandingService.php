<?php

namespace App\Services;

class BrandingService
{
    /**
     * Branding per-cabang belum ada sumber datanya. Kelas ini dulu menembak
     * `GET /app-settings/branding` di backend, tapi route itu tidak pernah
     * dibuat — hasilnya setiap page load membayar satu round-trip HTTP yang
     * pasti 404 plus satu Log::warning, lalu tetap jatuh ke default.
     *
     * Sampai backend menyediakan endpoint-nya (butuh kolom warna & favicon yang
     * belum ada di tabel app_settings), get() mengembalikan default langsung.
     * BrandingConfig sengaja dipertahankan supaya pemanggil di AdminPanelProvider
     * dan PortalPanelProvider tidak perlu berubah saat endpoint itu ada nanti.
     */
    private static ?BrandingConfig $memoized = null;

    /**
     * Get the current branding configuration.
     */
    public static function get(): BrandingConfig
    {
        return static::$memoized ??= BrandingConfig::default();
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
}
