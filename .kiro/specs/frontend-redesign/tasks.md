# Implementation Plan: Frontend Redesign

## Overview

Implementasi redesain frontend aplikasi Handayani menggunakan Filament v4 + Laravel 12. **Urutan diprioritaskan berdasarkan dampak UX terbesar** mengingat keterbatasan kredit. Task dengan dampak langsung terhadap pengalaman pengguna diutamakan; task teknis (performance tuning, accessibility polish, property tests) diletakkan di akhir sebagai optional.

## Tasks

- [x] 1. Set up shared services (minimal foundation)
  - [x] 1.1 Create PermissionHelper
    - Create `app/Helpers/PermissionHelper.php` with `hasAnyInGroup()`, `canViewJenjang()`, `visibleJenjang()`, `has()` methods
    - Implement group-level permission aggregation (hide group if no items accessible)
    - Implement jenjang-level permission filtering
    - _Requirements: 1.8, 2.7_

  - [x] 1.2 Create NavigationConfig value object
    - Create `app/Config/NavigationConfig.php` with GROUPS, JENJANG_PAGES, and JENJANG_OPTIONS constants
    - Define 4 groups: Akademik (academic-cap icon), Keuangan (banknotes icon), Laporan (chart-bar icon), Pengaturan (cog-6-tooth icon)
    - Define jenjang-aware pages: siswa, kelas, tagihan, pembayaran, kenaikan-kelas
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.2_

  - [x] 1.3 Create BrandingService with API integration and caching
    - Create `app/Services/BrandingService.php` with `get()`, `refresh()`, `logoUrl()`, `primaryColor()`, `faviconUrl()` methods
    - Create `app/Services/BrandingConfig.php` value object with `fromApiResponse()`, `default()`, `hasBranding()`, `primaryColorRgb()` methods
    - Fetch branding from backend API endpoint, cache in session
    - Implement fallback: cached session → default Filament branding
    - _Requirements: 9.1, 9.2, 9.3, 9.7_

  - [x] 1.4 Create DarkModeManager service
    - Create `app/Services/DarkModeManager.php` with `getPreference()`, `setPreference()`, `resolvedTheme()` methods
    - Store preference in session key `data.theme_preference`
    - Persist preference to backend via `PUT /user/preferences`
    - Handle save failure gracefully (apply locally, retry later)
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 2. Navigation reorganization + sub-navigation (HIGH IMPACT)
  - [x] 2.1 Configure AdminPanelProvider navigation groups
    - Modify `app/Providers/Filament/AdminPanelProvider.php` to use `NavigationBuilder`
    - Register 4 navigation groups with icons and labels
    - Place menu items into correct groups per NavigationConfig
    - Add breadcrumb configuration for all pages
    - Wire PermissionHelper to hide groups with no accessible items
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8_

  - [x] 2.2 Create HasJenjangSubNavigation trait
    - Create `app/Filament/Concerns/HasJenjangSubNavigation.php`
    - Implement `getSubNavigation()` returning jenjang options (KB, TK, MI) as sub-menu items
    - Implement `getJenjangRecordCount()` fetching count from API
    - Implement `mountWithJenjang()` to set active jenjang from URL parameter
    - Default to first available jenjang when none selected
    - Highlight active jenjang in sidebar
    - Respect user permissions via PermissionHelper
    - _Requirements: 2.1, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8_

  - [x] 2.3 Apply HasJenjangSubNavigation to jenjang-aware pages
    - Add trait to Siswa, Kelas, Tagihan, Pembayaran, and Kenaikan Kelas page classes
    - Update page routes to accept optional `{jenjang}` parameter
    - Pass jenjang context to Livewire table components for data filtering
    - Ensure pages without jenjang data render as single menu items
    - _Requirements: 2.2, 2.3, 2.6, 2.8_

- [x] 3. Dark mode + branch branding (HIGH IMPACT)
  - [x] 3.1 Configure Filament dark mode with three-way toggle
    - Enable dark mode in AdminPanelProvider with `darkMode(true)`
    - Create Alpine.js component for theme toggle (light/dark/system) in user menu
    - Wire toggle to DarkModeManager for persistence
    - Implement system preference detection via `prefers-color-scheme` media query
    - Apply theme without page reload using Alpine reactive state
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 3.2 Implement dark mode color adjustments and contrast compliance
    - Create custom Tailwind CSS theme extending Filament defaults for dark mode
    - Ensure all component surfaces (backgrounds, text, borders, interactive elements) have dark variants
    - Verify WCAG 2.1 AA contrast ratios: 4.5:1 normal text, 3:1 large text in both modes
    - _Requirements: 4.5, 4.6_

  - [x] 3.3 Integrate branch branding into panel theming
    - Load BrandingService at panel boot in AdminPanelProvider
    - Apply custom primary color as CSS custom property `--primary` (RGB format)
    - Display branch logo in sidebar header (fallback to text brand name)
    - Configure favicon per branch
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.7_

  - [x] 3.4 Fix dark mode on custom Blade views
    - Add `dark:` classes to tagihan-card-view.blade.php (card containers, text, borders, badges, buttons)
    - Add `dark:` classes to tagihan-siswa.blade.php (summary cards, list items, badges, empty states, sibling selector)
    - _Requirements: 14.1, 14.2, 14.6_

- [x] 4. Component migration to Filament native (HIGH IMPACT — simplifies UI + enables dark mode)
  - [x] 4.1 Migrate BulkAkunSiswa to Filament Table
    - Replace custom HTML table in `ManajemenAkunSiswaPage` with Filament `InteractsWithTable`
    - Add TextColumn for nama, email, kelas, jenjang with `->sortable()->searchable()`
    - Add IconColumn for is_active (boolean)
    - Add SelectFilter for jenjang (KB, TK, MI) and kelas
    - Add BulkAction for `toggleActive` and `resetPassword` (with confirmation)
    - Implement server-side pagination
    - Remove old `bulk-akun-siswa.blade.php` custom table view
    - Preserve existing permission checks and API data flow
    - _Requirements: 12.1, 12.6, 13.4, 14.3_

  - [x] 4.2 Migrate DetailWali to Filament Infolist
    - Replace custom HTML display with Filament Infolist component
    - Create Section "Data Wali" with Grid(2): nama, hubungan, telepon, email, alamat (columnSpanFull)
    - Create Section "Anak yang Terdaftar" with RepeatableEntry: nama, kelas, jenjang (columns: 3)
    - Follow existing `DetailSiswa` pattern for consistency
    - Remove old `detail-wali.blade.php` custom view
    - _Requirements: 12.2, 12.6, 14.4_

  - [x] 4.3 Migrate EmailPopulation to Filament Table
    - Replace custom HTML table with Filament `InteractsWithTable`
    - Add TextColumn for nama, kelas (sortable), email (searchable, placeholder "Belum diisi")
    - Add inline edit Action per row with TextInput email
    - Wire edit action to `updateEmail()` method calling backend API
    - Remove old custom HTML table view
    - _Requirements: 12.3, 12.6_

  - [x] 4.4 Migrate TagihanCardView payment modal to Filament Action
    - Replace Alpine.js `x-data` modal with Filament `Action::make('pay')`
    - Create form schema: Select (metode_pembayaran), TextInput (pembayar), TextInput (jumlah, numeric, prefix Rp), DatePicker (tanggal, default now)
    - Wire action to `processPayment()` method
    - Set modalHeading('Pembayaran Tagihan') and modalWidth('md')
    - Remove old Alpine.js modal code from blade view
    - _Requirements: 12.4, 12.6_

  - [x] 4.5 Replace custom select dropdowns with Filament Select
    - Replace custom `<select>` elements for period/filter selection with `Select::make()` components
    - Apply `->live()` and `->afterStateUpdated()` for reactivity
    - Apply to tahun_ajaran selector, semester filter, and other period filters
    - _Requirements: 12.5, 12.6_

- [x] 5. Table consistency — sorting, filtering, search (MEDIUM-HIGH IMPACT)
  - [x] 5.1 Add sorting to all Filament Tables
    - Add `->sortable()` to primary columns across all Filament Tables
    - Standard sortable columns: nama, tanggal/tanggal_dibuat, jumlah, status, email, role
    - Pass sort parameters to backend API via `?sort=column&direction=asc|desc`
    - _Requirements: 13.1, 13.6_

  - [x] 5.2 Add backend API sort support for unsupported endpoints
    - Add `sort` and `direction` query parameter handling to `/siswa`, `/pembayaran`, `/users`, `/pengeluaran` endpoints
    - Implement generic sort logic in base controller or query builder
    - _Requirements: 13.6, 13.7_

  - [x] 5.3 Add SelectFilters to tables lacking filtering
    - Add `SelectFilter::make('jenjang')` to DataSiswa table
    - Add `SelectFilter::make('metode')` to Pembayaran table
    - Add `SelectFilter::make('role')` to UserManagement table
    - Ensure filter values are passed as API query parameters
    - _Requirements: 13.2_

  - [x] 5.4 Standardize server-side search and pagination
    - Ensure all Filament Tables use `->searchable()` on appropriate columns with server-side `?search=` API parameter
    - Replace any client-side filtering
    - Set all tables to pagination options `[5, 10, 25]` with default 10
    - _Requirements: 13.3, 13.5_

- [x] 6. Siswa/Wali Portal (HIGH IMPACT for wali users)
  - [x] 6.1 Create PortalPanelProvider
    - Create `app/Providers/Filament/PortalPanelProvider.php`
    - Configure panel at `/portal` path with separate authentication guard
    - Apply same Filament theming as admin panel (dark mode, branding)
    - Configure mobile-first layout
    - Register panel in `config/app.php` providers
    - _Requirements: 8.1, 8.7_

  - [x] 6.2 Implement portal bottom navigation
    - Create custom bottom navigation Blade component for mobile viewports (< 768px)
    - Limit navigation to: Beranda, Tagihan, Riwayat Pembayaran, Profil
    - Switch to sidebar on desktop viewports
    - _Requirements: 8.6, 8.9_

  - [x] 6.3 Implement portal Beranda page with summary card
    - Create portal Beranda page with total outstanding tagihan summary card
    - Implement child selector widget for wali with multiple children
    - Display count of belum lunas, cicilan, and lunas tagihan
    - _Requirements: 8.3, 8.8_

  - [x] 6.4 Implement portal Tagihan list page
    - Create Tagihan list page grouped by status (belum lunas, cicilan, lunas)
    - Filter tagihan by active child selection
    - Display amount, due date, and status for each tagihan
    - _Requirements: 8.4_

  - [x] 6.5 Implement portal Riwayat Pembayaran page
    - Create payment history page showing completed pembayaran
    - Display date, amount, and kwitansi download link per payment
    - Filter by active child selection
    - _Requirements: 8.5_

  - [x] 6.6 Implement portal Profil page
    - Create profile page showing siswa/wali information
    - _Requirements: 8.9_

- [x] 7. Profile page migration (MEDIUM IMPACT)
  - [x] 7.1 Create Filament EditProfile page
    - Create `app/Filament/Pages/Auth/EditProfile.php` extending `Filament\Pages\Auth\EditProfile`
    - Add Section "Informasi Profil" with TextInput name (required) and TextInput email (email, unique)
    - Add Section "Ubah Password" with current_password, password (confirmed, min 8), password_confirmation
    - Implement `handleRecordUpdate()` to call backend API `PUT /user/profile`
    - Map API validation errors to inline form field errors
    - _Requirements: 15.1, 15.2, 15.3, 15.5, 15.6_

  - [x] 7.2 Configure profile page in AdminPanelProvider and cleanup
    - Add `->profile(EditProfile::class)` to panel configuration
    - Delete old `app/Filament/Pages/ProfilePage.php` and its blade view
    - Remove any custom routes referencing old profile page
    - _Requirements: 15.4, 15.7_

- [x] 8. Responsive layout (MEDIUM IMPACT)
  - [x] 8.1 Configure responsive sidebar behavior
    - Implement collapsible sidebar with hamburger toggle for viewports < 768px
    - Optimize sidebar display for tablet (768px–1024px) and desktop (> 1024px)
    - Use Filament's built-in responsive sidebar configuration
    - _Requirements: 3.1, 3.3_

  - [x] 8.2 Implement responsive tables and forms
    - Configure data tables to horizontally scroll or collapse to card layout on viewports < 768px
    - Stack form fields vertically with full-width inputs on mobile
    - Optimize table layouts and form inputs for tablet interaction (768px–1024px)
    - _Requirements: 3.2, 3.5, 3.6_

  - [x] 8.3 Ensure minimum touch target sizes
    - Add CSS rules ensuring all interactive elements have minimum 44x44px touch targets on viewports < 1024px
    - _Requirements: 3.4_

- [x] 9. Loading states and SPA transitions (MEDIUM IMPACT)
  - [x] 9.1 Create skeleton loader Blade component
    - Create `resources/views/components/skeleton-loader.blade.php`
    - Support table mode (configurable rows/columns) and card mode (configurable count)
    - Implement shimmer animation effect via CSS
    - Integrate with Livewire loading states
    - _Requirements: 5.1, 5.6_

  - [x] 9.2 Configure SPA transitions and loading indicators
    - Configure top progress bar for page transitions
    - Add loading indicator on form submit buttons during async operations
    - Disable submit buttons during submission to prevent duplicates
    - Show success/error notifications within 300ms of operation completion
    - _Requirements: 5.2, 5.3, 5.4, 5.5_

- [x] 10. Print styles (MEDIUM IMPACT for operators)
  - [x] 10.1 Create print stylesheet and layout component
    - Create `resources/views/components/print-layout.blade.php` with branch header slot
    - Create dedicated print stylesheet hiding sidebar, header, and interactive elements
    - Render printed content in black and white with sufficient contrast
    - _Requirements: 10.3, 10.4_

  - [x] 10.2 Implement kwitansi print layout
    - Apply print-layout component to kwitansi pages
    - Include: branch name, branch logo, student name, class, payment details, date, operator name
    - Apply proper page-break rules for tables
    - _Requirements: 10.1, 10.2, 10.5, 9.6_

- [ ] 11. Gradual rollout configuration
  - [ ] 11.1 Create central configuration file and document customization points
    - Ensure navigation is deployable as single config change in AdminPanelProvider
    - Ensure portal is deployable independently without affecting admin panel
    - Create central config documentation file listing all customization points
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [ ] 12. (OPTIONAL) Performance optimization
  - [ ]* 12.1 Configure Vite build optimization
    - Configure Vite 7 with tree-shaking for CSS bundles
    - Defer non-critical JavaScript loading until after initial render
    - Implement lazy-loading for Livewire components below viewport fold
    - _Requirements: 7.1, 7.3, 7.5_

  - [ ]* 12.2 Optimize layout stability (CLS)
    - Add explicit width/height attributes to all images and icons
    - Ensure skeleton loaders match expected layout dimensions
    - Implement pagination for tables with > 50 rows to limit DOM nodes
    - Target CLS score below 0.1 on all pages
    - _Requirements: 7.2, 7.4, 7.6_

- [ ] 13. (OPTIONAL) Accessibility improvements
  - [ ]* 13.1 Add semantic HTML and ARIA attributes
    - Ensure all pages use semantic elements: nav, main, header, footer, section
    - Add ARIA labels to all icon-only buttons and interactive elements without visible text
    - Ensure all form inputs have associated labels
    - Implement aria-live regions for form error announcements
    - _Requirements: 6.3, 6.4, 6.5, 6.7_

  - [ ]* 13.2 Implement keyboard navigation and focus management
    - Ensure full keyboard navigation with Tab, Shift+Tab, Enter, Escape, Arrow keys
    - Add visible focus indicators meeting WCAG 2.1 AA contrast requirements
    - Maintain logical heading hierarchy on all pages
    - _Requirements: 6.1, 6.2, 6.6_

- [ ] 14. (OPTIONAL) Property tests and integration tests
  - [ ]* 14.1 Write property test for navigation group visibility (Property 1)
    - **Validates: Requirements 1.8**
  - [ ]* 14.2 Write property test for jenjang filter correctness (Property 2)
    - **Validates: Requirements 2.3**
  - [ ]* 14.3 Write property test for jenjang record count (Property 3)
    - **Validates: Requirements 2.5**
  - [ ]* 14.4 Write property test for jenjang permission filtering (Property 4)
    - **Validates: Requirements 2.7**
  - [ ]* 14.5 Write property test for theme preference persistence (Property 5)
    - **Validates: Requirements 4.2**
  - [ ]* 14.6 Write property test for branch color application (Property 13)
    - **Validates: Requirements 9.3**
  - [ ]* 14.7 Write property test for portal tagihan summary (Property 11)
    - **Validates: Requirements 8.3**
  - [ ]* 14.8 Write property test for portal tagihan grouping (Property 12)
    - **Validates: Requirements 8.4**
  - [ ]* 14.9 Write property test for table filter matching (Property 14)
    - **Validates: Requirements 13.1, 13.2**
  - [ ]* 14.10 Write property test for sort order correctness (Property 15)
    - **Validates: Requirements 13.1, 13.6**
  - [ ]* 14.11 Write property test for server-side search (Property 16)
    - **Validates: Requirements 13.3**
  - [ ]* 14.12 Write property test for dark mode class coverage (Property 17)
    - **Validates: Requirements 14.1, 14.2, 14.6**
  - [ ]* 14.13 Write property test for email validation (Property 18)
    - **Validates: Requirements 15.2**
  - [ ]* 14.14 Write property test for password confirmation gate (Property 19)
    - **Validates: Requirements 15.3**
  - [ ]* 14.15 Write unit tests for BrandingService and PermissionHelper
    - _Requirements: 9.3, 9.7, 1.8, 2.7_
  - [ ]* 14.16 Write integration tests for migrated components
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.6_
  - [ ]* 14.17 Write accessibility integration tests (Dusk)
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

## Notes

- **Priority order based on UX impact**: Navigation → Dark mode → Component migration → Table consistency → Portal → Profile → Responsive → Loading states → Print → Rollout → Performance → Accessibility → Tests
- Tasks 1–11 are core implementation; tasks 12–14 are optional extras
- Tasks marked with `*` can be skipped entirely for faster delivery
- Component migrations (Task 4) give "two for one": cleaner UI + automatic dark mode support
- Table consistency (Task 5) directly improves data findability for all admin users
- The portal (Task 6) is placed after admin improvements since wali users benefit from the same theming/branding groundwork

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["2.1", "2.2"] },
    { "id": 2, "tasks": ["2.3", "3.1", "3.2", "3.3"] },
    { "id": 3, "tasks": ["3.4", "4.1", "4.2", "4.3"] },
    { "id": 4, "tasks": ["4.4", "4.5", "5.1", "5.2"] },
    { "id": 5, "tasks": ["5.3", "5.4", "6.1"] },
    { "id": 6, "tasks": ["6.2", "6.3", "6.4", "6.5", "6.6"] },
    { "id": 7, "tasks": ["7.1", "7.2", "8.1", "8.2", "8.3"] },
    { "id": 8, "tasks": ["9.1", "9.2", "10.1", "10.2"] },
    { "id": 9, "tasks": ["11.1", "12.1", "12.2"] },
    { "id": 10, "tasks": ["13.1", "13.2"] },
    { "id": 11, "tasks": ["14.1", "14.2", "14.3", "14.4", "14.5", "14.6", "14.7", "14.8", "14.9", "14.10", "14.11", "14.12", "14.13", "14.14", "14.15", "14.16", "14.17"] }
  ]
}
```
