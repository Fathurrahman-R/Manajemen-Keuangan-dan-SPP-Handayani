# Requirements Document

## Introduction

Optimasi dan penyempurnaan tampilan frontend aplikasi manajemen tagihan sekolah Handayani. Spec ini menggunakan Filament v4 sebagai fondasi UI, mengoptimalkan pengalaman pengguna melalui: reorganisasi navigasi (termasuk sub-navigasi per jenjang), responsive design, dark mode, loading states, aksesibilitas, performa, portal khusus siswa/wali, branding per-cabang, migrasi komponen custom ke Filament native, dan konsistensi tabel. Redesain dilakukan setelah semua fitur inti stabil.

### Current State (Pre-existing)

Beberapa aspek sudah terimplementasi sebelum spec ini dimulai:
- **SPA Mode**: Sudah aktif via `->spa(hasPrefetching: true)` di AdminPanelProvider
- **Navigation Groups**: Sudah menggunakan NavigationBuilder dengan 7 groups (Dashboard, Data Master, Transaksi, Laporan, Manajemen Akses, Tagihan Siswa, Pengaturan) — perlu reorganisasi ke 4 groups sesuai spec
- **Permission-based visibility**: Setiap NavigationItem sudah mengecek permissions dari session
- **Dashboard Filament Widgets**: StatsOverviewWidget, ChartWidgets, TableWidgets sudah diimplementasi
- **Siswa/Wali**: SiswaDashboard dan TagihanSiswa sudah ada dalam admin panel (bukan portal terpisah), termasuk child selector untuk wali

## Glossary

- **Filament_Panel**: Framework admin panel (Filament v4) yang digunakan sebagai fondasi UI, termasuk komponen bawaan (Table, Form, Infolist, Action, Widget), theming via Tailwind CSS, dan konfigurasi panel
- **Navigation_System**: Komponen sidebar, sub-navigasi, dan breadcrumb yang mengatur akses ke seluruh halaman aplikasi
- **Sub_Navigation**: Navigasi sekunder di dalam sidebar yang memisahkan konten berdasarkan kategori (contoh: per jenjang pada halaman Siswa)
- **Siswa_Wali_Portal**: Panel terpisah yang dioptimalkan untuk pengguna siswa dan wali murid dengan tampilan sederhana dan mobile-first
- **Admin_Panel**: Panel utama Filament untuk pengguna Admin dan Operator dengan fitur lengkap
- **Dark_Mode_Engine**: Sistem toggle tema gelap/terang dengan persistensi preferensi pengguna
- **Skeleton_Loader**: Placeholder animasi yang ditampilkan saat data sedang dimuat
- **Branch_Branding**: Konfigurasi visual (logo, warna) yang berbeda per cabang sekolah
- **CLS**: Cumulative Layout Shift — metrik performa web yang mengukur pergeseran tata letak visual
- **WCAG_2_1_AA**: Standar aksesibilitas web level AA dari Web Content Accessibility Guidelines versi 2.1
- **Responsive_Layout**: Tata letak yang menyesuaikan tampilan berdasarkan ukuran layar perangkat
- **Jenjang**: Tingkat pendidikan (KB, TK, MI) yang digunakan untuk mengkategorikan siswa

## Requirements

### Requirement 1: Navigation Reorganization

**User Story:** As an Admin, I want the sidebar navigation organized into logical groups, so that I can find features quickly.

**Current State:** Navigation groups sudah ada (7 groups) menggunakan NavigationBuilder di AdminPanelProvider. Perlu direstruktur menjadi 4 groups logis. Permission-based visibility sudah berfungsi.

#### Acceptance Criteria

1. THE Navigation_System SHALL reorganize existing menu items from 7 groups into four groups: Akademik, Keuangan, Laporan, and Pengaturan
2. THE Navigation_System SHALL place Siswa, Kelas, Kategori, Kenaikan Kelas, and Tahun Ajaran under the Akademik group
3. THE Navigation_System SHALL place Tagihan, Pembayaran, Pengeluaran, Request Pengeluaran, Jenis Tagihan, and Kas under the Keuangan group
4. THE Navigation_System SHALL place Dashboard, Import/Export, Kas Harian, and Rekap Bulanan under the Laporan group
5. THE Navigation_System SHALL place User Management, Role Management, Akun Siswa, App Settings, and Notification Settings under the Pengaturan group
6. THE Navigation_System SHALL display a breadcrumb trail showing the current page hierarchy on every page
7. THE Navigation_System SHALL display navigation group icons that visually represent each group category
8. WHEN a user does not have permission to view any item in a group, THE Navigation_System SHALL hide that entire group from the sidebar (existing behavior — formalize via PermissionHelper)

### Requirement 2: Sub-Navigation per Jenjang

**User Story:** As an Admin, I want pages that manage data per jenjang to have sub-navigation in the sidebar, so that I can quickly switch between jenjang (KB, TK, MI) without extra clicks.

#### Acceptance Criteria

1. THE Sub_Navigation SHALL display jenjang options (KB, TK, MI) as sidebar sub-menu items under applicable parent menu items
2. THE Sub_Navigation SHALL be applied to the following pages: Siswa, Kelas, Tagihan, Pembayaran, and Kenaikan Kelas
3. WHEN a user clicks a jenjang sub-menu item, THE Admin_Panel SHALL navigate to a view showing only data of that jenjang
4. THE Sub_Navigation SHALL highlight the currently active jenjang in the sidebar
5. THE Sub_Navigation SHALL display a record count per jenjang next to each sub-menu item
6. WHEN a user navigates to a parent menu item without selecting a jenjang, THE Admin_Panel SHALL display the first available jenjang by default
7. THE Sub_Navigation SHALL respect user permissions — only jenjang that the user has access to SHALL be visible
8. WHEN a page does not have jenjang-specific data, THE Navigation_System SHALL display that page as a single menu item without sub-navigation

### Requirement 3: Responsive Layout

**User Story:** As an Operator, I want the application to work well on tablets, so that I can process payments on a tablet device.

#### Acceptance Criteria

1. WHILE the viewport width is less than 768px, THE Responsive_Layout SHALL display a collapsible sidebar that can be toggled via a hamburger menu button
2. WHILE the viewport width is between 768px and 1024px, THE Responsive_Layout SHALL optimize table layouts and form inputs for tablet interaction
3. WHILE the viewport width is greater than 1024px, THE Responsive_Layout SHALL display the full sidebar and desktop-optimized layouts
4. THE Responsive_Layout SHALL render all interactive elements (buttons, inputs, links) with a minimum touch target size of 44x44 CSS pixels on viewports below 1024px
5. WHILE the viewport width is less than 768px, THE Responsive_Layout SHALL stack form fields vertically and use full-width inputs
6. THE Responsive_Layout SHALL use responsive data tables that horizontally scroll or collapse into card layouts on viewports below 768px

### Requirement 4: Dark Mode

**User Story:** As a User, I want to switch between light and dark themes, so that I can use the application comfortably in different lighting conditions.

**Current State:** Dark mode BELUM dikonfigurasi di AdminPanelProvider (tidak ada `->darkMode()`). Tidak ada toggle di user menu. 4 blade views (tagihan-card-view, tagihan-siswa, bulk-akun-siswa, detail-wali) tidak memiliki `dark:` CSS classes sama sekali.

#### Acceptance Criteria

1. THE Dark_Mode_Engine SHALL provide a toggle control accessible from the user menu to switch between light mode, dark mode, and system preference
2. WHEN a user selects a theme preference, THE Dark_Mode_Engine SHALL persist that preference in the user settings stored on the backend
3. WHEN a user has not set a preference, THE Dark_Mode_Engine SHALL detect and apply the operating system color scheme preference
4. WHEN the theme is switched, THE Dark_Mode_Engine SHALL apply the new theme without requiring a page reload
5. THE Dark_Mode_Engine SHALL maintain WCAG 2.1 AA contrast ratios (minimum 4.5:1 for normal text, 3:1 for large text) in both light and dark modes
6. THE Dark_Mode_Engine SHALL apply appropriate color adjustments to all Filament component surfaces including backgrounds, text, borders, and interactive elements

### Requirement 5: Loading States and Transitions

**User Story:** As a User, I want visual feedback when data is loading, so that I know the application is working and not frozen.

**Current State:** SPA mode sudah aktif (`->spa(hasPrefetching: true)`). Filament Tables sudah menggunakan `->deferLoading()`. Progress bar saat navigasi SPA sudah bawaan Filament. Yang belum ada: skeleton loaders custom, submit button disabled state enforcement, dan notification timing.

#### Acceptance Criteria

1. WHEN a page with data tables or lists is loading, THE Admin_Panel SHALL display skeleton loaders that match the expected layout structure
2. WHEN an asynchronous operation (form submission, data fetch) is in progress, THE Admin_Panel SHALL display a loading indicator on the triggering element
3. WHEN navigating between pages in SPA mode, THE Admin_Panel SHALL display a top progress bar indicating page transition
4. THE Admin_Panel SHALL disable form submit buttons during submission to prevent duplicate requests
5. WHEN an asynchronous operation completes, THE Admin_Panel SHALL display a success or error notification within 300ms of completion
6. THE Skeleton_Loader SHALL animate with a shimmer effect to indicate active loading state

### Requirement 6: Accessibility

**User Story:** As a User with disabilities, I want the application to be accessible via keyboard and screen reader, so that I can use all features without a mouse.

#### Acceptance Criteria

1. THE Admin_Panel SHALL support full keyboard navigation for all interactive elements using Tab, Shift+Tab, Enter, Escape, and Arrow keys
2. THE Admin_Panel SHALL provide visible focus indicators on all focusable elements that meet WCAG 2.1 AA contrast requirements
3. THE Admin_Panel SHALL use semantic HTML elements (nav, main, header, footer, section, article) to define page structure
4. THE Admin_Panel SHALL provide ARIA labels for all icon-only buttons and interactive elements without visible text
5. THE Admin_Panel SHALL ensure all form inputs have associated labels either via the label element or aria-labelledby attribute
6. THE Admin_Panel SHALL maintain a logical heading hierarchy (h1 through h6) on every page without skipping levels
7. WHEN an error occurs in a form field, THE Admin_Panel SHALL announce the error to screen readers using aria-live regions

### Requirement 7: Performance Optimization

**User Story:** As a User, I want pages to load quickly without visual jumps, so that the application feels responsive.

#### Acceptance Criteria

1. THE Admin_Panel SHALL lazy-load Livewire components that are below the initial viewport fold
2. THE Admin_Panel SHALL maintain a Cumulative Layout Shift (CLS) score below 0.1 on all pages
3. THE Filament_Panel SHALL generate optimized CSS bundles using Vite with tree-shaking to remove unused styles
4. WHEN images or icons are loaded, THE Admin_Panel SHALL specify explicit width and height attributes to prevent layout shifts
5. THE Admin_Panel SHALL defer loading of non-critical JavaScript until after the initial page render
6. WHEN a data table contains more than 50 rows, THE Admin_Panel SHALL implement pagination or virtual scrolling to limit DOM nodes

### Requirement 8: Siswa/Wali Portal

**User Story:** As a Wali Murid, I want a simple dedicated portal to view my child's bills and payment history, so that I can manage school payments easily from my phone.

**Current State:** Fungsionalitas siswa/wali sudah ada di dalam admin panel: SiswaDashboardPage (KPI + tagihan list + pembayaran terbaru via API /dashboard/siswa) dan TagihanSiswaPage (card-based tagihan view dengan sibling selector). Keduanya visible berdasarkan permission 'view-own-billing' / role 'siswa'. Requirement ini memindahkan fungsionalitas tersebut ke panel Filament terpisah di /portal dengan UX mobile-first.

#### Acceptance Criteria

1. THE Siswa_Wali_Portal SHALL provide a separate Filament panel with its own URL path (/portal) distinct from the Admin_Panel
2. THE Siswa_Wali_Portal SHALL display a mobile-first layout optimized for viewports below 768px
3. THE Siswa_Wali_Portal SHALL display a summary card showing total outstanding tagihan amount on the home page
4. THE Siswa_Wali_Portal SHALL provide a list view of all tagihan for the logged-in siswa, grouped by status (belum lunas, cicilan, lunas)
5. THE Siswa_Wali_Portal SHALL provide a payment history page showing all completed pembayaran with date, amount, and kwitansi download link
6. THE Siswa_Wali_Portal SHALL use a bottom navigation bar on mobile viewports instead of a sidebar
7. THE Siswa_Wali_Portal SHALL apply the same Filament theming as the Admin_Panel for visual consistency
8. WHEN a wali has multiple children, THE Siswa_Wali_Portal SHALL provide a child selector to switch between siswa profiles
9. THE Siswa_Wali_Portal SHALL limit navigation to only: Beranda, Tagihan, Riwayat Pembayaran, and Profil

### Requirement 9: Branch Branding and Customization

**User Story:** As an Admin, I want each school branch to have its own logo and color scheme, so that the application reflects each branch's identity.

#### Acceptance Criteria

1. THE Branch_Branding SHALL allow configuration of a branch logo (displayed in sidebar header and login page) via App Settings
2. THE Branch_Branding SHALL allow configuration of a primary color override per branch via App Settings
3. WHEN a branch has a custom primary color configured, THE Filament_Panel SHALL apply that color as the primary color scale for users of that branch
4. WHEN a branch has a custom logo configured, THE Admin_Panel SHALL display that logo in the sidebar header replacing the default brand name
5. THE Branch_Branding SHALL provide a favicon configuration per branch
6. WHEN generating print-friendly views (kwitansi, reports), THE Branch_Branding SHALL include the branch logo in the header and apply print-optimized styles
7. IF a branch has not configured custom branding, THEN THE Filament_Panel SHALL apply the default Filament branding

### Requirement 10: Print Styles

**User Story:** As an Operator, I want printed documents (kwitansi, reports) to be clean and professional, so that they can be given to parents.

#### Acceptance Criteria

1. WHEN a user triggers print on a kwitansi page, THE Admin_Panel SHALL render a print-optimized layout with branch logo header, structured content, and no navigation elements
2. WHEN a user triggers print on a report page, THE Admin_Panel SHALL render tables with proper page-break rules to avoid splitting rows across pages
3. THE Admin_Panel SHALL provide a dedicated print stylesheet that hides sidebar, header, and interactive elements
4. THE Admin_Panel SHALL render printed content in black and white with sufficient contrast for photocopying
5. WHEN printing a kwitansi, THE Admin_Panel SHALL include: branch name, branch logo, student name, class, payment details, date, and operator name

### Requirement 11: Gradual Rollout Strategy

**User Story:** As an Admin, I want the redesign to be rolled out gradually, so that existing workflows are not disrupted.

#### Acceptance Criteria

1. THE Filament_Panel SHALL allow incremental theme customization via the existing Vite theme configuration without requiring per-page code changes
2. THE Navigation_System SHALL be deployable as a single configuration change in AdminPanelProvider without modifying individual page files
3. THE Siswa_Wali_Portal SHALL be deployable as a separate Filament panel that does not affect the existing Admin_Panel
4. WHEN the redesign is partially deployed, THE Admin_Panel SHALL maintain full functionality of all existing features without regression
5. THE Admin_Panel SHALL document all customization points in a central configuration file that can be updated independently of component code

### Requirement 12: Component Migration to Filament Native

**User Story:** As a developer, I want all pages to use Filament native components consistently, so that maintenance is simpler and dark mode/theming works automatically.

**Current State:** 16 komponen sudah menggunakan Filament Table (InteractsWithTable). 5 views masih pakai custom HTML tables (BulkAkunSiswa, KenaikanKelas student table, SiswaDashboard, EmailPopulation, DetailWali). 2 views pakai custom card layouts (TagihanCardView, TagihanSiswa). TagihanCardView payment modal pakai Alpine.js custom. Dashboard sudah pakai Filament Widgets (Stats, Chart, Table).

#### Acceptance Criteria

1. THE Admin_Panel SHALL replace the BulkAkunSiswa custom HTML table with a Filament Table component using built-in bulk actions, SelectFilter for jenjang/kelas, and server-side pagination
2. THE Admin_Panel SHALL replace the DetailWali custom HTML display with Filament Infolist component (matching the existing DetailSiswa pattern)
3. THE Admin_Panel SHALL replace the EmailPopulation custom HTML table with a Filament Table component using inline edit actions per row
4. THE Admin_Panel SHALL replace the TagihanCardView Alpine.js payment modal with a Filament Action modal using Filament Form components (Select for metode, TextInput for pembayar)
5. THE Admin_Panel SHALL replace all custom `<select>` dropdowns used for period/filter selection with Filament Select components where applicable within Filament page contexts
6. WHEN a component is migrated to Filament native, THE Admin_Panel SHALL preserve all existing functionality including permissions checks, API calls, and data flow

### Requirement 13: Table Consistency (Sorting, Filtering, Search)

**User Story:** As an Admin, I want all data tables to have consistent sorting, filtering, and search capabilities, so that I can find data efficiently across all pages.

**Current State:** Semua 16 Filament Tables TIDAK memiliki `->sortable()` di kolom manapun. Hanya 4 tabel punya filter (Pengeluaran, Tagihan, KasHarian, RekapBulanan). Search ada tapi tidak konsisten (beberapa client-side, beberapa server-side). Tidak ada bulk actions bawaan Filament — ManajemenAkunSiswa pakai custom checkbox. Pagination konsisten [5, 10, 25] default 5.

#### Acceptance Criteria

1. THE Admin_Panel SHALL add column-level sorting (sortable) to primary columns in all Filament Tables: nama, tanggal, jumlah, status, and other frequently-queried fields
2. THE Admin_Panel SHALL add SelectFilter to tables that currently lack filtering but have categorical data: DataSiswa (jenjang filter), Pembayaran (metode filter), UserManagement (role filter)
3. THE Admin_Panel SHALL ensure all Filament Tables that fetch data from API use server-side search consistently via API query parameter, not client-side filtering
4. THE Admin_Panel SHALL add Filament built-in bulk actions to ManajemenAkunSiswa (replacing custom checkbox selection) for toggling active status and resetting passwords
5. THE Admin_Panel SHALL ensure all tables use consistent pagination options [5, 10, 25] with default 10 per page
6. WHEN sorting is applied to a column, THE Admin_Panel SHALL pass the sort parameter to the backend API and backend SHALL return sorted results
7. THE Admin_Panel SHALL add backend API support for sort parameters on endpoints that currently do not support sorting: /siswa, /pembayaran, /users, /pengeluaran

### Requirement 14: Dark Mode Compatibility Fix

**User Story:** As a User using dark mode, I want all pages to display correctly in dark mode, so that no content is unreadable or invisible.

**Current State:** 4 views TIDAK memiliki dark mode support sama sekali: tagihan-card-view.blade.php, tagihan-siswa.blade.php, bulk-akun-siswa.blade.php, detail-wali.blade.php. Semua menggunakan `bg-white`, `text-gray-900`, dll tanpa `dark:` variants. Views lain yang sudah punya dark: classes: siswa-dashboard, email-population, kenaikan-kelas.

#### Acceptance Criteria

1. THE Admin_Panel SHALL add dark mode CSS classes (dark: variants) to the tagihan-card-view.blade.php view for all backgrounds, text colors, borders, badges, and interactive elements
2. THE Admin_Panel SHALL add dark mode CSS classes to the tagihan-siswa.blade.php view for all summary cards, tagihan list items, badges, and empty states
3. THE Admin_Panel SHALL add dark mode CSS classes to the bulk-akun-siswa.blade.php view for the table, filters, pagination, modal, and all text elements (or migrate to Filament Table per Requirement 12)
4. THE Admin_Panel SHALL migrate detail-wali.blade.php to Filament Infolist component which provides automatic dark mode support (per Requirement 12)
5. WHEN migrating a component to Filament native (Requirement 12), dark mode compatibility SHALL be automatically achieved through Filament's built-in theming
6. THE Admin_Panel SHALL verify that all custom Blade components and views include appropriate dark: Tailwind classes for: backgrounds (dark:bg-gray-800), text (dark:text-gray-100), borders (dark:border-gray-700), and badges (dark:bg-{color}-900/30 dark:text-{color}-400)

### Requirement 15: Profile Page Migration to Filament Native

**User Story:** As a User, I want a profile page that uses Filament's built-in profile components, so that email editing and profile management work consistently with the rest of the application.

**Current State:** ProfilePage saat ini adalah custom Filament Page dengan form HTML manual untuk update email (memerlukan password konfirmasi). Page ini tidak menggunakan Filament's built-in profile page/component (`Filament\Pages\Auth\EditProfile`), sehingga tidak mendapat fitur bawaan seperti avatar, nama display, dan integrasi user menu otomatis.

#### Acceptance Criteria

1. THE Admin_Panel SHALL replace the custom ProfilePage with Filament's built-in profile page mechanism (`->profile()` on the panel or custom EditProfile page extending `Filament\Pages\Auth\EditProfile`)
2. THE Admin_Panel SHALL include an email field in the profile form that validates email format and uniqueness within the branch
3. THE Admin_Panel SHALL require current password confirmation before allowing email changes (maintaining existing security behavior)
4. THE Admin_Panel SHALL integrate the profile page into the user menu dropdown so it is accessible from the user avatar/name area in the panel header
5. THE Admin_Panel SHALL display the current user's email (if set) in the profile form as a pre-filled editable field
6. THE Admin_Panel SHALL show appropriate validation errors from the backend API (duplicate email, invalid format, wrong password) inline in the form
7. THE Admin_Panel SHALL remove the old custom ProfilePage (`app/Filament/Pages/ProfilePage.php`) and its blade view after migration
