<?php

namespace App\Services;

class BrandingConfig
{
    public function __construct(
        public ?string $logoUrl = null,
        public ?string $primaryColor = null,
        public ?string $faviconUrl = null,
        public string $branchName = 'Handayani',
        public ?string $logoBase64 = null,
    ) {}

    /**
     * Create a BrandingConfig from a backend API response array.
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            logoUrl: $data['logo_url'] ?? null,
            primaryColor: $data['primary_color'] ?? null,
            faviconUrl: $data['favicon_url'] ?? null,
            branchName: $data['branch_name'] ?? 'Handayani',
            logoBase64: $data['logo_base64'] ?? null,
        );
    }

    /**
     * Return default branding (no custom branding configured).
     */
    public static function default(): self
    {
        return new self;
    }

    /**
     * Check if any custom branding is configured.
     */
    public function hasBranding(): bool
    {
        return $this->logoUrl !== null
            || $this->primaryColor !== null
            || $this->faviconUrl !== null;
    }

    /**
     * Convert hex primary color to RGB string for Filament CSS custom properties.
     * Example: '#1e40af' → '30, 64, 175'
     */
    public function primaryColorRgb(): ?string
    {
        if ($this->primaryColor === null) {
            return null;
        }

        $hex = ltrim($this->primaryColor, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return null;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }
}
