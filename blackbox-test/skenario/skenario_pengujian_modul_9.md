# Skenario Pengujian Blackbox — Modul 9: Manajemen RBAC Dinamis (Resource Key)

> **Tanggal:** 10 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament) + `http://127.0.0.1:8080` (Backend API)
> **Akun:** `admin123` / `admin123` (Superadmin)
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, State Transition Testing, Error Guessing
> **Aspek:** Functional, Security (RBAC), UI/UX, Regression

---

## Ringkasan Perubahan

Modul ini memperkenalkan sistem RBAC **sepenuhnya dinamis** berbasis **Resource Key** — pointer unik yang di-refer oleh kode, bukan nama permission secara langsung.

| Komponen | Fungsi |
|----------|--------|
| `page_permissions` | **Tabel tunggal** untuk Resource Registry + Page Security (merged). Binding: `resource_key` → `permission_name` |
| `permission_endpoints` | Mapping endpoint API ke permission (independen). Binding: `resource_key` → `permission_id` |
| `RbacController` | CRUD API untuk permission, page_permissions, endpoint mapping, role assignment |
| `RbacDashboard` | Halaman Filament multi-tab: Permissions, Assign Role, Resource & Page Registry, Endpoint Mapping |
| `PermissionHelper` | Helper frontend: `hasResource()` untuk cek akses, `hasAnyInGroup()` untuk navigasi |
| Superadmin | Bypass total via `Gate::before`. Tidak perlu explicit permission. |

**Perubahan Arsitektur:**
- `permission_resources` (tabel) → **dihapus**, merge ke `page_permissions`
- `PermissionResource` model → **dihapus**
- `RbacResourcesTable` → **dihapus**
- `PermissionHelper::has()` → **dihapus**, gunakan `hasResource()`
- `DynamicPermissionMiddleware` → **dinonaktifkan**
- Endpoint mapping sekarang **independen** — punya `resource_key` sendiri
- Proteksi halaman via `CheckFilamentPagePermission` menggunakan `resource_key`, bukan `route_pattern`

---

## Daftar Skenario (Test Cases)

### A. Resource & Page Registry — Manajemen Resource Key

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RBAC-001** | Melihat daftar resource key dari seeder | Equivalence Partitioning | Functional | Login sebagai superadmin | 1. Buka menu `Pengaturan > RBAC Dashboard`<br>2. Klik tab "Resource & Page Registry" | N/A | Tabel menampilkan 90+ resource key dengan kolom: Resource Key, Permission Name, Group, Aktif, Aksi. Data dari seeder: `dashboard`, `siswa`, `siswa.create`, `kelas`, `tagihan`, `rbac`, dll. | High |
| **RBAC-002** | Filter resource key berdasarkan group | Equivalence Partitioning | Functional | Login sebagai superadmin | 1. Buka tab "Resource & Page Registry"<br>2. Klik filter "Group"<br>3. Pilih `akademik` | Group: akademik | Tabel hanya menampilkan resource key dengan group `akademik` (siswa, kelas, kategori, tahun-ajaran, kenaikan-kelas). | High |
| **RBAC-003** | Cari resource key via kolom search | Equivalence Partitioning | Functional | Login sebagai superadmin | 1. Di tab "Resource & Page Registry"<br>2. Ketik `pembayaran` di kolom search | Search: `pembayaran` | Tabel menampilkan: `pembayaran`, `pembayaran.create`, `pembayaran.delete`, `pembayaran.print` | Medium |
| **RBAC-004** | Tambah resource key baru | Equivalence Partitioning | Functional | Login dengan `create-permission` | 1. Klik "Tambah Resource"<br>2. Isi Resource Key: `test-resource`<br>3. Pilih Bind Permission: `view-dashboard`<br>4. Isi Group: `testing`<br>5. Klik Simpan | Resource Key: `test-resource`<br>Permission: `view-dashboard`<br>Group: `testing` | Resource key baru muncul di tabel. Toast sukses. | High |
| **RBAC-005** | Validasi duplikasi resource_key | Error Guessing | Functional | Resource `test-resource` sudah ada | 1. Klik "Tambah Resource"<br>2. Isi Resource Key: `test-resource` (sama)<br>3. Klik Simpan | Key: `test-resource` | Form menolak, tampil pesan error validasi duplikasi key. | High |
| **RBAC-006** | Edit resource key — ganti bind permission | Equivalence Partitioning | Functional | Ada resource `test-resource` | 1. Klik "Edit" pada baris `test-resource`<br>2. Ganti Bind Permission ke `view-siswa`<br>3. Klik Simpan | Permission baru: `view-siswa` | Binding permission berubah. Kolom permission name berubah dari `view-dashboard` ke `view-siswa`. | High |
| **RBAC-007** | Nonaktifkan resource key (toggle active) | State Transition Testing | Functional | Ada resource `test-resource` aktif | 1. Klik ikon "Toggle Active" pada baris `test-resource`<br>2. Perhatikan status | N/A | Status `is_active` berubah menjadi tidak aktif (ikon silang). Data tidak hilang. | High |
| **RBAC-008** | Aktifkan kembali resource key | State Transition Testing | Functional | Resource `test-resource` tidak aktif | 1. Klik ikon "Toggle Active" lagi | N/A | Status kembali aktif (ikon ceklis). | Medium |
| **RBAC-009** | Hapus resource key | Equivalence Partitioning | Functional | Ada resource `test-resource` | 1. Klik ikon "Hapus" (tong sampah)<br>2. Konfirmasi penghapusan | N/A | Resource key hilang dari tabel. | Medium |

### B. Permission CRUD

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RBAC-010** | Melihat daftar permission | Equivalence Partitioning | Functional | Login sebagai superadmin | 1. Buka tab "Permissions"<br>2. Scroll daftar permission | N/A | Tabel menampilkan semua permission dari database. Kolom: Name, Label, Group, Audience, Aksi. | High |
| **RBAC-011** | Menambah permission baru via UI | Equivalence Partitioning | Functional | Login dengan `create-permission` | 1. Klik "+ Permission Baru"<br>2. Isi Name: `view-laporan-keuangan`<br>3. Isi Label: `Lihat Laporan Keuangan`<br>4. Isi Group: `Laporan`<br>5. Klik Simpan | Name: `view-laporan-keuangan`<br>Label: `Lihat Laporan Keuangan` | Permission baru muncul di tabel. | High |
| **RBAC-012** | Validasi duplikasi nama permission | Error Guessing | Functional | Permission `view-laporan-keuangan` sudah ada | 1. Klik "+ Permission Baru"<br>2. Isi nama yang SAMA<br>3. Klik Simpan | Name: `view-laporan-keuangan` | Form menolak, pesan error validasi (422). | High |
| **RBAC-013** | Edit permission | Equivalence Partitioning | Functional | Ada permission `view-laporan-keuangan` | 1. Klik "Edit"<br>2. Ubah Label menjadi `Lihat Laporan`<br>3. Klik Update | Label baru: `Lihat Laporan` | Label berubah di tabel. | High |
| **RBAC-014** | Hapus permission | Equivalence Partitioning | Functional | Ada permission test | 1. Klik "Hapus"<br>2. Konfirmasi | N/A | Baris hilang dari tabel. Permission terhapus dari database. | Medium |
| **RBAC-015** | Permission dengan audience siswa | Equivalence Partitioning | Functional | Login superadmin | 1. Klik "+ Permission Baru"<br>2. Isi Name: `test-siswa-only`<br>3. Isi Audience: `siswa`<br>4. Simpan | Name: `test-siswa-only`<br>Audience: `siswa` | Permission muncul dengan audience `siswa`. | Medium |
| **RBAC-016** | Permission baru muncul di dropdown Resource Registry | Equivalence Partitioning | Functional | Permission `test-siswa-only` sudah ada | 1. Buka tab "Resource & Page Registry"<br>2. Klik "Tambah Resource"<br>3. Buka dropdown "Bind Permission"<br>4. Cari `test-siswa-only` | N/A | Permission `test-siswa-only` muncul di opsi dropdown. | High |
| **RBAC-017** | Permission baru muncul di dropdown Endpoint Mapping | Equivalence Partitioning | Functional | Permission `test-siswa-only` sudah ada | 1. Buka tab "Endpoint Mapping"<br>2. Klik "Tambah Endpoint"<br>3. Buka dropdown "Bind Permission"<br>4. Cari `test-siswa-only` | N/A | Permission `test-siswa-only` juga muncul di dropdown Endpoint Mapping (tabel berbeda, permission sama). | High |

### C. Endpoint Mapping (Independen)

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RBAC-018** | Melihat daftar endpoint mapping | Equivalence Partitioning | Functional | Login dengan `view-permission` | 1. Buka tab "Endpoint Mapping"<br>2. Cek tabel | N/A | Menampilkan daftar endpoint mapping dengan kolom: Resource Key, Permission Name, Group, Aktif, Aksi. | High |
| **RBAC-019** | Menambah endpoint mapping baru | Equivalence Partitioning | Functional | Login dengan `edit-permission` | 1. Klik "+ Tambah Endpoint"<br>2. Isi Resource Key: `api.test-endpoint`<br>3. Pilih Permission: `view-siswa`<br>4. Isi Group: `Testing`<br>5. Klik Simpan | Key: `api.test-endpoint`<br>Permission: `view-siswa`<br>Group: `Testing` | Mapping baru muncul di tabel. | High |
| **RBAC-020** | Endpoint mapping independen — resource key berbeda dari page_permissions | Equivalence Partitioning | Functional | Endpoint `api.test-endpoint` sudah ada | 1. Buka tab "Resource & Page Registry"<br>2. Cari resource key `api.test-endpoint` | N/A | Resource key `api.test-endpoint` **TIDAK muncul** di tabel Resource & Page Registry — kedua tabel independen. | High |
| **RBAC-021** | Endpoint mapping bisa pakai resource key sama dengan nama berbeda binding | Equivalence Partitioning | Functional | Ada `pembayaran.print` di page_permissions (bind: `print-kwitansi`) | 1. Tambah endpoint dengan Resource Key: `pembayaran.print`<br>2. Pilih Permission: `view-pembayaran` (berbeda!)<br>3. Simpan | Key: `pembayaran.print`<br>Permission: `view-pembayaran` | Dua `pembayaran.print` — satu di page_permissions (bind `print-kwitansi`), satu di endpoints (bind `view-pembayaran`). Binding berbeda diperbolehkan. | Medium |
| **RBAC-022** | Edit endpoint mapping | Equivalence Partitioning | Functional | Ada mapping `api.test-endpoint` | 1. Klik "Edit"<br>2. Ganti Permission ke `view-kelas`<br>3. Simpan | Permission baru: `view-kelas` | Permission berubah di tabel. | High |
| **RBAC-023** | Nonaktifkan endpoint mapping | State Transition Testing | Functional | Mapping `api.test-endpoint` aktif | 1. Klik "Toggle Active" | N/A | Status jadi tidak aktif. Data tidak hilang. | Medium |
| **RBAC-024** | Hapus endpoint mapping | Equivalence Partitioning | Functional | Ada mapping test | 1. Klik "Hapus"<br>2. Konfirmasi | N/A | Mapping hilang dari tabel. | Medium |

### D. Role Assignment

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RBAC-025** | Melihat daftar role dan permission | Equivalence Partitioning | Functional | Login dengan `assign-permission` | 1. Buka tab "Assign Role"<br>2. Klik role "admin" di panel kiri | N/A | Panel kiri menampilkan semua role. Panel kanan menampilkan semua permission dengan checkbox (tercentang sesuai role admin). | High |
| **RBAC-026** | Assign permission ke role | Equivalence Partitioning | Functional | Tab "Assign Role" terbuka, role "admin" terpilih | 1. Centang permission baru (misal `test-siswa-only`)<br>2. Klik "Simpan" | Centang `test-siswa-only` | Role admin sekarang memiliki permission `test-siswa-only`. | High |
| **RBAC-027** | Unassign permission dari role — efek ke user | State Transition Testing | Functional & Security | Role "admin" memiliki `view-siswa` | 1. Uncheck `view-siswa`<br>2. Klik "Simpan"<br>3. Login sbg admin biasa (non-superadmin) yang sebelumnya punya akses<br>4. Cek akses menu Siswa | Uncheck: `view-siswa` | Step 1-2: sukses. Step 4: Menu Siswa tidak muncul atau akses 403 (karena `hasResource('siswa')` = false). **Kembalikan permission setelah test.** | Critical |
| **RBAC-028** | Role siswa — hanya punya permission terbatas | Equivalence Partitioning | Functional | Login superadmin | 1. Klik role "siswa"<br>2. Cek permission yang tercentang | N/A | Hanya 4 permission: `view-own-billing`, `view-tagihan-siswa`, `pay-tagihan-online`, `view-midtrans-transactions`. | Medium |
| **RBAC-029** | Role baru — tidak bisa dibuat dengan nama yang sama | Error Guessing | Functional | Role "admin" sudah ada | 1. Buka "Role Management"<br>2. Klik "Tambah Role"<br>3. Isi nama "admin"<br>4. Simpan | Nama: `admin` | Validasi: role "admin" sudah ada. | High |

### E. Akses & Proteksi Halaman

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RBAC-030** | Superadmin bypass — akses semua resource | Equivalence Partitioning | Functional & Security | Login sebagai superadmin | 1. Buka Dashboard<br>2. Buka menu Akademik > Siswa<br>3. Buka menu Keuangan > Tagihan<br>4. Buka Pengaturan > User Management | Akun superadmin `admin123` | Semua menu dapat diakses tanpa error. Superadmin bypass berfungsi. | Critical |
| **RBAC-031** | User biasa — hanya akses resource yang diassign | Equivalence Partitioning | Functional & Security | Buat user admin non-superadmin, assign 3 permission: `view-dashboard`, `view-siswa`, `view-kelas` | 1. Login sebagai user test<br>2. Buka menu Akademik > Siswa<br>3. Buka menu Akademik > Kelas<br>4. Coba buka menu Keuangan > Tagihan<br>5. Coba buka `/role-management` via URL | User: testrbac<br>Permission: dashboard, siswa, kelas | ✅ Menu Siswa & Kelas bisa diakses.<br>❌ Menu Tagihan tidak muncul (tidak punya `view-tagihan`).<br>❌ Akses `/role-management` ditolak (403). | Critical |
| **RBAC-032** | Nonaktifkan resource key — akses dicabut | State Transition Testing | Security | Resource key `siswa` aktif, user admin biasa punya `view-siswa` | 1. Superadmin nonaktifkan `siswa` di tab Resource & Page Registry<br>2. Login sebagai admin biasa<br>3. Coba buka menu Siswa | Nonaktifkan `siswa` | Menu Siswa tidak muncul atau 403 — meskipun user punya `view-siswa`, resource key `siswa` dinonaktifkan. **Aktifkan kembali setelah test.** | High |
| **RBAC-033** | Proteksi halaman Filament — akses langsung via URL | Equivalence Partitioning | Security | User tanpa permission | 1. Login sebagai user test<br>2. Buka URL langsung: `http://127.0.0.1:8000/role-management`<br>3. Buka URL: `http://127.0.0.1:8000/rbac-dashboard` | N/A | **403** atau redirect. `CheckFilamentPagePermission` middleware dan `canAccess()` mencegah akses. | High |

### F. API & Backend Regression

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RBAC-034** | Superadmin bypass — akses API tanpa explicit permission | Equivalence Partitioning | Security & Regression | Login sebagai superadmin | 1. Panggil `GET /api/users` (superadmin)<br>2. Panggil `GET /api/siswa` (superadmin) | Token superadmin | Response **200 OK**. Superadmin bypass via `Gate::before`. | Critical |
| **RBAC-035** | Admin dengan permission — akses API sesuai | Equivalence Partitioning | Functional & Regression | Admin biasa punya `view-user`, `view-siswa` | 1. Panggil `GET /api/users`<br>2. Panggil `GET /api/siswa` | Token admin biasa | **200 OK** untuk kedua endpoint. | Critical |
| **RBAC-036** | User tanpa permission — akses API via controller `can()` | Equivalence Partitioning | Security & Regression | User test TIDAK punya `view-user` | 1. Panggil `GET /api/users` | Token user tanpa `view-user` | Response **403 Forbidden** dari controller (`$request->user()->can(Permission::VIEW_USER->value)`). | Critical |
| **RBAC-037** | Unauthorized akses ke API RBAC (/rbac/*) | Equivalence Partitioning | Security | User siswa (token siswa) | 1. Panggil `GET /api/rbac/page-permissions`<br>2. Panggil `POST /api/rbac/permissions` | Token siswa | **403 Forbidden**. Siswa tidak punya permission RBAC. | High |
| **RBAC-038** | RBAC Dashboard — akses langsung oleh user tanpa permission | Equivalence Partitioning | Security | User tanpa `view-permission` | 1. Login sebagai user tanpa akses RBAC<br>2. Buka URL: `http://127.0.0.1:8000/rbac-dashboard` | N/A | **403** atau redirect. | High |
| **RBAC-039** | Regression: GET /api/users tetap terproteksi | Regression | Functional & Security | DynamicPermissionMiddleware dinonaktifkan | 1. Login superadmin → GET /api/users<br>2. Login admin dgn `view-user` → GET /api/users<br>3. Login user tanpa `view-user` → GET /api/users | Tiga token berbeda | Superadmin: **200**. Admin: **200**. User tanpa: **403** (proteksi via `can()` di controller, bukan middleware). | Critical |
| **RBAC-040** | Regression: route publik tanpa auth tetap bisa diakses | Regression | Functional | Route publik (`/api/midtrans/notification`) | 1. Panggil `POST /api/midtrans/notification` tanpa token | N/A | **200** (tidak kena auth). | High |

### G. Edge Cases & Keamanan

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RBAC-041** | Token invalid/expired — tidak crash | Error Guessing | Security | Token expired | 1. Panggil endpoint API dengan token `Bearer invalid_token` | Token: `invalid_token` | **401 Unauthenticated** dari middleware `auth:sanctum`. | High |
| **RBAC-042** | Menghapus permission yang masih di-bind ke resource key | Error Guessing | Security | Permission `view-user` di-bind ke resource key `user-management` | 1. Hapus permission `view-user` via UI<br>2. Cek resource key `user-management` di page_permissions | N/A | `page_permissions.permission_name` = `view-user` (masih ada string, tapi permission hilang). Saat cek `hasResource('user-management')`, karena permission tidak ada di session user, return false — proteksi tetap jalan. | High |
| **RBAC-043** | Superadmin tanpa explicit permission di database — login tetap sukses | Equivalence Partitioning | Functional | Superadmin tidak punya permission explicit di DB | 1. Login sebagai superadmin<br>2. Buka dashboard<br>3. Buka User Management | Akun superadmin | Login sukses. Semua menu terlihat. Superadmin bypass. | Critical |
| **RBAC-044** | Akses portal siswa oleh admin — ditolak | Equivalence Partitioning | Security | Login sebagai admin | 1. Coba buka `/portal/beranda` | N/A | **403** atau redirect ke dashboard. | High |
| **RBAC-045** | Akses dashboard admin oleh siswa — ditolak | Equivalence Partitioning | Security | Login sebagai siswa | 1. Coba buka `/dashboard-page` | N/A | **403** atau redirect ke portal. | High |
| **RBAC-046** | Filter group di Resource & Page Registry — kombinasi filter + search | Equivalence Partitioning | UI/UX | Login superadmin | 1. Pilih filter Group = `keuangan`<br>2. Ketik `tagihan` di search | Group: `keuangan`<br>Search: `tagihan` | Tabel menampilkan resource key dengan group `keuangan` yang mengandung "tagihan": `jenis-tagihan`, `jenis-tagihan.create`, `tagihan`, `tagihan.create`, dll. | Medium |
| **RBAC-047** | Permission name case-sensitive | Error Guessing | Functional | Permission `view-dashboard` (lowercase) | 1. Coba buat permission: `View-Dashboard` (capital) | Name: `View-Dashboard` | Permission `View-Dashboard` adalah entitas berbeda. Keduanya bisa ada bersamaan. | Low |

---

## Catatan Pengujian

1. **Superadmin vs Admin Biasa**: Superadmin bypass semua gate — tidak perlu permission. Admin biasa tetap perlu permission explicit.
2. **Resource Key adalah pointer**: Kode hanya merefer `resource_key`, bukan nama permission. Binding permission ke resource key bisa diubah kapan saja via UI.
3. **Dua tabel independen**: `page_permissions` (Resource Registry + Page Security) dan `permission_endpoints` (Endpoint Mapping) tidak saling terkait. Resource key yang sama bisa ada di kedua tabel dengan binding permission berbeda.
4. **Tidak ada DynamicPermissionMiddleware**: Proteksi endpoint backend dilakukan via `$request->user()->can()` di controller, atau via middleware `permission:` di route (jika hardcode).
5. **Testing**: Sebaiknya gunakan akun testing khusus (non-superadmin) untuk RBAC-031, 032, 033, 035, 036, 039.
6. **Restore setelah test**: Untuk RBAC-027 dan RBAC-032, pastikan mengembalikan permission/resource key ke status semula setelah test selesai.

## Ringkasan

| Kategori | Jumlah TC | Prioritas Tinggi |
|----------|-----------|-----------------|
| A. Resource & Page Registry | 9 | 5 |
| B. Permission CRUD | 8 | 6 |
| C. Endpoint Mapping | 7 | 5 |
| D. Role Assignment | 5 | 3 |
| E. Akses & Proteksi Halaman | 4 | 4 |
| F. API & Backend Regression | 7 | 7 |
| G. Edge Cases & Keamanan | 7 | 5 |
| **Total** | **47** | **35** |
