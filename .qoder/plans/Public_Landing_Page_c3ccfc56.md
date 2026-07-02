# Public Landing Page Implementation Plan

## Goal
Convert the `portal-reference/handayani-joyful-portal` React/TanStack reference into a standard Laravel Blade + Tailwind CSS v4 + Alpine.js public landing page served at `/` in `frontend-v2`. The implementation must stay inside `frontend-v2`; `portal-reference/` is read-only reference material and `backend/` must not be modified.

## Key Findings from Exploration
- `frontend-v2/resources/views/layouts/public.blade.php` already exists and is wired to `@vite(['resources/css/public.css', 'resources/js/public.js'])`; it expects snake_case config keys (`handayani-public.short_name`, `handayani-public.name`).
- `frontend-v2/resources/views/components/public/geometric-pattern.blade.php` already exists and matches the reference SVG pattern.
- `frontend-v2/package.json` already includes `alpinejs` and Tailwind v4 tooling.
- `frontend-v2/routes/web.php` has no root `/` route yet.
- `frontend-v2/vite.config.js` has no public asset entries yet.
- The spec `.kiro/specs/profil-portal-publik/tasks.md` marks several tasks `[x]` but the actual files do not exist, so the plan treats all implementation steps as pending.

---

## Task Breakdown and Dependencies

### Task 1 — Public site configuration
- **File:** `frontend-v2/config/handayani-public.php` (new)
- Define snake_case keys to match the existing layout and `design.md`: `name`, `short_name`, `tagline`, `address`, `phone`, `email`, `whatsapp_number`, `spp_portal_url`.
- Use `env()` with defaults copied from `portal-reference/handayani-joyful-portal/src/config/site.ts`.
- **Dependencies:** none.

### Task 2 — Public asset entry points
- **File:** `frontend-v2/resources/css/public.css` (new)
  - `@import "tailwindcss";`
  - `@source` directives scoped to public layout/views/components only.
  - `@theme` block with brand tokens (background, foreground, primary, accent, surface, border, muted, muted-foreground) and fonts (`--font-display: Manrope`, `--font-sans: Inter`).
  - `.reveal` and `.reveal-in` utility classes per the spec animation contract.
- **File:** `frontend-v2/resources/js/public.js` (new)
  ```js
  import '../css/public.css';
  import Alpine from 'alpinejs';

  window.Alpine = Alpine;
  Alpine.start();
  ```
- **Dependencies:** none.

### Task 3 — Wire public assets into Vite
- **File:** `frontend-v2/vite.config.js`
- Add `resources/css/public.css` and `resources/js/public.js` to the `laravel` plugin `input` array, preserving existing admin/Filament entries.
- **Dependencies:** Task 2.

### Task 4 — Shared reveal component
- **File:** `frontend-v2/resources/views/components/public/reveal.blade.php` (new)
- Props: `delay` (default `0ms`), `as` (default `div`), `class` (default `''`).
- Use Alpine `x-data` with a single `IntersectionObserver` that sets `isVisible = true` and unobserves after the first intersection, and disconnects on cleanup.
- Apply the `.reveal` class by default and `.reveal-in` when visible; set `transition-delay` from the `delay` prop.
- **Dependencies:** Task 2.

### Task 5 — Section Blade components
Create the following under `frontend-v2/resources/views/components/public/`, mirroring the reference section components (`Nav.tsx`, `Hero.tsx`, `About.tsx`, `Jenjang.tsx`, `SppCta.tsx`, `Kontak.tsx`, `Footer.tsx`):
- `nav.blade.php` — sticky header, desktop/mobile nav, logo SVG, "Portal SPP" button, Alpine mobile toggle.
- `hero.blade.php` — `id="beranda"`, geometric background, badge, H1 from config, tagline, CTAs, stats, hero illustration.
- `about.blade.php` — `id="tentang"`, mission/vision cards, institutional values list, reveal wrappers.
- `jenjang.blade.php` — `id="jenjang"`, three education-level cards, hover effects, reveal wrappers with staggered delay.
- `spp-cta.blade.php` — `id="spp"`, gradient background, geometric overlay, CTA buttons, trust badges.
- `kontak.blade.php` — `id="kontak"`, contact details from config, WhatsApp link, lazy-loaded OSM iframe.
- `footer.blade.php` — logo, tagline, nav links, address, copyright, "Dibangun dengan amanah."
- **Dependencies:** Task 1 (config keys), Task 4 (`x-public.reveal`), existing `x-public.geometric-pattern`.

### Task 6 — Assemble public page view
- **File:** `frontend-v2/resources/views/public/index.blade.php` (new)
- Extend `layouts.public` and compose the section components in order:
  ```blade
  @extends('layouts.public')

  @section('content')
      <x-public.nav />
      <x-public.hero />
      <x-public.about />
      <x-public.jenjang />
      <x-public.spp-cta />
      <x-public.kontak />
      <x-public.footer />
  @endsection
  ```
- **Dependencies:** Task 5.

### Task 7 — Hero illustration asset
- **Source:** `portal-reference/handayani-joyful-portal/src/assets/hero-illustration.jpg`
- **Destination:** `frontend-v2/public/images/hero-illustration.jpg`
- Reference it in `hero.blade.php` via `asset('images/hero-illustration.jpg')` with alt text `"Ilustrasi gedung sekolah Handayani"`.
- **Dependencies:** Task 5 (hero component).

### Task 8 — Controller and route
- **File:** `frontend-v2/app/Http/Controllers/PublicPageController.php` (new)
  ```php
  <?php

  namespace App\Http\Controllers;

  class PublicPageController extends Controller
  {
      public function index()
      {
          return view('public.index');
      }
  }
  ```
- **File:** `frontend-v2/routes/web.php`
  - Add at the top, with no auth middleware:
    ```php
    use App\Http\Controllers\PublicPageController;

    Route::get('/', [PublicPageController::class, 'index'])->name('public.index');
    ```
- **Dependencies:** Task 6 (view exists).

### Task 9 — Verification and tests
- Run `npm run build` in `frontend-v2` to confirm Vite can build the new public entries.
- Add `frontend-v2/tests/Feature/PublicPageTest.php`:
  - Assert `GET /` returns 200.
  - Assert it uses `PublicPageController@index`.
  - Assert the rendered HTML contains `<section id="beranda">` and the configured site name.
- Add `frontend-v2/tests/Unit/PublicConfigTest.php`:
  - Assert `config('handayani-public')` contains all expected keys with defaults matching the reference `SITE` config.
- **Dependencies:** Tasks 1–8.

---

## Code Patterns and Conventions
- **Blade-only public surface:** No Filament components, no Livewire components, no admin `app.css`/`app.js` on the public page.
- **Config-driven content:** All institutional text and URLs must come from `config('handayani-public.*')`; no hard-coded contact details.
- **Tailwind v4:** Use `@import "tailwindcss"`, `@theme` tokens, and scoped `@source` paths. Avoid `@apply` per the spec.
- **Alpine.js sprinkles:** Use `x-data` only for the mobile nav toggle and reveal animations. Do not introduce global state.
- **Inline SVGs:** Use inline SVGs for logo and small icons to avoid extra icon-library dependencies.
- **Section anchors:** `beranda`, `tentang`, `jenjang`, `spp`, `kontak`.

---

## Risk Mitigations
1. **Spec vs. code mismatch** — `tasks.md` marks some items complete while files are missing. Treat every implementation step as pending and verify file existence.
2. **Config key naming conflict** — `requirements.md` uses camelCase but the existing layout and `design.md` use snake_case. Use snake_case consistently to avoid editing the existing layout.
3. **Vite build failure** — Create `public.css` and `public.js` before editing `vite.config.js`; run `npm run build` immediately after wiring the inputs.
4. **Filament/Livewire asset leakage** — Keep `public.js` free of imports from `app.js`, Livewire, or Filament. Inspect the rendered `<head>` to confirm only `public.css`/`public.js` load on `/`.
5. **Alpine not initializing** — Verify `window.Alpine` is defined and mobile menu toggles after build.
6. **Visual drift from reference** — Copy Tailwind classes directly from the React section components; compare rendered output side-by-side with the reference portal.
7. **Route conflict** — Only add `Route::get('/')`; leave existing `/forgot-password`, `/reset-password`, and `/logout` routes untouched. Verify `/login` still reaches Filament.
8. **Missing hero image** — Copy the illustration before deployment; optionally assert `public_path('images/hero-illustration.jpg')` exists in tests.
9. **Accessibility** — Preserve `aria-label`, `aria-expanded`, semantic headings, and `section[id]` scroll-margin from the reference.

---

## Rejected Alternatives
1. **Reuse `resources/css/app.css` and `resources/js/app.js`** — Rejected. The admin entries pull in Filament and Livewire assets, which violates the spec’s requirement for a lean, isolated public bundle and would bloat the landing page.
2. **Implement sections as Livewire components** — Rejected. The spec explicitly forbids Livewire on the public page; the required interactivity (nav toggle, reveal) is trivial with Alpine.js.
3. **Build the public page in `backend/` or `portal-reference/`** — Rejected. `frontend-v2` is the active frontend application, and `portal-reference/` is read-only UI reference material.
4. **Create a Filament page for the landing page** — Rejected. The spec requires a standard Laravel route/controller/Blade view, not a Filament panel page, and the page must not require authentication.

---

## Success Criteria
- `GET /` in `frontend-v2` returns a rendered public landing page with all seven sections.
- The page loads only `public.css` and `public.js`; no Filament or Livewire scripts/styles leak in.
- Mobile navigation toggles, anchor links scroll smoothly, and reveal animations fire on scroll.
- All institutional content comes from `config/handayani-public.php` and matches the reference `SITE` defaults.
- `npm run build` completes without errors and the Pest tests pass.
