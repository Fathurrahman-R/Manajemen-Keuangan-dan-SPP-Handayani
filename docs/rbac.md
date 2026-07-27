# RBAC

Dipindah dari tab "Panduan" di halaman Manajemen RBAC (`frontend-v2/app/Filament/Pages/RbacDashboard.php`). Tab itu sudah dihapus, isinya pindah ke sini.

RBAC di sini dinamis penuh: permission, resource key, dan endpoint mapping semuanya dikelola lewat UI. Tidak ada nama permission yang di-hardcode di kode.

## Ringkasan Arsitektur RBAC

**Konsep Utama: Resource Key**

Semua entitas keamanan (halaman, tombol, API endpoint) diidentifikasi pakai `resource_key`, string unik macam `siswa.create` atau `api.laporan.export`. Kode tidak pernah nyebut nama permission langsung, cuma resource_key.

**3 Tabel yang Terlibat:**

| Tabel | Fungsi | Cara Binding |
|-------|--------|-------------|
| `permissions` (Spatie) | Daftar permission (CRUD via UI) | — |
| `page_permissions` | **Resource Registry + Page Security (merged)** | `resource_key` → `permission_name` |
| `permission_endpoints` | Endpoint Mapping API (independen) | `resource_key` → `permission_id` |

**Alur Akses:**

1. **Login** → Frontend panggil `GET /api/rbac/user-resources`, backend balikin daftar `resource_key` yang user punya (berdasarkan permission-role).
2. **Simpan di Session** → Cache frontend simpan daftar resource_key ke `session('data.resources')` otomatis saat login.
3. **Cek Visibilitas UI** → `PermissionHelper::hasResource('siswa.create')` cukup baca session, zero query.
4. **Cek Proteksi** → Halaman dilindungi oleh `PermissionHelper::hasResource()` di `mount()` dan `shouldRegisterNavigation()`. Backend endpoint dilindungi oleh middleware `endpoint.permission:xxx`.
5. **Cek Backend** → Route backend pakai middleware `resource:resource_key` (future) atau `can()` di controller.

**Superadmin bypass.** `Gate::before` ngasih superadmin akses penuh. `PermissionHelper::hasResource()` selalu return `true` untuk superadmin.

| Komponen | Berkas / Lokasi | Fungsi |
|---|---|---|
| `Permission` Enum | `backend/app/Enum/Permission.php` | Source of truth permission permanen |
| `permissions` (DB) | Database (Spatie) | CRUD via UI + seeder |
| `page_permissions` (DB) | Database | Satu tabel untuk resource registry + page security |
| `permission_endpoints` (DB) | Database | Mapping endpoint API ke permission (independen) |
| `PermissionHelper` | `frontend-v2/app/Helpers/` | Helper `hasResource()` untuk proteksi halaman + endpoint |
| `RbacDashboard` | frontend-v2 (halaman Manajemen RBAC) | UI manajemen permission, role, resource, endpoint |

## Langkah 1: Daftarkan Permission Baru

**Cara 1, lewat UI (tanpa deploy ulang):** buka tab **Permissions** di Manajemen RBAC, klik **Permission Baru**, isi:

| Field | Contoh | Keterangan |
|-------|--------|------------|
| `name` | `view-laporan-keuangan` | Kebab-case, harus unik. |
| `label` | Lihat Laporan Keuangan | Teks tampilan di checkbox Role. |
| `group` | Laporan Keuangan | Grup di checkbox Role Management. |
| `audience` | *(kosong)* atau `siswa` | Kosong = Admin. Isi "siswa" untuk role siswa. |

Setelah disimpan, permission langsung bisa dipilih di dropdown tab **Assign Role**, dan bisa di-bind ke **Resource Key**.

**Cara 2, lewat enum backend (buat seeder/permanen):**

```php
// backend/app/Enum/Permission.php

enum Permission: string
{
    // ... existing cases ...

    // ═══ FITUR BARU ═══
    case VIEW_LAPORAN_KEUANGAN = 'view-laporan-keuangan';
    case EXPORT_LAPORAN_KEUANGAN = 'export-laporan-keuangan';
}
```

```bash
cd backend
php artisan db:seed --class=RoleAndPermissionSeeder
```

## Langkah 2: Daftarkan Resource Key

Langkah **paling penting**. Semua kontrol akses (navigasi, tombol, halaman) menggunakan resource_key yang didaftarkan di tabel `page_permissions`.

**Convention Resource Key:**

```
{fitur}.{subfitur}        → level fitur (navigasi, halaman)
{fitur}.{subfitur}.{aksi} → level aksi (tombol create, edit, delete)
```

Contoh: `siswa`, `siswa.create`, `siswa.update`, `siswa.delete`

Buka tab **Resource & Page Registry**, klik **Tambah Resource**. Isi:

| Field | Contoh | Keterangan |
|-------|--------|------------|
| Resource Key | `siswa.create` | Identifier unik. Convention: **dot notation**. |
| Bind Permission | `create-siswa` | Permission yang diperlukan (dropdown). |
| Group | `akademik` | Pengelompokan untuk navigasi sidebar. |
| Deskripsi | *(opsional)* | Catatan internal. |
| Aktif | Ya | Nonaktifkan untuk mencabut akses tanpa hapus. |

**Cek di Frontend:**

```php
// Di Blade / Livewire — kontrol tombol/aksi
use App\Helpers\PermissionHelper;

if (PermissionHelper::hasResource('siswa.create')) {
    // Tampilkan tombol Buat Siswa
}

// Di Filament Page — proteksi halaman
public static function canAccess(): bool
{
    return PermissionHelper::hasResource('siswa');
}
```

**Cara kerja proteksi halaman (via PermissionHelper + mount()):**

1. Middleware membaca **semua aturan aktif** dari tabel `page_permissions`.
2. Untuk setiap aturan, cek apakah user punya `resource_key` tersebut via `hasResource()`.
3. Aturan **pertama yang cocok** (resource_key milik user) → izinkan akses.
4. Jika **tidak ada aturan yang cocok** → 403 Forbidden.

> [!IMPORTANT]
> Jika user tidak punya akses ke resource manapun di `page_permissions`, maka semua halaman akan di-reject (kecuali login). Pastikan minimal ada 1 resource dengan group yang sesuai dengan role user.

Contoh resource key yang sudah di-seed (`PermissionResourceSeeder`):

| Group | Resource Key | Permission |
|-------|-------------|------------|
| dashboard | `dashboard` | view-dashboard |
| akademik | `siswa`, `siswa.create`, `siswa.read`, ... | view-siswa, create-siswa, ... |
| akademik | `kelas`, `kelas.create`, `kelas.read`, ... | view-kelas, create-kelas, ... |
| akademik | `kategori`, `kategori.create`, ... | view-kategori, create-kategori, ... |
| akademik | `tahun-ajaran`, `tahun-ajaran.create`, ... | view-tahun-ajaran, create-tahun-ajaran, ... |
| akademik | `kenaikan-kelas`, `kenaikan-kelas.process`, ... | view-kenaikan-kelas, ... |
| keuangan | `jenis-tagihan`, `jenis-tagihan.create`, ... | view-jenis-tagihan, ... |
| keuangan | `tagihan`, `tagihan.create`, ... | view-tagihan, create-tagihan, ... |
| keuangan | `pembayaran`, `pembayaran.create`, ... | view-pembayaran, create-pembayaran, ... |
| keuangan | `pengeluaran`, `pengeluaran.request`, ... | view-pengeluaran, create-pengeluaran-request, ... |
| keuangan | `midtrans`, `midtrans-config` | view-midtrans-transactions, ... |
| laporan | `kas-harian`, `rekap-bulanan`, ... | view-kas-harian, view-rekap-bulanan, ... |
| pengaturan | `user-management`, `user-management.create`, ... | view-user, create-user, ... |
| pengaturan | `role-management`, `role.create`, ... | view-roles, create-role, ... |
| pengaturan | `rbac`, `rbac.view`, `rbac.create`, ... | view-permissions, view-permission, ... |
| pengaturan | `app-setting`, `branch`, `notification-setting`, ... | view-app-setting, view-branch, ... |

## Langkah 3: Mapping Endpoint API

Mapping endpoint backend ke permission. Resource_key di sini independen, tidak harus sama dengan yang di `page_permissions`.

Buka tab **Endpoint Mapping**, klik **Tambah Endpoint**. Isi:

| Field | Contoh | Keterangan |
|-------|--------|------------|
| Resource Key | `api.siswa.index` | Identifier unik untuk endpoint ini. Convention: `api.{fitur}.{aksi}`. |
| Bind Permission | `view-siswa` | Pilih permission dari dropdown. |
| Group | API Siswa | Pengelompokan opsional. |
| Deskripsi | *(opsional)* | Catatan internal. |
| Aktif | Ya | Centang untuk mengaktifkan. |

**Endpoint mapping sekarang independen:**
- Resource key endpoint **tidak harus sama** dengan resource key di `page_permissions`.
- Bisa membuat resource key `api.siswa.index` yang di-bind ke permission `view-siswa`.
- Tabel `permission_endpoints` punya kolom `permission_id` langsung, jadi tidak perlu auto-resolve.

**Cara proteksi di Backend (future):**

```php
// backend/routes/api.php
Route::get('/api/siswa', [SiswaController::class, 'index'])
    ->middleware(['auth:sanctum', 'resource:api.siswa.index']);
```

**Cara proteksi di Frontend:**

```php
// Tidak perlu — endpoint hanya dicek di backend via middleware.
// Tapi jika ada tombol "Export API Key" yang berhubungan:
if (PermissionHelper::hasResource('api.laporan.export')) {
    // Tampilkan tombol export
}
```

> [!NOTE]
> Middleware `resource:` di backend belum diimplementasi. Saat ini proteksi endpoint bisa dilakukan via `$request->user()->can('nama-permission')` di controller.

## Langkah 4: Assign Permission ke Role

Buka tab **Assign Role**:

1. Pilih role dari daftar (contoh: admin, superadmin, user, siswa).
2. Centang permission yang sesuai dengan resource yang ingin diakses.
3. Klik **Simpan**.

**Apa yang terjadi setelah assign:**

1. Permission langsung aktif untuk semua user dengan role tersebut.
2. Saat user login/logout ulang, frontend memanggil `GET /api/rbac/user-resources`.
3. Backend mengembalikan daftar `resource_key` dari `page_permissions` yang permission_name-nya cocok.
4. `PermissionHelper` nyimpen di session, jadi semua pengecekan `hasResource()` cepat dan zero query.
5. Superadmin mendapat **semua** resource tanpa perlu assign.

**Urutan workflow untuk menambah fitur baru:**

1. Lewat UI: bikin permission baru di tab Permissions. Atau lewat enum: tambah case di `App\Enum\Permission` lalu `php artisan db:seed --class=RoleAndPermissionSeeder`.
2. **(Via UI)** Daftarkan resource key di tab Resource & Page Registry.
3. *(Opsional)* Mapping endpoint API di tab Endpoint Mapping.
4. **(Via UI)** Assign permission ke role di tab Assign Role.
5. Implementasi kode fitur di frontend/backend menggunakan `PermissionHelper::hasResource()`.

## Panduan Kode: Backend (Laravel API)

**1. Daftarkan di Permission Enum:**

```php
// backend/app/Enum/Permission.php

enum Permission: string
{
    // ... existing cases ...

    // ═══ FITUR BARU: ABSENSI ═══
    case VIEW_ABSENSI = 'view-absensi';
    case CREATE_ABSENSI = 'create-absensi';
    case READ_ABSENSI = 'read-absensi';
    case UPDATE_ABSENSI = 'update-absensi';
    case DELETE_ABSENSI = 'delete-absensi';
}
```

**2. Gunakan Permission di Controller:**

```php
// backend/app/Http/Controllers/AbsensiController.php

use App\Enum\Permission;

class AbsensiController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Absensi::all()]);
    }

    public function store(Request $request)
    {
        // Proteksi tambahan di level controller
        if (!$request->user()->can(Permission::CREATE_ABSENSI->value)) {
            abort(403, 'Unauthorized');
        }

        // ... logic create ...
    }
}
```

**3. Daftarkan route.** Disarankan lewat `can()` di controller, tanpa middleware route:

```php
// backend/routes/api.php
Route::apiResource('absensi', AbsensiController::class)
    ->middleware(['auth:sanctum']);
```

Cukup gunakan `auth:sanctum`. Permission dicek manual di controller via `$request->user()->can()`.

Alternatifnya lewat Spatie middleware, kalau memang perlu hardcode:

```php
Route::get('/absensi', [AbsensiController::class, 'index'])
    ->middleware(['auth:sanctum', 'permission:view-absensi']);
```

> [!NOTE]
> Jika ingin proteksi dinamis (tanpa hardcode nama permission di route), gunakan middleware `resource:` yang akan datang.

## Panduan Kode: Frontend Filament

Semua kontrol akses di frontend pakai `PermissionHelper::hasResource()`. `has()` sudah tidak ada.

**1. Proteksi Halaman Filament:**

```php
// frontend-v2/app/Filament/Pages/AbsensiPage.php

use App\Helpers\PermissionHelper;

class AbsensiPage extends Page
{
    // Proteksi via canAccess() — menggunakan resource_key
    public static function canAccess(): bool
    {
        return PermissionHelper::hasResource('absensi');
    }
}
```

**2. Sembunyikan Tombol/Aksi Berdasarkan Resource Key:**

```php
// Di header action:
HeaderAction::make('create')
    ->visible(fn() => PermissionHelper::hasResource('absensi.create'))

// Di table action:
Tables\Actions\EditAction::make()
    ->visible(fn() => PermissionHelper::hasResource('absensi.update'))

Tables\Actions\DeleteAction::make()
    ->visible(fn() => PermissionHelper::hasResource('absensi.delete'))
```

**3. Conditional Rendering di Blade:**

```blade
{{-- resources/views/absensi/index.blade.php --}}

@if(PermissionHelper::hasResource('absensi.export'))
    <x-filament::button wire:click="export" color="success" icon="heroicon-o-arrow-down-tray">
        Export Absensi
    </x-filament::button>
@endif
```

**4. Gunakan hasResource untuk Navigasi:**

```php
// Cek resource sebagai gate navigasi
if (PermissionHelper::hasResource('absensi-online')) {
    // Tampilkan menu absensi online
}

// Atau di Filament sidebar:
NavigationItem::make('Absensi Online')
    ->url('/absensi-online')
    ->visible(fn() => PermissionHelper::hasResource('absensi-online'))
    ->icon('heroicon-o-clock')
```

## Referensi PermissionHelper API

`has()` sudah dihapus, pakai `hasResource()`.

| Method | Parameter | Return | Keterangan |
|---|---|---|---|
| `hasResource()` | `string $resourceKey` | bool | Cek akses resource key. Superadmin selalu true. **Method utama.** |
| `hasAnyInGroup()` | `string $group` | bool | Cek apakah user punya akses ke salah satu resource dalam grup (mis: akademik). |

**File:** `frontend-v2/app/Helpers/PermissionHelper.php`

**Superadmin bypass.** Semua method di atas punya bypass: kalau user punya role `superadmin`, langsung return `true` tanpa cek database.

**Session Cache:** saat login, frontend memanggil `GET /api/rbac/user-resources` dan menyimpan hasilnya di `session('data.resources')`. Semua pengecekan `hasResource()` hanya membaca session, tanpa query database.

**Method utama, `hasResource()`:**
- `PermissionHelper::hasResource('siswa')` → cek akses resource key `siswa`
- `PermissionHelper::hasResource('siswa.create')` → cek akses resource key `siswa.create`
- Resource key didaftarkan di tabel `page_permissions` (tab **Resource & Page Registry**).

## Workflow Seeder & Permission Sync

Tidak ada command `permissions:sync*`. Semua sinkronisasi permission/role/resource key/endpoint lewat seeder, pakai `firstOrCreate`/`updateOrCreate` jadi aman diulang.

**1. Sync Permission Enum ke Database + refresh role:**

```bash
# Dari direktori backend
php artisan db:seed --class=RoleAndPermissionSeeder
```

**2. Seed Resource Key ke page_permissions:**

```bash
php artisan db:seed --class=PermissionResourceSeeder
```

Ini akan mengisi resource key yang sudah didefinisikan di `PermissionResourceSeeder`.

**3. Seed mapping endpoint API → permission:**

```bash
php artisan db:seed --class=PermissionEndpointSeeder
```

**Kapan harus menjalankan:**
- `RoleAndPermissionSeeder`: habis nambah/ubah/hapus case di `App\Enum\Permission`.
- `PermissionResourceSeeder`: habis deploy ulang database, atau habis nambah resource key baru di seeder.
- `PermissionEndpointSeeder`: habis nambah/ubah mapping endpoint API ke permission di seeder.

> [!WARNING]
> Hapus/rename permission via UI dapat menyebabkan error jika permission tersebut masih di-bind ke resource key. Sebaiknya **nonaktifkan** dulu permission via UI sebelum menghapus.

## Catatan Penting & FAQ

**Q: Apakah superadmin perlu permission?**
Tidak. `Gate::before` memberi bypass penuh ke semua fitur. `PermissionHelper::hasResource()` selalu return `true` untuk superadmin.

**Q: Apa bedanya resource key dengan permission?**
Resource key itu pointer, string unik yang di-refer kode. Permission name itu izin sesungguhnya yang dicek Spatie. Satu resource key di-bind ke satu permission name di tabel `page_permissions`. Kode tidak pernah nyebut permission name, cuma resource key.

**Q: Kenapa endpoint mapping pakai resource_key sendiri?**
Agar fleksibel. Resource key di `page_permissions` (`siswa.create`) bisa berbeda dengan di endpoint mapping (`api.siswa.store`). Keduanya punya permission binding masing-masing.

**Q: Saya ingin menambah fitur baru, apa langkah-langkahnya?**
1. Buat permission baru (UI atau Enum).
2. Daftarkan resource key di tab Resource & Page Registry.
3. *(Opsional)* Mapping endpoint API.
4. Assign permission ke role di tab Assign Role.
5. Di kode frontend: gunakan `PermissionHelper::hasResource('resource-key')`.
6. Di kode backend: gunakan `$request->user()->can('nama-permission')`.

**Q: Proteksi halaman Filament cara kerjanya bagaimana?**
Middleware membaca **semua aturan aktif** dari `page_permissions`. Untuk setiap aturan, cek `hasResource(resource_key)`. Aturan pertama yang cocok (user punya resource) → izinkan. Jika tidak ada yang cocok → 403 Forbidden.

**Q: Kenapa saya bisa akses halaman Filament meskipun belum assign permission?**
Kemungkinan: (1) login sebagai superadmin (bypass total). (2) `PermissionHelper::hasResource()` mengembalikan true. (3) `mount()` atau `shouldRegisterNavigation()` tidak diimplementasikan. Proteksi sekarang via mount() gate + backend `EndpointPermission`, bukan middleware global.

**Q: Bagaimana dengan DynamicPermissionMiddleware yang lama?**
Sudah dinonaktifkan. Tidak ada lagi middleware berbasis method+path di backend. Proteksi endpoint dilakukan via `can()` di controller. Di masa depan akan ada middleware `resource:resource_key`.

## Ringkasan untuk Pengembang Baru

**Backend Checklist:**
1. Tambah case di `App\Enum\Permission`
2. Jalankan `php artisan db:seed --class=RoleAndPermissionSeeder`
3. Di controller: `$request->user()->can(Permission::NAMA->value)`
4. Routing: cukup `auth:sanctum`, tanpa middleware `dynamic.permission`

**Frontend Checklist:**
1. Daftarkan resource key dan pattern proteksi di tab **Resource & Page Registry**
2. Gunakan `PermissionHelper::hasResource()` untuk proteksi komponen (halaman, tombol, navigasi)
3. Assign permission ke role di tab **Assign Role**

> [!IMPORTANT]
> - Superadmin tidak butuh permission, `Gate::before` yang bypass.
> - Permission baru cukup didaftarkan lewat UI, tanpa deploy ulang.
> - Setiap permission baru harus di-**assign ke role** via Assign Role.
> - Permission yang bersifat permanen sebaiknya **ada di `App\Enum\Permission`** agar konsisten saat di-seed ulang.
> - Proteksi halaman cukup didaftarkan di tab Resource & Page Registry, tanpa nulis kode PHP.
> - Pakai `PermissionHelper::hasResource()` buat kontrol tampilan komponen, jangan cuma andelin middleware.

