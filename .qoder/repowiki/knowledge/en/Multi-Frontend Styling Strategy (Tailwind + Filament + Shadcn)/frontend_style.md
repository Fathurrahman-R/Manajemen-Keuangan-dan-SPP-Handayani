This monorepo contains three distinct frontend styling systems, each targeting a different application layer:

**1. React Vite Frontend (`frontend/`)**
- Tailwind CSS v3 with PostCSS and Autoprefixer
- Minimal `tailwind.config.js` using default theme with no custom tokens or plugins
- Global styles via `index.css` importing Tailwind base/components/utilities layers
- Custom component-level CSS in `App.css` (Vite scaffold defaults)
- Uses `react-icons` for iconography; no design system or component library beyond Tailwind utilities

**2. Filament Admin & Portal Panel (`frontend-v2/`)**
- Tailwind CSS v4 with the new `@import 'tailwindcss'` syntax and `@theme` block
- Primary UI built on **Filament v4** admin panel framework, with extensive overrides in `resources/css/filament/admin/theme.css`
- The theme file is the central styling authority: it imports Filament's base theme, registers `@tailwindcss/forms`, and defines a comprehensive design system including:
  - WCAG 2.1 AA contrast compliance documentation and verified ratios
  - Dark mode surfaces (`.card-surface`, `.card-surface-muted`, `.card-surface-stat`) with consistent light/dark variants
  - Status badge system (`.badge-success`, `.badge-warning`, `.badge-danger`, `.badge-info`, `.badge-neutral`) with dark-mode-safe color combinations
  - Typography hierarchy (`.text-heading`, `.text-body`, `.text-muted`) and border utilities (`.border-surface`, `.border-surface-strong`)
  - Form input surfaces (`.input-surface`) with focus ring states matching primary color
  - Table surfaces (`.table-surface`, `.table-header`, `.table-row`, `.table-row-striped`) for Livewire tables
  - Skeleton loading animations (`.skeleton`, `.skeleton-text`, `.skeleton-circle`, `.skeleton-card`) with shimmer keyframes
  - Print styles that strip navigation, force light mode, and optimize kwitansi/receipt output
  - Responsive breakpoints for mobile-first table overflow and stacked form fields
- Also uses Alpine.js for lightweight interactivity and WireUI components
- Blade templates under `resources/views/filament/` and `resources/views/livewire/` are scanned by Tailwind's `@source` directives

**3. Joyful Portal Reference App (`portal-reference/handayani-joyful-portal/`)**
- Standalone TanStack Start app using **Tailwind CSS v4** with CSS variables-based theming
- **shadcn/ui** setup configured via `components.json` (style: "new-york", baseColor: "slate", cssVariables: true)
- Radix UI primitives as the foundation (`@radix-ui/*` packages) with shadcn wrappers
- Centralized design tokens defined as CSS custom properties in `src/styles.css` using OKLCH color space:
  - Brand palette: deep blue primary (`oklch(0.47 0.18 263)`), teal accent (`oklch(0.55 0.12 175)`), neutral grays
  - Semantic tokens: `--background`, `--foreground`, `--primary`, `--accent`, `--destructive`, `--muted`, etc.
  - Typography: Inter for body text, Manrope for display headings
  - Radius scale: `--radius-sm` through `--radius-3xl` derived from base `--radius`
- Animation system via `tw-animate-css` package plus custom reveal utilities (`.reveal`, `.reveal-in`)
- Dark mode variant via `@custom-variant dark (&:is(.dark *))`
- Accessibility: `:focus-visible` outlines using primary color, smooth scrolling, reduced-motion considerations

**Cross-cutting conventions:**
- All apps use Tailwind CSS as the utility-first engine (v3 for legacy React app, v4 for modern apps)
- Color tokens are expressed as CSS custom properties in the reference portal, while the Filament app uses Tailwind classes directly with documented contrast ratios
- No shared CSS between applications — each has its own isolated build pipeline and token set
- Dark mode support is implemented consistently across all three apps