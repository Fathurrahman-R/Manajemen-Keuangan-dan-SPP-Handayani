# Filament Pages

<cite>
**Referenced Files in This Document**
- [AdminPanelProvider.php](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php)
- [NavigationConfig.php](file://frontend-v2/app/Config/NavigationConfig.php)
- [handayani.php](file://frontend-v2/config/handayani.php)
- [filament-api-login.php](file://frontend-v2/config/filament-api-login.php)
- [DashboardPage.php](file://frontend-v2/app/Filament/Pages/DashboardPage.php)
- [HasPeriodFilter.php](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php)
- [dashboard.blade.php](file://frontend-v2/resources/views/filament/pages/dashboard.blade.php)
- [PortalBerandaPage.php](file://frontend-v2/app/Filament/Portal/Pages/PortalBerandaPage.php)
- [beranda.blade.php](file://frontend-v2/resources/views/filament/portal/pages/beranda.blade.php)
- [SiswaDashboard.php](file://frontend-v2/app/Livewire/SiswaDashboard.php)
- [siswa-dashboard.blade.php](file://frontend-v2/resources/views/livewire/siswa-dashboard.blade.php)
</cite>

## Update Summary
**Changes Made**
- Updated Dashboard page to disable 'all periods' option for better data accuracy
- Enhanced academic year filter with session state management via updatedSelectedTahunAjaranId() method
- Removed child selection dropdown for wali users in portal interface
- Improved student portal interface with enhanced period filtering
- Added comprehensive period filter trait with session persistence

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)
10. [Appendices](#appendices)

## Introduction
This document explains how Filament pages are structured, configured, and integrated within the project's admin panel and portal. It covers page layout configuration, navigation integration with permission-based access control, routing conventions, Livewire component interactions, complex workflows (tabs, multi-step forms), breadcrumb navigation, responsive design considerations, page-specific actions, modal dialogs, and external resource loading patterns. Recent enhancements include improved dashboard functionality with disabled 'all periods' option, enhanced academic year filtering with session state management, and streamlined student portal interface.

## Project Structure
The Filament application is organized around a central Panel Provider that configures authentication, SPA behavior, breadcrumbs, discovery of pages and widgets, custom navigation groups, branding, middleware, and render hooks. Pages are discovered automatically from the app/Filament/Pages directory and can be paired with Blade views for custom layouts. Portal pages follow a similar pattern under a dedicated namespace. The system now includes enhanced period filtering capabilities through the HasPeriodFilter trait.

```mermaid
graph TB
AP["AdminPanelProvider<br/>configures panel, auth, nav, branding"] --> NAV["NavigationBuilder<br/>groups & items"]
AP --> PAGES["Filament Pages<br/>discovered from App\\Filament\\Pages"]
AP --> WIDGETS["Widgets<br/>AccountWidget + others"]
AP --> THEME["Vite Theme<br/>resources/css/filament/admin/theme.css"]
AP --> MIDDLEWARE["Auth & HTTP Middleware"]
NAV --> ITEMS["NavigationItem per feature<br/>with visibility & active checks"]
PAGES --> VIEWS["Blade Views<br/>x-filament-panels::page"]
PAGES --> LW["Livewire Components<br/>tables, cards, forms"]
PAGES --> PERIOD["HasPeriodFilter Trait<br/>academic year filtering"]
PERIOD --> SESSION["Session State Management<br/>selected_tahun_ajaran_id"]
```

**Diagram sources**
- [AdminPanelProvider.php:53-134](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L53-L134)
- [AdminPanelProvider.php:141-191](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L141-L191)
- [AdminPanelProvider.php:234-401](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L234-L401)
- [HasPeriodFilter.php:1-166](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L1-166)

**Section sources**
- [AdminPanelProvider.php:53-134](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L53-L134)
- [AdminPanelProvider.php:141-191](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L141-L191)
- [AdminPanelProvider.php:234-401](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L234-L401)

## Core Components
- Admin Panel Provider: Centralizes panel configuration including login/password reset pages, SPA mode, breadcrumbs, widget registration, theme, branding, middleware, and render hooks.
- Navigation Configuration: A centralized class defines navigation groups, labels, icons, and which pages support jenjang-based sub-navigation.
- Period Filter System: Enhanced academic year filtering with session state management and configurable 'all periods' options.
- Feature Flags: Environment-driven toggles for SPA loading, custom navigation, profile migration, and Midtrans integration.
- External API Login Config: Settings for external authentication URL, timeout, and failure logging.

Key responsibilities:
- Page discovery and routing via Filament's auto-discovery mechanism.
- Permission-aware navigation grouping and item visibility.
- Branding and theming injection into the panel.
- Integration points for notifications and UI enhancements via render hooks.
- Academic year period filtering with session persistence.

**Section sources**
- [AdminPanelProvider.php:53-134](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L53-L134)
- [NavigationConfig.php:11-48](file://frontend-v2/app/Config/NavigationConfig.php#L11-L48)
- [handayani.php:14-29](file://frontend-v2/config/handayani.php#L14-L29)
- [filament-api-login.php:15-39](file://frontend-v2/config/filament-api-login.php#L15-L39)
- [HasPeriodFilter.php:1-166](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L1-166)

## Architecture Overview
The system uses a single default Filament panel at root path with SPA enabled and breadcrumbs turned on. Authentication is handled by custom login and password reset pages. The sidebar navigation is built dynamically based on permissions and configuration. Pages are discovered and can include custom Blade templates to host Livewire components for tables, card views, and forms. The enhanced architecture now includes sophisticated period filtering with session state management.

```mermaid
sequenceDiagram
participant Browser as "Browser"
participant Panel as "AdminPanelProvider"
participant Nav as "NavigationBuilder"
participant Page as "Filament Page"
participant Period as "HasPeriodFilter"
participant Session as "Session Store"
participant View as "Blade View"
participant LW as "Livewire Component"
Browser->>Panel : Request "/"
Panel->>Nav : Build navigation groups/items
Nav-->>Panel : Groups with visible items
Panel-->>Browser : HTML with SPA assets
Browser->>Page : Navigate to page route
Page->>Period : Initialize period filter
Period->>Session : Load selected_tahun_ajaran_id
Session-->>Period : Return stored value or null
Page->>View : Render x-filament-panels : : page
View->>LW : Mount table/form/card
LW-->>View : Rendered content
View-->>Browser : Final page HTML
```

**Diagram sources**
- [AdminPanelProvider.php:53-134](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L53-L134)
- [AdminPanelProvider.php:141-191](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L141-L191)
- [HasPeriodFilter.php:26-61](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L26-61)

## Detailed Component Analysis

### Enhanced Dashboard Page
The dashboard page has been significantly enhanced with improved period filtering capabilities. The 'all periods' option is now disabled to ensure data accuracy for metrics like outstanding payments and billing status that don't make sense across multiple periods.

**Updated** Enhanced with disabled 'all periods' option and session state management

```mermaid
classDiagram
class DashboardPage {
+bool allowAllPeriodsOption = false
+mount() void
+updatedSelectedTahunAjaranId(value) void
+getHeaderActions() array
}
class HasPeriodFilter {
+?int selectedTahunAjaranId
+array tahunAjaranOptions
+mountHasPeriodFilter(allowAllPeriodsOption) void
+updatedSelectedTahunAjaranId(value) void
+loadTahunAjaranOptions() void
}
DashboardPage --> HasPeriodFilter : "uses"
```

**Diagram sources**
- [DashboardPage.php:10-62](file://frontend-v2/app/Filament/Pages/DashboardPage.php#L10-62)
- [HasPeriodFilter.php:8-166](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L8-166)

**Section sources**
- [DashboardPage.php:10-62](file://frontend-v2/app/Filament/Pages/DashboardPage.php#L10-62)
- [dashboard.blade.php:1-71](file://frontend-v2/resources/views/filament/pages/dashboard.blade.php#L1-71)

### Period Filter System
The new HasPeriodFilter trait provides comprehensive academic year filtering with session state management. It supports configurable 'all periods' options, automatic fallback to active periods, and seamless integration with existing Livewire components.

**New** Comprehensive period filtering with session persistence

```mermaid
flowchart TD
Start(["Component Mount"]) --> CheckAllow{"Check allowAllPeriodsOption"}
CheckAllow --> |true| SetNull["Set selectedTahunAjaranId = null"]
CheckAllow --> |false| SetAktif["Set selectedTahunAjaranId = getAktifId()"]
SetNull --> LoadOptions["Load Tahun Ajaran Options"]
SetAktif --> LoadOptions
LoadOptions --> ValidateSession["Validate Session Value"]
ValidateSession --> |Valid| UseSession["Use Stored Value"]
ValidateSession --> |Invalid| Fallback["Fallback to Active Period"]
UseSession --> End(["Ready"])
Fallback --> End
```

**Diagram sources**
- [HasPeriodFilter.php:26-61](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L26-61)

**Section sources**
- [HasPeriodFilter.php:1-166](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L1-166)

### Enhanced Student Portal Interface
The student portal has been streamlined by removing the child selection dropdown for wali users while maintaining period filtering capabilities. This simplifies the user interface while preserving essential functionality.

**Updated** Removed child selection dropdown for wali users, enhanced period filtering

```mermaid
sequenceDiagram
participant User as "User"
participant Portal as "PortalBerandaPage"
participant Filter as "HasPeriodFilter"
participant API as "API Service"
User->>Portal : Access portal beranda
Portal->>Filter : mountHasPeriodFilter()
Filter->>API : GET /tahun-ajaran
API-->>Filter : Return period options
Filter->>Portal : Initialize with selected period
Portal->>User : Render simplified interface
```

**Diagram sources**
- [PortalBerandaPage.php:11-84](file://frontend-v2/app/Filament/Portal/Pages/PortalBerandaPage.php#L11-84)
- [beranda.blade.php:1-61](file://frontend-v2/resources/views/filament/portal/pages/beranda.blade.php#L1-61)

**Section sources**
- [PortalBerandaPage.php:11-84](file://frontend-v2/app/Filament/Portal/Pages/PortalBerandaPage.php#L11-84)
- [beranda.blade.php:1-61](file://frontend-v2/resources/views/filament/portal/pages/beranda.blade.php#L1-61)
- [SiswaDashboard.php:1-77](file://frontend-v2/app/Livewire/SiswaDashboard.php#L1-77)
- [siswa-dashboard.blade.php:1-147](file://frontend-v2/resources/views/livewire/siswa-dashboard.blade.php#L1-147)

### Custom Tabbed Interface Example
A tabbed interface is implemented in a Blade view that renders tabs and delegates content to a Livewire table. Tabs are stateful via Livewire and show a loading indicator during transitions. An event listener opens external URLs in new tabs.

```mermaid
flowchart TD
Start(["Render Tabbed Page"]) --> Tabs["Loop through $this->getTabs()<br/>render buttons"]
Tabs --> Click{"User clicks tab?"}
Click --> |Yes| SetTab["wire:click setTab(key)<br/>show loading"]
SetTab --> LoadContent["Mount table component"]
LoadContent --> Render["Render table inside page"]
Click --> |No| Idle["Keep current tab"]
Render --> End(["Page Ready"])
Idle --> End
```

**Section sources**
- [manajemen-akun-siswa.blade.php:1-36](file://frontend-v2/resources/views/filament/pages/manajemen-akun-siswa.blade.php#L1-L36)

### Simple Page with Livewire Table
A minimal page wraps a Livewire component directly inside the standard Filament page shell. This pattern is used for straightforward list or management screens.

```mermaid
sequenceDiagram
participant Router as "Filament Router"
participant Page as "UserManagement Page"
participant View as "user-management.blade.php"
participant LW as "livewire : user-management"
Router->>Page : Resolve route
Page->>View : Render page shell
View->>LW : Mount component
LW-->>View : Render table
View-->>Router : Return response
```

**Section sources**
- [user-management.blade.php:1-3](file://frontend-v2/resources/views/filament/pages/user-management.blade.php#L1-L3)

### Portal Page Pattern
Portal pages follow the same pattern: a Blade view wrapping a Livewire table. This ensures consistent UX across admin and portal panels.

```mermaid
sequenceDiagram
participant Portal as "Portal Panel"
participant Page as "Riwayat Pembayaran Page"
participant View as "riwayat-pembayaran.blade.php"
participant LW as "Table Component"
Portal->>Page : Navigate to riwayat-pembayaran
Page->>View : Render page shell
View->>LW : Mount table
LW-->>View : Render rows
View-->>Portal : Return response
```

**Section sources**
- [riwayat-pembayaran.blade.php:1-3](file://frontend-v2/resources/views/filament/portal/pages/riwayat-pembayaran.blade.php#L1-L3)

### Permission-Based Access Control
- Navigation groups are hidden if the user lacks any permission within the group.
- Individual items use visible checks tied to specific permissions.
- Active states are determined by route names and query parameters (e.g., jenjang).

```mermaid
flowchart TD
Start(["Build Navigation"]) --> CheckGroup["Check hasAnyInGroup(group)"]
CheckGroup --> |False| SkipGroup["Skip group"]
CheckGroup --> |True| AddItems["Add items with visible checks"]
AddItems --> IsActive["Set isActiveWhen(route/query)"]
IsActive --> End(["Groups & Items Ready"])
SkipGroup --> End
```

**Section sources**
- [AdminPanelProvider.php:141-191](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L141-L191)
- [AdminPanelProvider.php:234-401](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L234-L401)
- [NavigationConfig.php:11-48](file://frontend-v2/app/Config/NavigationConfig.php#L11-L48)

### Routing and Home URL
- The default panel sets a home URL pointing to a dashboard page route name.
- Breadcrumbs are enabled globally for the panel.
- Pages are auto-discovered from the specified namespace and directory.

```mermaid
flowchart TD
Init["Panel Initialization"] --> Home["Set homeUrl('dashboard-page')"]
Home --> Breadcrumbs["Enable breadcrumbs(true)"]
Breadcrumbs --> Discover["Discover pages in App\\Filament\\Pages"]
Discover --> Routes["Routes resolved by Filament"]
```

**Section sources**
- [AdminPanelProvider.php:53-68](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L53-L68)

### Layout Configuration and Theming
- Dark mode is enabled.
- Vite theme path is configured for custom styles.
- Branding (name, logo, favicon, primary color) is applied conditionally via BrandingService.

```mermaid
classDiagram
class AdminPanelProvider {
+brandName(resolveBrandName())
+brandLogo(resolveBrandLogo())
+favicon(resolveFavicon())
+colors(resolvePanelColors())
}
```

**Section sources**
- [AdminPanelProvider.php:102-106](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L102-106)
- [AdminPanelProvider.php:425-477](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L425-477)

### Interaction with Livewire Components
- Pages embed Livewire components directly in Blade views.
- Tabbed interfaces manage state via Livewire methods and events.
- Tables and card views are mounted within the page shell.
- Period filtering integrates seamlessly with Livewire state management.

```mermaid
sequenceDiagram
participant Page as "Filament Page"
participant View as "Blade Template"
participant LW as "Livewire Component"
Page->>View : Render x-filament-panels : : page
View->>LW : Mount <livewire : ... />
LW-->>View : Rendered markup
View-->>Page : Complete page
```

**Section sources**
- [user-management.blade.php:1-3](file://frontend-v2/resources/views/filament/pages/user-management.blade.php#L1-L3)
- [manajemen-akun-siswa.blade.php:1-36](file://frontend-v2/resources/views/filament/pages/manajemen-akun-siswa.blade.php#L1-L3)

### Complex Workflows: Multi-Step Forms
While not shown explicitly in the referenced files, multi-step forms can be implemented by:
- Using Livewire properties to track step state.
- Rendering conditional sections in Blade views.
- Validating inputs per step and advancing state accordingly.
- Persisting intermediate data in session or database as needed.

### Modal Dialogs
Modal dialogs can be integrated using Wire Elements Modal or Filament Actions. Typical patterns:
- Trigger modals from table actions or form buttons.
- Use wire:click to open modals and wire:model to bind data.
- Close modals after successful operations and show notifications.

### External Resource Loading Patterns
- External API login settings define endpoint URL, timeout, and failure logging.
- Logout action attempts an API call before clearing session and redirecting.
- Notifications poller is injected via render hooks.

```mermaid
sequenceDiagram
participant User as "User"
participant Panel as "AdminPanelProvider"
participant API as "External Auth API"
participant Session as "Session"
User->>Panel : Click Logout
Panel->>API : DELETE /logout
API-->>Panel : Response (ignore errors)
Panel->>Session : Clear & invalidate
Panel-->>User : Redirect to login
```

**Section sources**
- [AdminPanelProvider.php:74-89](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L74-89)
- [filament-api-login.php:15-39](file://frontend-v2/config/filament-api-login.php#L15-39)

## Dependency Analysis
The following diagram shows key dependencies between the panel provider, navigation configuration, period filter system, and feature flags.

```mermaid
graph TB
APP["AppServiceProvider"] --> PANEL["AdminPanelProvider"]
PANEL --> NAVCFG["NavigationConfig"]
PANEL --> CFG["handayani.php"]
PANEL --> LOGINCFG["filament-api-login.php"]
PANEL --> VIEWS["Blade Views"]
VIEWS --> LW["Livewire Components"]
LW --> PERIOD["HasPeriodFilter Trait"]
PERIOD --> SESSION["Session State"]
PERIOD --> API["ApiService"]
```

**Diagram sources**
- [AdminPanelProvider.php:53-134](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L53-L134)
- [NavigationConfig.php:11-48](file://frontend-v2/app/Config/NavigationConfig.php#L11-L48)
- [handayani.php:14-29](file://frontend-v2/config/handayani.php#L14-L29)
- [filament-api-login.php:15-39](file://frontend-v2/config/filament-api-login.php#L15-39)
- [HasPeriodFilter.php:1-166](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L1-166)

**Section sources**
- [AdminPanelProvider.php:53-134](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L53-L134)
- [NavigationConfig.php:11-48](file://frontend-v2/app/Config/NavigationConfig.php#L11-L48)
- [handayani.php:14-29](file://frontend-v2/config/handayani.php#L14-L29)
- [filament-api-login.php:15-39](file://frontend-v2/config/filament-api-login.php#L15-39)

## Performance Considerations
- Enable SPA mode for smoother navigation and reduced full reloads.
- Use pagination in tables and limit data fetched per request.
- Defer heavy computations to background jobs where applicable.
- Cache expensive queries and avoid N+1 problems in Livewire components.
- Keep navigation visibility checks lightweight; rely on precomputed permissions.
- Leverage session state for period filters to reduce API calls.
- Implement proper caching strategies for academic year options.

## Troubleshooting Guide
Common issues and resolutions:
- Navigation items not visible: Verify permissions and group membership; ensure hasAnyInGroup returns true for the group.
- Active state not updating: Confirm isActiveWhen matches route names and query parameters.
- Breadcrumbs missing: Ensure breadcrumbs are enabled in panel configuration.
- External logout failing: Check API URL and timeout settings; errors are ignored but session is cleared regardless.
- Tab switching not working: Ensure Livewire method exists and targets match wire:target attributes.
- Period filter not persisting: Check session configuration and ensure updatedSelectedTahunAjaranId method is properly implemented.
- All periods option still showing: Verify allowAllPeriodsOption property is set to false in the page class.
- Child dropdown not appearing: Check if wali user has multiple children in session data.

**Section sources**
- [AdminPanelProvider.php:141-191](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L141-L191)
- [AdminPanelProvider.php:74-89](file://frontend-v2/app/Providers/Filament/AdminPanelProvider.php#L74-L89)
- [manajemen-akun-siswa.blade.php:1-36](file://frontend-v2/resources/views/filament/pages/manajemen-akun-siswa.blade.php#L1-L36)
- [DashboardPage.php:46-49](file://frontend-v2/app/Filament/Pages/DashboardPage.php#L46-L49)
- [HasPeriodFilter.php:63-79](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L63-L79)

## Conclusion
The Filament pages in this project are configured centrally via the Admin Panel Provider, with dynamic, permission-aware navigation and robust integration with Livewire components. Recent enhancements include sophisticated period filtering with session state management, disabled 'all periods' option for dashboard accuracy, and streamlined student portal interface. Custom Blade views enable advanced layouts such as tabbed interfaces, while feature flags and external API configurations provide flexibility for authentication and UI enhancements. Following the patterns outlined here will help maintain consistency, scalability, and performance across admin and portal experiences.

## Appendices

### Responsive Design Considerations
- Use Tailwind utility classes for responsive layouts in Blade views.
- Ensure tables and forms adapt gracefully to mobile screens.
- Test navigation collapse and accessibility on small devices.
- Period filter dropdowns should be responsive and accessible.

### Period Filter Implementation Guide
For implementing period filtering in new pages:
1. Use the HasPeriodFilter trait in your Livewire component or page class.
2. Set allowAllPeriodsOption property to control 'all periods' behavior.
3. Implement updatedSelectedTahunAjaranId method for session persistence.
4. Pass selectedTahunAjaranId to API calls and child components.
5. Use getTahunAjaranSelectComponent() for consistent UI.

**Section sources**
- [HasPeriodFilter.php:1-166](file://frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php#L1-166)
- [DashboardPage.php:10-62](file://frontend-v2/app/Filament/Pages/DashboardPage.php#L10-62)
- [PortalBerandaPage.php:11-84](file://frontend-v2/app/Filament/Portal/Pages/PortalBerandaPage.php#L11-84)