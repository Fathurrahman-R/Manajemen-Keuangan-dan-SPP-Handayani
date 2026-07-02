# Design Document: Profil Portal Publik

## Overview

This document describes the design for the public profile/landing page (Profil Portal Publik) for Yayasan Handayani in `frontend-v2`. The page serves as the entry point for visitors before the login page, replicating the exact visual design from the Lovable.ai reference implementation (`portal-reference/handayani-joyful-portal`) using Blade + Alpine.js + Tailwind CSS v4.

**Key Constraints:**
- Public page (no authentication required)
- Standard Laravel routing/controller/Blade — NOT Filament components or layout
- Separate Vite entry points (`public.css`, `public.js`) from Filament admin theme
- Alpine.js for client-side interactivity (mobile nav, scroll animations)
- Blade components in `resources/views/components/public/`

---

## Architecture

### High-Level Structure

```
frontend-v2/
├── app/Http/Controllers/
│   └── PublicPageController.php          # Standard Laravel controller
├── config/
│   └── handayani-public.php              # Site configuration
├── resources/
│   ├── css/
│   │   ├── app.css                       # Existing (Filament/admin)
│   │   ├── filament/admin/theme.css      # Existing (Filament theme)
│   │   └── public.css                    # NEW: Public page entry point
│   ├── js/
│   │   ├── app.js                        # Existing (Filament/admin)
│   │   ├── bootstrap.js                  # Existing
│   │   └── public.js                     # NEW: Public page entry point
│   ├── views/
│   │   ├── layouts/
│   │   │   └── public.blade.php          # NEW: Base layout for public pages
│   │   ├── public/
│   │   │   └── index.blade.php           # NEW: Main public page
│   │   └── components/public/            # NEW: Reusable Blade components
│   │       ├── nav.blade.php
│   │       ├── hero.blade.php
│   │       ├── about.blade.php
│   │       ├── jenjang.blade.php
│   │       ├── spp-cta.blade.php
│   │       ├── kontak.blade.php
│   │       ├── footer.blade.php
│   │       ├── geometric-pattern.blade.php
│   │       └── reveal.blade.php
│   └── images/
│       └── hero-illustration.jpg         # NEW: Hero illustration (copied from reference)
├── routes/
│   └── web.php                           # MODIFIED: Add public route
├── vite.config.js                        # MODIFIED: Add public entry points
└── public/images/
    └── hero-illustration.jpg             # NEW: Copied asset
```

### Request Flow

1. Visitor accesses `/` (root route)
2. `routes/web.php` routes to `PublicPageController@index`
3. Controller returns `view('public.index')`
4. `public.index` extends `layouts.public`
5. Layout includes `@vite(['resources/css/public.css', 'resources/js/public.js'])`
6. Blade components compose the page sections
7. Alpine.js handles mobile nav toggle and IntersectionObserver reveal animations

### Separation from Filament

| Aspect | Filament Admin/Portal | Public Page |
|--------|----------------------|-------------|
| Routing | `Filament::registerPages()` | `routes/web.php` |
| Controller | Page classes | `PublicPageController` |
| Layout | `<x-filament-panels::page>` | `layouts.public.blade.php` |
| Styles | `resources/css/filament/admin/theme.css` | `resources/css/public.css` |
| Scripts | Filament's Alpine + Livewire | `resources/js/public.js` (Alpine only) |
| Auth | Sanctum + middleware | None (public) |

---

## Components and Interfaces

### 1. PublicPageController

```php
// app/Http/Controllers/PublicPageController.php
namespace App\Http\Controllers;

class PublicPageController extends Controller
{
    public function index()
    {
        return view('public.index');
    }
}
```

**Interface:** Single `index()` method returning the public landing page view.

### 2. Config: `handayani-public.php`

```php
// config/handayani-public.php
return [
    'name' => env('HANDAYANI_PUBLIC_NAME', 'Yayasan Lembaga Pendidikan Anak Handayani'),
    'short_name' => env('HANDAYANI_PUBLIC_SHORT_NAME', 'Handayani'),
    'tagline' => env('HANDAYANI_PUBLIC_TAGLINE', 'Membentuk Generasi Berilmu dan Berakhlak'),
    'address' => env('HANDAYANI_PUBLIC_ADDRESS', 'Jl. Pendidikan Islam No. 45, Jakarta Selatan, DKI Jakarta 12345'),
    'phone' => env('HANDAYANI_PUBLIC_PHONE', '(021) 1234-5678'),
    'email' => env('HANDAYANI_PUBLIC_EMAIL', 'info@handayani.sch.id'),
    'whatsapp_number' => env('HANDAYANI_PUBLIC_WHATSAPP', '6281234567890'),
    'spp_portal_url' => env('HANDAYANI_PUBLIC_SPP_PORTAL_URL', '#spp-portal'),
];
```

**Usage in views:** `config('handayani-public.name')`, `config('handayani-public.short_name')`, etc.

### 3. Blade Components (`resources/views/components/public/`)

| Component | Props | Responsibility |
|-----------|-------|----------------|
| `nav` | — | Sticky header, logo, nav links, mobile hamburger, Portal SPP button |
| `hero` | — | Hero section with pattern, badge, H1, tagline, CTAs, stats, illustration |
| `about` | — | About section with Misi/Visi cards, Nilai Institusional list |
| `jenjang` | — | Three education level cards in responsive grid |
| `spp-cta` | — | Gradient CTA section with pattern overlay, buttons, trust badges |
| `kontak` | — | Contact info + WhatsApp button + OpenStreetMap iframe |
| `footer` | — | Logo, tagline, nav links, address, copyright, tagline |
| `geometric-pattern` | `opacity`, `stroke`, `class` | SVG mashrabiya pattern (8-point star tessellation) |
| `reveal` | `delay`, `class`, `as` | IntersectionObserver scroll animation wrapper |

### 4. CSS Theme Variables (`resources/css/public.css`)

```css
@import "tailwindcss";

@theme {
    /* Colors matching reference styles.css */
    --color-background: #ffffff;
    --color-foreground: #0f172a;
    --color-primary: #0d9488;
    --color-primary-foreground: #ffffff;
    --color-accent: #f97316;
    --color-accent-foreground: #ffffff;
    --color-surface: #f8fafc;
    --color-border: #e2e8f0;
    --color-muted: #f1f5f9;
    --color-muted-foreground: #64748b;

    /* Fonts */
    --font-display: "Manrope", ui-sans-serif, system-ui;
    --font-sans: "Inter", ui-sans-serif, system-ui;
}

/* Reveal animation */
.reveal {
    opacity: 0;
    transform: translateY(16px);
    transition: opacity 700ms ease, transform 700ms ease;
}
.reveal-in {
    opacity: 1;
    transform: translateY(0);
}
```

### 5. JavaScript Entry (`resources/js/public.js`)

```javascript
// resources/js/public.js
import '../css/public.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

---

## Component Hierarchy

### Blade Template Structure (Parent → Child)

```
layouts/public.blade.php                    ← Base layout (extends nothing)
└── public/index.blade.php                  ← Main page (extends layouts.public)
    ├── @section('content')
    │   ├── <x-public.nav />                ← Navigation header (Alpine: mobile menu)
    │   ├── <x-public.hero />               ← Hero section (uses geometric-pattern, reveal)
    │   ├── <x-public.about />              ← About section (uses reveal)
    │   │   ├── <x-public.reveal delay="0ms">Misi card</x-public.reveal>
    │   │   ├── <x-public.reveal delay="100ms">Visi card</x-public.reveal>
    │   │   └── <x-public.reveal delay="200ms">Nilai list</x-public.reveal>
    │   ├── <x-public.jenjang />            ← Education levels (uses reveal)
    │   │   ├── <x-public.reveal delay="0ms">KB/PAUD card</x-public.reveal>
    │   │   ├── <x-public.reveal delay="100ms">TK card</x-public.reveal>
    │   │   └── <x-public.reveal delay="200ms">MI card</x-public.reveal>
    │   ├── <x-public.spp-cta />            ← SPP CTA section (uses geometric-pattern, reveal)
    │   ├── <x-public.kontak />             ← Contact section
    │   └── <x-public.footer />             ← Footer
```

### Alpine.js x-data Structures

#### Nav Component (`nav.blade.php`)
```blade
<div x-data="{ 
    open: false, 
    closeMenu() { this.open = false; },
    init() {
        this.$watch('open', v => { if (v) document.body.style.overflow = 'hidden'; else document.body.style.overflow = ''; })
    }
}">
    <!-- Hamburger button -->
    <button @click="open = !open" @keydown.escape="open = false" aria-expanded="open" aria-label="Toggle menu">
        <svg x-show="!open" ...><!-- Menu icon --></svg>
        <svg x-show="open" ...><!-- Close (X) icon --></svg>
    </button>
    
    <!-- Mobile dropdown -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 transform -translate-y-2" 
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         @click.outside="closeMenu()" @keydown.escape.window="closeMenu()">
        <!-- Nav links + Portal SPP button -->
    </div>
</div>
```

#### Reveal Component (`reveal.blade.php`)
```blade
<{{ $as ?? 'div' }} 
    x-data="{ 
        isVisible: false, 
        observer: null,
        init() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.isVisible = true;
                        this.observer.unobserve(this.$el);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
            this.observer.observe(this.$el);
        },
        destroy() {
            if (this.observer) this.observer.disconnect();
        }
    }" 
    :class="['reveal', isVisible ? 'reveal-in' : '']" 
    :style="delay ? 'transition-delay: ' + delay : ''"
    x-init="init()" x-cleanup="destroy()">
    {{ $slot }}
</{{ $as ?? 'div' }}>
```

**Props:** `delay` (string, default "0ms"), `class` (string, default ""), `as` (string, default "div")

#### Geometric Pattern Component (`geometric-pattern.blade.php`)
```blade
<!-- Stateless component - no x-data needed -->
<svg class="{{ $class ?? '' }}" width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
    <defs>
        <pattern id="mashrabiya" patternUnits="userSpaceOnUse" width="100" height="100">
            <!-- 8-point star -->
            <path d="M50 10 L55 35 L80 35 L60 50 L65 75 L50 60 L35 75 L40 50 L20 35 L45 35 Z" 
                  fill="none" stroke="{{ $stroke ?? 'currentColor' }}" stroke-width="1.5" opacity="{{ $opacity ?? '0.04' }}"/>
            <!-- Outer octagon -->
            <path d="M50 5 L75 20 L85 50 L75 80 L50 95 L25 80 L15 50 L25 20 Z"
                  fill="none" stroke="{{ $stroke ?? 'currentColor' }}" stroke-width="1" opacity="{{ $opacity ?? '0.04' }}"/>
            <!-- Inner rotated square -->
            <path d="M50 20 L80 50 L50 80 L20 50 Z"
                  fill="none" stroke="{{ $stroke ?? 'currentColor' }}" stroke-width="1" opacity="{{ $opacity ?? '0.04' }}"/>
        </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#mashrabiya)" />
</svg>
```

**Props:** `opacity` (string, default "0.04"), `stroke` (string, default "currentColor"), `class` (string, default "")

---

## Data Models

No new Eloquent models are required for the public page. All content is static or configuration-driven.

### Configuration Data Shape

```php
// config('handayani-public') returns:
[
    'name' => 'Yayasan Lembaga Pendidikan Anak Handayani',
    'short_name' => 'Handayani',
    'tagline' => 'Membentuk Generasi Berilmu dan Berakhlak',
    'address' => 'Jl. Pendidikan Islam No. 45, Jakarta Selatan, DKI Jakarta 12345',
    'phone' => '(021) 1234-5678',
    'email' => 'info@handayani.sch.id',
    'whatsapp_number' => '6281234567890',
    'spp_portal_url' => '#spp-portal',
]
```

### Static Content (in Blade components)

- **Navigation links**: `['Beranda' => '#beranda', 'Tentang' => '#tentang', 'Jenjang' => '#jenjang', 'SPP' => '#spp', 'Kontak' => '#kontak']`
- **Hero stats**: `[['3', 'Jenjang Terpadu'], ['20+', 'Tahun Berdiri'], ['100%', 'Kurikulum Nasional']]`
- **Education levels**: 3 items with code, name, age, description, programs[]
- **Nilai Institusional**: 3 items with number, title, description
- **SPP trust badges**: 3 items with icon, label, description
- **Contact items**: 3 items with icon, label, value (phone/email as links)
- **Footer links**: 4 items matching nav (minus Beranda)

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, two areas are suitable for property-based testing:

1. **Reveal Component IntersectionObserver Logic** — The core animation trigger logic varies with viewport position, element position, threshold, and rootMargin. Running 100+ iterations with randomized configurations can uncover edge cases in the observer callback logic.

2. **Alpine.js Mobile Nav Toggle State Machine** — The open/close state transitions (click hamburger → open, click link → close, click outside → close, resize to desktop → close) form a state machine that can be verified across all event sequences.

All other acceptance criteria are deterministic rendering/configuration checks (EXAMPLE, SMOKE, EDGE_CASE) best covered by example-based tests.

### Property 1: Reveal IntersectionObserver Invariant

**For any** DOM element wrapped in `<x-public.reveal>`, **for any** valid `IntersectionObserverInit` options (threshold ∈ [0,1], rootMargin ∈ string), **when** the element crosses the intersection threshold from not-intersecting to intersecting, **the** Alpine.js component **SHALL** add the `reveal-in` class exactly once and never remove it.

**Validates:** Requirements 5.7, 6.8, 7.7, 11.3, 14.3

### Property 2: Reveal Animation CSS Contract

**For any** element with class `reveal` that receives the `reveal-in` class, **the** computed styles **SHALL** transition from `opacity: 0; transform: translateY(16px)` to `opacity: 1; transform: translateY(0)` over 700ms with `ease` timing, **and** the transition-delay **SHALL** equal the `delay` prop value in milliseconds.

**Validates:** Requirements 11.4, 11.5, 11.6

### Property 3: Mobile Nav State Machine

**For any** sequence of user interactions on the Nav component (hamburger click, nav link click, viewport resize crossing md breakpoint, outside click), **the** `open` state **SHALL** follow this transition table:

| Current State | Event | Next State |
|---------------|-------|------------|
| closed | hamburger click | open |
| open | hamburger click | closed |
| open | nav link click | closed |
| open | resize to ≥ md | closed |
| open | outside click | closed |
| closed | resize | closed |

**Validates:** Requirements 3.5, 3.6, 3.7

### Property 4: Config-Driven Content Consistency

**For any** valid configuration array returned by `config('handayani-public')`, **for any** Blade component that consumes config values, **the** rendered output **SHALL** contain exactly the config values for: `name`, `short_name`, `tagline`, `address`, `phone`, `email`, `whatsapp_number`, `spp_portal_url` — with no hardcoded fallbacks in the component templates.

**Validates:** Requirements 2.1, 2.2, 2.3, 2.4, 3.2, 3.4, 4.4, 4.5, 4.6, 8.3, 8.5, 9.2, 9.3, 9.5

---

## Error Handling

### 1. Missing Configuration

- **Scenario:** `config/handayani-public.php` missing or incomplete
- **Handling:** Config file provides sensible defaults via `env()` with fallbacks matching reference implementation. No runtime exceptions.
- **Validation:** Run `php artisan config:cache` in CI to catch missing config early.

### 2. Missing Hero Illustration

- **Scenario:** `public/images/hero-illustration.jpg` not found
- **Handling:** Blade `asset()` helper returns valid URL; browser shows broken image. No server error.
- **Mitigation:** Include hero image in repo; add CI check for file existence.

### 3. Alpine.js Load Failure

- **Scenario:** Alpine.js fails to load (CDN blocked, npm build issue)
- **Handling:** Mobile nav defaults to closed (CSS `hidden` on mobile menu). Reveal animations don't trigger (elements stay with `opacity: 0`).
- **Graceful degradation:** Page content still accessible; animations are progressive enhancement.
- **Fix:** Alpine bundled via npm in `public.js` (not CDN) to avoid external dependency.

### 4. Vite Manifest Missing (Production)

- **Scenario:** `@vite` directive can't find manifest
- **Handling:** Laravel throws `Illuminate\Foundation\ViteException` with clear message.
- **Prevention:** `npm run build` in deployment pipeline; verify `public/build/manifest.json` exists.

### 5. OpenStreetMap Iframe Load Failure

- **Scenario:** OSM tile server unreachable
- **Handling:** Iframe shows error placeholder; contact info on left still functional.
- **Mitigation:** `loading="lazy"` defers load; `referrerPolicy="no-referrer-when-downgrade"` for privacy.

---

## Testing Strategy

### Dual Testing Approach

| Test Type | Scope | Tool | Iterations |
|-----------|-------|------|------------|
| **Unit/Example Tests** | Deterministic rendering, config, routing, static content | Pest + Laravel Testing | 1 per case |
| **Property-Based Tests** | Reveal IntersectionObserver, Mobile Nav state machine, Config consistency | Pest + `giorgiosironi/eris` | ≥ 100 per property |

### Test Organization

```
frontend-v2/tests/
├── Feature/
│   ├── PublicPageTest.php           # Route, controller, view rendering
│   ├── PublicConfigTest.php         # Config file, values, env overrides
│   ├── PublicComponentsTest.php     # Each Blade component renders correctly
│   ├── PublicAssetsTest.php         # Vite entry points, manifest, hero image
│   └── PublicAlpineIntegrationTest.php  # Alpine.js directives present
└── Unit/
    ├── Properties/
    │   ├── RevealIntersectionObserverPropertyTest.php  # Property 1, 2
    │   ├── MobileNavStateMachinePropertyTest.php       # Property 3
    │   └── ConfigConsistencyPropertyTest.php           # Property 4
    └── Components/
        ├── GeometricPatternSvgTest.php   # Exact SVG output
        └── RevealCssContractTest.php     # CSS rules verification
```

### Property-Based Test Implementation

**Library:** `giorgiosironi/eris` (already in `composer.json` for frontend-v2)

**Configuration:** Each property test runs minimum 100 iterations.

**Tag Format (in test docblock):**
```php
/**
 * Feature: profil-portal-publik, Property 1: Reveal IntersectionObserver Invariant
 * For any DOM element wrapped in <x-public.reveal>, for any valid IntersectionObserverInit options...
 */
```

**Generators for Property 1 (Reveal IntersectionObserver):**
- `threshold`: `float()->between(0, 1)`
- `rootMargin`: `string()->fromRegex('/^-?\d+px( -?\d+px){0,3}$/')`
- `elementPosition`: `array(int, int)` for top/bottom relative to viewport
- `viewportHeight`: `int()->between(300, 1200)`

**Generators for Property 3 (Mobile Nav State Machine):**
- `eventSequence`: `list()->of(elements: ['hamburger', 'navLink', 'resizeMd', 'resizeLg', 'outsideClick'])`

**Generators for Property 4 (Config Consistency):**
- `configOverrides`: `associativeArray()` with keys subset of config keys, values `string()`

### Example-Based Test Coverage

| Requirement | Test Type | Description |
|-------------|-----------|-------------|
| 1.1–1.5 | Feature | Route `/` → 200, correct controller, view, no auth middleware |
| 2.1–2.4 | Unit | Config file exists, all keys present, matches reference, used in views |
| 3.1–3.8 | Feature/Unit | Nav renders with all links, logo, hamburger, Alpine `x-data`, smooth scroll |
| 4.1–4.9 | Feature/Unit | Hero section structure, pattern, badge, H1, CTAs, stats, illustration |
| 5.1–5.7 | Feature/Unit | About section, Misi/Visi cards, 3 values with numbers, Reveal usage |
| 6.1–6.8 | Feature/Unit | Jenjang grid (1/2/3 cols), 3 levels, hover classes, Reveal with delay |
| 7.1–7.7 | Feature/Unit | SPP CTA gradient, pattern opacity 0.12, buttons, 3 badges, Reveal |
| 8.1–8.7 | Feature/Unit | Contact layout, 3 items with icons/links, WhatsApp URL, OSM iframe |
| 9.1–9.6 | Feature/Unit | Footer gradient border, logo, links, address, copyright year, tagline |
| 10.1–10.5 | Unit | GeometricPattern component props, exact SVG paths |
| 11.1–11.6 | Unit | Reveal component props, CSS rules, delay style |
| 12.1–12.7 | Unit | Vite entry points, CSS variables, fonts, vite.config.js, layout @vite |
| 13.1–13.6 | Feature/Unit | Layout structure, all 9 components, index includes all in order, no @apply |
| 14.1–14.5 | Feature/Unit | Alpine import, Nav x-data, Reveal IntersectionObserver, no Livewire/Filament |
| 15.1–15.4 | Unit | Hero image copied, asset() helper, alt text, container styling |

### CI Integration

```yaml
# .github/workflows/test.yml (add to existing)
- name: Run Public Page Tests
  run: |
    cd frontend-v2
    ./vendor/bin/pest --filter=Public
    ./vendor/bin/pest --filter=Property
```

### Manual QA Checklist

- [ ] Page loads at `/` without authentication
- [ ] All 5 sections visible: Hero, About, Jenjang, SPP CTA, Kontak
- [ ] Mobile hamburger opens/closes menu (test at < 768px)
- [ ] Smooth scroll on nav link clicks
- [ ] Reveal animations trigger on scroll (observe opacity/translateY)
- [ ] Hero illustration displays with gradient backdrop
- [ ] WhatsApp button opens `wa.me` with prefilled message
- [ ] OpenStreetMap iframe loads and is interactive
- [ ] No console errors (Alpine, Vite, network)
- [ ] Responsive at 375px, 768px, 1024px, 1440px
- [ ] Dark mode not required (reference is light-only)

---

## Implementation Notes

### Tailwind v4 Migration Notes

- Use `@import "tailwindcss"` (not `@tailwind base/components/utilities`)
- Define theme via `@theme { --color-*: ...; --font-*: ... }`
- No `@apply` in components — write utilities directly in Blade templates
- Fonts (Manrope, Inter) loaded via `@font-face` in `public.css` or Google Fonts `<link>` in layout

### Alpine.js Integration

- Bundled via npm: `npm install alpinejs` → `import Alpine from 'alpinejs'` in `public.js`
- No CDN fallback — avoids CSP/external dependency issues
- `window.Alpine = Alpine; Alpine.start()` makes it globally available

### Geometric Pattern SVG

Exact port from reference. Three paths in `<defs><pattern>`:
1. 8-point star: `M40 4 L48 24 L68 24 L52 38 L60 60 L40 46 L20 60 L28 38 L12 24 L32 24 Z`
2. Outer octagon: `M40 0 L68 12 L80 40 L68 68 L40 80 L12 68 L0 40 L12 12 Z`
3. Inner rotated square: `M40 16 L64 40 L40 64 L16 40 Z`

### Ponytail Simplifications

- **ponytail:** Hero illustration treated as static asset — no dynamic image optimization (e.g., responsive images, WebP). Upgrade: Add `<picture>` with WebP/AVIF when asset pipeline supports it.
- **ponytail:** OpenStreetMap iframe uses fixed bbox — no dynamic centering on institution coordinates. Upgrade: Generate iframe src from config lat/lng.
- **ponytail:** No CMS for content — all copy hardcoded in Blade components. Upgrade: Move to database-translatable content blocks if marketing team needs editing.

---

## File Checklist (Implementation Order)

1. `config/handayani-public.php`
2. `app/Http/Controllers/PublicPageController.php`
3. `routes/web.php` (add route)
4. `resources/css/public.css`
5. `resources/js/public.js`
6. `vite.config.js` (add entry points)
7. `resources/views/layouts/public.blade.php`
8. `resources/views/components/public/geometric-pattern.blade.php`
9. `resources/views/components/public/reveal.blade.php`
10. `resources/views/components/public/nav.blade.php`
11. `resources/views/components/public/hero.blade.php`
12. `resources/views/components/public/about.blade.php`
13. `resources/views/components/public/jenjang.blade.php`
14. `resources/views/components/public/spp-cta.blade.php`
15. `resources/views/components/public/kontak.blade.php`
16. `resources/views/components/public/footer.blade.php`
17. `resources/views/public/index.blade.php`
18. Copy `hero-illustration.jpg` → `public/images/`
19. `npm install alpinejs`
20. Tests (Pest + Eris property tests)

---

## Dependencies

- **New npm dependency:** `alpinejs` (^3.x)
- **Existing PHP dependency:** `giorgiosironi/eris` (for property tests)
- **No new Composer packages required**