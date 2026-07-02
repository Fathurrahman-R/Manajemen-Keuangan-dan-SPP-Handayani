# Implementation Plan: Frontend Polish Phase 3 & 4

## Overview

This plan converts the Phase 3 (API error handling, dark mode, portal verification) and Phase 4 (Filament v4 UI polish, notifications, table features, dead code cleanup) design into incremental coding tasks. All work is inside the `frontend-v2/` directory. Tasks build sequentially so each step compiles and runs before the next begins.

## Tasks

- [x] 1. Add `HandlesApiErrors` trait to all 13 Table components and Setting
  - [x] 1.1 Add `use HandlesApiErrors` and wrap `records()` closures in `DataCategory`, `DataKelas`, `DataSiswa`, `DataWali`
    - Import `use App\Livewire\Concerns\HandlesApiErrors;` in each file
    - Wrap the `ApiService::client()->get(...)` call in `try { ... if (!$response->ok()) { $this->handleApiError($response); return []; } ... } catch (ConnectionException $e) { $this->notifyConnectionError(); return []; } catch (\Throwable $e) { $this->notifyUnexpectedError(); return []; }`
    - Keep any existing `->collect()->sortBy()` logic inside the `try` block
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 1.2 Add `use HandlesApiErrors` and wrap `records()` closures in `BranchManagement`, `JenisTagihan`, `UserManagement`, `RoleManagement`, `TahunAjaranManagement`
    - Same pattern as 1.1
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 1.3 Add `use HandlesApiErrors` and wrap `records()` closures in `EmailPopulation`, `KasHarian`, `RekapBulanan`, `PengeluaranRequest`
    - Same pattern as 1.1
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 1.4 Write property test for Table component error handling (Property 1)
    - **Property 1: Table component records() always returns [] on any API failure**
    - **Validates: Requirements 1.1, 1.2, 1.4, 1.5**
    - Create `tests/Feature/Properties/TableComponentErrorHandlingTest.php` using Pest + Eris
    - Generator: produce arbitrary combinations of component class (from the 13), failure type (`ConnectionException`, HTTP 400, HTTP 500, generic `RuntimeException`), and component state (random `$search`, `$filterJenjang`, `$perPage`, `$page`)
    - Assert `records()` closure returns an empty array or empty Collection for every generated combination
    - Run minimum 100 iterations
    - _Requirements: 1.1, 1.2, 1.4, 1.5_

- [x] 2. Add `HandlesApiErrors` to `Setting.php` and add null guard
  - [x] 2.1 Refactor `Setting::mount()` to use `HandlesApiErrors` with null guard on `$this->setting`
    - Import `use App\Livewire\Concerns\HandlesApiErrors;` in `Setting.php`
    - Wrap the API call in `mount()` with `try { $response = ...; if ($response->ok()) { $this->setting = $response->json('data'); } else { $this->handleApiError($response); $this->setting = null; } } catch (ConnectionException $e) { $this->notifyConnectionError(); $this->setting = null; }`
    - Add `if ($this->setting === null) return;` guard before `fillForm()` and before the save action body
    - _Requirements: 1.3, 8.3, 8.4_

  - [x] 2.2 Add Section grouping and refresh-after-save to `Setting.php`
    - Wrap the edit action `->schema([...])` fields in three `Section::make()` components: "Informasi Sekolah" (`nama_sekolah`, `alamat`, `lokasi`, `kode_pos`, `logo`), "Kontak" (`email`, `telepon`), "Kepemimpinan" (`kepala_sekolah`, `bendahara`)
    - After a successful update API response, re-fetch the settings and reassign `$this->setting`
    - Display a success notification after successful update
    - _Requirements: 8.1, 8.2, 8.5_

- [x] 3. Checkpoint — Table error handling
  - Ensure all modified Table components pass `php artisan route:clear && php artisan config:clear && php artisan view:clear` without errors, ask the user if questions arise.

- [x] 4. Standardize Dashboard Widget error handling
  - [x] 4.1 Add explicit `!$response->ok()` fallback checks to `DashboardStatsWidget` and the 4 ChartWidgets (`KasBulananChart`, `PembayaranBulananChart`, `StatusTagihanChart`, `TunggakanJenjangChart`)
    - In each `getStats()` / `getData()` method, add an `if (!$response->ok()) { return $this->fallbackStats(); }` check before processing the response body
    - `getStats()` fallback: array of zero-value `Stat` objects
    - `getData()` fallback: `['datasets' => [], 'labels' => []]`
    - Ensure outer `catch (\Throwable $e)` returns the same fallback shape
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 4.2 Add `emptyStateHeading`, `emptyStateDescription`, and `emptyStateIcon` to `PembayaranTerbaruWidget`, `TagihanJatuhTempoWidget`, `TopTunggakanWidget`
    - Add inside each widget's `table()` method: `->emptyStateHeading('Tidak Ada Data')->emptyStateDescription('Belum ada data yang tersedia.')->emptyStateIcon('heroicon-o-document-text')`
    - Add `if (!$response->ok()) { return []; }` inside the `records()` closure in all three TableWidgets
    - _Requirements: 2.2, 2.3, 2.5_

  - [x] 4.3 Write property test for Dashboard Widget fallback data (Property 2)
    - **Property 2: Dashboard widget returns safe fallback data for any API failure**
    - **Validates: Requirements 2.1, 2.2, 2.4, 2.5**
    - Create `tests/Feature/Properties/DashboardWidgetFallbackTest.php` using Pest + Eris
    - Generator: produce combinations of widget class (all 8), failure type (`ConnectionException`, HTTP 4xx, HTTP 5xx), and widget state (random `$selectedTahunAjaranId`: null, 0, positive int)
    - Assert: `StatsOverviewWidget` → `getStats()` returns array of `Stat` objects; `ChartWidget` → `getData()` returns array with `datasets` and `labels` keys; `TableWidget` → `records()` returns empty Collection — no exceptions thrown in any case
    - Run minimum 100 iterations
    - _Requirements: 2.1, 2.2, 2.4, 2.5_

- [x] 5. Fix Portal page error handling
  - [x] 5.1 Refactor `SiswaDashboard` to use `HandlesApiErrors` and remove `$this->error` pattern
    - Import `use App\Livewire\Concerns\HandlesApiErrors;` in `SiswaDashboard.php`
    - In `loadData()`: replace `catch (Exception $e) { $this->error = '...' }` with `catch (ConnectionException $e) { $this->notifyConnectionError(); }` and `catch (\Throwable $e) { $this->notifyUnexpectedError(); }`
    - Add `if (!$response->ok()) { $this->handleApiError($response); return; }` check after each API call
    - Remove the `$this->error` public property and all references to it in the Blade view
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 5.2 Verify and fix `PortalProfilPage` API call wrapping
    - Read `PortalProfilPage.php` and confirm `/users/current` call is inside a `try/catch` with `!$response->ok()` check
    - If missing, apply the same `HandlesApiErrors` pattern: import trait, wrap call, handle `ConnectionException`, handle `!ok()`, handle `\Throwable`
    - Verify null-safe access on nested data (`$response->json('data.tagihan') ?? []`, etc.)
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 6. Apply dark mode Tailwind classes to all custom Blade views
  - [x] 6.1 Add `dark:` classes to all custom HTML elements in `resources/views/livewire/` views
    - For every `bg-white` → add `dark:bg-gray-900`; `bg-gray-50` → `dark:bg-gray-800`; `bg-{color}-100` → `dark:bg-{color}-900/30`
    - For every `text-gray-900`/`text-gray-800` → add `dark:text-white`/`dark:text-gray-100`; `text-gray-600`/`text-gray-700` → `dark:text-gray-300`/`dark:text-gray-400`
    - For every `text-{color}-800`/`text-{color}-700` → add `dark:text-{color}-300`/`dark:text-{color}-400`
    - For every `border-gray-200` → add `dark:border-gray-700`; `ring-gray-950/5` → `dark:ring-white/10`
    - Container divs: apply full pattern `bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm`
    - Files: `siswa-dashboard.blade.php`, `tagihan-card-view.blade.php`, `pembayaran-card-view.blade.php`, `kenaikan-kelas.blade.php`, `data-siswa.blade.php`, `data-kelas.blade.php`, `branch-management.blade.php`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

  - [x] 6.2 Add `dark:` classes to all custom HTML elements in `resources/views/filament/pages/` views
    - Apply the same rule matrix from 6.1
    - Add `dark:hover:bg-gray-700` to interactive hover states; `dark:disabled:bg-gray-800 dark:disabled:text-gray-500` to disabled states; `dark:focus:ring-primary-500` to focus rings
    - Files: `settings.blade.php`, `change-password.blade.php`, any remaining page views with custom HTML
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

- [x] 7. Fix `TagihanCardView::payAction()` form schema pattern
  - [x] 7.1 Replace `->form([...])` with `->schema([...])` in `TagihanCardView::payAction()`
    - Open `app/Livewire/TagihanCardView.php`, locate `payAction()` method
    - Change every `->form([Select::make(...)])` occurrence to `->schema([Select::make(...)])`
    - Confirm no other `->form([` pattern remains in the file
    - _Requirements: 5.1, 5.2, 5.3_

- [x] 8. Verify Dashboard Widget base classes
  - [x] 8.1 Assert all 8 Dashboard Widgets extend correct Filament base classes and have no custom `$view` property
    - Read all 8 widget files: `DashboardStatsWidget`, `KasBulananChart`, `PembayaranBulananChart`, `PembayaranTerbaruWidget`, `StatusTagihanChart`, `TagihanJatuhTempoWidget`, `TopTunggakanWidget`, `TunggakanJenjangChart`
    - Verify `DashboardStatsWidget extends StatsOverviewWidget`, chart widgets extend `ChartWidget`, table widgets extend `TableWidget`
    - Remove any `protected static string $view` property if found; remove any `render()` or `getView()` override if found
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 9. Replace raw HTML in Card View Blade files with Filament components
  - [x] 9.1 Replace raw `<input type="text">` search fields in `tagihan-card-view.blade.php` and `pembayaran-card-view.blade.php`
    - Replace with `<x-filament::input.wrapper><x-filament::input wire:model.live="search" .../></x-filament::input.wrapper>`
    - _Requirements: 7.1, 7.6_

  - [x] 9.2 Replace raw `<select>` filter dropdowns (jenjang, status, per-page) in both card view Blade files
    - Replace with `<x-filament::input.wrapper><x-filament::input.select wire:model.live="...">...</x-filament::input.select></x-filament::input.wrapper>`
    - _Requirements: 7.2, 7.6_

  - [x] 9.3 Replace raw `<button>` primary action buttons ("Bayar", etc.) in both card view Blade files
    - Replace with `<x-filament::button wire:click="..." color="primary">Bayar</x-filament::button>`
    - _Requirements: 7.3, 7.6_

  - [x] 9.4 Replace raw `<span>` status badges in both card view Blade files
    - Replace with `<x-filament::badge color="...">{{ $status }}</x-filament::badge>` using color mapping: Lunas→success, Belum Lunas→warning, Belum Dibayar→danger, Tunai→info, Non-Tunai→gray
    - _Requirements: 7.4, 7.6_

  - [x] 9.5 Replace raw pagination `<button>` elements in both card view Blade files
    - Replace Prev/Next with `<x-filament::button outlined wire:click="previousPage">« Prev</x-filament::button>`
    - Replace page number buttons similarly using `<x-filament::button outlined wire:click="gotoPage({{ $i }})">{{ $i }}</x-filament::button>`
    - _Requirements: 7.5, 7.6_

  - [x] 9.6 Replace raw icon-only `<button>` elements (delete, download kwitansi) in both card view Blade files
    - Replace delete button with `<x-filament::icon-button icon="heroicon-o-trash" color="danger" tooltip="Hapus" wire:click="deleteTagihan({{ $tagihan['id'] }})"/>`
    - Replace download button with `<x-filament::icon-button icon="heroicon-o-arrow-down-tray" color="primary" tooltip="Download Kwitansi" wire:click="downloadKwitansi({{ $tagihan['id'] }})"/>`
    - _Requirements: 7.7, 7.6_

- [x] 10. Fix Login page — move redirect logic from `__construct()` to `mount()`
  - [x] 10.1 Remove `__construct()` and add equivalent logic in `mount()` in `Login.php`
    - Delete the `__construct()` method body that reads `session()->get('data.token')` and redirects
    - Add a `mount(): void` method (or add to existing `mount()` if present) with the same token check: `if (!is_null($token)) { $roles = session()->get('data.roles', []); $target = in_array('siswa', $roles) ? '/tagihan-siswa' : '/data-master-siswa'; $this->redirect(filament()->getUrl() . $target); }`
    - Confirm the class still extends `Filament\Pages\Auth\Login` and has no custom `$view` property
    - _Requirements: 9.1, 9.2, 9.5_

- [x] 11. Refactor `ChangePassword` to use Filament `HasForms` + `InteractsWithForms`
  - [x] 11.1 Implement `HasForms` contract and `InteractsWithForms` trait in `ChangePassword.php`
    - Add `implements HasForms` to class declaration and `use InteractsWithForms;` trait
    - Replace public string properties (`$current_password`, `$new_password`, `$new_password_confirmation`) with `public ?array $data = [];`
    - Define `form(Schema $schema): Schema` method returning a schema with a `Section::make('Ubah Password')` wrapping three `TextInput::make()` fields (`current_password` required, `new_password` required+minLength(8)+confirmed(), `new_password_confirmation` required), with `->statePath('data')`
    - Update `mount()` to call `$this->form->fill()`
    - Update `submit()` to call `$state = $this->form->getState()` and use `$state['current_password']`, `$state['new_password']` for the API call
    - _Requirements: 10.1, 10.2, 10.4, 10.5_

  - [x] 11.2 Update `change-password.blade.php` to use `{{ $this->form }}`
    - Replace all custom `<form>` HTML, `<input>` fields, and label markup with: `<form wire:submit.prevent="submit">{{ $this->form }}<x-filament::button type="submit">Ubah Password</x-filament::button></form>`
    - _Requirements: 10.3, 10.5_

- [x] 12. Checkpoint — UI and error handling
  - Ensure the build passes (`npm run build` in `frontend-v2/`) and PHP validation passes, ask the user if questions arise.

- [x] 13. Implement Database Notification bridge
  - [x] 13.1 Run (or create) Laravel notifications table migration in `frontend-v2/`
    - Check if `database/migrations/*_create_notifications_table.php` exists; if not, run `php artisan notifications:table` and `php artisan migrate`
    - Confirm the `notifications` table has columns: `id` (UUID), `type`, `notifiable_type`, `notifiable_id`, `data` (JSON), `read_at` (nullable timestamp), `created_at`, `updated_at`
    - _Requirements: 11.1, 11.3_

  - [x] 13.2 Create `NotificationSyncService` class
    - Create `app/Services/NotificationSyncService.php`
    - Implement `public static function syncFromApi(array $apiNotifications, int $userId): void`
    - Inside: iterate over `$apiNotifications`, call `DatabaseNotification::updateOrCreate(['id' => 'backend-' . $n['id']], [...])` mapping `title` and `message` into the `data` JSON array, setting `read_at` to `$n['created_at'] ?? now()` when `is_read` is true and `null` when false
    - _Requirements: 11.2, 11.3_

  - [x] 13.3 Write property test for `NotificationSyncService` field mapping (Property 3)
    - **Property 3: Notification mapping preserves fields and converts is_read correctly**
    - **Validates: Requirements 11.2, 11.3**
    - Create `tests/Feature/Properties/NotificationMappingTest.php` using Pest + Eris
    - Generator: produce arbitrary `{id: int, title: string, message: string, is_read: bool, created_at: string}` arrays (including empty strings, Unicode, very long strings)
    - Assert: mapped `data->title` equals input `title`; mapped `data->message` equals input `message`; `is_read = true` ⟹ `read_at` is non-null; `is_read = false` ⟹ `read_at` is null
    - Run minimum 100 iterations
    - _Requirements: 11.2, 11.3_

  - [x] 13.4 Create `NotificationPoller` Livewire component
    - Create `app/Livewire/NotificationPoller.php` with a `render()` that returns `view('livewire.notification-poller')`
    - Add a `poll()` method that calls `ApiService::client()->get('/notifications', ['per_page' => 50])`, on success calls `NotificationSyncService::syncFromApi($response->json('data') ?? [], auth()->id())`, on any exception does nothing (silent fallback)
    - Create `resources/views/livewire/notification-poller.blade.php` with a single `<div wire:poll.30s="poll"></div>`
    - _Requirements: 11.1, 11.2, 11.6_

  - [x] 13.5 Register `->databaseNotifications()` and embed `NotificationPoller` in `AdminPanelProvider`
    - Add `->databaseNotifications()` and `->databaseNotificationsPolling('30s')` to the panel configuration chain in `AdminPanelProvider.php`
    - Add a `->renderHook(PanelsRenderHook::BODY_START, fn() => Blade::render('<livewire:notification-poller />'))` to embed the poller on every admin page
    - _Requirements: 11.1, 11.4, 11.5_

- [x] 14. Apply native Filament table features across all Table components
  - [x] 14.1 Add `emptyStateHeading`, `emptyStateDescription`, `emptyStateIcon` to all Table components missing any of the three
    - Check each of the 13 Table components plus `KenaikanKelas` for missing empty-state config
    - Add `->emptyStateHeading('Tidak Ada Data')->emptyStateDescription('Belum ada data yang tersedia.')->emptyStateIcon('heroicon-o-document-text')` where absent
    - _Requirements: 12.1_

  - [x] 14.2 Add `->paginated([5, 10, 25, 50])` and `->defaultPaginationPageOption(10)` to all Table components
    - Apply to all Table components that do not already have these configured
    - _Requirements: 12.2_

  - [x] 14.3 Add `->toggleable(isToggledHiddenByDefault: true)` to columns 5 and beyond in all Table components with 5+ columns
    - Identify columns beyond the first 4 by definition order in each `table()` method
    - Add `->toggleable(isToggledHiddenByDefault: true)` to each such column
    - _Requirements: 12.3_

  - [x] 14.4 Replace custom Blade categorical filters with native `SelectFilter` components in all applicable Table components
    - For `DataSiswa`, `DataKelas`, `BranchManagement`, `UserManagement`, `RoleManagement`, `TahunAjaranManagement`, `JenisTagihan`, `PengeluaranRequest`: add `->filters([SelectFilter::make('status')..., SelectFilter::make('jenjang')...])` as appropriate
    - Remove any custom Blade filter UI for these fields
    - _Requirements: 12.4, 12.5_

- [x] 15. Remove dead code and unused imports
  - [x] 15.1 Remove all unused `use` statements from PHP files in `frontend-v2/app/`
    - Scan each modified file from tasks 1–14 plus all other files in `app/Livewire/`, `app/Filament/`, `app/Services/`, `app/Providers/` for `use` statements referencing classes not used in the same file
    - Delete each unused import line
    - _Requirements: 13.1_

  - [x] 15.2 Remove all commented-out code blocks from PHP files in `frontend-v2/app/` and `resources/views/`
    - Scan for consecutive lines of commented-out PHP or HTML code (excluding phpDoc, inline explanatory comments, TODO/FIXME markers)
    - Delete each commented-out block
    - _Requirements: 13.2_

  - [x] 15.3 Remove orphan references to deleted classes or files
    - Grep for references to `ThemeToggle`, `DarkModeManager`, `Pengeluaran` (old CRUD), `ProfilePage`, `TransaksiPengeluaran`, `HasJenjangSubNavigation` in `frontend-v2/app/` and `resources/views/`
    - Remove any remaining `use` statements, class instantiations, or Blade embeds pointing to these deleted targets
    - _Requirements: 13.3_

  - [x] 15.4 Verify frontend build and PHP syntax after cleanup
    - Run `php artisan view:clear && php artisan config:clear && php artisan route:clear` from the `frontend-v2/` directory; confirm exit code 0
    - Run `npm run build` from the `frontend-v2/` directory; confirm exit code 0
    - _Requirements: 13.4, 13.5_

- [x] 16. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, run `php artisan test` from `frontend-v2/`, fix any failing test, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP delivery
- Property tests (1.4, 4.3, 13.3) require the Eris package (`composer require --dev giorgiosironi/eris`) — install before running
- All work is confined to `frontend-v2/`; the backend is treated as an immutable external service
- Checkpoints at tasks 3, 12, and 16 ensure incremental validation across the three main phases
- The `HandlesApiErrors` trait already exists at `app/Livewire/Concerns/HandlesApiErrors.php`
- Dashboard Widgets intentionally do NOT use the `HandlesApiErrors` trait (they extend Filament base classes that don't support Livewire traits) — each widget keeps its own inline `try/catch`
- For the notification bridge (task 13), the local `notifications` table is synced from the backend; Filament reads this table natively via `->databaseNotifications()`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "7.1", "8.1", "10.1"] },
    { "id": 1, "tasks": ["1.4", "2.1", "4.1", "5.1", "5.2", "6.1", "6.2"] },
    { "id": 2, "tasks": ["2.2", "4.2", "4.3", "9.1", "9.2", "11.1", "13.1"] },
    { "id": 3, "tasks": ["9.3", "9.4", "9.5", "9.6", "11.2", "13.2", "14.1"] },
    { "id": 4, "tasks": ["13.3", "13.4", "14.2", "14.3", "14.4"] },
    { "id": 5, "tasks": ["13.5", "15.1", "15.2", "15.3"] },
    { "id": 6, "tasks": ["15.4"] }
  ]
}
```
