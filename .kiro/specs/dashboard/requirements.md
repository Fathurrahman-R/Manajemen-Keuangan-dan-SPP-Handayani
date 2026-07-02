# Requirements Document

## Introduction

Fitur ini menyediakan **Dashboard** sebagai halaman utama setelah login, menampilkan ringkasan statistik, grafik, dan metrik kunci terkait tagihan dan pembayaran. Dashboard memberikan visibilitas cepat terhadap kondisi keuangan sekolah per periode tahun ajaran, termasuk:

1. Summary cards (KPI) untuk total tagihan, pembayaran, tunggakan, jumlah siswa aktif, siswa menunggak, dan persentase pelunasan
2. Grafik visualisasi: pembayaran per bulan, tunggakan per jenjang, tren pemasukan vs pengeluaran, dan distribusi status tagihan
3. Quick lists: siswa dengan tunggakan terbesar, tagihan mendekati jatuh tempo, dan pembayaran terbaru
4. Filter periode tahun ajaran untuk melihat data historis
5. Role-based view: Admin/Operator melihat dashboard lengkap, Siswa/Wali melihat ringkasan tagihan pribadi
6. Multi-branch scoping: data dashboard terisolasi per cabang

Fitur ini bergantung pada `periode-tahun-ajaran` (scoping data ke periode) dan `tagihan-card-view` (data tagihan/pembayaran).

## Glossary

- **Dashboard**: Halaman utama aplikasi yang menampilkan ringkasan statistik, grafik, dan daftar cepat terkait tagihan dan pembayaran
- **Backend_API**: Laravel API yang menyediakan endpoint data agregat untuk dashboard
- **Frontend_App**: Aplikasi Laravel + Livewire + Filament yang menampilkan antarmuka dashboard
- **Admin_Operator**: Pengguna dengan role admin atau operator yang melihat dashboard lengkap seluruh siswa dalam branch
- **Siswa_Wali**: Pengguna dengan role siswa atau wali yang melihat dashboard ringkasan tagihan pribadi
- **Branch**: Cabang sekolah; semua data dashboard di-scope ke branch pengguna yang terautentikasi
- **Periode_Aktif**: TahunAjaran dengan status "Aktif" untuk branch tertentu; menjadi default filter dashboard
- **TahunAjaran**: Entitas periode tahun ajaran yang menjadi scope data tagihan dan pembayaran
- **Tagihan**: Record kewajiban pembayaran siswa, memiliki status "Lunas", "Belum Lunas", atau "Belum Dibayar"
- **Pembayaran**: Record transaksi pembayaran terhadap tagihan, memiliki tanggal, metode, dan jumlah
- **Pengeluaran**: Record pengeluaran kas sekolah, memiliki uraian, jumlah, dan tanggal
- **Tunggakan**: Selisih antara total nominal tagihan dan total pembayaran yang sudah diterima
- **Jenjang**: Tingkat pendidikan siswa (TK, MI, atau KB)
- **KPI_Card**: Komponen visual berupa kartu yang menampilkan satu metrik ringkasan dengan label dan nilai
- **Summary_Data**: Kumpulan data agregat yang dihitung dari tagihan, pembayaran, dan siswa untuk ditampilkan di KPI_Card
- **Chart_Data**: Kumpulan data agregat yang diformat untuk ditampilkan dalam bentuk grafik

## Requirements

### Requirement 1: Endpoint Summary Data (KPI)

**User Story:** Sebagai Admin_Operator, saya ingin mendapatkan data ringkasan tagihan dan pembayaran dalam satu endpoint, sehingga dashboard dapat menampilkan KPI cards dengan cepat.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/summary` that returns a JSON object containing: total_tagihan (sum of jumlah from all JenisTagihan linked to Tagihan records), total_terbayar (sum of jumlah from all Pembayaran records), total_tunggakan (total_tagihan minus total_terbayar), jumlah_siswa_aktif (count of Siswa with status "Aktif"), jumlah_siswa_menunggak (count of distinct Siswa who have at least one Tagihan with status not equal to "Lunas"), and persentase_pelunasan (total_terbayar divided by total_tagihan multiplied by 100, rounded to two decimal places)
2. WHEN the `/api/dashboard/summary` endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter all Tagihan and Pembayaran calculations to only include records associated with the specified TahunAjaran
3. WHEN the `/api/dashboard/summary` endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
4. IF no Periode_Aktif exists for the authenticated user's branch and no `tahun_ajaran_id` parameter is provided, THEN THE Backend_API SHALL return a JSON object with all numeric values set to zero
5. THE Backend_API SHALL scope all summary calculations to the authenticated user's branch_id
6. IF total_tagihan equals zero, THEN THE Backend_API SHALL return persentase_pelunasan as zero to avoid division by zero
7. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 2: Endpoint Chart Data Pembayaran Per Bulan

**User Story:** Sebagai Admin_Operator, saya ingin melihat grafik pembayaran per bulan, sehingga saya dapat memantau tren penerimaan pembayaran sepanjang tahun ajaran.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/charts/pembayaran-bulanan` that returns a JSON array of 12 objects, each containing: bulan (month number 1-12), nama_bulan (Indonesian month name), and total (sum of Pembayaran.jumlah for that month)
2. WHEN the endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter Pembayaran records to only include those linked to Tagihan associated with the specified TahunAjaran
3. WHEN the endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
4. THE Backend_API SHALL return total as zero for months that have no Pembayaran records
5. THE Backend_API SHALL scope all calculations to the authenticated user's branch_id
6. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 3: Endpoint Chart Data Tunggakan Per Jenjang

**User Story:** Sebagai Admin_Operator, saya ingin melihat distribusi tunggakan berdasarkan jenjang pendidikan, sehingga saya dapat mengidentifikasi jenjang mana yang memiliki tunggakan terbesar.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/charts/tunggakan-jenjang` that returns a JSON array of objects, each containing: jenjang (string: "TK", "MI", or "KB"), total_tagihan (sum of nominal tagihan for siswa in that jenjang), total_terbayar (sum of pembayaran for siswa in that jenjang), and total_tunggakan (total_tagihan minus total_terbayar)
2. WHEN the endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter calculations to only include Tagihan associated with the specified TahunAjaran
3. WHEN the endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
4. THE Backend_API SHALL scope all calculations to the authenticated user's branch_id
5. IF a jenjang has no Tagihan records for the selected period, THEN THE Backend_API SHALL include that jenjang in the response with total_tagihan, total_terbayar, and total_tunggakan all set to zero
6. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 4: Endpoint Chart Data Pemasukan vs Pengeluaran Per Bulan

**User Story:** Sebagai Admin_Operator, saya ingin melihat tren pemasukan (pembayaran) dibandingkan pengeluaran per bulan, sehingga saya dapat memantau arus kas sekolah.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/charts/kas-bulanan` that returns a JSON array of 12 objects, each containing: bulan (month number 1-12), nama_bulan (Indonesian month name), pemasukan (sum of Pembayaran.jumlah for that month), and pengeluaran (sum of Pengeluaran.jumlah for that month)
2. WHEN the endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter Pembayaran records to only include those linked to Tagihan associated with the specified TahunAjaran, and SHALL filter Pengeluaran records by tanggal falling within the tanggal_mulai and tanggal_selesai range of the specified TahunAjaran
3. WHEN the endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
4. THE Backend_API SHALL return pemasukan and pengeluaran as zero for months that have no records
5. THE Backend_API SHALL scope Pembayaran calculations to the authenticated user's branch_id via Tagihan.branch_id, and scope Pengeluaran calculations to the authenticated user's branch_id via Pengeluaran.branch_id
6. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 5: Endpoint Chart Data Status Tagihan

**User Story:** Sebagai Admin_Operator, saya ingin melihat distribusi status tagihan (Lunas, Belum Lunas, Belum Dibayar), sehingga saya dapat memahami proporsi tagihan yang sudah dan belum diselesaikan.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/charts/status-tagihan` that returns a JSON array of three objects, each containing: status (string: "Lunas", "Belum Lunas", or "Belum Dibayar"), jumlah (count of Tagihan records with that status), and persentase (jumlah divided by total Tagihan count multiplied by 100, rounded to two decimal places)
2. WHEN the endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter Tagihan records to only include those associated with the specified TahunAjaran
3. WHEN the endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
4. THE Backend_API SHALL scope all calculations to the authenticated user's branch_id
5. IF no Tagihan records exist for the selected period, THEN THE Backend_API SHALL return all three status objects with jumlah set to zero and persentase set to zero
6. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 6: Endpoint Quick List Siswa Tunggakan Terbesar

**User Story:** Sebagai Admin_Operator, saya ingin melihat daftar siswa dengan tunggakan terbesar, sehingga saya dapat memprioritaskan penagihan.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/top-tunggakan` that returns a JSON array of at most 10 objects, each containing: nis (string), nama (string), kelas (string, resolved from SiswaKelas for the selected period), jenjang (string), total_tagihan (sum of nominal tagihan for that siswa), total_terbayar (sum of pembayaran for that siswa), and total_tunggakan (total_tagihan minus total_terbayar)
2. THE Backend_API SHALL order the results by total_tunggakan descending and limit to 10 records
3. WHEN the endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter calculations to only include Tagihan associated with the specified TahunAjaran
4. WHEN the endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
5. THE Backend_API SHALL exclude siswa with total_tunggakan equal to zero or less from the results
6. THE Backend_API SHALL scope all calculations to the authenticated user's branch_id
7. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 7: Endpoint Quick List Tagihan Jatuh Tempo

**User Story:** Sebagai Admin_Operator, saya ingin melihat tagihan yang akan jatuh tempo dalam 7 hari ke depan, sehingga saya dapat mengingatkan siswa/wali untuk segera membayar.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/tagihan-jatuh-tempo` that returns a JSON array of Tagihan records where the associated JenisTagihan.jatuh_tempo falls within the next 7 calendar days from the current server date, and the Tagihan status is not "Lunas"
2. WHEN the endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter Tagihan records to only include those associated with the specified TahunAjaran
3. WHEN the endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
4. THE Backend_API SHALL return each record containing: kode_tagihan (string), nama_siswa (string), nama_jenis_tagihan (string), jatuh_tempo (date string), jumlah (numeric), and status (string)
5. THE Backend_API SHALL order the results by jatuh_tempo ascending
6. THE Backend_API SHALL scope all results to the authenticated user's branch_id
7. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 8: Endpoint Quick List Pembayaran Terbaru

**User Story:** Sebagai Admin_Operator, saya ingin melihat 5 pembayaran terbaru, sehingga saya dapat memantau aktivitas pembayaran terkini.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/pembayaran-terbaru` that returns a JSON array of at most 5 Pembayaran records ordered by tanggal descending, then by created_at descending
2. WHEN the endpoint is called with a `tahun_ajaran_id` query parameter, THE Backend_API SHALL filter Pembayaran records to only include those linked to Tagihan associated with the specified TahunAjaran
3. WHEN the endpoint is called without a `tahun_ajaran_id` query parameter, THE Backend_API SHALL default to filtering by the Periode_Aktif of the authenticated user's branch
4. THE Backend_API SHALL return each record containing: kode_pembayaran (string), nama_siswa (string), nama_jenis_tagihan (string), tanggal (date string), metode (string), and jumlah (numeric)
5. THE Backend_API SHALL scope all results to the authenticated user's branch_id via Tagihan.branch_id
6. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 9: Dashboard Frontend untuk Admin/Operator

**User Story:** Sebagai Admin_Operator, saya ingin melihat dashboard lengkap dengan KPI cards, grafik, dan quick lists setelah login, sehingga saya dapat memantau kondisi keuangan sekolah secara menyeluruh.

#### Acceptance Criteria

1. THE Frontend_App SHALL display the dashboard as the landing page after successful login for users with Admin_Operator role
2. THE Frontend_App SHALL display six KPI_Card components showing: total tagihan (formatted as Rupiah currency), total terbayar (formatted as Rupiah currency), total tunggakan (formatted as Rupiah currency), jumlah siswa aktif (integer), jumlah siswa menunggak (integer), and persentase pelunasan (percentage with two decimal places)
3. THE Frontend_App SHALL display a bar chart visualizing pembayaran per bulan with month names on the x-axis and total amount on the y-axis
4. THE Frontend_App SHALL display a donut chart visualizing tunggakan per jenjang with jenjang labels and total_tunggakan values
5. THE Frontend_App SHALL display a line chart visualizing pemasukan vs pengeluaran per bulan with two lines (pemasukan and pengeluaran) and month names on the x-axis
6. THE Frontend_App SHALL display a pie chart visualizing distribusi status tagihan with status labels and jumlah values
7. THE Frontend_App SHALL display a table showing top 10 siswa dengan tunggakan terbesar, with columns: NIS, Nama, Kelas, Jenjang, Total Tunggakan (formatted as Rupiah currency)
8. THE Frontend_App SHALL display a table showing tagihan yang akan jatuh tempo dalam 7 hari, with columns: Kode Tagihan, Nama Siswa, Jenis Tagihan, Jatuh Tempo, Jumlah (formatted as Rupiah currency), Status
9. THE Frontend_App SHALL display a list showing 5 pembayaran terbaru, with columns: Kode Pembayaran, Nama Siswa, Jenis Tagihan, Tanggal, Metode, Jumlah (formatted as Rupiah currency)
10. THE Frontend_App SHALL render the dashboard layout responsively, displaying KPI cards in a 3-column grid on desktop (viewport width 1024px or greater), 2-column grid on tablet (viewport width 768px to 1023px), and single-column stack on mobile (viewport width less than 768px)

### Requirement 10: Filter Periode pada Dashboard

**User Story:** Sebagai Admin_Operator, saya ingin memfilter data dashboard berdasarkan tahun ajaran, sehingga saya dapat melihat statistik untuk periode tertentu.

#### Acceptance Criteria

1. THE Frontend_App SHALL display a TahunAjaran dropdown selector at the top of the dashboard page, populated with all available TahunAjaran records for the user's branch sorted by nama descending, defaulting to the Periode_Aktif
2. WHEN the Admin_Operator selects a different TahunAjaran from the dropdown, THE Frontend_App SHALL re-fetch all dashboard data (summary, charts, and quick lists) filtered by the selected tahun_ajaran_id without a full page reload
3. WHILE the dashboard data is being fetched after a period change, THE Frontend_App SHALL display loading indicators on each dashboard component (KPI cards, charts, and tables)
4. IF the Backend_API returns an error for any dashboard endpoint, THEN THE Frontend_App SHALL display an error message within the affected component area indicating that data could not be loaded
5. IF no TahunAjaran records exist for the user's branch, THEN THE Frontend_App SHALL hide the period selector and display a message indicating that no period data is available

### Requirement 11: Dashboard Frontend untuk Siswa/Wali

**User Story:** Sebagai Siswa_Wali, saya ingin melihat ringkasan tagihan pribadi saya setelah login, sehingga saya dapat mengetahui status pembayaran saya.

#### Acceptance Criteria

1. THE Frontend_App SHALL display a simplified dashboard as the landing page after successful login for users with Siswa_Wali role
2. THE Frontend_App SHALL display KPI_Card components for Siswa_Wali showing: total tagihan pribadi (sum of nominal tagihan milik siswa tersebut, formatted as Rupiah currency), total terbayar pribadi (sum of pembayaran milik siswa tersebut, formatted as Rupiah currency), and total tunggakan pribadi (total tagihan minus total terbayar, formatted as Rupiah currency)
3. THE Frontend_App SHALL display a list of all Tagihan milik siswa tersebut for the Periode_Aktif, showing: nama jenis tagihan, jumlah (formatted as Rupiah currency), jatuh tempo, and status with visual badge (green for "Lunas", yellow for "Belum Lunas", red for "Belum Dibayar")
4. THE Frontend_App SHALL display a list of pembayaran terbaru milik siswa tersebut (at most 5 records), showing: tanggal, jenis tagihan, metode, and jumlah (formatted as Rupiah currency)
5. THE Frontend_App SHALL scope all displayed data to the siswa associated with the authenticated Siswa_Wali user account
6. IF the authenticated Siswa_Wali user is a wali with multiple children, THEN THE Frontend_App SHALL display a child selector dropdown and show dashboard data for the selected child

### Requirement 12: Endpoint Dashboard Data untuk Siswa/Wali

**User Story:** Sebagai Siswa_Wali, saya ingin mendapatkan data ringkasan tagihan pribadi saya melalui API, sehingga dashboard dapat menampilkan informasi yang relevan.

#### Acceptance Criteria

1. THE Backend_API SHALL provide a GET endpoint `/api/dashboard/siswa` that returns a JSON object containing: total_tagihan (sum of nominal tagihan for the authenticated siswa), total_terbayar (sum of pembayaran for the authenticated siswa), total_tunggakan (total_tagihan minus total_terbayar), tagihan_list (array of tagihan records for the Periode_Aktif), and pembayaran_terbaru (array of at most 5 recent pembayaran records)
2. THE Backend_API SHALL scope all data to the siswa associated with the authenticated user account
3. WHEN the endpoint is called by a wali user with a `siswa_id` query parameter, THE Backend_API SHALL return data for the specified siswa, provided the siswa is associated with the authenticated wali
4. IF a wali user calls the endpoint without a `siswa_id` parameter and has multiple children, THEN THE Backend_API SHALL return data for the first child (ordered by NIS ascending)
5. IF a wali user provides a `siswa_id` that is not associated with the authenticated wali account, THEN THE Backend_API SHALL return HTTP 403 with an error message indicating access denied
6. THE Backend_API SHALL filter tagihan_list to only include records associated with the Periode_Aktif of the siswa's branch
7. THE Backend_API SHALL require authentication and return HTTP 401 for unauthenticated requests

### Requirement 13: Performance dan Caching

**User Story:** Sebagai Admin_Operator, saya ingin dashboard dimuat dengan cepat meskipun data besar, sehingga saya tidak perlu menunggu lama untuk melihat statistik.

#### Acceptance Criteria

1. THE Backend_API SHALL cache the response of each dashboard endpoint (summary, charts, and quick lists) using a cache key that includes the branch_id and tahun_ajaran_id, with a time-to-live of 5 minutes
2. WHEN a new Pembayaran record is created or a Tagihan status is updated within a branch, THE Backend_API SHALL invalidate all dashboard cache entries for that branch_id
3. WHEN a new Pengeluaran record is created or updated within a branch, THE Backend_API SHALL invalidate the kas-bulanan chart cache entry for that branch_id
4. THE Frontend_App SHALL provide a manual refresh button on the dashboard that forces re-fetching all dashboard data bypassing the cache (by appending a cache-bust parameter or using a dedicated refresh endpoint)
5. THE Backend_API SHALL respond to all dashboard endpoints within 3 seconds for branches with up to 10,000 Tagihan records
6. IF the cache is unavailable (cache driver failure), THEN THE Backend_API SHALL fall back to computing data directly from the database without returning an error to the client

### Requirement 14: Permission dan Keamanan Dashboard

**User Story:** Sebagai sistem, saya ingin memastikan bahwa setiap pengguna hanya dapat mengakses data dashboard sesuai dengan role dan branch mereka, sehingga tidak ada kebocoran data antar cabang atau antar siswa.

#### Acceptance Criteria

1. THE Backend_API SHALL restrict access to admin dashboard endpoints (`/api/dashboard/summary`, `/api/dashboard/charts/*`, `/api/dashboard/top-tunggakan`, `/api/dashboard/tagihan-jatuh-tempo`, `/api/dashboard/pembayaran-terbaru`) to users with the "view-dashboard" permission
2. THE Backend_API SHALL restrict access to the siswa dashboard endpoint (`/api/dashboard/siswa`) to users with the "view-own-billing" permission
3. THE Backend_API SHALL register permissions "view-dashboard" and "view-own-billing" in the spatie/laravel-permission system during the database seeder execution
4. IF an authenticated user without the required permission accesses a dashboard endpoint, THEN THE Backend_API SHALL return HTTP 403 with a JSON body containing an error message indicating insufficient permission
5. THE Backend_API SHALL ensure that all dashboard endpoints return data scoped exclusively to the authenticated user's branch_id, preventing cross-branch data access
6. IF the Frontend_App detects that the authenticated user does not have "view-dashboard" permission, THEN THE Frontend_App SHALL hide the admin dashboard navigation item and redirect the user to the Siswa_Wali dashboard or a default page

