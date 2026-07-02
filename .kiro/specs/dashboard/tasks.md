# Implementation Plan: Dashboard

## Overview

Implementasi fitur Dashboard mencakup backend API endpoints untuk data agregat (summary KPI, chart data, quick lists), service layer dengan caching, cache invalidation via observer, permission seeder, serta frontend Livewire components untuk Admin/Operator dan Siswa/Wali. Semua endpoint bersifat read-only dan menggunakan tabel-tabel yang sudah ada.

## Tasks

- [x] 1. Set up backend foundation (Service, Controller, Routes, Permissions)
  - [x] 1.1 Create DashboardService with helper methods
    - Create `app/Services/DashboardService.php`
    - Implement `resolveTahunAjaranId(?int $tahunAjaranId, int $branchId): ?int` — returns Periode_Aktif when null
    - Implement `getCacheKey(string $endpoint, int $branchId, ?int $tahunAjaranId): string` — format `dashboard:{branch_id}:{tahun_ajaran_id}:{endpoint}`
    - Implement static `invalidateCache(int $branchId): void` — clears all dashboard cache keys for branch
    - Implement static `invalidateKasCache(int $branchId): void` — clears kas-bulanan cache for branch
    - _Requirements: 1.3, 1.4, 13.1_

  - [x] 1.2 Create DashboardController with route registration
    - Create `app/Http/Controllers/DashboardController.php`
    - Define all 9 action methods (summary, chartPembayaranBulanan, chartTunggakanJenjang, chartKasBulanan, chartStatusTagihan, topTunggakan, tagihanJatuhTempo, pembayaranTerbaru, siswaDashboard)
    - Register routes in `routes/api.php` under `/dashboard` prefix with auth:sanctum middleware
    - Apply `permission:view-dashboard` middleware to admin endpoints
    - Apply `permission:view-own-billing` middleware to siswa endpoint
    - _Requirements: 14.1, 14.2, 1.7, 2.6, 3.6, 4.6, 5.6, 6.7, 7.7, 8.6, 12.7_

  - [x] 1.3 Update Permission Seeder
    - Add `view-dashboard` and `view-own-billing` permissions to `RoleAndPermissionSeeder`
    - Assign `view-dashboard` to Admin and Operator roles
    - Assign `view-own-billing` to Siswa and Wali roles
    - _Requirements: 14.3_

  - [x] 1.4 Create DashboardCacheObserver
    - Create `app/Observers/DashboardCacheObserver.php`
    - Implement `created`, `updated`, `deleted` methods that call `DashboardService::invalidateCache()` or `invalidateKasCache()`
    - Register observer for Pembayaran, Tagihan, and Pengeluaran models in `AppServiceProvider::boot()`
    - _Requirements: 13.2, 13.3, 13.6_

- [x] 2. Implement Summary and Chart Endpoints
  - [x] 2.1 Implement getSummary() in DashboardService
    - Calculate total_tagihan (sum of jenis_tagihans.jumlah for matching tagihan)
    - Calculate total_terbayar (sum of pembayaran.jumlah)
    - Calculate total_tunggakan (total_tagihan - total_terbayar)
    - Calculate jumlah_siswa_aktif (count of Siswa with status "Aktif")
    - Calculate jumlah_siswa_menunggak (distinct siswa with tagihan status != "Lunas")
    - Calculate persentase_pelunasan (handle division by zero)
    - Apply caching with 5-minute TTL
    - Scope by branch_id and tahun_ajaran_id
    - Wire to DashboardController::summary()
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [ ]* 2.2 Write property test for Summary Aggregation Correctness
    - **Property 1: Summary Aggregation Correctness**
    - **Validates: Requirements 1.1, 1.6**
    - Create `tests/Feature/Dashboard/SummaryAggregationTest.php`
    - Use factories to generate random Tagihan/Pembayaran amounts
    - Verify total_tagihan, total_terbayar, total_tunggakan, persentase_pelunasan calculations

  - [x] 2.3 Implement getChartPembayaranBulanan() in DashboardService
    - Return 12 objects with bulan (1-12), nama_bulan (Indonesian), total
    - Group Pembayaran by MONTH(tanggal) where tagihan.tahun_ajaran_id matches
    - Return zero for months without data
    - Apply caching and branch/period scoping
    - Wire to DashboardController::chartPembayaranBulanan()
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 2.4 Implement getChartTunggakanJenjang() in DashboardService
    - Group by siswa.jenjang (TK, MI, KB)
    - For each jenjang: sum tagihan nominal, sum pembayaran, calculate tunggakan
    - Include all three jenjang even if no data (values set to zero)
    - Apply caching and branch/period scoping
    - Wire to DashboardController::chartTunggakanJenjang()
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 2.5 Implement getChartKasBulanan() in DashboardService
    - Return 12 objects with bulan, nama_bulan, pemasukan, pengeluaran
    - Pemasukan = Pembayaran grouped by month (via tagihan.tahun_ajaran_id)
    - Pengeluaran = Pengeluaran grouped by month (filtered by TahunAjaran tanggal_mulai/tanggal_selesai range)
    - Return zero for months without data
    - Apply caching and branch/period scoping
    - Wire to DashboardController::chartKasBulanan()
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 2.6 Implement getChartStatusTagihan() in DashboardService
    - Group Tagihan by status (Lunas, Belum Lunas, Belum Dibayar)
    - Return jumlah (count) and persentase for each status
    - Handle empty data (all zeros)
    - Apply caching and branch/period scoping
    - Wire to DashboardController::chartStatusTagihan()
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ]* 2.7 Write property tests for Chart endpoints
    - **Property 4: Monthly Chart Structural Invariant**
    - **Property 5: Tunggakan Per Jenjang Completeness**
    - **Property 6: Status Tagihan Percentage Correctness**
    - **Property 13: Pengeluaran Date Range Filtering**
    - **Validates: Requirements 2.1, 2.4, 3.1, 3.5, 4.1, 4.4, 5.1, 5.5, 4.2**
    - Create `tests/Feature/Dashboard/MonthlyChartStructureTest.php`
    - Create `tests/Feature/Dashboard/TunggakanJenjangTest.php`
    - Create `tests/Feature/Dashboard/StatusTagihanTest.php`
    - Create `tests/Feature/Dashboard/PengeluaranDateFilterTest.php`

- [x] 3. Implement Quick List Endpoints
  - [x] 3.1 Implement getTopTunggakan() in DashboardService
    - Per siswa: calculate total_tagihan - total_terbayar as tunggakan
    - Order by tunggakan DESC, limit 10
    - Exclude siswa with tunggakan <= 0
    - Resolve kelas from SiswaKelas for the selected period
    - Return nis, nama, kelas, jenjang, total_tagihan, total_terbayar, total_tunggakan
    - Apply caching and branch/period scoping
    - Wire to DashboardController::topTunggakan()
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

  - [x] 3.2 Implement getTagihanJatuhTempo() in DashboardService
    - Join with JenisTagihan where jatuh_tempo BETWEEN today AND today+7
    - Filter status != 'Lunas'
    - Order by jatuh_tempo ASC
    - Return kode_tagihan, nama_siswa, nama_jenis_tagihan, jatuh_tempo, jumlah, status
    - Apply caching and branch/period scoping
    - Wire to DashboardController::tagihanJatuhTempo()
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

  - [x] 3.3 Implement getPembayaranTerbaru() in DashboardService
    - Order by tanggal DESC, created_at DESC, limit 5
    - Join with Tagihan, Siswa, JenisTagihan for related names
    - Return kode_pembayaran, nama_siswa, nama_jenis_tagihan, tanggal, metode, jumlah
    - Apply caching and branch/period scoping
    - Wire to DashboardController::pembayaranTerbaru()
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [ ]* 3.4 Write property tests for Quick List endpoints
    - **Property 7: Top Tunggakan Ordering and Filtering**
    - **Property 8: Tagihan Jatuh Tempo Date and Status Filter**
    - **Property 9: Pembayaran Terbaru Recency and Limit**
    - **Validates: Requirements 6.1, 6.2, 6.5, 7.1, 7.5, 8.1**
    - Create `tests/Feature/Dashboard/TopTunggakanTest.php`
    - Create `tests/Feature/Dashboard/TagihanJatuhTempoTest.php`
    - Create `tests/Feature/Dashboard/PembayaranTerbaruTest.php`

- [x] 4. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement Siswa/Wali Dashboard Endpoint
  - [x] 5.1 Implement getSiswaDashboard() in DashboardService
    - Calculate total_tagihan, total_terbayar, total_tunggakan for specific siswa
    - Get tagihan_list filtered by Periode_Aktif
    - Get pembayaran_terbaru (at most 5 records, ordered by tanggal DESC)
    - No caching (personal data, low volume)
    - _Requirements: 12.1, 12.6_

  - [x] 5.2 Implement siswaDashboard() in DashboardController
    - Handle wali user with `siswa_id` parameter — verify siswa belongs to wali
    - Handle wali without `siswa_id` — default to first child by NIS ascending
    - Return HTTP 403 if wali accesses unrelated siswa
    - Scope data to authenticated user's siswa
    - _Requirements: 12.2, 12.3, 12.4, 12.5_

  - [ ]* 5.3 Write property tests for Siswa/Wali endpoint
    - **Property 10: Siswa Personal Data Isolation**
    - **Property 11: Wali-Child Access Control**
    - **Validates: Requirements 11.5, 12.2, 12.3, 12.5**
    - Create `tests/Feature/Dashboard/SiswaDataIsolationTest.php`
    - Create `tests/Feature/Dashboard/WaliAccessControlTest.php`

- [ ] 6. Implement Permission and Security Tests
  - [ ]* 6.1 Write property tests for Permission Enforcement
    - **Property 12: Permission Enforcement**
    - **Validates: Requirements 14.1, 14.2, 14.4**
    - Create `tests/Feature/Dashboard/PermissionEnforcementTest.php`
    - Test users without `view-dashboard` get 403 on all admin endpoints
    - Test users without `view-own-billing` get 403 on siswa endpoint

  - [ ]* 6.2 Write property tests for Branch and Period Isolation
    - **Property 2: Period Filtering Isolation**
    - **Property 3: Branch Data Isolation**
    - **Validates: Requirements 1.2, 1.5, 2.2, 2.5, 3.2, 3.4, 4.2, 4.5, 5.2, 5.4, 6.3, 6.6, 7.2, 7.6, 8.2, 8.5, 14.5**
    - Create `tests/Feature/Dashboard/PeriodFilteringTest.php`
    - Create `tests/Feature/Dashboard/BranchIsolationTest.php`

- [x] 7. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Implement Admin Dashboard Frontend
  - [x] 8.1 Create AdminDashboard Livewire Component
    - Create `frontend-v2/app/Livewire/AdminDashboard.php`
    - Implement `mount()` to load tahunAjaran options and all dashboard data
    - Implement `loadAllData()` to fetch from all API endpoints via ApiService::client()
    - Implement `updatedSelectedTahunAjaranId()` to re-fetch on period change
    - Implement `refresh()` for manual refresh button
    - Handle loading states and error states
    - _Requirements: 9.1, 10.2, 10.3, 10.4_

  - [x] 8.2 Create AdminDashboard Blade Template with KPI Cards
    - Create `frontend-v2/resources/views/livewire/admin-dashboard.blade.php`
    - Add period selector dropdown at top (populated from tahunAjaran options, default Periode_Aktif)
    - Add refresh button
    - Display 6 KPI cards: total tagihan, total terbayar, total tunggakan (Rupiah), jumlah siswa aktif, jumlah siswa menunggak (integer), persentase pelunasan (%)
    - Implement responsive grid: 3-col desktop (≥1024px), 2-col tablet (768-1023px), 1-col mobile (<768px)
    - Add loading indicators and error states per component
    - _Requirements: 9.2, 9.10, 10.1, 10.3, 10.4, 10.5_

  - [x] 8.3 Implement Chart Components with Alpine.js + Chart.js
    - Add Chart.js CDN script to layout
    - Implement bar chart for Pembayaran per Bulan (month names x-axis, total y-axis)
    - Implement donut chart for Tunggakan per Jenjang (jenjang labels, total_tunggakan values)
    - Implement line chart for Pemasukan vs Pengeluaran (two lines, month names x-axis)
    - Implement pie chart for Status Tagihan (status labels, jumlah values)
    - Use Alpine.js x-data/x-init/x-effect for chart initialization and reactive updates
    - Layout: 2x2 grid on desktop
    - _Requirements: 9.3, 9.4, 9.5, 9.6_

  - [x] 8.4 Implement Quick List Tables
    - Add table for top 10 siswa tunggakan terbesar (NIS, Nama, Kelas, Jenjang, Total Tunggakan as Rupiah)
    - Add table for tagihan jatuh tempo 7 hari (Kode Tagihan, Nama Siswa, Jenis Tagihan, Jatuh Tempo, Jumlah as Rupiah, Status)
    - Add list for 5 pembayaran terbaru (Kode Pembayaran, Nama Siswa, Jenis Tagihan, Tanggal, Metode, Jumlah as Rupiah)
    - _Requirements: 9.7, 9.8, 9.9_

  - [x] 8.5 Register Admin Dashboard Route
    - Add route in `frontend-v2/routes/web.php`: GET `/dashboard` → AdminDashboard component
    - Apply `auth` and `permission:view-dashboard` middleware
    - Set as landing page after login for Admin/Operator role
    - _Requirements: 9.1, 14.6_

- [x] 9. Implement Siswa/Wali Dashboard Frontend
  - [x] 9.1 Create SiswaDashboard Livewire Component
    - Create `frontend-v2/app/Livewire/SiswaDashboard.php`
    - Implement `mount()` to load personal dashboard data via ApiService::client()
    - Implement child selector for wali with multiple children
    - Implement `updatedSelectedSiswaId()` to reload for different child
    - Handle loading and error states
    - _Requirements: 11.1, 11.5, 11.6_

  - [x] 9.2 Create SiswaDashboard Blade Template
    - Create `frontend-v2/resources/views/livewire/siswa-dashboard.blade.php`
    - Display child selector dropdown (only visible for wali with multiple children)
    - Display 3 KPI cards: total tagihan pribadi, total terbayar pribadi, total tunggakan pribadi (Rupiah)
    - Display tagihan list with status badges (green=Lunas, yellow=Belum Lunas, red=Belum Dibayar)
    - Display pembayaran terbaru list (5 records: tanggal, jenis tagihan, metode, jumlah as Rupiah)
    - _Requirements: 11.2, 11.3, 11.4, 11.6_

  - [x] 9.3 Register Siswa/Wali Dashboard Route
    - Add route in `frontend-v2/routes/web.php`: GET `/my-dashboard` → SiswaDashboard component
    - Apply `auth` and `permission:view-own-billing` middleware
    - Set as landing page after login for Siswa/Wali role
    - Implement permission-based redirect (no view-dashboard → redirect to siswa dashboard)
    - _Requirements: 11.1, 14.6_

- [x] 10. Implement Cache Refresh and Performance
  - [x] 10.1 Add manual refresh button with cache-bust
    - Implement refresh button in AdminDashboard that forces re-fetch bypassing cache
    - Add `_refresh` timestamp parameter or use dedicated refresh mechanism
    - _Requirements: 13.4_

  - [ ]* 10.2 Write unit tests for caching behavior
    - Test cache population on first call
    - Test cache hit on second call
    - Test cache invalidation when Pembayaran/Tagihan/Pengeluaran changes
    - Test cache fallback when cache driver fails
    - _Requirements: 13.1, 13.2, 13.3, 13.6_

- [x] 11. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The project uses PHP (Laravel) for backend and Laravel + Livewire + Filament for frontend
- Chart.js is loaded via CDN and initialized through Alpine.js (no npm package needed)
- All dashboard endpoints are read-only — no database schema changes required
- Cache strategy uses Laravel Cache with 5-minute TTL per branch+period combination

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.3"] },
    { "id": 1, "tasks": ["1.2", "1.4"] },
    { "id": 2, "tasks": ["2.1", "2.3", "2.4", "2.5", "2.6"] },
    { "id": 3, "tasks": ["2.2", "2.7", "3.1", "3.2", "3.3"] },
    { "id": 4, "tasks": ["3.4", "5.1"] },
    { "id": 5, "tasks": ["5.2", "5.3", "6.1", "6.2"] },
    { "id": 6, "tasks": ["8.1", "9.1"] },
    { "id": 7, "tasks": ["8.2", "8.3", "8.4", "9.2"] },
    { "id": 8, "tasks": ["8.5", "9.3", "10.1"] },
    { "id": 9, "tasks": ["10.2"] }
  ]
}
```
