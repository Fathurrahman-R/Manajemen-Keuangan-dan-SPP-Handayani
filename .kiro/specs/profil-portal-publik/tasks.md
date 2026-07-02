# Implementation Plan: Profil Portal Publik

## Overview

Convert the Lovable.ai reference implementation (React/TanStack/Tailwind) to a Laravel Blade + Alpine.js + Tailwind v4 public landing page in `frontend-v2/`. The page is served at `/` via standard Laravel routing (not Filament), with separate Vite entry points from the admin panel.

Tasks are ordered by dependency: config → controller/route → Vite entries → layout → shared components → section components → page assembly → assets → tests.

## ⚠️ IMPORTANT: Reference Files

**For ALL UI component tasks (3.x, 4.x, 5.x, 6.x, 7.x, 8.x, 9.x, 10.x, 11.x), you MUST read the reference implementation files in `portal-reference/handayani-joyful-portal/` before writing code.** Key reference files:
- `portal-reference/handayani-joyful-portal/src/App.tsx` — Main page structure and component composition
- `portal-reference/handayani-joyful-portal/src/components/` — Individual React components (Nav, Hero, About, Jenjang, SppCta, Kontak, Footer, GeometricPattern, Reveal)
- `portal-reference/handayani-joyful-portal/src/styles.css` — CSS variables, Tailwind config equivalent, reveal animations
- `portal-reference/handayani-joyful-portal/src/config/site.ts` — Site configuration (matches handayani-public.php)
- `portal-reference/handayani-joyful-portal/src/assets/` — Hero illustration and other assets

Replicate visual design EXACTLY: colors, spacing, typography, hover effects, animations, SVG patterns.

## Tasks

- [x] 1. Set up configuration and controller foundation
  - [x] 1.1 Create `config/handayani-public.php` with site settings matching reference `site.ts`
    - Keys: name, short_name, tagline, address, phone, email, whatsapp_number, spp_portal_url
    - Use `env()` with fallbacks matching reference values exactly
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [x] 1.2 Create `app/Http/Controllers/PublicPageController.php` with `index()` returning `view('public.index')`
    - Standard Laravel controller (not Filament Page)
    - _Requirements: 1.2, 1.3, 1.5_
  
  - [x] 1.3 Register root route `/` in `routes/web.php` → `PublicPageController@index`
    - No auth middleware, no Filament registration
    - _Requirements: 1.1, 1.3, 1.4_

- [ ] 2. Create Vite entry points and public asset pipeline
  - [x] 2.1 Create `resources/css/public.css` with Tailwind v4 `@import "tailwindcss"` and `@theme` block
    - Colors: background, foreground, primary, primary-foreground, accent, accent-foreground, surface, border, muted, muted-foreground
    - Fonts: --font-display (Manrope), --font-sans (Inter)
    - Reveal animation CSS: `.reveal { opacity: 0; transform: translateY(16px); transition: 700ms ease }` and `.reveal-in { opacity: 1; transform: translateY(0) }`
    - _Requirements: 12.1, 12.3, 11.4, 11.5_
  
  - [ ] 2.2 Create `resources/js/public.js` importing `../css/public.css` and Alpine.js
    - `import Alpine from 'alpinejs'; window.Alpine = Alpine; Alpine.start();`
    - _Requirements: 12.2, 12.6, 14.1_
  
  - [ ] 2.3 Update `vite.config.js` to include public entry points in `input` array
    - Add `resources/css/public.css` and `resources/js/public.js`
    - _Requirements: 12.7_
  
  - [x] 2.4 Install Alpine.js via npm
    - `npm install alpinejs` in `frontend-v2/`
    - _Requirements: 14.1_

- [ ] 3. Create base layout and shared utility components
  - [ ] 3.1 Create `resources/views/layouts/public.blade.php` base layout
    - DOCTYPE, html, head with meta tags, `@vite(['resources/css/public.css', 'resources/js/public.js'])`, body, `@yield('content')`
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/App.tsx` (root layout structure)
    - _Requirements: 13.1, 13.2, 13.3, 12.8_
  
  - [ ] 3.2 Create `resources/views/components/public/geometric-pattern.blade.php`
    - Props: `opacity`, `stroke`, `class`
    - Exact SVG mashrabiya pattern from reference (3 paths in `<defs><pattern>`)
    - Width/height 100%, patternUnits="userSpaceOnUse"
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/GeometricPattern.tsx` and `src/styles.css`
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_
  
  - [ ] 3.3 Create `resources/views/components/public/reveal.blade.php`
    - Props: `delay`, `class`, `as` (default: 'div')
    - Alpine.js `x-data` with IntersectionObserver adding `reveal-in` class
    - Applies `transition-delay` from `delay` prop
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/Reveal.tsx` and `src/styles.css` (.reveal, .reveal-in)
    - _Requirements: 11.1, 11.2, 11.3, 11.6, 14.3_

- [ ] 4. Create Navigation Header component
  - [ ] 4.1 Create `resources/views/components/public/nav.blade.php`
    - Sticky `top-0 z-50` header with logo SVG + `config('handayani-public.short_name')`
    - Nav links: Beranda (#beranda), Tentang (#tentang), Jenjang (#jenjang), SPP (#spp), Kontak (#kontak)
    - "Portal SPP" button → `config('handayani-public.spp_portal_url')`
    - Mobile hamburger with `x-data="{ open: false }"` toggling dropdown menu
    - Smooth scroll behavior via `href="#section"` anchors
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/Nav.tsx`
    - **VISUAL SPECS:**
      - Header: `bg-white/80 backdrop-blur-md border-b border-border` (sticky, semi-transparent)
      - Logo: SVG max-h-8 (32px), `text-primary`
      - Short name: `font-display font-bold text-xl text-foreground`
      - Nav links: `text-sm font-medium text-foreground/70 hover:text-primary transition-colors px-3 py-2`
      - Portal SPP button: `btn-primary` (bg-primary text-primary-foreground px-6 py-2.5 rounded-lg font-medium hover:bg-primary/90)
      - Mobile menu: `md:hidden`, dropdown `absolute top-full left-0 right-0 bg-white border-t border-border py-4 shadow-lg`
      - Hamburger icon: 24x24, `stroke-current`, `x-show="!open"` / `x-show="open"` for menu/close icons
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 14.2_

- [ ] 5. Create Hero section component
  - [ ] 5.1 Create `resources/views/components/public/hero.blade.php`
    - Section `id="beranda"` with geometric-pattern background
    - Badge "Yayasan Pendidikan Islam" with accent dot
    - H1: `config('handayani-public.name')` with font-display text-4xl→6xl
    - Tagline + description from config
    - Two CTAs: "Kenali Kami" (scroll to #tentang) and "Portal Pembayaran SPP" (link to spp_portal_url)
    - Three stats: "3 Jenjang Terpadu", "20+ Tahun Berdiri", "100% Kurikulum Nasional"
    - Hero illustration image on right (lg:col-span-5) via `asset('images/hero-illustration.jpg')`
    - Alt text: "Ilustrasi gedung sekolah Handayani"
    - Rounded-3xl container with gradient glow backdrop
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/Hero.tsx`
    - **VISUAL SPECS:**
      - Section: `relative min-h-screen flex items-center pt-20 pb-16 lg:pb-24 overflow-hidden`
      - Pattern bg: `<x-public.geometric-pattern opacity="0.04" stroke="currentColor" class="absolute inset-0" />`
      - Badge: `inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent/10 text-accent text-sm font-medium` with dot `w-2 h-2 rounded-full bg-accent`
      - H1: `font-display font-bold text-4xl sm:text-5xl lg:text-6xl text-foreground leading-tight`
      - Tagline: `text-lg sm:text-xl text-muted-foreground max-w-2xl mt-6`
      - Description: `text-muted-foreground mt-4 max-w-xl`
      - CTAs: `flex flex-col sm:flex-row gap-4 mt-8`
        - Primary: `btn-primary px-8 py-3.5 text-base`
        - Secondary: `btn-outline px-8 py-3.5 text-base` (border-border hover:bg-muted)
      - Stats: `grid grid-cols-3 gap-6 mt-16 pt-8 border-t border-border`
        - Stat number: `font-display font-bold text-3xl sm:text-4xl text-foreground`
        - Stat label: `text-sm text-muted-foreground mt-1`
      - Illustration: `relative lg:col-span-5` with container `relative rounded-3xl overflow-hidden bg-gradient-to-br from-primary/10 to-accent/10 p-2`
        - Image: `rounded-2xl w-full h-auto`
        - Glow: `absolute -inset-4 bg-gradient-to-r from-primary/20 to-accent/20 blur-2xl rounded-3xl -z-10`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 15.1, 15.2, 15.3, 15.4_

- [ ] 6. Create About section (Tentang) component
  - [ ] 6.1 Create `resources/views/components/public/about.blade.php`
    - Section `id="tentang"` with `border-y border-border bg-surface`
    - Label "Tentang Kami" + main heading
    - Two cards: Misi (primary accent) and Visi (accent color)
    - "Nilai Institusional" with three bordered items:
      - 01 Integritas
      - 02 Profesionalisme
      - 03 Keteladanan
    - Each with number, title, description in grid layout
    - Wrapped in `<x-public.reveal>` for scroll animation
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/About.tsx`
    - **VISUAL SPECS:**
      - Section: `py-16 sm:py-24 lg:py-32`
      - Label: `text-sm font-medium text-primary uppercase tracking-wider`
      - Heading: `font-display font-bold text-3xl sm:text-4xl text-foreground mt-2 max-w-2xl`
      - Misi/Visi cards: `grid md:grid-cols-2 gap-6 mt-12`
        - Card: `p-6 rounded-2xl border border-border bg-background`
        - Misi card accent: `border-l-4 border-primary`
        - Visi card accent: `border-l-4 border-accent`
        - Icon: `w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4` / `bg-accent/10 text-accent`
        - Title: `font-display font-semibold text-xl text-foreground`
        - Description: `text-muted-foreground mt-2 leading-relaxed`
      - Nilai Institusional: `mt-16`
        - Label: `text-sm font-medium text-primary uppercase tracking-wider`
        - Heading: `font-display font-bold text-2xl sm:text-3xl text-foreground mt-2`
        - Items: `divide-y divide-border mt-8`
        - Each item: `py-6 flex items-start gap-4`
        - Number: `font-display font-bold text-3xl text-primary/50 w-16 shrink-0`
        - Title: `font-display font-semibold text-lg text-foreground`
        - Description: `text-muted-foreground mt-1`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

- [ ] 7. Create Education Levels (Jenjang) section component
  - [ ] 7.1 Create `resources/views/components/public/jenjang.blade.php`
    - Section `id="jenjang"` with `bg-background`
    - Section label + heading
    - Responsive grid: 1 col mobile, 2 col md, 3 col lg
    - Three cards: KB/PAUD (Usia 2-4), TK (Usia 4-6), MI (Usia 6-12)
    - Each card: code badge, age, name, description, program list with check icons
    - Hover: `-translate-y-1 border-primary/40 shadow-xl`
    - "Info pendaftaran" link scrolling to #kontak
    - Each card wrapped in `<x-public.reveal>` with staggered delay
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/Jenjang.tsx`
    - **VISUAL SPECS:**
      - Section: `py-16 sm:py-24 lg:py-32`
      - Label: `text-sm font-medium text-primary uppercase tracking-wider`
      - Heading: `font-display font-bold text-3xl sm:text-4xl text-foreground mt-2 max-w-2xl`
      - Grid: `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-12`
      - Card: `p-6 rounded-2xl border border-border bg-background hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl transition-all duration-300`
      - Code badge: `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mb-3`
        - KB: `bg-primary/10 text-primary`
        - TK: `bg-accent/10 text-accent`
        - MI: `bg-emerald/10 text-emerald-600`
      - Age: `text-sm text-muted-foreground mb-2`
      - Name: `font-display font-bold text-xl text-foreground mb-2`
      - Description: `text-muted-foreground text-sm mb-4 leading-relaxed`
      - Programs: `space-y-2`
        - Each: `flex items-center gap-2 text-sm text-muted-foreground`
        - Check icon: `w-4 h-4 text-primary shrink-0`
      - Link: `mt-4 text-sm font-medium text-primary hover:text-primary/80 flex items-center gap-1`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8_

- [ ] 8. Create SPP Portal CTA section component
  - [ ] 8.1 Create `resources/views/components/public/spp-cta.blade.php`
    - Section `id="spp"` with gradient background (primary to accent)
    - Geometric pattern overlay with opacity ~0.12
    - Label "Layanan Orang Tua" + heading "Portal Pembayaran SPP"
    - Two buttons: "Masuk Portal SPP" (primary, links to spp_portal_url) and "Butuh bantuan?" (outline, scrolls to #kontak)
    - Three trust badges in frosted glass cards: Terverifikasi, Real-time, Aman SSL
    - Each badge: icon, label, description
    - Wrapped in `<x-public.reveal>`
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/SppCta.tsx`
    - **VISUAL SPECS:**
      - Section: `relative py-16 sm:py-24 lg:py-32 overflow-hidden`
      - Background: `bg-gradient-to-br from-primary via-primary/80 to-accent`
      - Pattern overlay: `<x-public.geometric-pattern opacity="0.12" stroke="currentColor" class="absolute inset-0 text-white/10" />`
      - Container: `relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`
      - Label: `text-sm font-medium text-primary-foreground/80 uppercase tracking-wider`
      - Heading: `font-display font-bold text-3xl sm:text-4xl lg:text-5xl text-primary-foreground mt-2 max-w-2xl`
      - Buttons: `flex flex-col sm:flex-row gap-4 mt-8`
        - Primary: `px-8 py-3 rounded-xl bg-primary-foreground text-primary font-medium hover:bg-primary-foreground/90 transition-colors`
        - Outline: `px-8 py-3 rounded-xl border-2 border-primary-foreground/30 text-primary-foreground font-medium hover:border-primary-foreground hover:bg-primary-foreground/10 transition-all`
      - Badges: `grid grid-cols-1 sm:grid-cols-3 gap-4 mt-12`
        - Card: `backdrop-blur-sm bg-white/10 border border-white/20 rounded-2xl p-6`
        - Icon: `w-10 h-10 rounded-xl bg-white/10 text-primary-foreground flex items-center justify-center mb-3`
        - Label: `font-display font-semibold text-lg text-primary-foreground`
        - Description: `text-sm text-primary-foreground/70 mt-1`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

- [ ] 9. Create Contact (Kontak) section component
  - [ ] 9.1 Create `resources/views/components/public/kontak.blade.php`
    - Section `id="kontak"` with `bg-surface border-t`
    - Left side (lg:col-span-5): label, heading, description
    - Three contact items: Alamat (MapPin), Telepon (Phone, tel: link), Email (Mail, mailto: link)
    - Each item: icon in colored circle, label, value
    - WhatsApp button with MessageCircle icon → `https://wa.me/{whatsapp_number}?text=...`
    - Right side (lg:col-span-7): OpenStreetMap iframe with title "Peta Lokasi Handayani", lazy loading
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/Kontak.tsx`
    - **VISUAL SPECS:**
      - Section: `py-16 sm:py-24 lg:py-32`
      - Grid: `grid lg:grid-cols-12 gap-12 lg:gap-16`
      - Left label: `text-sm font-medium text-primary uppercase tracking-wider`
      - Left heading: `font-display font-bold text-3xl sm:text-4xl text-foreground mt-2 max-w-xl`
      - Left description: `text-muted-foreground mt-4 max-w-xl leading-relaxed`
      - Contact items: `space-y-6 mt-8`
        - Item: `flex items-start gap-4`
        - Icon circle: `w-12 h-12 rounded-xl flex items-center justify-center shrink-0`
          - MapPin: `bg-primary/10 text-primary`
          - Phone: `bg-accent/10 text-accent`
          - Mail: `bg-emerald/10 text-emerald-600`
        - Label: `text-sm font-medium text-foreground`
        - Value: `text-muted-foreground` (phone/email as links with hover:text-primary)
      - WhatsApp button: `mt-8 inline-flex items-center gap-2 px-6 py-3 bg-green-500 text-white rounded-xl font-medium hover:bg-green-600 transition-colors`
        - Icon: `w-5 h-5`
      - Right side map: `relative h-[500px] lg:h-[600px] rounded-2xl overflow-hidden border border-border`
      - Iframe: `w-full h-full border-0` with `loading="lazy"` and `title="Peta Lokasi Handayani"`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7_

- [ ] 10. Create Footer component
  - [ ] 10.1 Create `resources/views/components/public/footer.blade.php`
    - `bg-background` with top gradient border
    - Left: logo + shortName, tagline text
    - Navigation links: Tentang, Jenjang, Portal SPP, Kontak
    - Address + copyright with current year `{{ date('Y') }}`
    - Tagline "Dibangun dengan amanah."
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/components/Footer.tsx`
    - **VISUAL SPECS:**
      - Footer: `bg-background border-t border-gradient-to-r from-primary via-accent to-primary/50 py-12 sm:py-16`
      - Grid: `grid lg:grid-cols-12 gap-8 lg:gap-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`
      - Left (lg:col-span-5):
        - Logo: `flex items-center gap-2 mb-4` — SVG `w-8 h-8` + shortName `font-display font-bold text-xl text-foreground`
        - Tagline: `text-muted-foreground text-sm leading-relaxed max-w-xs`
      - Center links (lg:col-span-4):
        - Heading: `font-display font-semibold text-lg text-foreground mb-4`
        - Links: `space-y-3` — `text-sm text-muted-foreground hover:text-primary transition-colors` (Tentang, Jenjang, Portal SPP, Kontak)
      - Right (lg:col-span-3):
        - Heading: `font-display font-semibold text-lg text-foreground mb-4`
        - Address: `text-sm text-muted-foreground leading-relaxed mb-4`
        - Copyright: `text-sm text-muted-foreground/60` — `© {{ date('Y') }} {{ config('handayani-public.short_name') }}. Hak Cipta Dilindungi.`
        - Tagline: `text-sm text-primary mt-2 font-medium` — "Dibangun dengan amanah."
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

- [ ] 11. Assemble main public page
  - [ ] 11.1 Create `resources/views/public/index.blade.php`
    - Extends `layouts.public`
    - Includes all section components in order: nav, hero, about, jenjang, spp-cta, kontak, footer
    - **REFERENCE:** `portal-reference/handayani-joyful-portal/src/App.tsx` (component composition order)
    - _Requirements: 13.5, 13.6, 1.5_

- [ ] 12. Add hero illustration asset
  - [ ] 12.1 Copy `hero-illustration.jpg` from reference to `frontend-v2/public/images/hero-illustration.jpg`
    - Source: `portal-reference/handayani-joyful-portal/src/assets/hero-illustration.jpg`
    - _Requirements: 15.1, 15.2_

- [ ] 13. Checkpoint — Verify page renders and core functionality works
  - [ ] 13.1 Ensure all tests pass, ask the user if questions arise.

- [ ] 14. Write automated tests (Pest + Eris property-based)
  - [ ]* 14.1 Feature test: Route `/` returns 200, uses correct controller, renders `public.index` view, no auth middleware
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_
  
  - [ ]* 14.2 Unit test: Config file exists, all 8 keys present, values match reference, env overrides work
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [ ]* 14.3 Feature/Unit tests: Each Blade component renders expected markup
    - nav.blade.php (links, logo, hamburger, Alpine x-data)
    - hero.blade.php (structure, stats, illustration, CTAs)
    - about.blade.php (misi/visi cards, 3 values with numbers)
    - jenjang.blade.php (3 cards, responsive grid, hover classes)
    - spp-cta.blade.php (gradient, pattern overlay, 2 buttons, 3 badges)
    - kontak.blade.php (3 contact items, WhatsApp link, OSM iframe)
    - footer.blade.php (logo, links, address, copyright year, tagline)
    - geometric-pattern.blade.php (exact SVG paths, props)
    - reveal.blade.php (Alpine IntersectionObserver, delay style)
    - _Requirements: 3.1-3.8, 4.1-4.9, 5.1-5.7, 6.1-6.8, 7.1-7.7, 8.1-8.7, 9.1-9.6, 10.1-10.5, 11.1-11.6_
  
  - [ ]* 14.4 Unit test: Vite entry points exist, CSS variables defined, fonts declared, vite.config.js includes public entries, layout uses @vite directive
    - _Requirements: 12.1-12.8_
  
  - [ ]* 14.5 Unit test: Alpine.js imported in public.js, Nav uses x-data, Reveal uses IntersectionObserver, no Livewire/Filament components in public views
    - _Requirements: 14.1-14.5_
  
  - [ ]* 14.6 Unit test: Hero illustration copied to public/images, asset() helper used, alt text correct, container styling
    - _Requirements: 15.1-15.4_
  
  - [ ]* 14.7 Property test: Reveal IntersectionObserver invariant (Property 1)
    - **Property 1: Reveal IntersectionObserver Invariant**
    - **Validates: Requirements 11.3, 11.4, 11.5, 11.6, 5.7, 6.8, 7.7**
    - For any element wrapped in `<x-public.reveal>`, for any valid `IntersectionObserverInit` options (threshold 0-1, rootMargin), the element receives `reveal-in`veal-in` class iff it intersects the viewport per the observer config. Runs ≥100 iterations with Eris generators for threshold, rootMargin, element position, viewport height.
  
  - [ ]* 14.8 Property test: Mobile Nav state machine (Property 3)
    - **Property 3: Mobile Nav State Machine**
    - **Validates: Requirements 3.5, 3.6, 3.7**
    - The Nav component's Alpine `x-data="{ open: false }"` state machine transitions correctly for any sequence of events: hamburger click, nav link click, resize to ≥md, resize to <md, outside click. Runs ≥100 iterations with Eris generator for event sequences.
  
  - [ ]* 14.9 Property test: Config consistency (Property 4)
    - **Property 4: Config Consistency**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
    - For any valid `.env` override subset of the 8 config keys, `config('handayani-public')` returns merged values where env overrides take precedence over defaults, and all 8 keys are always present. Runs ≥100 iterations with Eris generator for config override objects.

- [ ] 15. Final checkpoint — All tests pass
  - [ ] 15.1 Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional (tests) and can be skipped for faster MVP delivery
- Each task references specific requirement numbers for traceability
- Property-based tests (14.7, 14.8, 14.9) require `giorgiosironi/eris` (already in composer.json)
- Implementation language: **PHP (Laravel 12)** with **Blade** templates, **Alpine.js** for interactivity, **Tailwind CSS v4** via Vite

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 1, "tasks": ["2.1", "2.2", "2.3", "2.4"] },
    { "id": 2, "tasks": ["3.1", "3.2", "3.3"] },
    { "id": 3, "tasks": ["4.1"] },
    { "id": 4, "tasks": ["5.1"] },
    { "id": 5, "tasks": ["6.1"] },
    { "id": 6, "tasks": ["7.1"] },
    { "id": 7, "tasks": ["8.1"] },
    { "id": 8, "tasks": ["9.1"] },
    { "id": 9, "tasks": ["10.1"] },
    { "id": 10, "tasks": ["11.1", "12.1"] },
    { "id": 11, "tasks": ["13.1"] },
    { "id": 12, "tasks": ["14.1", "14.2", "14.3", "14.4", "14.5", "14.6", "14.7", "14.8", "14.9"] },
    { "id": 13, "tasks": ["15.1"] }
  ]
}
```