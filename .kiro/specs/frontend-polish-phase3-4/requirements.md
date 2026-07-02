# Requirements Document

## Introduction

This document specifies the remaining frontend polish work for the Handayani school management system, covering the completion of Phase 3 (API synchronization, error handling, and dark mode) and Phase 4 (UI polish using Filament v4 native components). All changes are frontend-only within the `frontend-v2` directory, with no backend modifications required.

## Glossary

- **Frontend_App**: The Laravel + Filament v4 + Livewire application in `frontend-v2/` that consumes the backend REST API
- **Table_Component**: A Livewire component implementing `HasTable` that uses `->records(fn() => ...)` to fetch data from the backend API
- **Card_View_Component**: A Livewire component (e.g., TagihanCardView, PembayaranCardView) that renders grouped data in card layout with custom Blade views
- **HandlesApiErrors_Trait**: The existing trait at `app/Livewire/Concerns/HandlesApiErrors.php` providing standardized error notification methods (`handleApiError`, `notifyConnectionError`, `notifyUnexpectedError`)
- **Dashboard_Widget**: A Filament widget class in `app/Filament/Widgets/` that fetches statistics or chart data from the backend API
- **Portal_Page**: A Filament page in `app/Filament/Portal/Pages/` serving the parent/student portal with API-driven content
- **Blade_View**: A `.blade.php` template file in `resources/views/` rendering the UI for Livewire components or Filament pages
- **Filament_Native_Component**: A UI component provided by Filament v4 (e.g., `<x-filament::button>`, `<x-filament::badge>`, `TextInput::make()`)
- **ApiService**: The service class (`App\Services\ApiService`) used to make HTTP requests to the backend API
- **Empty_Paginator**: A `LengthAwarePaginator` instance with zero items returned as a fallback when API calls fail, preventing Filament table rendering errors

## Requirements

### Requirement 1: Table Component Error Handling

**User Story:** As an administrator, I want data tables to gracefully handle API failures, so that I see a friendly notification instead of a white error screen when the backend is unreachable.

**Current State:** `DataCategory`, `DataKelas`, `DataSiswa`, `DataWali`, `BranchManagement`, `JenisTagihan`, `UserManagement`, `RoleManagement`, `TahunAjaranManagement`, `EmailPopulation`, `KasHarian`, `RekapBulanan`, and `PengeluaranRequest` all call `ApiService::client()` directly inside `records()` closures without any `try/catch`. None of these components use the `HandlesApiErrors` trait. The trait already exists at `app/Livewire/Concerns/HandlesApiErrors.php` and is implemented correctly in card-view components (`TagihanCardView`, `PembayaranCardView`).

#### Acceptance Criteria

1. WHEN the ApiService call within a Table_Component `records()` closure throws an `Illuminate\Http\Client\ConnectionException`, THE Table_Component SHALL catch the exception, invoke `notifyConnectionError()` from HandlesApiErrors_Trait, and return an empty array within the same request cycle
2. WHEN the ApiService call within a Table_Component `records()` closure returns an HTTP error response (status code 400 or above), THE Table_Component SHALL catch the error, invoke `handleApiError()` from HandlesApiErrors_Trait passing the response object, and return an empty array within the same request cycle
3. THE Table_Component SHALL use the HandlesApiErrors_Trait in the following components: DataCategory, DataKelas, DataSiswa, DataWali, BranchManagement, JenisTagihan, UserManagement, RoleManagement, TahunAjaranManagement, EmailPopulation, KasHarian, RekapBulanan, PengeluaranRequest, and Setting
4. WHEN an API call fails due to either a connection exception or an HTTP error response in any Table_Component `records()` closure, THE Table_Component SHALL display the Filament table empty state with the component's configured `emptyStateHeading` text rather than rendering an unhandled exception page
5. IF an unexpected exception (any `\Throwable` other than `ConnectionException`) is thrown within a Table_Component `records()` closure, THEN THE Table_Component SHALL catch the exception, invoke `notifyUnexpectedError()` from HandlesApiErrors_Trait, and return an empty array

### Requirement 2: Dashboard Widget Resilience

**User Story:** As an administrator, I want the dashboard to remain functional even when some API endpoints are unavailable, so that partial data loss does not break the entire dashboard page.

**Current State:** All 8 widgets already extend the correct Filament base classes (`StatsOverviewWidget`, `ChartWidget`, `TableWidget`) and most already have a `try/catch (\Throwable $e)` block returning `$data = []` as fallback. However, the catch blocks are inconsistent — some catch only `\Throwable` with a bare `$data = []` (no HTTP error check), and HTTP error responses (4xx/5xx) that don't throw exceptions are silently ignored (not differentiated from success). The `PembayaranTerbaruWidget`, `TagihanJatuhTempoWidget`, and `TopTunggakanWidget` (TableWidgets) do not have an `emptyStateHeading` configured.

#### Acceptance Criteria

1. WHEN a Dashboard_Widget API call throws a connection exception (timeout, connection refused, or network error), THE Dashboard_Widget SHALL catch the exception and render with fallback data: zero values for StatsOverviewWidget stats, empty arrays for ChartWidget datasets and labels, and empty collections for TableWidget records
2. WHEN a Dashboard_Widget API call returns an HTTP error response (status code 4xx or 5xx), THE Dashboard_Widget SHALL catch the error and render with the same fallback data as criterion 1
3. THE Frontend_App SHALL implement independent try/catch error handling in each of the 8 widgets: DashboardStatsWidget, KasBulananChart, PembayaranBulananChart, PembayaranTerbaruWidget, StatusTagihanChart, TagihanJatuhTempoWidget, TopTunggakanWidget, and TunggakanJenjangChart
4. WHEN a Dashboard_Widget encounters an API error, THE Dashboard_Widget SHALL NOT propagate the exception to other widgets, ensuring that all remaining widgets on the same page continue to render independently with their own data or fallback data
5. WHEN a TableWidget (PembayaranTerbaruWidget, TagihanJatuhTempoWidget, TopTunggakanWidget) renders with fallback data due to an API error, THE TableWidget SHALL display its configured empty state heading and icon instead of a blank or broken layout

### Requirement 3: Portal Pages API Verification

**User Story:** As a parent or student, I want the portal pages to handle API failures gracefully, so that I see helpful feedback instead of an error page.

**Current State:** `PortalRiwayatPembayaranPage` already has a `try/catch` in its `records()` closure returning an empty `LengthAwarePaginator` on failure ✅. `TagihanSiswa` already uses `HandlesApiErrors` ✅. However, `PortalBerandaPage` delegates to `SiswaDashboard` Livewire component which catches `Exception $e` but sets `$this->error` string without showing a Filament notification (inconsistent UX). `PortalTagihanPage` delegates to `TagihanSiswa` which is already handled. `PortalProfilPage` calls `/users/current` — needs verification. The `SiswaDashboard` component's `loadData()` catches generic `Exception` but does not use `HandlesApiErrors` trait methods.

#### Acceptance Criteria

1. WHEN a Portal_Page API call fails due to an HTTP 4xx or 5xx response, THE Portal_Page SHALL display a danger notification containing the error message extracted from the response JSON `message` field or the first value in the `errors` object, and render the page with empty or fallback data instead of triggering an unhandled exception
2. IF a Portal_Page API call fails due to a network error (connection timeout or no response received within 30 seconds), THEN THE Portal_Page SHALL display a persistent danger notification indicating that the server could not be reached, and render the page with empty or fallback data
3. THE Frontend_App SHALL verify and fix API response handling for: PortalBerandaPage (via SiswaDashboard component calling `/dashboard/siswa`), PortalProfilPage (calling `/users/current`), PortalRiwayatPembayaranPage (calling `/pembayaran` within a Filament Table records closure), and PortalTagihanPage (via TagihanSiswa component calling `/tagihan/siswa`)
4. WHEN a Portal_Page receives a successful API response, THE Portal_Page SHALL extract data from the response `data` key using null-safe access patterns (null coalescing to empty arrays or default values) for all nested properties including `data.tagihan`, `data.siblings`, `data.total_tagihan`, `data.tagihan_list`, `data.pembayaran_terbaru`, and pagination `meta.total`
5. WHILE a Portal_Page is loading API data, THE Portal_Page SHALL display a loading indicator within the content area until the API response is received or an error state is determined

### Requirement 4: Dark Mode Compatibility for All Blade Views

**User Story:** As a user who prefers dark mode, I want all pages to be fully readable in dark mode, so that no custom view has white backgrounds or unreadable text when dark mode is active.

#### Acceptance Criteria

1. THE Blade_View SHALL include Tailwind `dark:` variant classes for background colors (`dark:bg-*`), text colors (`dark:text-*`), border colors (`dark:border-*`), and ring colors (`dark:ring-*`) on every custom HTML element that specifies a light-mode color class
2. WHEN dark mode is active, THE Frontend_App SHALL render all custom Blade views with dark backgrounds no lighter than `gray-800` (e.g., `dark:bg-gray-900`, `dark:bg-gray-800`) and text colors no darker than `gray-300` (e.g., `dark:text-white`, `dark:text-gray-100`, `dark:text-gray-300`) to maintain a minimum WCAG AA contrast ratio (4.5:1 for normal text, 3:1 for large text)
3. THE Frontend_App SHALL apply dark mode classes to all Blade views in `resources/views/livewire/` and `resources/views/filament/pages/` that contain custom HTML elements (defined as: raw HTML `<div>`, `<input>`, `<select>`, `<button>`, `<table>`, `<span>`, `<label>`, or `<p>` tags with inline Tailwind color classes, as opposed to Filament components such as `<x-filament::section>`, `<x-filament::button>`, or `<x-filament::badge>` which handle dark mode internally)
4. WHEN a Blade_View uses Filament section-like styling for container elements, THE Blade_View SHALL use the pattern `bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm` for those containers
5. WHEN dark mode is active, THE Blade_View SHALL apply dark-compatible classes to interactive states including hover (`dark:hover:bg-gray-700` or darker), focus rings (`dark:focus:ring-primary-500`), and disabled states (`dark:disabled:bg-gray-800 dark:disabled:text-gray-500`) on all custom interactive elements (inputs, buttons, checkboxes, select dropdowns)
6. WHEN dark mode is active, THE Blade_View SHALL render colored status indicators (badges, summary cards, overdue markers) using the `dark:bg-{color}-900/30 dark:text-{color}-400` pattern instead of light-mode `bg-{color}-100 text-{color}-800` to ensure readability against dark backgrounds

### Requirement 5: Form Actions Use Filament v4 Schema Pattern

**User Story:** As a developer maintaining this codebase, I want all form-based actions to use the Filament v4 `->schema([...])` pattern consistently, so that validation and rendering follow the framework's standard approach.

**Current State:** Most components already use `->schema([...])` correctly (e.g., `DataCategory`, `BranchManagement`). However, `TagihanCardView.payAction()` still uses the deprecated `->form([...])` pattern (line: `->form([Select::make('metode_pembayaran')...])`). Additionally, `ChangePassword` uses `$this->validate()` with public string properties instead of Filament form components.

#### Acceptance Criteria

1. THE Frontend_App SHALL define form fields using `->schema([...])` with native Filament form components (TextInput, Select, DatePicker, Textarea, FileUpload, CheckboxList) for every action that presents user-editable input fields in a modal, including header actions, record actions, and bulk actions across Table_Components; confirmation-only actions (those using `->requiresConfirmation()` without input fields) are exempt from this requirement
2. WHEN a form action requires input validation, THE Frontend_App SHALL declare validation rules exclusively via Filament's built-in schema validation methods (e.g., `->required()`, `->email()`, `->maxLength()`, `->numeric()`, `->minValue()`, `->minLength()`) on form components within `->schema([...])`, and SHALL NOT use manual `$this->validate()` calls or the deprecated `->form([...])` method
3. THE Frontend_App SHALL ensure all form actions that collect user input in DataCategory, DataKelas, DataSiswa, BranchManagement, JenisTagihan, UserManagement, RoleManagement, TahunAjaranManagement, and PengeluaranRequest use the `->schema([...])` pattern with zero occurrences of `->form([...])` or inline Blade `<input>`/`<select>` elements for modal form rendering
4. IF the backend API returns a validation error (HTTP 422) after a form action submission that passed Filament schema validation, THEN THE Frontend_App SHALL display the backend error message to the user via a Filament Notification without discarding the user's previously entered form data

### Requirement 6: Dashboard Widgets Use Native Filament Base Classes

**User Story:** As a developer, I want all dashboard widgets to extend the correct Filament v4 base classes, so that they benefit from built-in styling, loading states, and dark mode support.

**Current State:** All 8 widgets already extend the correct Filament base classes — `DashboardStatsWidget` extends `StatsOverviewWidget`, chart widgets extend `ChartWidget`, and table widgets extend `TableWidget`. None override `render()` or `getView()`. This requirement is largely already satisfied; the remaining work is ensuring no widget sets a custom `$view` property and that data-fetching methods are the only overrides.

#### Acceptance Criteria

1. THE DashboardStatsWidget SHALL extend `Filament\Widgets\StatsOverviewWidget`
2. THE chart widgets (KasBulananChart, PembayaranBulananChart, StatusTagihanChart, TunggakanJenjangChart) SHALL extend `Filament\Widgets\ChartWidget`
3. THE table widgets (PembayaranTerbaruWidget, TagihanJatuhTempoWidget, TopTunggakanWidget) SHALL extend `Filament\Widgets\TableWidget`
4. WHEN a Dashboard_Widget overrides methods, THE Dashboard_Widget SHALL only override data-fetching methods: `getStats()` for StatsOverviewWidget, `getData()` and `getType()` for ChartWidget, and `table()` for TableWidget
5. THE Dashboard_Widget SHALL NOT override view-rendering methods (`render()`, `getView()`) or set a custom `protected static string $view` property, so that Filament's built-in view templates are used for layout, loading states, and dark mode support
6. IF an API call within a Dashboard_Widget data-fetching method fails, THEN THE Dashboard_Widget SHALL catch the exception and return an empty dataset (empty array for charts, empty collection for tables, or zero-value stats) instead of propagating the error

### Requirement 7: Card Views Use Filament Blade Components

**User Story:** As a user, I want card-based pages to have consistent styling with the rest of the Filament UI, so that buttons, badges, inputs, and pagination look native and work in dark mode.

**Current State:** Both `tagihan-card-view.blade.php` and `pembayaran-card-view.blade.php` already use `<x-filament::section>`, `<x-filament::input.wrapper>`, `<x-filament::input.select>`, and have `dark:` classes throughout. However, the following custom elements remain: (1) raw `<input type="text">` for search in both views, (2) raw `<select>` for jenjang and status filters in `tagihan-card-view.blade.php`, (3) raw `<button>` for "Bayar" payment CTA, (4) raw `<span>` with manual color classes for status badges, (5) raw `<button>` elements with manual border classes for pagination nav (Prev/Next and page number buttons), and (6) raw `<button>` elements wrapping inline SVG for delete and download kwitansi actions.

#### Acceptance Criteria

1. WHEN a Card_View_Component renders a text search input, THE Blade_View SHALL use `<x-filament::input.wrapper>` containing `<x-filament::input>` instead of a raw `<input type="text">` element with manual Tailwind classes
2. WHEN a Card_View_Component renders a select filter (jenjang, status, or per-page selector), THE Blade_View SHALL use `<x-filament::input.wrapper>` containing `<x-filament::input.select>` instead of a raw `<select>` element with manual Tailwind classes
3. WHEN a Card_View_Component renders a primary action button (e.g., "Bayar", "Tambah Tagihan"), THE Blade_View SHALL use `<x-filament::button>` with the appropriate `color`, `size`, and `icon` attributes instead of a custom styled `<button>` element
4. WHEN a Card_View_Component renders a status badge (tagihan status or payment method), THE Blade_View SHALL use `<x-filament::badge>` with the appropriate `color` attribute instead of a custom styled `<span>` element with manual background and text color classes
5. WHEN a Card_View_Component renders pagination controls with more than 1 page, THE Blade_View SHALL use custom navigation buttons styled with Filament's `fi-btn` utility classes or `<x-filament::button>` components with `outlined` style, replacing raw `<button>` elements with manual border and background classes
6. THE Frontend_App SHALL apply criteria 1 through 5 to both the TagihanCardView and PembayaranCardView Blade view files, and all replaced components SHALL inherit Filament's built-in dark mode styling without requiring additional manual `dark:` class overrides on those elements
7. WHEN a Card_View_Component renders icon-only action buttons (delete, download kwitansi), THE Blade_View SHALL use `<x-filament::icon-button>` with the appropriate `icon`, `color`, and `tooltip` attributes instead of custom `<button>` elements wrapping inline SVG icons

### Requirement 8: Settings Page Uses Filament Form

**User Story:** As an administrator, I want the settings page to use Filament's native form layout with sections, so that it is consistent with other pages and supports dark mode.

**Current State:** `Settings.php` already uses `->schema([...])` with Filament form components (TextInput, Textarea, FileUpload, Grid) in its edit action ✅. However: (1) `mount()` calls the API without using `HandlesApiErrors` — on failure it only shows a notification but `$this->setting` remains unset, which will cause null pointer errors when `fillForm()` tries to access `$this->setting['id']`; (2) after a successful update, the action does not refresh `$this->setting` with new data, so the displayed infolist remains stale; (3) the view `settings.blade.php` uses custom HTML for displaying settings data (not a Filament infolist component). The section grouping (School Info, Contact, Leadership) is not yet implemented — all fields are in a flat Grid.

#### Acceptance Criteria

1. THE Settings Page SHALL display school information using Filament `Section` components grouped as follows: a "School Info" section containing nama_sekolah, alamat, lokasi, kode_pos, and logo; a "Contact" section containing email and telepon; and a "Leadership" section containing kepala_sekolah and bendahara
2. THE Settings Page SHALL use Filament form components (TextInput, Textarea, FileUpload) within the edit action `schema()`, with each field pre-filled from the current settings data
3. WHEN the settings API call fails during page mount, THE Settings Page SHALL display an error notification via the HandlesApiErrors trait and render the page without settings data (no infolist sections shown) rather than throwing an unhandled exception
4. IF the settings update API call fails, THEN THE Settings Page SHALL display an error notification extracted from the API response via the HandlesApiErrors trait and retain the form data without closing the modal
5. WHEN the administrator successfully submits the edit form, THE Settings Page SHALL display a success notification and refresh the displayed settings data to reflect the saved changes within the same page load (no full page reload required)

### Requirement 9: Login Page Verification

**User Story:** As a developer, I want to confirm the login page extends Filament's native auth page, so that it receives framework updates and consistent styling automatically.

**Current State:** `Login.php` already extends `Filament\Auth\Pages\Login` ✅ and overrides only the correct methods (`authenticate()`, `form()`, `getUsernameFormComponent()`, etc.) ✅. However, it has a `__construct()` method that performs a redirect check — this is incorrect in Filament pages as Livewire components do not use PHP constructors for lifecycle logic. The redirect logic should be in `mount()` instead. Also, there is no custom `$view` property ✅.

#### Acceptance Criteria

1. THE Login Page SHALL extend `Filament\Pages\Auth\Login` as its base class
2. THE Login Page SHALL override only the `authenticate()` method for backend API credential verification, and form-component methods (`form()`, `getUsernameFormComponent()`, `getPasswordFormComponent()`, `getRememberFormComponent()`, `getCredentialsFromFormData()`) for input field customization
3. THE Login Page SHALL NOT override layout, rendering, or view methods (`content()`, `getFormContentComponent()`, `getFormActions()`, `getAuthenticateFormAction()`, `mount()`)
4. THE Login Page SHALL NOT define a custom Blade view (no `$view` property and no corresponding blade template in `resources/views/`)
5. THE Login Page SHALL NOT use a `__construct()` method for redirect logic; any session-based redirect checks SHALL be performed via Filament's built-in authentication redirect mechanism or a dedicated guard

### Requirement 10: Change Password Page Uses Filament Styling

**User Story:** As a user, I want the change password page to look and behave like other Filament pages, so that the experience is consistent.

**Current State:** `ChangePassword.php` uses plain Livewire public string properties (`$current_password`, `$new_password`, `$new_password_confirmation`) with `$this->validate()` and a custom `submit()` method. It does not implement `HasForms` or use `InteractsWithForms`. The blade view uses a custom HTML form with manual `<input>` elements. The page is only accessible when `data.must_change_password` is true (it redirects away otherwise).

#### Acceptance Criteria

1. THE ChangePassword Page SHALL implement the `HasForms` contract and use the `InteractsWithForms` trait to enable Filament form rendering
2. THE ChangePassword Page SHALL define a form schema using a Filament `Section` component with heading "Ubah Password" and a description to wrap the three password TextInput fields
3. THE ChangePassword Page SHALL render the form using `{{ $this->form }}` in the Blade view rather than custom HTML form elements
4. THE ChangePassword Page SHALL use Filament form components (TextInput with `->password()`) for current password, new password, and confirmation fields, with validation rules of required and minimum 8 characters for the new password and confirmed matching for the confirmation field
5. THE ChangePassword Page SHALL provide a submit action rendered as a Filament form action button labeled "Ubah Password" that retrieves validated form state via `$this->form->getState()` before calling the backend API

### Requirement 11: Database Notifications Integration

**User Story:** As an administrator, I want to see a notification bell icon in the header showing unread notifications from the system, so that I am aware of important events without checking a separate page.

**Current State:** No database notifications are implemented in the frontend. The backend already has a `NotificationController` with endpoints at `/notifications` (list), mark-as-read, and mark-all-as-read, and a `Notification` model with fields: `title`, `message`, `is_read` (boolean), `created_at`. Filament's `->databaseNotifications()` expects the standard Laravel `DatabaseNotification` model (with `data` JSON column and `read_at` timestamp), so a mapping layer or custom notification provider is required.

#### Acceptance Criteria

1. THE AdminPanelProvider SHALL register `->databaseNotifications()` to enable the notification bell icon in the header, displaying the current unread notification count as a numeric badge (0 or more)
2. WHEN the administrator opens the notification bell panel, THE Frontend_App SHALL fetch up to 50 notifications from the backend `/notifications` API endpoint and display each notification showing its title, message, and relative timestamp (e.g., "2 hours ago")
3. THE Frontend_App SHALL map backend `/notifications` API response fields to Filament's `DatabaseNotification` model format, converting `title` and `message` into the Filament notification `data` array, and converting `is_read` (boolean) to Filament's `read_at` (timestamp or null)
4. WHEN the administrator clicks on an unread notification in the panel, THE Frontend_App SHALL call the backend mark-as-read endpoint for that notification and update the unread count badge without requiring a full page reload
5. WHEN the administrator clicks "Mark all as read", THE Frontend_App SHALL call the backend mark-all-as-read endpoint and reset the unread count badge to zero
6. IF the backend `/notifications` API is unreachable or returns an error, THEN THE Frontend_App SHALL display an error notification indicating the notifications could not be loaded and show the bell icon without a count badge

### Requirement 12: Table Features Use Native Filament Capabilities

**User Story:** As an administrator, I want all data tables to use Filament's built-in features for filtering, empty states, and pagination options, so that the UX is consistent and feature-rich.

#### Acceptance Criteria

1. THE Table_Component SHALL configure `->emptyStateHeading()`, `->emptyStateDescription()`, and `->emptyStateIcon('heroicon-o-document-text')` for all tables that do not already have all three configured
2. THE Table_Component SHALL configure `->paginated([5, 10, 25, 50])` with `->defaultPaginationPageOption(10)` on all data tables
3. WHERE a Table_Component has 5 or more columns, THE Table_Component SHALL add `->toggleable()` to columns beyond the first 4 columns (by definition order) so that users can show or hide secondary information
4. IF a Table_Component displays data that is filterable by a categorical field (status, jenjang, role, or tahun ajaran), THEN THE Table_Component SHALL use `->filters([...])` with native Filament `SelectFilter` components for each such categorical field, instead of custom filter UI in Blade
5. WHEN a Table_Component already implements custom Blade-based filter controls for categorical data, THE Table_Component SHALL replace those custom controls with native Filament `SelectFilter` or `Filter` components configured within the table's `->filters([...])` method

### Requirement 13: Dead Code and Unused Import Cleanup

**User Story:** As a developer, I want the codebase to be free of unused imports, dead code, and orphan references, so that the code is maintainable and readable.

#### Acceptance Criteria

1. THE Frontend_App SHALL remove all unused `use` statements (imports that reference classes, functions, or constants not used within the same file) from PHP files across the `frontend-v2/app/` directory
2. THE Frontend_App SHALL remove all commented-out code blocks (lines of previously-active PHP code that have been commented out, excluding phpDoc annotations, inline explanatory comments, and TODO/FIXME markers) from PHP files across the `frontend-v2/app/` and `resources/views/` directories
3. THE Frontend_App SHALL remove references to deleted classes or files that no longer exist in the codebase, including `use` statements, class instantiations, trait usages, and method calls that point to non-existent targets, across the `frontend-v2/app/` directory
4. WHEN cleanup is performed, THE Frontend_App SHALL pass `npm run build` without errors to confirm no frontend asset dependencies were broken
5. WHEN cleanup is performed, THE Frontend_App SHALL pass PHP syntax validation (`php artisan view:clear && php artisan route:clear && php artisan config:clear`) without errors to confirm no PHP runtime dependencies were broken
