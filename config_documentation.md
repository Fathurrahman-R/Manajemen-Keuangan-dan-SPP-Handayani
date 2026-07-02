# Handayani System & Portal Configuration Documentation

This document explains the central configuration system designed for the **Manajemen Keuangan & SPP Handayani** application. These configurations allow developers and administrators to customize, toggle, and roll out features dynamically.

---

## 🛠️ Central Configuration File

All custom features, layout adjustments, and rollout toggles are defined in:
📄 **[config/handayani.php](file:///d:/First%20Project/handayani/frontend-v2/config/handayani.php)**

### Configuration Keys & Environment Variables

You can configure these settings using standard `.env` variables or by directly editing the configuration file.

| Configuration Key | Env Variable | Default | Description |
|---|---|---|---|
| `handayani.features.portal_enabled` | `HANDAYANI_PORTAL_ENABLED` | `true` | When `true`, the Student/Parent portal is active. When `false`, accessing portal routes immediately returns a `404 Not Found` response. |
| `handayani.features.custom_navigation_enabled` | `HANDAYANI_CUSTOM_NAVIGATION_ENABLED` | `true` | Toggles the 4-group organized custom navigation sidebar in the Admin Panel. If `false`, falls back to Filament's default auto-discovery. |
| `handayani.features.profile_migration_enabled` | `HANDAYANI_PROFILE_MIGRATION_ENABLED` | `true` | Activates the native Filament-based `EditProfile` page in place of the old fallback profile page. |
| `handayani.features.spa_loading_enabled` | `HANDAYANI_SPA_LOADING_ENABLED` | `true` | Toggles Single Page Application (SPA) transitions and prefetching in both panels for instant page loading. |
| `handayani.portal.path` | `HANDAYANI_PORTAL_PATH` | `'portal'` | Defines the URL path prefix for the student/parent portal (e.g., `/portal`). |
| `handayani.portal.breadcrumbs` | `HANDAYANI_PORTAL_BREADCRUMBS` | `false` | Sets whether breadcrumbs should be displayed in the portal top navigation bar. |

---

## 🚀 How to Roll Out Features

### 1. Toggle Student/Parent Portal
- **To Enable (Deploy):**
  Ensure the following is in your `.env` file:
  ```env
  HANDAYANI_PORTAL_ENABLED=true
  HANDAYANI_PORTAL_PATH=portal
  ```
- **To Disable (Rollback):**
  Change the env variable to:
  ```env
  HANDAYANI_PORTAL_ENABLED=false
  ```
  *Note: Disabling the portal has absolutely zero impact on the admin panel. Any attempt to reach `/portal` will safely result in a 404.*

### 2. Toggle Reorganized Sidebar Navigation
- **To Use Reorganized 4-Group Sidebar:**
  ```env
  HANDAYANI_CUSTOM_NAVIGATION_ENABLED=true
  ```
- **To Revert to Default Auto-Discovery:**
  ```env
  HANDAYANI_CUSTOM_NAVIGATION_ENABLED=false
  ```

### 3. Adjust Performance (SPA Prefetching)
- **To Enable Prefetching:**
  ```env
  HANDAYANI_SPA_LOADING_ENABLED=true
  ```
- **To Disable (useful for heavy pages or debugging):**
  ```env
  HANDAYANI_SPA_LOADING_ENABLED=false
  ```

---

## 🎨 Branding & Customization Points

Custom branding (logos, favicons, primary colors, branch name) is resolved dynamically through **`App\Services\BrandingService`**.

1. **Colors:** Resolved from `/app-settings/branding` API endpoint and applied automatically to the panel.
2. **Logos & Icons:** Automatic fallback to default text or asset links if the API does not provide custom files.
