<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RbacDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $title = 'RBAC Dashboard';

    protected static ?string $navigationLabel = 'Manajemen RBAC';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.rbac-dashboard';

    public string $activeTab = 'permissions';

    /** @var array<int, array{id: int, name: string, permissions: string[]}> */
    public array $rolesList = [];

    public ?int $selectedRoleId = null;

    public array $selectedRolePerms = [];

    public array $allPerms = [];

    public static function canAccess(): bool
    {
        return PermissionHelper::hasResource('rbac');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('rbac'), 403);

        $this->reloadAll();
    }

    public function reloadAll(): void
    {
        $r = ApiService::client()->get('/rbac/roles');
        $this->rolesList = $r->successful() ? ($r->json()['data'] ?? []) : [];

        $r2 = ApiService::client()->get('/rbac/permissions');
        $this->allPerms = $r2->successful() ? ($r2->json()['data'] ?? []) : [];
    }

    // ── Role assignment ──

    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $r = ApiService::client()->get("/rbac/roles/{$roleId}/permissions");
        $this->selectedRolePerms = $r->successful() ? ($r->json()['data']['permissions'] ?? []) : [];
    }

    public function toggleRolePerm(string $permName): void
    {
        if (in_array($permName, $this->selectedRolePerms)) {
            $this->selectedRolePerms = array_values(array_filter($this->selectedRolePerms, fn ($p) => $p !== $permName));
        } else {
            $this->selectedRolePerms[] = $permName;
        }
    }

    public function saveRolePerms(): void
    {
        if (! $this->selectedRoleId) {
            return;
        }
        ApiService::client()->put("/rbac/roles/{$this->selectedRoleId}/permissions", [
            'permissions' => $this->selectedRolePerms,
        ]);
        Notification::make()->title('Permission role disimpan.')->success()->send();
        $this->reloadAll();
    }

    // ── Guide Schema ──

    public function guideSchema(): Schema
    {
        return Schema::make($this)
            ->components([

                Section::make('Panduan RBAC untuk Pengembang')
                    ->description('Sistem RBAC bersifat dinamis penuh — permission, resource key, endpoint mapping, semuanya dikelola via UI. Tidak ada hardcoded permission name di kode.')
                    ->icon('heroicon-o-book-open')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_toc')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
**Daftar Isi:**
1. [Ringkasan Arsitektur RBAC](#arsitektur)
2. [Langkah 1: Daftarkan Permission Baru](#langkah-1)
3. [Langkah 2: Daftarkan Resource Key](#langkah-2)
4. [Langkah 3: Mapping Endpoint API](#langkah-3)
5. [Langkah 4: Assign Permission ke Role](#langkah-4)
6. [Panduan Kode: Backend](#kode-backend)
7. [Panduan Kode: Frontend Filament](#kode-frontend)
8. [Panduan Kode: PermissionHelper](#kode-helper)
9. [Workflow Seeder](#seeder)
10. [FAQ](#faq)
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Arsitektur — NEW
                // ═══════════════════════════════════════════

                Section::make('A. Ringkasan Arsitektur RBAC (Sekarang)')
                    ->description('Hanya ada 2 tabel bisnis + 1 tabel Spatie. Resource Registry dan Page Security sudah digabung menjadi satu tabel.')
                    ->collapsible()
                    ->icon('heroicon-o-archive-box')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_arch_intro')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
**Konsep Utama: Resource Key**

Semua entitas keamanan (halaman, tombol, API endpoint) diidentifikasi oleh **resource_key** — string unik seperti `siswa.create` atau `api.laporan.export`. Kode TIDAK PERNAH menyebut nama permission secara langsung, hanya resource_key.

**3 Tabel yang Terlibat:**

| Tabel | Fungsi | Cara Binding |
|-------|--------|-------------|
| `permissions` (Spatie) | Daftar permission (CRUD via UI) | — |
| `page_permissions` | **Resource Registry + Page Security (merged)** | `resource_key` → `permission_name` |
| `permission_endpoints` | Endpoint Mapping API (independen) | `resource_key` → `permission_id` |

**Alur Akses:**

1. **Login** → Frontend panggil `GET /api/rbac/user-resources`, backend balikin daftar `resource_key` yang user punya (berdasarkan permission-role).
2. **Simpan di Session** → Cache frontend simpan daftar resource_key ke `session(\'data.resources\')` otomatis saat login.
3. **Cek Visibilitas UI** → `PermissionHelper::hasResource(\'siswa.create\')` cukup baca session, zero query.
4. **Cek Proteksi** → Halaman dilindungi oleh `PermissionHelper::hasResource()` di `mount()` dan `shouldRegisterNavigation()`. Backend endpoint dilindungi oleh middleware `endpoint.permission:xxx`.
5. **Cek Backend** → Route backend pakai middleware `resource:resource_key` (future) atau `can()` di controller.

**Superadmin Bypass:** Gate::before memberi superadmin akses penuh. `PermissionHelper::hasResource()` selalu return `true` untuk superadmin.
                            ')),

                        TextEntry::make('_arch_table')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->guideTable([
                                ['Komponen', 'Berkas / Lokasi', 'Fungsi'],
                                ['<code>Permission</code> Enum', '<code>backend/app/Enum/Permission.php</code>', 'Source of truth permission permanen'],
                                ['<code>permissions</code> (DB)', 'Database (Spatie)', 'CRUD via UI + seeder'],
                                ['<code>page_permissions</code> (DB)', 'Database', 'Satu tabel untuk resource registry + page security'],
                                ['<code>permission_endpoints</code> (DB)', 'Database', 'Mapping endpoint API ke permission (independen)'],
                                ['<code>PermissionHelper</code>', '<code>frontend-v2/app/Helpers/</code>', 'Helper untuk hasResource() di UI'],
                                ['<code>PermissionHelper</code>', '<code>frontend-v2/app/Helpers/</code>', 'Helper hasResource() untuk proteksi halaman + endpoint'],
                                ['<code>RbacDashboard</code>', 'frontend-v2 (halaman ini)', 'UI manajemen permission, role, resource, endpoint'],
                            ])),
                    ]),

                // ═══════════════════════════════════════════
                // Langkah 1
                // ═══════════════════════════════════════════

                Section::make('B. Langkah 1: Daftarkan Permission Baru')
                    ->description('Ada 2 cara: via UI (dinamis) dan via Backend Enum (untuk seeding permanen).')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-key')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_s1_title')
                            ->label('Cara 1: Via UI (Tanpa Deploy Ulang)')
                            ->html()
                            ->default($this->md('
Buka tab **Permissions**, klik **Permission Baru**, lalu isi:

| Field | Contoh | Keterangan |
|-------|--------|------------|
| `name` | `view-laporan-keuangan` | Kebab-case, harus unik. |
| `label` | Lihat Laporan Keuangan | Teks tampilan di checkbox Role. |
| `group` | Laporan Keuangan | Grup di checkbox Role Management. |
| `audience` | *(kosong)* atau `siswa` | Kosong = Admin. Isi "siswa" untuk role siswa. |

Setelah disimpan, permission langsung bisa dipilih di dropdown tab **Assign Role**, dan bisa di-bind ke **Resource Key**.
                            ')),

                        TextEntry::make('_s1_enum')
                            ->label('Cara 2: Via Backend Enum (Untuk Seeder/Permanent)')
                            ->html()
                            ->default($this->md('
```php
// backend/app/Enum/Permission.php

enum Permission: string
{
    // ... existing cases ...

    // ═══ FITUR BARU ═══
    case VIEW_LAPORAN_KEUANGAN = \'view-laporan-keuangan\';
    case EXPORT_LAPORAN_KEUANGAN = \'export-laporan-keuangan\';
}
```

Kemudian jalankan:
```bash
cd backend
php artisan permissions:sync
```
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Langkah 2 — NEW (merged resource + page security)
                // ═══════════════════════════════════════════

                Section::make('C. Langkah 2: Daftarkan Resource Key')
                    ->description('Langkah PALING PENTING. Semua kontrol akses (navigasi, tombol, halaman) menggunakan resource_key yang didaftarkan di tabel page_permissions.')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-document-text')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_s2_intro')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
**Convention Resource Key:**

```
{fitur}.{subfitur}      → level fitur (navigasi, halaman)
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

if (PermissionHelper::hasResource(\'siswa.create\')) {
    // Tampilkan tombol Buat Siswa
}

// Di Filament Page — proteksi halaman
public static function canAccess(): bool
{
    return PermissionHelper::hasResource(\'siswa\');
}
```

**Cara kerja proteksi halaman (via PermissionHelper + mount()):**

1. Middleware membaca **semua aturan aktif** dari tabel `page_permissions`.
2. Untuk setiap aturan, cek apakah user punya `resource_key` tersebut via `hasResource()`.
3. Aturan **pertama yang cocok** (resource_key milik user) → izinkan akses.
4. Jika **tidak ada aturan yang cocok** → 403 Forbidden.

> **Penting:** Jika user tidak punya akses ke resource manapun di `page_permissions`, maka semua halaman akan di-reject (kecuali login). Pastikan minimal ada 1 resource dengan group yang sesuai dengan role user.
                            ')),

                        TextEntry::make('_s2_contoh_resources')
                            ->label('Contoh Resource Key yang sudah di-Seed')
                            ->html()
                            ->default($this->md('
Dari seeder, berikut resource yang sudah terdaftar:

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
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Langkah 3 — NEW (endpoint mapping independent)
                // ═══════════════════════════════════════════

                Section::make('D. Langkah 3: Mapping Endpoint API')
                    ->description('Mapping endpoint backend ke permission. Resource_key di sini INDEPENDEN — tidak harus sama dengan yang di page_permissions.')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-link')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_s3')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
Buka tab **Endpoint Mapping**, klik **Tambah Endpoint**. Isi:

| Field | Contoh | Keterangan |
|-------|--------|------------|
| Resource Key | `api.siswa.index` | Identifier unik untuk endpoint ini. Convention: `api.{fitur}.{aksi}`. |
| Bind Permission | `view-siswa` | Pilih permission dari dropdown. |
| Group | API Siswa | Pengelompokan opsional. |
| Deskripsi | *(opsional)* | Catatan internal. |
| Aktif | Ya | Centang untuk mengaktifkan. |

**Endpoint mapping sekarang independen:**
- Resource key endpoint **TIDAK harus sama** dengan resource key di page_permissions.
- Anda bisa membuat resource key `api.siswa.index` yang di-bind ke permission `view-siswa`.
- Tabel `permission_endpoints` punya kolom `permission_id` langsung — tidak perlu auto-resolve.

**Cara proteksi di Backend (future):**

```php
// backend/routes/api.php
Route::get(\'/api/siswa\', [SiswaController::class, \'index\'])
    ->middleware([\'auth:sanctum\', \'resource:api.siswa.index\']);
```

**Cara proteksi di Frontend:**

```php
// Tidak perlu — endpoint hanya dicek di backend via middleware.
// Tapi jika ada tombol "Export API Key" yang berhubungan:
if (PermissionHelper::hasResource(\'api.laporan.export\')) {
    // Tampilkan tombol export
}
```

> **Catatan:** Middleware `resource:` di backend belum diimplementasi. Saat ini proteksi endpoint bisa dilakukan via `$request->user()->can(\'nama-permission\')` di controller.
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Langkah 4 — Assign Role
                // ═══════════════════════════════════════════

                Section::make('E. Langkah 4: Assign Permission ke Role')
                    ->description('Menghubungkan permission ke role. Langkah final agar resource benar-benar aktif.')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-shield-check')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_s4')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
Buka tab **Assign Role**:

1. Pilih role dari daftar (contoh: admin, superadmin, user, siswa).
2. Centang permission yang sesuai dengan resource yang ingin diakses.
3. Klik **Simpan**.

**Apa yang terjadi setelah assign:**

1. Permission langsung aktif untuk semua user dengan role tersebut.
2. Saat user login/logout ulang, frontend memanggil `GET /api/rbac/user-resources`.
3. Backend mengembalikan daftar `resource_key` dari `page_permissions` yang permission_name-nya cocok.
4. `PermissionHelper` menyimpan di session — semua pengecekan `hasResource()` jadi cepat (zero query).
5. Superadmin mendapat **semua** resource tanpa perlu assign.

**Urutan workflow untuk menambah fitur baru:**

1. **(Via UI)** Buat permission baru di tab Permissions — atau **(Via Enum)** Tambah case di `App\Enum\Permission` + `php artisan permissions:sync`.
2. **(Via UI)** Daftarkan resource key di tab Resource & Page Registry.
3. *(Opsional)* Mapping endpoint API di tab Endpoint Mapping.
4. **(Via UI)** Assign permission ke role di tab Assign Role.
5. Implementasi kode fitur di frontend/backend menggunakan `PermissionHelper::hasResource()`.
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Panduan Kode Backend
                // ═══════════════════════════════════════════

                Section::make('F. Panduan Kode: Backend (Laravel API)')
                    ->description('Cara mengimplementasikan permission di backend.')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-server-stack')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_code_enum')
                            ->label('1. Daftarkan di Permission Enum')
                            ->html()
                            ->default($this->codeBlock('
// backend/app/Enum/Permission.php

enum Permission: string
{
    // ... existing cases ...

    // ═══ FITUR BARU: ABSENSI ═══
    case VIEW_ABSENSI = \'view-absensi\';
    case CREATE_ABSENSI = \'create-absensi\';
    case READ_ABSENSI = \'read-absensi\';
    case UPDATE_ABSENSI = \'update-absensi\';
    case DELETE_ABSENSI = \'delete-absensi\';
}
                            ')),

                        TextEntry::make('_code_controller')
                            ->label('2. Gunakan Permission di Controller')
                            ->html()
                            ->default($this->codeBlock('
// backend/app/Http/Controllers/AbsensiController.php

use App\Enum\Permission;

class AbsensiController extends Controller
{
    public function index()
    {
        return response()->json([\'data\' => Absensi::all()]);
    }

    public function store(Request $request)
    {
        // Proteksi tambahan di level controller
        if (!$request->user()->can(Permission::CREATE_ABSENSI->value)) {
            abort(403, \'Unauthorized\');
        }

        // ... logic create ...
    }
}
                            ')),

                        TextEntry::make('_code_route')
                            ->label('3. Daftarkan Route')
                            ->html()
                            ->default($this->md('
**Disarankan — via `can()` di controller** (tanpa middleware route):

```php
// backend/routes/api.php
Route::apiResource(\'absensi\', AbsensiController::class)
    ->middleware([\'auth:sanctum\']);
```

Cukup gunakan `auth:sanctum`. Permission dicek manual di controller via `$request->user()->can()`.

**Alternatif — via Spatie middleware (jika perlu hardcode):**

```php
Route::get(\'/absensi\', [AbsensiController::class, \'index\'])
    ->middleware([\'auth:sanctum\', \'permission:view-absensi\']);
```

> **Catatan:** Jika ingin proteksi dinamis (tanpa hardcode nama permission di route), gunakan middleware `resource:` yang akan datang.
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Panduan Kode Frontend — NEW (hasResource only)
                // ═══════════════════════════════════════════

                Section::make('G. Panduan Kode: Frontend Filament')
                    ->description('Semua kontrol akses di frontend menggunakan `PermissionHelper::hasResource()` — TIDAK ADA `has()` lagi.')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-window')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_code_page')
                            ->label('1. Proteksi Halaman Filament')
                            ->html()
                            ->default($this->codeBlock('
// frontend-v2/app/Filament/Pages/AbsensiPage.php

use App\Helpers\PermissionHelper;

class AbsensiPage extends Page
{
    // Proteksi via canAccess() — menggunakan resource_key
    public static function canAccess(): bool
    {
        return PermissionHelper::hasResource(\'absensi\');
    }

    // Di masa depan: resourceKey akan dibaca middleware otomatis
    // protected ?string $resourceKey = \'absensi\';
}
                            ')),

                        TextEntry::make('_code_table')
                            ->label('2. Sembunyikan Tombol/Aksi Berdasarkan Resource Key')
                            ->html()
                            ->default($this->codeBlock('
// frontend-v2/app/Filament/Pages/AbsensiPage.php

// Di header action:
HeaderAction::make(\'create\')
    ->visible(fn() => PermissionHelper::hasResource(\'absensi.create\'))

// Di table action:
Tables\Actions\EditAction::make()
    ->visible(fn() => PermissionHelper::hasResource(\'absensi.update\'))

Tables\Actions\DeleteAction::make()
    ->visible(fn() => PermissionHelper::hasResource(\'absensi.delete\'))
                            ')),

                        TextEntry::make('_code_blade')
                            ->label('3. Conditional Rendering di Blade')
                            ->html()
                            ->default($this->codeBlock('
<!-- resources/views/absensi/index.blade.php -->

@if(PermissionHelper::hasResource(\'absensi.export\'))
    <x-filament::button
        wire:click="export"
        color="success"
        icon="heroicon-o-arrow-down-tray"
    >
        Export Absensi
    </x-filament::button>
@endif
                            ')),

                        TextEntry::make('_code_resource')
                            ->label('4. Gunakan hasResource untuk Navigasi')
                            ->html()
                            ->default($this->codeBlock('
// Cek resource sebagai gate navigasi
if (PermissionHelper::hasResource(\'absensi-online\')) {
    // Tampilkan menu absensi online
}

// Atau di Filament sidebar:
NavigationItem::make(\'Absensi Online\')
    ->url(\'/absensi-online\')
    ->visible(fn() => PermissionHelper::hasResource(\'absensi-online\'))
    ->icon(\'heroicon-o-clock\')
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // PermissionHelper API — NEW (no has())
                // ═══════════════════════════════════════════

                Section::make('H. Referensi PermissionHelper API')
                    ->description('Semua method yang tersedia. Catatan: `has()` sudah dihapus — gunakan `hasResource()`.')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-code-bracket')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_helper_api')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->guideTable([
                                ['Method', 'Parameter', 'Return', 'Keterangan'],
                                ['<code>hasResource()</code>', '<code>string $resourceKey</code>', 'bool', 'Cek akses resource key. Superadmin selalu true. **Method UTAMA.**'],
                                ['<code>hasAnyInGroup()</code>', '<code>string $group</code>', 'bool', 'Cek apakah user punya akses ke salah satu resource dalam grup (mis: akademik).'],
                                ['<code>canViewJenjang()</code>', '<code>string $jenjang</code>', 'bool', 'Cek akses jenjang (KB/TK/MI).'],
                                ['<code>visibleJenjang()</code>', '<code>void</code>', 'array', 'Daftar jenjang yang visible untuk user.'],
                            ])),

                        TextEntry::make('_helper_note')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
**File:** `frontend-v2/app/Helpers/PermissionHelper.php`

**Superadmin Bypass:**
Semua method di atas memiliki superadmin bypass — jika user memiliki role `superadmin`, return `true` tanpa perlu cek database.

**Session Cache:**
Saat login, frontend memanggil `GET /api/rbac/user-resources` dan menyimpan hasilnya di `session(data.resources)`. Semua pengecekan `hasResource()` hanya membaca session, tanpa query database.

**Method UTAMA — `hasResource()`:**
- `PermissionHelper::hasResource(siswa)` → cek akses resource key `siswa`
- `PermissionHelper::hasResource(siswa.create)` → cek akses resource key `siswa.create`
- Resource key didaftarkan di tabel `page_permissions` (tab **Resource & Page Registry**).
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Seeder
                // ═══════════════════════════════════════════

                Section::make('I. Workflow Seeder & Permission Sync')
                    ->description('Cara menyinkronkan permission enum + resource key ke database.')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-circle-stack')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_seeder')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
**1. Sync Permission Enum ke Database:**

```bash
# Dari direktori backend
php artisan permissions:sync
# Hapus permission yang tidak ada di enum (hati-hati!)
php artisan permissions:sync --prune
```

**2. Seed Resource Key ke page_permissions:**

```bash
php artisan db:seed --class=PermissionResourceSeeder
```

Ini akan mengisi 90+ resource key yang sudah didefinisikan di `PermissionResourceSeeder`.

**Kapan harus menjalankan:**

- `permissions:sync` — setelah menambah/mengubah case di `App\Enum\Permission`.
- `db:seed --class=PermissionResourceSeeder` — setelah deploy ulang database, atau setelah menambah resource key baru di seeder.

**Peringatan:**
Hapus/rename permission via UI dapat menyebabkan error jika permission tersebut masih di-bind ke resource key. Sebaiknya **nonaktifkan** dulu permission via UI sebelum menghapus.
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // FAQ — NEW
                // ═══════════════════════════════════════════

                Section::make('J. Catatan Penting & FAQ')
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-question-mark-circle')
                    ->columns(1)
                    ->schema([

                        TextEntry::make('_faq')
                            ->hiddenLabel()
                            ->html()
                            ->default($this->md('
**Q: Apakah superadmin perlu permission?**
Tidak. `Gate::before` memberi bypass penuh ke semua fitur. `PermissionHelper::hasResource()` selalu return `true` untuk superadmin.

**Q: Apa bedanya resource key dengan permission?**
Resource key adalah **pointer** — string unik yang di-refer oleh kode. Permission name adalah **izin sesungguhnya** yang dicek oleh Spatie. Satu resource key di-bind ke satu permission name di tabel `page_permissions`. Kode **tidak pernah** menyebut permission name, hanya resource key.

**Q: Kenapa endpoint mapping pakai resource_key sendiri?**
Agar fleksibel. Resource key di page_permissions (`siswa.create`) bisa berbeda dengan di endpoint mapping (`api.siswa.store`). Keduanya punya permission binding masing-masing.

**Q: Saya ingin menambah fitur baru, apa langkah-langkahnya?**
1. Buat permission baru (UI atau Enum).
2. Daftarkan resource key di tab Resource & Page Registry.
3. *(Opsional)* Mapping endpoint API.
4. Assign permission ke role di tab Assign Role.
5. Di kode frontend: gunakan `PermissionHelper::hasResource(\'resource-key\')`.
6. Di kode backend: gunakan `$request->user()->can(\'nama-permission\')`.

**Q: Proteksi halaman Filament cara kerjanya bagaimana?**
Middleware membaca **semua aturan aktif** dari `page_permissions`. Untuk setiap aturan, cek `hasResource(resource_key)`. Aturan pertama yang cocok (user punya resource) → izinkan. Jika tidak ada yang cocok → 403 Forbidden.

**Q: Kenapa saya bisa akses halaman Filament meskipun belum assign permission?**
Kemungkinan: (1) Anda login sebagai superadmin (bypass total). (2) `PermissionHelper::hasResource()` mengembalikan true. (3) `mount()` atau `shouldRegisterNavigation()` tidak diimplementasikan. Proteksi sekarang via mount() gate + backend EndpointPermission, bukan middleware global.

**Q: Bagaimana dengan DynamicPermissionMiddleware yang lama?**
Sudah dinonaktifkan. Tidak ada lagi middleware berbasis method+path di backend. Proteksi endpoint dilakukan via `can()` di controller. Di masa depan akan ada middleware `resource:resource_key`.
                            ')),
                    ]),

                // ═══════════════════════════════════════════
                // Footer / Summary
                // ═══════════════════════════════════════════

                Section::make('Ringkasan untuk Pengembang Baru')
                    ->collapsible()
                    ->icon('heroicon-o-check-circle')
                    ->columns(2)
                    ->schema([

                        TextEntry::make('_summary_backend')
                            ->label('Backend Checklist')
                            ->html()
                            ->default($this->md('
1. Tambah case di `App\Enum\Permission`
2. Jalankan `php artisan permissions:sync`
3. Di controller: `$request->user()->can(Permission::NAMA->value)`
4. Routing: cukup `auth:sanctum` — tanpa middleware `dynamic.permission`
                            ')),

                        TextEntry::make('_summary_frontend')
                            ->label('Frontend Checklist')
                            ->html()
                            ->default($this->md('
1. Daftarkan resource key dan pattern proteksi di tab **Resource & Page Registry**
2. Gunakan `PermissionHelper::hasResource()` untuk proteksi komponen (halaman, tombol, navigasi)
3. Assign permission ke role di tab **Assign Role**
                            ')),

                        TextEntry::make('_summary_warning')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->html()
                            ->default('<div class="mt-2 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
                                <p class="font-semibold text-amber-800 dark:text-amber-200 mb-2">Hal Penting</p>
                                <ul class="list-disc pl-5 space-y-1 text-sm text-amber-700 dark:text-amber-300">
                                    <li>Superadmin <strong>tidak butuh permission</strong> — Gate::before bypass.</li>
                                    <li>Permission baru cukup daftar via UI — <strong>tanpa deploy ulang</strong>.</li>
                                    <li>Setiap permission baru harus di-<strong>assign ke role</strong> via Assign Role.</li>
                                    <li>Permission yang bersifat permanen sebaiknya <strong>ada di <code>App\Enum\Permission</code></strong> agar konsisten saat di-seed ulang.</li>
                                    <li>Proteksi halaman cukup daftarkan di tab **Resource & Page Registry** — tanpa kode PHP.</li>
                                    <li>Gunakan <code>PermissionHelper::hasResource()</code> untuk kontrol tampilan komponen — jangan hanya andalkan middleware.</li>
                                </ul>
                            </div>'),
                    ]),

            ]);
    }

    private function guideTable(array $rows): string
    {
        $html = '<table class="w-full text-xs border-collapse border border-gray-200 dark:border-gray-700">';
        foreach ($rows as $i => $row) {
            $tag = $i === 0 ? 'th' : 'td';
            $class = $i === 0
                ? 'border border-gray-200 dark:border-gray-700 p-2 text-left bg-gray-100 dark:bg-gray-800'
                : 'border border-gray-200 dark:border-gray-700 p-2 text-left';
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= "<{$tag} class=\"{$class}\">{$cell}</{$tag}>";
            }
            $html .= '</tr>';
        }

        return $html.'</table>';
    }

    /**
     * Helper to render markdown-like text as HTML inside TextEntry.
     * Supports **bold**, `code`, [text](link), and basic lists.
     */
    private function md(string $text): string
    {
        // Escape HTML chars first (except our intentional markup)
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);

        // Convert markdown code blocks ```...``` to <pre><code>
        $text = preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($m) {
            $lang = $m[1] ? ' class="language-'.htmlspecialchars($m[1]).'"' : '';

            // We already escaped, need to re-allow certain chars inside code blocks
            return '<pre'.$lang.'><code>'.$m[2].'</code></pre>';
        }, $text);

        // Convert **text** to <strong>
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Convert `code` to <code>
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // Convert [text](url) to <a>
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="text-primary-600 dark:text-primary-400 underline">$1</a>', $text);

        // Convert paragraphs (double newlines)
        $paragraphs = array_filter(explode("\n\n", $text));
        $html = '';
        foreach ($paragraphs as $para) {
            $para = trim($para);
            // Skip if it's already a block element
            if (str_starts_with($para, '<pre') || str_starts_with($para, '<table') || str_starts_with($para, '<ul') || str_starts_with($para, '<div')) {
                $html .= $para."\n";

                continue;
            }
            // Convert single newlines to <br> within paragraphs
            $para = str_replace("\n", '<br>', $para);

            // Check if it's a list item
            if (preg_match('/^\d+\.\s/', $para) || str_starts_with(ltrim($para), '- ')) {
                $html .= $para."\n";
            } else {
                $html .= '<p class="text-sm text-gray-600 dark:text-gray-400 mb-2">'.$para.'</p>';
            }
        }

        // Convert numbered lists
        $html = preg_replace_callback('/(\d+\.\s[^\n]+)(\n\d+\.\s[^\n]+)*/', function ($m) {
            $items = preg_split('/\n(?=\d+\.\s)/', $m[0]);
            $lis = '';
            foreach ($items as $item) {
                $content = preg_replace('/^\d+\.\s/', '', trim($item));
                $lis .= '<li>'.$content.'</li>';
            }

            return '<ol class="list-decimal pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">'.$lis.'</ol>';
        }, $html);

        // Convert dashes to unordered lists
        $html = preg_replace_callback('/(?:^- [^\n]+(?:\n- [^\n]+)*)/m', function ($m) {
            $items = preg_split('/\n- /', $m[0]);
            $lis = '';
            foreach ($items as $i => $item) {
                if ($i === 0) {
                    $item = preg_replace('/^- /', '', $item);
                }
                $lis .= '<li>'.trim($item).'</li>';
            }

            return '<ul class="list-disc pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">'.$lis.'</ul>';
        }, $html);

        return $html;
    }

    /**
     * Render a code block with syntax highlighting support.
     */
    private function codeBlock(string $code): string
    {
        $code = trim($code);

        // Use filament's default code styling
        return '<pre class="p-4 rounded-lg overflow-x-auto text-xs leading-relaxed"
            style="background: #1e293b; color: #e2e8f0; font-family: \'JetBrains Mono\', \'Fira Code\', monospace;">'
            .htmlspecialchars($code, ENT_QUOTES, 'UTF-8', false).
            '</pre>';
    }
}
