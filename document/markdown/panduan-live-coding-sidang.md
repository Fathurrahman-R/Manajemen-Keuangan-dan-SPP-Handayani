# Panduan Live Coding Sidang TA — Handayani

Dokumen tempur untuk sidang. Isinya peta codebase + resep eksekusi terurut, supaya kalau penguji bilang "coba tambahkan kolom X" atau "coba ubah tampilan ini", yang dilakukan bukan mencari-cari file, tapi langsung menjalankan resep.

Dokumentasi teknis lengkap tetap di `docs/` — panduan ini merujuknya, tidak menggantikannya:
[Gotchas](../../docs/gotchas.md) · [RBAC](../../docs/rbac.md) · [Command Reference](../../docs/commands.md) · [Testing](../../docs/testing.md) · [Setup](../../docs/setup.md) · [Midtrans](../../docs/midtrans.md)

**Isi cepat:** §1 peta 60 detik · §2 tabel fitur→file · §3 alur end-to-end · §4 Resep A tambah field · §5 Resep B validasi · §6 Resep C UI · §7 Resep D fitur baru · **§8.0 pemeriksaan pra-sidang** · **§8.5 Midtrans** · **§8.6 Email & notifikasi** · §9 troubleshooting · §10 jangan dilakukan · §11 pertanyaan konsep

---

## 1. Peta 60 detik (hafalkan, untuk dijawab tanpa buka laptop)

Monorepo, **dua aplikasi Laravel 12 yang berbagi satu database**:

- **`backend/`** — API headless. Pemilik skema database, migrasi, model, aturan bisnis, autentikasi Sanctum. Tidak punya tampilan.
- **`frontend-v2/`** — Laravel 12 + Filament 4 + Livewire 3. Panel admin dan portal siswa. **Tidak punya migrasi dan tidak pakai Eloquent** untuk data domain — semua data diambil lewat HTTP ke backend via `ApiService`, token Sanctum disimpan di session.

Alur satu request:

```
Browser → Filament Page (tipis, cek izin)
        → Komponen Livewire (tabel, form, aksi)
        → ApiService::client()  [Bearer token + header X-Branch-Id]
        → HTTP  →  backend/routes/api.php
        → middleware auth:sanctum + active.branch + endpoint.permission:<resource_key>
        → Controller → FormRequest (validasi) → Model/Service → Database
        → API Resource (toArray) → JSON → balik ke Livewire → dirender
```

**RBAC dua lapis** (ini pertanyaan favorit penguji):

| Lapis | Tabel | Dicek di mana |
|---|---|---|
| UI (halaman, tombol, menu) | `page_permissions` | `PermissionHelper::hasResource('siswa.create')` di frontend, baca session |
| API (endpoint) | `permission_endpoints` | middleware `endpoint.permission:siswa.create` di backend |

Keduanya memetakan `resource_key` → nama permission Spatie. Kode tidak pernah menulis nama permission secara langsung, hanya `resource_key`. Superadmin di-bypass lewat `Gate::before`.

**Angka untuk disebut:** ~38 tabel, 30 controller backend, 25 halaman Filament, 33 komponen Livewire, 12 widget dashboard.

---

## 2. Tabel lookup: penguji sebut X → buka file Y

Semua path relatif dari root repo. `FE` = `frontend-v2/`, `BE` = `backend/`.

| Fitur | FE Page (`app/Filament/Pages/`) | FE Livewire (`app/Livewire/`) | Blade (`resources/views/filament/pages/`) | Endpoint | BE Controller (`app/Http/Controllers/`) |
|---|---|---|---|---|---|
| Data Siswa | `DataMasterSiswa.php` | `DataSiswa.php` | `data-master-siswa.blade.php` | `/siswa/{jenjang}` | `SiswaController.php` |
| Detail Siswa | `DetailSiswa.php` | `DetailSiswa.php` | `detail-siswa.blade.php` | `/siswa/{jenjang}/{id}` | `SiswaController.php` |
| Data Kelas | `DataMasterKelas.php` | `DataKelas.php` | `data-master-kelas.blade.php` | `/kelas/{jenjang}` | `KelasController.php` |
| Kategori | `DataMasterCategory.php` | `DataCategory.php` | `data-master-category.blade.php` | `/kategori` | `KategoriController.php` |
| Jenis Tagihan | `TransaksiJenisTagihan.php` | `JenisTagihan.php` | `transaksi-jenis-tagihan.blade.php` | `/jenis-tagihan` | `JenisTagihanController.php` |
| Tagihan | `TransaksiTagihan.php` | `TagihanCardView.php` | `transaksi-tagihan.blade.php` | `/tagihan`, `/pembayaran/bayar/*` | `TagihanController.php` |
| Pembayaran | `TransaksiPembayaran.php` | `PembayaranCardView.php` | `transaksi-pembayaran.blade.php` | `/pembayaran/grouped`, `/pembayaran/kwitansi/*` | `PembayaranController.php`, `PdfGeneratorController.php` |
| Pengeluaran (approval) | `PengeluaranRequestPage.php` | `PengeluaranRequest.php` | `pengeluaran-request.blade.php` | `/pengeluaran-request` | `PengeluaranRequestController.php` |
| Kenaikan Kelas | `KenaikanKelasPage.php` | `KenaikanKelas.php` | `kenaikan-kelas.blade.php` | `/kenaikan-kelas/*` | `KenaikanKelasController.php` |
| Tahun Ajaran | `TahunAjaranManagement.php` | `TahunAjaranManagement.php` | `tahun-ajaran-management.blade.php` | `/tahun-ajaran` | `TahunAjaranController.php` |
| Laporan Kas Harian | `LaporanKasHarian.php` | `KasHarian.php` | `laporan-kas-harian.blade.php` | `/laporan/kas` | `KasController.php` |
| Rekap Bulanan | `LaporanRekapBulanan.php` | `RekapBulanan.php` | `laporan-rekap-bulanan.blade.php` | `/laporan/rekap` | `KasController.php` |
| Manajemen User | `UserManagement.php` | `UserManagement.php` | `user-management.blade.php` | `/users` | `UserController.php` |
| Manajemen RBAC | `RbacDashboard.php` | `RbacPermissionsTable.php`, `RbacEndpointsTable.php`, `RbacPagePermissionsTable.php`, `RoleManagement.php` | `rbac-dashboard.blade.php` | `/rbac/*` | `RbacController.php` |
| Midtrans (peta lengkap di **§8.5**) | `TransaksiMidtransPage.php`, `TransaksiMidtransDetailPage.php` | `TransaksiMidtrans.php`, `TransaksiMidtransDetail.php` | `transaksi-midtrans*.blade.php` | `/midtrans/*` | `MidtransAdminController.php`, `MidtransTransactionController.php`, `MidtransNotificationController.php` |
| Akun Siswa | `ManajemenAkunSiswa.php` | `AkunSiswaCredentialsTable.php` | — | `/akun-siswa/*` | `AkunSiswaController.php` |
| Cabang | `BranchManagement.php`, `BranchApprovalSettingsPage.php` | `BranchManagement.php`, `BranchApprovalSettings.php` | `branch-*.blade.php` | `/branches`, `/branch-approval-settings` | `BranchController.php`, `BranchApprovalSettingController.php` |
| Notifikasi | `NotificationLogPage.php`, `NotificationSettingsPage.php` | `NotificationLogTable.php`, `NotificationSettings.php` | `notification-*.blade.php` | `/notification-*` | `NotificationLogController.php`, `NotificationSettingController.php` |
| Dashboard | `DashboardPage.php` | 12 widget di `app/Filament/Widgets/` | `dashboard.blade.php` | `/dashboard/overview` | `DashboardController.php` |
| Portal Siswa | `app/Filament/Portal/Pages/Portal*.php` | `PortalSiswa*Table.php`, `SiswaDashboard.php` | — | `/tagihan/siswa`, `/pembayaran/siswa` | `TagihanController@siswaView`, `PembayaranController@siswaView` |

**Backend, per fitur** (nama file mengikuti nama fitur, konsisten):

| Lapis | Lokasi | Contoh |
|---|---|---|
| Route | `BE/routes/api.php` (368 baris, dikelompokkan per prefix) | `Route::prefix('/siswa')` |
| Validasi | `BE/app/Http/Requests/` | `SiswaRequest.php`, `KelasRequest.php`, `TagihanRequest.php` |
| Serialisasi | `BE/app/Http/Resources/` | `SiswaResource.php`, `KelasResource.php` |
| Model | `BE/app/Models/` (32 model) | `Siswa.php`, `Kelas.php`, `Tagihan.php` |
| Aturan bisnis kompleks | `BE/app/Services/` | `KenaikanKelasService.php`, `WorkflowService.php`, `LaporanService.php`, `AutoApprovalService.php` |
| Migrasi | `BE/database/migrations/` | **hanya di sini, jangan pernah di `frontend-v2`** |
| Test | `BE/tests/Feature/` | `SiswaTest.php`, `KelasTest.php`, `KenaikanKelasTest.php` |

---

## 3. Anatomi end-to-end (modal menjawab "coba jelaskan alurnya")

Contoh: admin membuka **Data Siswa**, lalu menambah siswa baru.

1. **`FE/app/Filament/Pages/DataMasterSiswa.php:31`** — halaman ini sengaja tipis. Isinya cuma `mount()` yang memanggil `abort_if(! PermissionHelper::hasResource('siswa.view'), 403)` dan membaca query param `jenjang`. Navigasinya tidak didaftarkan otomatis (`shouldRegisterNavigation()` return `false`), karena menu sidebar dirakit manual di `AdminPanelProvider` agar bisa dipisah per jenjang (KB/TK/MI).

2. **`FE/resources/views/filament/pages/data-master-siswa.blade.php`** — cuma merender komponen Livewire: `@livewire('data-siswa', ['jenjang' => $this->activeJenjang])`.

3. **`FE/app/Livewire/DataSiswa.php:53`** — `table()` mendefinisikan tabel Filament. Sumber datanya bukan Eloquent, tapi closure `->records()` di L57 yang menerima `$search`, `$page`, `$recordsPerPage`, `$filters`, `$sortColumn` dari Filament, menyusunnya jadi query string, lalu memanggil API di L93 dan membungkus hasilnya jadi `LengthAwarePaginator` manual.

4. **`FE/app/Services/ApiService.php:18`** — `client()` mengembalikan `PendingRequest` yang sudah berisi header `Authorization: Bearer <token dari session>`, `Accept: application/json`, dan `X-Branch-Id` kalau ada cabang aktif, dengan `baseUrl(config('handayani.api_url'))`.

5. **`BE/routes/api.php:161`** — `Route::get('/{jenjang}', [SiswaController::class, 'index'])->middleware('endpoint.permission:siswa.view')`, di dalam grup `['auth:sanctum', 'active.branch']`. Middleware-nya terdaftar di `BE/bootstrap/app.php:22` dan `:26`.

6. **`BE/app/Http/Controllers/SiswaController.php:31`** — `index()` membangun query dengan eager loading `['ayah','ibu','wali','kelas','kategori']`, memfilter `jenjang` dan `branch_id` milik user, menerapkan search/filter/sort, lalu `paginate()`.

7. **`BE/app/Http/Resources/SiswaResource.php`** — `toArray()` menentukan bentuk JSON persis. Field yang tidak ditulis di sini **tidak akan pernah sampai ke frontend**, walaupun ada di database.

8. Balik ke Livewire, `TextColumn::make('nis')` dan kawan-kawan (L136–160) membaca key JSON tadi. Karena record-nya array, bukan model, kolom relasi ditulis `TextColumn::make('kelas.nama')` atau pakai `->state(fn (array $record) => $record['ayah']['nama'] ?? '-')`.

Untuk **menambah** siswa: tombolnya `Action::make('add')` di L999, berisi `Wizard` dengan `TextInput`/`Select`, dan `->action(...)` di L975 yang mem-`POST` `$data` ke `/siswa/{jenjang}`. Validasi dilakukan di **`BE/app/Http/Requests/SiswaRequest.php`**; kalau gagal, `failedValidation()` (L188) melempar HTTP **400** dengan body `{"errors": {...}}` — bukan 422 seperti default Laravel — dan Livewire menampilkannya lewat `$response->json('errors')`.

---

## 4. Resep A — Menambah field/kolom baru

Skenario khas: *"Coba tambahkan kolom `wali_kelas` di data kelas, tampilkan di tabel dan bisa diisi saat tambah kelas."*

Contoh nyata yang sudah ada di repo dan bisa dicontek persis: kolom `level` pada tabel `kelas`, ditambahkan lewat migrasi `BE/database/migrations/2026_05_26_100000_add_level_column_to_kelas_table.php`.

> **Resep ini sudah diuji end-to-end** di branch buangan pada 2026-08-04 dengan kolom `wali_kelas` pada tabel `kelas`: create/update/list lewat API dan lewat UI Filament semuanya jalan, validasi `max:100` mengembalikan HTTP 400 sesuai format. Semua jebakan di bawah adalah yang benar-benar ditemui saat pengujian itu.

**Tujuh titik sentuh, urutannya wajib seperti ini:**

**Langkah 1 — Migrasi (backend).**

```bash
cd backend
php artisan make:migration add_wali_kelas_column_to_kelas_table --no-interaction
```

```php
public function up(): void
{
    Schema::table('kelas', function (Blueprint $table) {
        $table->string('wali_kelas', 100)->nullable()->after('nama');
    });
}

public function down(): void
{
    Schema::table('kelas', function (Blueprint $table) {
        $table->dropColumn('wali_kelas');
    });
}
```

```bash
php artisan migrate
```

> Kolom baru **selalu** dibuat `nullable()` saat live coding. Tabel sudah berisi data seeder; kolom `NOT NULL` tanpa default akan menggagalkan migrasi di depan penguji.

**Langkah 2 — Model (`BE/app/Models/Kelas.php`).** Tambahkan ke `$fillable`:

```php
protected $fillable = [
    'jenjang',
    'nama',
    'wali_kelas',   // ← baru
    'branch_id',
    'level',
];
```

Kalau tipenya bukan string (integer, boolean, date), tambahkan juga di `casts()`. Perhatikan: `Kelas` pakai method `casts()`, sedangkan `Siswa` masih pakai properti `$casts` — ikuti gaya file yang sedang diedit.

**Langkah 3 — Validasi (`BE/app/Http/Requests/KelasRequest.php`).**

```php
'wali_kelas' => [
    'nullable',
    'string',
    'max:100',
],
```

Field yang tidak ada di `rules()` akan dibuang oleh `$request->validated()` dan **tidak akan pernah tersimpan**, walaupun sudah masuk `$fillable`.

**Langkah 3b — Controller: cek cara data disimpan (`BE/app/Http/Controllers/KelasController.php`).**

Ini langkah yang paling gampang terlewat. **Tidak semua controller menyimpan data dengan mass-assignment.** Buka controller-nya dan lihat:

- **Mass-assignment** — contoh `SiswaController::create()`: `new Siswa($data)`. Cukup `$fillable`, controller tidak perlu disentuh.
- **Assignment manual** — contoh `KelasController`: field ditulis satu per satu. Di sini `$fillable` saja **tidak cukup**, controller wajib diedit:

```php
// create() — di dalam new Kelas([...])
$kelas = new Kelas([
    'jenjang' => $jenjangUp,
    'nama' => $namaUp,
    'wali_kelas' => $data['wali_kelas'] ?? null,   // ← baru
    'branch_id' => $branchId,
    'level' => $level,
]);

// update() — di dekat $kelas->nama = ...
$kelas->wali_kelas = array_key_exists('wali_kelas', $data) ? $data['wali_kelas'] : $kelas->wali_kelas;
```

Kalau langkah ini dilewat pada controller bergaya manual, API akan membalas 201/200 seolah sukses tapi kolomnya tetap `null` — gejala paling membingungkan saat ditonton penguji.

**Langkah 4 — API Resource (`BE/app/Http/Resources/KelasResource.php`).**

```php
return [
    'id' => $this->id,
    'jenjang' => $this->jenjang,
    'nama' => $this->nama,
    'wali_kelas' => $this->wali_kelas,   // ← baru
    'level' => $this->level,
    'branch_id' => $this->branch_id,
];
```

**Langkah 5 — Form di Livewire (`FE/app/Livewire/DataKelas.php`).** Ada **dua** form yang harus diurus: aksi `update` (schema L111, `fillForm` L106, payload L122) dan aksi `add` (schema L229, payload L245).

```php
// di ->schema([...]) kedua aksi
TextInput::make('wali_kelas')
    ->label('Wali Kelas')
    ->maxLength(100),
```

```php
// di ->fillForm(...) aksi update, supaya nilainya terisi saat modal edit dibuka
'wali_kelas' => $record['wali_kelas'] ?? null,
```

```php
// di ->action(...) kedua aksi, karena payload disusun manual
$payload = [
    'nama' => $data['nama'],
    'wali_kelas' => $data['wali_kelas'] ?? null,   // ← baru
    'level' => ...,
];
```

> Jebakan terbesar: di `DataKelas` payload disusun manual per key, jadi field baru **harus** ditambahkan ke `$payload`. Bandingkan dengan `DataSiswa` yang mengirim `$data` utuh (`->post('/siswa/'.$this->activeTab, $data)`) sehingga tidak perlu langkah ini. Selalu cek dulu bentuk `->action()` di file yang sedang diedit.

**Langkah 6 — Kolom tabel (`FE/app/Livewire/DataKelas.php`, dalam `->columns([...])`).**

```php
TextColumn::make('wali_kelas')
    ->label('Wali Kelas')
    ->searchable()
    ->placeholder('-'),
```

**Verifikasi:** refresh halaman Data Kelas → kolom muncul → tambah/edit satu kelas → nilainya tersimpan dan tampil. Tidak perlu `npm run build` karena tidak ada kelas CSS baru.

**Diagnosis cepat kalau gagal:**

| Gejala | Penyebab |
|---|---|
| Kolom tampil kosong terus | Lupa **Langkah 4** (API Resource) |
| API balas sukses tapi kolom tetap `null` di DB | Lupa **Langkah 3b** (controller assign manual) |
| Form terisi, disimpan, hilang lagi | Lupa **Langkah 2** (`$fillable`) atau **Langkah 3** (rules) |
| Nilai lama tidak muncul saat modal edit dibuka | Lupa menambah field di `->fillForm(...)` |
| Muncul notifikasi error validasi | Rules terlalu ketat, atau field belum ada di `rules()` sama sekali |
| `Column not found` | Migrasi belum dijalankan (`php artisan migrate`) |

**Membatalkan migrasi kalau penguji minta dikembalikan** — jangan pakai `migrate:rollback --step=1`. Perintah itu membatalkan **satu batch penuh**, dan kalau ada migrasi lain yang kebetulan ikut jalan bersamaan, semuanya ikut dibatalkan. Sebut file-nya eksplisit:

```bash
php artisan migrate:rollback --path=database/migrations/2026_08_04_174948_add_wali_kelas_column_to_kelas_table.php
```

---

## 5. Resep B — Mengubah validasi / aturan bisnis

**Validasi input** ada di `BE/app/Http/Requests/`. Nama file mengikuti fitur (`SiswaRequest`, `TagihanRequest`, `PengeluaranRequest`, `BulkPromotionRequest`, …). Untuk siswa, aturannya berbeda per jenjang: `SiswaRequest.php:27` membaca `$this->route('jenjang')` lalu membuat field wajib/opsional secara dinamis (MI butuh ayah & ibu, KB/TK butuh wali).

Contoh mengubah aturan NIS dari minimal 4 jadi minimal 8 digit:

```php
// BE/app/Http/Requests/SiswaRequest.php
'nis' => [
    'required',
    'max:20',
    'regex:/^[0-9]+$/',
    'min:8',    // ← dari 4
],
```

Pesan error kustom ditambahkan di `messages()` pada file yang sama:

```php
public function messages(): array
{
    return [
        'nis.min' => 'NIS minimal 8 digit.',
        // ...
    ];
}
```

**Format error** seragam di seluruh backend: `failedValidation()` melempar HTTP **400** dengan body `{"errors": {"field": ["pesan"]}}`. Di frontend, pola penanganannya seperti ini (contoh `DataKelas.php:130`):

```php
if (! $response->ok()) {
    $errors = $response->json()['errors'] ?? [];
    $errorKeys = array_keys($errors);
    $message = ! empty($errorKeys) ? $errors[$errorKeys[0]][0] : 'Gagal';
    Notification::make()->title($message)->danger()->send();
}
```

Ada juga **validasi sisi frontend** di Filament (`->required()`, `->validationMessages([...])`, `->numeric()`, `->minValue()`). Kalau penguji minta "validasi supaya tidak bisa kosong", pilihannya:
- cukup ubah frontend → cepat, tapi bisa ditembus lewat API langsung;
- ubah backend → benar secara keamanan;
- **jawaban terbaik saat sidang:** ubah keduanya, lalu jelaskan bahwa frontend untuk UX dan backend untuk penegakan.

**Aturan bisnis non-validasi** ada di `BE/app/Services/`:

| Aturan | File |
|---|---|
| Kenaikan kelas, kelulusan, transfer lintas jenjang, undo batch | `KenaikanKelasService.php` |
| Alur approval pengeluaran (submit → approve → disburse) | `WorkflowService.php` |
| Auto-approval per cabang | `AutoApprovalService.php` |
| Perhitungan laporan kas & rekap | `LaporanService.php` |
| Nomor kwitansi, kode tagihan, kode pembayaran | `GenerateKodeTagihan.php`, `GenerateKodePembayaran.php`, `GenerateSejumlahKwitansi.php` |
| Perhitungan fee Midtrans | `Midtrans/` |

**Test terkait** — jalankan hanya yang relevan, bukan seluruh suite:

```bash
cd backend
php artisan test --filter=SiswaTest
php artisan test --filter=KelasTest
php artisan test --filter=KenaikanKelasTest
```

---

## 6. Resep C — Mengubah tampilan (UI)

Semua di `frontend-v2`. Yang perlu diingat: **komponen Filament ditulis dalam PHP**, jadi sebagian besar perubahan UI cuma edit PHP dan langsung terlihat setelah refresh, tanpa build asset.

### Ganti label / judul / teks

```php
// Label kolom & field — di komponen Livewire
TextColumn::make('nama')->label('Nama Lengkap Siswa'),
TextInput::make('nis')->label('Nomor Induk Siswa'),

// Judul halaman & menu — di Filament Page
protected static ?string $title = 'Data Siswa';
protected static ?string $navigationLabel = 'Siswa';
protected static ?int $navigationSort = 1;
```

### Tambah / sembunyikan / urutkan kolom

```php
->columns([
    TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
    TextColumn::make('agama')
        ->label('Agama')
        ->toggleable(isToggledHiddenByDefault: true),   // bisa dimunculkan user lewat menu kolom
    TextColumn::make('nisn')
        ->hidden(fn ($livewire) => $livewire->activeTab !== 'MI'),  // kondisional
])
```

Urutan tampil = urutan array. Menghapus kolom cukup menghapus barisnya.

### Badge berwarna untuk status

```php
TextColumn::make('status')
    ->label('Status')
    ->badge()
    ->color(fn (?string $state): string => match ($state) {
        'Aktif' => 'success',
        'Lulus' => 'info',
        'Pindah' => 'warning',
        'Keluar' => 'danger',
        default => 'gray',
    }),
```

### Tambah filter

```php
->filters([
    SelectFilter::make('status')
        ->options([
            'Aktif' => 'Aktif',
            'Lulus' => 'Lulus',
        ]),
])
```

Filter belum berfungsi sampai nilainya diteruskan ke API di dalam closure `->records()`:

```php
if (! empty($filters['status']['value'] ?? null)) {
    $params['status'] = $filters['status']['value'];
}
```

…dan backend membacanya (`SiswaController.php:61`). Untuk opsi dropdown dari master data, pakai `ApiService::cachedGet()` supaya tidak memanggil API berulang (`DataSiswa.php:171`).

Catatan Filament 4: filter baru diterapkan setelah user menekan tombol Apply (`deferFilters` default aktif). Matikan dengan `->deferFilters(false)` kalau penguji menanyakan kenapa tidak langsung berubah.

### Tambah aksi (tombol)

```php
->recordActions([
    Action::make('view')
        ->tooltip('Lihat Siswa')
        ->icon('heroicon-s-eye')
        ->iconButton()
        ->url(fn (array $record): string => 'detail-siswa/'.Str::lower($this->activeTab).'/'.$record['id'])
        ->visible(fn () => PermissionHelper::hasResource('siswa.view'))
        ->color('gray'),
])
```

Aksi dengan modal form: `->schema([...])` untuk isian, `->fillForm(...)` untuk mengisi nilai awal, `->action(function (array $data, $record) { ... })` untuk mengirim ke API, `->after(fn () => $this->resetTable())` untuk merefresh tabel.

`->visible(...)` dengan `PermissionHelper::hasResource()` adalah cara standar menyembunyikan tombol berdasar hak akses di project ini — sebutkan itu kalau ditanya.

### Warna brand & tema panel

`FE/app/Providers/Filament/AdminPanelProvider.php`:

```php
->viteTheme('resources/css/filament/admin/theme.css')   // L104
->brandName($this->resolveBrandName())                  // L105
->brandLogo($this->resolveBrandLogo())                  // L106
->colors($this->resolvePanelColors())                   // L108
```

Warna primer di-resolve di `resolvePanelColors()` (L415) dari branding dinamis, dengan fallback `Color::hex('#1B4FBF')` (L420). CSS kustom di `FE/resources/css/filament/admin/theme.css`.

**Perubahan warna/CSS butuh build asset** — lihat bagian bawah.

### Menu sidebar

Sidebar **tidak** auto-discover. Item dirakit manual di `AdminPanelProvider` (`buildNavigation()`, `buildAkademikItems()`, `buildKeuanganItems()`, `buildPengaturanItems()`, mulai ~L145):

```php
NavigationItem::make('Absensi')
    ->url('/absensi')
    ->icon('heroicon-o-clock')
    ->group('Akademik')
    ->visible(fn () => PermissionHelper::hasResource('absensi'))
```

Itu sebabnya banyak Page punya `shouldRegisterNavigation(): bool { return false; }` — supaya tidak muncul dobel.

### Teks landing page publik

Semua teks ada di **`FE/config/handayani-public.php`**, bukan di Blade. Beberapa key wajib array karena dirender `@foreach`: `about.misi`, `nav_links`, `ekstrakurikuler.kegiatan`, `fasilitas.sarana`, `hero.stats`, `jenjang.levels`. Setelah mengubah config: `php artisan config:clear`.

### Kapan wajib `npm run build`

| Perubahan | Perlu build? |
|---|---|
| Label, kolom, filter, aksi, badge (PHP saja) | Tidak — cukup refresh |
| Kelas Tailwind **baru** yang belum pernah dipakai di project | **Ya** |
| Edit `theme.css` / `app.css` / warna panel | **Ya** |
| Isi `config/handayani-public.php` | Tidak, tapi `php artisan config:clear` |

```bash
cd frontend-v2
npm run build     # aman, dipakai saat demo
npm run dev       # hot reload, tapi bikin file public/hot — hapus sebelum akses dari HP/tunnel
```

---

## 7. Resep D — Menambah fitur/endpoint baru dari nol

Skenario paling berat. Urutannya: backend dulu sampai endpoint bisa dipanggil, baru RBAC, baru UI.

**Bagian 1 — Backend**

```bash
cd backend
php artisan make:model Absensi -m --no-interaction
php artisan make:controller AbsensiController --no-interaction
php artisan make:request AbsensiRequest --no-interaction
php artisan make:resource AbsensiResource --no-interaction
```

1. Isi migrasi, `php artisan migrate`.
2. Model: `$table`, `$fillable`, `casts()`, relasi. Sertakan `branch_id` kalau datanya per cabang.
3. `AbsensiRequest`: `rules()` + `failedValidation()` yang mengembalikan 400 — **salin pola dari `KelasRequest.php`** supaya format error konsisten dengan seluruh API.
4. Controller: scope `->where('branch_id', Auth::user()->branch_id)`, kembalikan `AbsensiResource::collection(...)` atau `(new AbsensiResource($x))->response()->setStatusCode(201)`.
5. Route di `BE/routes/api.php`, di dalam grup `['auth:sanctum', 'active.branch']`:

```php
Route::prefix('/absensi')->group(function () {
    Route::get('/', [AbsensiController::class, 'index'])->middleware('endpoint.permission:absensi.view');
    Route::post('/', [AbsensiController::class, 'create'])->middleware('endpoint.permission:absensi.create');
});
```

**Bagian 2 — RBAC (kalau dilewat, hasilnya 403 dan kelihatan gagal di depan penguji)**

```php
// BE/app/Enum/Permission.php
case VIEW_ABSENSI = 'view-absensi';
case CREATE_ABSENSI = 'create-absensi';
```

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

Lalu lewat UI **Manajemen RBAC** (tanpa deploy ulang):
1. Tab **Resource & Page Registry** → tambah resource key `absensi` dan `absensi.create`, bind ke permission di atas, isi group.
2. Tab **Endpoint Mapping** → tambah resource key `absensi.view` / `absensi.create` (yang dipakai di middleware route), bind ke permission.
3. Tab **Assign Role** → centang permission untuk role yang bersangkutan.

Resource key untuk UI dan untuk endpoint **independen** — boleh sama, boleh beda. Cache RBAC 60 detik, jadi tunggu sebentar atau logout–login sebelum menyimpulkan gagal.

**Bagian 3 — Frontend**

```bash
cd frontend-v2
php artisan make:livewire Absensi --no-interaction
```

Halaman Filament `FE/app/Filament/Pages/AbsensiPage.php`:

```php
class AbsensiPage extends Page
{
    protected string $view = 'filament.pages.absensi';
    protected static ?string $title = 'Absensi';

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('absensi'), 403);
    }
}
```

Blade `FE/resources/views/filament/pages/absensi.blade.php`:

```blade
<x-filament-panels::page>
    <livewire:absensi />
</x-filament-panels::page>
```

Komponen Livewire — **salin kerangka `DataKelas.php`**, itu contoh terkecil yang lengkap (tabel + create + update + delete + bulk delete): implements `HasActions, HasSchemas, HasTable`, `use HandlesApiErrors, InteractsWithActions, InteractsWithSchemas, InteractsWithTable`, lalu `table()` dengan closure `->records()` yang memanggil `ApiService::client()->get('/absensi', $params)`.

Terakhir, daftarkan item menu di `AdminPanelProvider` (lihat Resep C).

---

## 8. Ops saat demo

### 8.0 Pemeriksaan pra-sidang (jalankan pagi hari sebelum berangkat)

Tiga blocker berikut **benar-benar ditemukan** saat menguji panduan ini pada 2026-08-04, dan semuanya membuat aplikasi mati total tanpa pesan yang jelas. Cek satu per satu:

**1. Kredensial database backend cocok dengan MySQL yang jalan.**

```bash
cd backend && php artisan tinker --execute="echo \App\Models\Kelas::count();"
```

Kalau muncul `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`, berarti `DB_PASSWORD` di `backend/.env` tidak cocok dengan MySQL lokal. Di mesin ini MySQL Laragon (`C:\laragon\bin\mysql\`) memakai **password kosong**, jadi `backend/.env` harus `DB_PASSWORD=` — sama seperti `frontend-v2/.env`. Setelah mengubah: `php artisan config:clear`.

**2. `SESSION_DRIVER` tidak menunjuk layanan yang mati.**

`frontend-v2/.env` sempat memakai `SESSION_DRIVER=redis` sementara Redis dijalankan lewat container Docker `handayani-redis` yang tidak menyala. Akibatnya **seluruh halaman frontend balas HTTP 500**, termasuk halaman login, dengan pesan di log `Connection refused [tcp://127.0.0.1:6379]`. Untuk sidang, hilangkan ketergantungan itu:

```env
SESSION_DRIVER=database
CACHE_STORE=database
```

Tabel `sessions` sudah ada, jadi tidak perlu migrasi tambahan. Kalau tetap ingin Redis, pastikan container-nya menyala **sebelum** demo dan `redis-cli ping` membalas `PONG`.

**3. File `public/hot` konsisten dengan Vite.**

`frontend-v2/public/hot` yang tertinggal membuat `@vite` menunjuk `localhost:5173`. Kalau Vite tidak dijalankan, semua CSS/JS gagal dimuat dan tampilan berantakan. Dua pilihan yang aman, jangan setengah-setengah:

```bash
# Pilihan A (paling aman untuk sidang): asset statis
rm frontend-v2/public/hot && cd frontend-v2 && npm run build

# Pilihan B: biarkan hot, tapi Vite HARUS jalan sepanjang demo
cd frontend-v2 && npm run dev
```

**4. Migrasi sudah mutakhir.** `php artisan migrate --no-interaction` di `backend`. Kalau ternyata ada beberapa migrasi yang masih pending, lebih baik ketahuan sekarang daripada saat penguji menonton.

**5. Uji login sungguhan** di browser dengan `superadmin@handayani.com` / `!handayani123` sampai dashboard terbuka.

### 8.1 Urutan menyalakan

Empat terminal, atau pakai `composer run dev`:

```bash
# 1. Backend — WAJIB port 8080
cd backend && php artisan serve --port=8080

# 2. Queue worker — wajib untuk email & import/export
cd backend && composer run queue

# 3. Frontend
cd frontend-v2 && php artisan serve          # port 8000

# 4. Asset (opsional saat demo; kalau tidak, cukup npm run build sekali di awal)
cd frontend-v2 && npm run dev
```

Alternatif: `cd backend && composer run dev` menjalankan serve + queue + Vite sekaligus.

**Akun login hasil seeder** (password semua: `!handayani123`):

| Login pakai | Role |
|---|---|
| `superadmin@handayani.com` | superadmin |
| `developer@handayani.com` | developer |
| `yayasan@handayani.com` | kepala-yayasan |
| `admin_selat_panjang` | admin cabang |

Tiga akun pertama **harus pakai email, bukan username** — `IdentifierService` mematikan login-by-username untuk user non-siswa yang punya email. Admin cabang justru sebaliknya, pakai username.

**Yang harus dijalankan setelah tiap jenis edit:**

| Setelah mengubah | Jalankan |
|---|---|
| Migrasi | `php artisan migrate` (di `backend`) |
| Enum `Permission` | `php artisan db:seed --class=RoleAndPermissionSeeder` |
| Mapping endpoint di seeder | `php artisan db:seed --class=PermissionEndpointSeeder` |
| `.env` atau file `config/` | `php artisan config:clear` |
| Permission/role lewat UI | tunggu ±60 detik (cache RBAC), atau logout–login |
| CSS / kelas Tailwind baru | `npm run build` di `frontend-v2` |
| Kode PHP biasa | tidak ada — cukup refresh |

---

## 8.5 Midtrans — bagian yang paling mungkin dicecar

Ini modul paling "berteknologi" di sistem, jadi peluang ditanya besar. Detail setup ada di [Setup Midtrans](../../docs/midtrans.md); bagian ini fokus ke yang perlu dijawab dan diubah di depan penguji.

### Peta file

| Bagian | File |
|---|---|
| Konfigurasi (toggle, fee, expiry, order prefix, finish URL) | `BE/config/midtrans.php` |
| Webhook (publik) | `BE/app/Http/Controllers/MidtransNotificationController.php` |
| Logika webhook | `BE/app/Services/Midtrans/MidtransNotificationService.php` |
| Verifikasi tanda tangan | `BE/app/Services/Midtrans/SignatureVerifier.php` |
| Peta status Midtrans → internal | `BE/app/Services/Midtrans/StatusMapper.php` |
| Aturan transisi status | `BE/app/Services/Midtrans/StatusTransitionGuard.php` |
| Enum status internal | `BE/app/Services/Midtrans/MidtransInternalStatus.php` |
| Inisiasi transaksi (Snap) | `BE/app/Services/Midtrans/MidtransInitiationService.php` |
| Hitung biaya admin | `BE/app/Services/Midtrans/MidtransFeeService.php` |
| Pembuatan `order_id` | `BE/app/Services/Midtrans/OrderIdGenerator.php` |
| Sinkronisasi manual dari admin | `BE/app/Services/Midtrans/MidtransStatusSyncService.php` |
| Pencatatan log webhook | `BE/app/Services/Midtrans/MidtransLogService.php` |
| HTTP client ke Midtrans | `BE/app/Services/Midtrans/MidtransClient.php`, `MidtransSnapClient.php` |
| 21 exception khusus + 1 base class | `BE/app/Exceptions/Midtrans/` |
| Model | `BE/app/Models/MidtransTransaction.php`, `MidtransTransactionLog.php` |
| Endpoint admin/portal | `BE/app/Http/Controllers/MidtransAdminController.php`, `MidtransTransactionController.php` |
| Tombol bayar di portal siswa | `FE/app/Livewire/TagihanSiswa.php` |
| Halaman admin transaksi | `FE/app/Livewire/TransaksiMidtrans.php`, `TransaksiMidtransDetail.php` |
| Klien API frontend | `FE/app/Services/MidtransApi.php` |
| Teks bahasa Indonesia | `FE/lang/id/midtrans.php` |
| Prune log | `BE/app/Console/Commands/MidtransPruneLogsCommand.php` |

### Alur lengkap, untuk diceritakan

**Fase 1 — inisiasi** (`MidtransInitiationService::initiate()`), siswa klik "Bayar Online":

1. Cek `config('midtrans.enabled')`, lalu cek kredensial terisi.
2. Buka transaksi database, kunci baris `tagihans` dengan `lockForUpdate()`.
3. **Verifikasi kepemilikan** — NIS milik user harus sama dengan `tagihan.nis`, kalau tidak `TagihanForbiddenException` (403). Siswa tidak bisa membayar tagihan siswa lain.
4. Hitung sisa: `jenis_tagihan.jumlah - tagihan.tmp`. Kalau ≤ 0 → `TagihanSudahLunasException`.
5. Validasi nominal: minimal Rp 10.000 (`min_amount`), dan tidak boleh melebihi sisa.
6. **Cek transaksi pending yang masih berjalan** untuk tagihan itu. Kalau ada, tidak membuat transaksi baru — melempar `TagihanHasPendingTransactionException` yang **membawa snap token lama**, jadi siswa dilanjutkan ke transaksi yang sama. Ini yang mencegah tagihan punya dua transaksi hidup sekaligus.
7. Hitung biaya admin per kanal, lalu tegakkan invarian `gross_amount == amount_paid + fee_amount` (`assertGrossInvariant`).
8. Bikin `order_id` format `HDY-{kode_tagihan}-{epoch_ms}`, simpan `MidtransTransaction` status `pending`, `expired_at` = sekarang + `expiry_minutes`.
9. Minta Snap token ke Midtrans, kirim `item_details` dua baris: nominal tagihan dan "Biaya Admin Pembayaran Online" — jadi siswa melihat rinciannya di halaman Snap.

**Fase 2 — siswa membayar** di halaman Snap Midtrans, lalu diarahkan balik ke `finish_url` (default `FRONTEND_URL` + `/portal/status-pembayaran`).

**Fase 3 — webhook** (`MidtransNotificationService::handle()`), Midtrans memanggil `POST /api/midtrans/notification`:

1. Cek `webhook_enabled` (bukan `enabled` — lihat penjelasan di bawah).
2. **Catat log mentah dulu** ke `midtrans_transaction_logs`, sebelum validasi apa pun. Jadi notifikasi yang ditolak pun tetap ada jejaknya.
3. **Verifikasi tanda tangan.** Gagal → 403 `INVALID_SIGNATURE`.
4. Buka transaksi DB, kunci `MidtransTransaction` dengan `lockForUpdate()`, retry 2x kalau deadlock. Tidak ketemu → 404 `ORDER_NOT_FOUND`.
5. Cocokkan `gross_amount` dengan yang tersimpan. Beda → 422 `AMOUNT_MISMATCH`.
6. Petakan status, cek transisi. Status sama → no-op 200. Transisi terlarang → 409 `INVALID_STATUS_TRANSITION`.
7. Kalau status sukses (`settlement`/`capture`) → catat `Pembayaran`, tambah `tagihan.tmp`, set status `Lunas`/`Belum Lunas`, dispatch event `PembayaranRecorded` (yang memicu notifikasi email).

**Fase 4 — jaring pengaman.** Kalau webhook tidak sampai (ngrok mati, URL belum didaftarkan), admin bisa menekan tombol **Sync** di halaman Transaksi Midtrans. `MidtransStatusSyncService::syncManual()` menarik status langsung dari API Midtrans lalu memakai **jalur pemrosesan yang sama persis** (`processVerifiedPayload()`), jadi tidak ada logika ganda yang bisa menyimpang.

### Empat jawaban yang harus lancar

**"Webhook Anda publik tanpa autentikasi, bukankah itu celah?"**
Pemanggilnya server Midtrans, bukan user yang login — tidak ada token yang bisa dikirim. Pengamanannya tanda tangan kriptografis: `SHA-512(order_id + status_code + gross_amount + server_key)`, dibandingkan dengan `hash_equals()` (**constant-time**, supaya tidak bisa ditebak lewat timing attack). Server key tidak pernah keluar dari server, jadi penyerang tidak bisa memalsukan tanda tangan yang cocok. Ditambah lagi `gross_amount` dicocokkan ulang dengan nilai yang kami simpan sendiri, jadi nominal palsu tetap ditolak 422.

**"Bagaimana kalau Midtrans mengirim notifikasi yang sama dua kali?"**
Ada tiga lapis. Pertama, kalau status baru sama dengan status sekarang, langsung no-op. Kedua, `StatusTransitionGuard` hanya mengizinkan transisi yang sah — dari status terminal seperti `expire` atau `deny` tidak bisa berpindah ke mana-mana. Ketiga, sebelum mencatat `Pembayaran` ada penjagaan idempoten: kalau sudah ada `Pembayaran` dengan `midtrans_order_id` yang sama, langsung berhenti. Jadi notifikasi ganda tidak akan pernah menghasilkan pembayaran dobel.

**"Bagaimana kalau dua notifikasi masuk bersamaan?"**
Seluruh pemrosesan ada di dalam transaksi database dengan `lockForUpdate()` pada baris transaksi dan baris tagihan, plus retry 2x kalau terjadi deadlock. Jadi notifikasi kedua menunggu sampai yang pertama selesai commit, lalu melihat status yang sudah diperbarui dan menjadi no-op.

**"Bagaimana kalau siswa membayar lebih besar dari sisa tagihan?"**
Dicegah dua kali: saat inisiasi (`AmountExceedsSisaException`), dan sekali lagi saat webhook memproses (`OverpaymentBlockedException`) — karena antara inisiasi dan pembayaran, admin bisa saja mencatat pembayaran tunai untuk tagihan yang sama.

### Kalau diminta mengubah sesuatu

**Ubah biaya admin per kanal** — paling gampang, tidak menyentuh kode:

```env
# backend/.env
HANDAYANI_MIDTRANS_FEE_QRIS_PERCENT=1.0
HANDAYANI_MIDTRANS_FEE_BANK_TRANSFER=5000
```

```bash
cd backend && php artisan config:clear
```

Struktur kanal ada di `config/midtrans.php` → `fee_channels`. Dua tipe didukung: `flat` (`['type'=>'flat','amount'=>4000]`) dan `percent` (`['type'=>'percent','percent'=>0.7,'flat'=>0]`). Menambah kanal baru = menambah satu entri di array itu; endpoint `/midtrans/fee-channels` otomatis ikut mengembalikannya karena `availableChannels()` membaca config, bukan daftar hardcoded.

**Ubah masa berlaku transaksi:** `MIDTRANS_EXPIRY_MINUTES` (default 1440 = 24 jam). Turunkan sebentar kalau penguji ingin melihat skenario expired, lalu **kembalikan lagi**.

**Ubah nominal minimum:** `min_amount` di `config/midtrans.php` (Rp 10.000, ditulis sebagai asumsi A2 di laporan).

**Ubah pesan error yang dilihat siswa:** `FE/lang/id/midtrans.php`. Kunci pesannya mengikuti `error_code` dari backend (mis. `AMOUNT_BELOW_MINIMUM`, `TAGIHAN_NOT_FOUND`), jadi menambah exception baru berarti menambah satu kunci di file itu.

**Menambah exception baru:** turunkan dari `App\Exceptions\Midtrans\MidtransException`, isi `$errorCode` dan `$httpStatus`. Tidak perlu menyentuh controller — `BE/bootstrap/app.php:33` sudah punya `renderable()` yang mengubah semua turunan `MidtransException` jadi JSON `{"error_code": ..., "message": ...}` dengan status yang sesuai.

**Test yang relevan:**

```bash
cd backend
php artisan test --filter=MidtransAdminSyncTest
php artisan test --filter=MidtransExceptionRendererTest
```

### Demo Midtrans saat sidang — realistis saja

Pembayaran sungguhan butuh webhook, dan webhook butuh URL publik lewat ngrok yang URL-nya berubah tiap restart dan harus didaftarkan ulang di dashboard sandbox. Terlalu rapuh untuk dipertaruhkan di depan penguji.

Rencana yang aman, berurutan:

1. **Siapkan sebelum hari-H**: ngrok jalan, URL notifikasi sudah terdaftar, satu pembayaran sandbox sudah pernah berhasil sehingga ada data nyata di halaman Transaksi Midtrans dan di log.
2. **Saat sidang**, tunjukkan data hasil langkah 1 — halaman daftar transaksi, halaman detail, dan log notifikasi mentahnya. Ini membuktikan integrasinya jalan tanpa bergantung pada jaringan saat itu.
3. Kalau penguji ingin melihat transaksi **baru** dibuat: tunjukkan alur sampai halaman Snap terbuka (ini tidak butuh webhook sama sekali), lalu jelaskan bahwa penyelesaiannya lewat webhook.
4. Kalau webhook ternyata tidak masuk: pakai tombol **Sync** di halaman admin. Itu memang jaring pengaman yang dirancang untuk kondisi ini — memakainya justru menunjukkan sistemnya sudah mengantisipasi kegagalan webhook.
5. Kartu uji sandbox: `4811 1111 1111 1114`, CVV `123`, expiry `12/30`, OTP `112233`.

Kalau tidak sempat menyiapkan sama sekali, set `HANDAYANI_MIDTRANS_ENABLED=false` di kedua `.env` — sisa aplikasi jalan normal dan tombol bayar online otomatis tersembunyi, jadi tidak ada tombol yang error saat diklik.

> **Jangan pakai kredensial production.** Semua kunci harus berawalan `SB-`. Server key setara password merchant: jangan tampilkan di layar, jangan masuk screenshot laporan.

## 8.6 Email & notifikasi — modul kedua yang rawan dicecar

Yang membuat modul ini menarik bagi penguji: email itu efek samping yang tidak bisa dibatalkan, jadi pertanyaannya selalu berputar di sekitar "bagaimana kalau gagal", "bagaimana kalau dobel", dan "bagaimana kalau alamatnya tidak ada".

### Peta file

| Bagian | File |
|---|---|
| Orkestrator utama | `BE/app/Services/Notifications/NotificationService.php` |
| Penentu alamat tujuan | `BE/app/Services/Notifications/RecipientResolver.php` |
| Notifikasi workflow pengeluaran | `BE/app/Services/WorkflowNotificationService.php` |
| Validasi & normalisasi email | `BE/app/Services/EmailValidationService.php` |
| Lampiran PDF kwitansi | `BE/app/Services/Notifications/KwitansiPdfService.php` |
| 5 kelas notifikasi | `BE/app/Notifications/` |
| Template email | `BE/resources/views/emails/` (10 berkas Blade) |
| Event | `BE/app/Events/TagihanCreated.php`, `PembayaranRecorded.php` |
| Listener | `BE/app/Listeners/SendTagihanBaruNotification.php`, `SendKwitansiNotification.php` |
| Pengaturan per cabang | `BE/app/Models/NotificationSetting.php` |
| Riwayat pengiriman | `BE/app/Models/NotificationLog.php` |
| Penjaga anti-duplikat | `BE/app/Models/NotificationSentRecord.php` |
| Berhenti berlangganan | `BE/app/Models/EmailOptOut.php`, `EmailOptOutController.php` |
| Penjadwalan | `BE/routes/console.php` + `SendNotificationReminders.php` |
| UI pengaturan | `FE/app/Livewire/NotificationSettings.php` |
| UI log | `FE/app/Livewire/NotificationLogTable.php` |

### Enam jenis email dan pemicunya

| Jenis | Pemicu | Jalur |
|---|---|---|
| Tagihan baru | Tagihan dibuat | Event `TagihanCreated` → listener → `sendTagihanBaru()` |
| Kwitansi pembayaran | Pembayaran tercatat (termasuk dari webhook Midtrans) | Event `PembayaranRecorded` → listener → `sendKwitansiPembayaran()`, dengan lampiran PDF |
| Pengingat jatuh tempo | Terjadwal harian 08:00 | `notifications:send-reminders` → `processReminders()` |
| Tagihan terlambat | Terjadwal harian 08:00 | `processOverdue()` |
| Workflow pengeluaran | Submit / approve / reject / disburse | `WorkflowNotificationService::notifyApprovers()` dan `notifyRequester()` |
| OTP verifikasi & reset password | Aksi user | `UserController`, `PasswordResetController` |

Listener **tidak didaftarkan manual** di provider — Laravel 12 menemukannya otomatis dari type-hint parameter `handle()`. Kalau ditanya "di mana event ini didaftarkan", itu jawabannya.

### Rantai gerbang sebelum satu email dikirim

Ini bagian paling layak diceritakan, karena memperlihatkan sistemnya tidak asal kirim. Semua di `NotificationService::sendTagihanBaru()` dan saudara-saudaranya, urut:

1. **Jenis notifikasi aktif untuk cabang ini?** Dibaca dari `notification_settings` per cabang. Tidak ada baris setting = dianggap aktif. Tidak aktif → log `skipped`, alasan `disabled`.
2. **Ada alamat tujuannya?** `RecipientResolver` mencari berurutan: email akun user siswa → email wali → email ibu → email ayah. Tidak ketemu satu pun → `skipped`, alasan `no_email_available`.
3. **Alamat itu sudah berhenti berlangganan?** Cek `email_opt_outs` per jenis notifikasi → `skipped`, alasan `opted_out`.
4. **Formatnya valid?** → `skipped`, alasan `invalid_email`.
5. **Melebihi batas kirim?** Maksimal **100 email per jam per cabang** lewat `RateLimiter` → `skipped`, alasan `rate_limited`.
6. **Kirim**, lalu catat status `sent`. Kalau melempar exception → catat `failed` beserta pesan errornya.

Intinya: **setiap kemungkinan berakhir dengan satu baris di `notification_logs`**, lengkap dengan alasannya. Tidak ada email yang hilang diam-diam tanpa jejak — dan itu yang membuat halaman Log Notifikasi berguna, bukan sekadar hiasan.

### Antrian, percobaan ulang, dan anti-duplikat

Semua kelas notifikasi `implements ShouldQueue` dan memakai antrian khusus:

```php
// BE/app/Notifications/TagihanBaruNotification.php
public $tries = 3;
public $backoff = [10, 30, 60];   // detik: coba lagi setelah 10s, 30s, 60s

public function __construct(...) {
    $this->onQueue('notifications');
}
```

Kalau tiga percobaan habis, method `failed()` menandai baris log terkait menjadi `failed` beserta pesan errornya. Jadi status `sent` di log sebenarnya berarti "berhasil dimasukkan antrian", dan akan dikoreksi menjadi `failed` kalau pengiriman betul-betul gagal.

**Anti-duplikat untuk email terjadwal** ada di `NotificationSentRecord::alreadySent()`: kunci gabungan `tagihan_kode` + jenis + **tanggal**. Jadi kalau scheduler dijalankan dua kali dalam sehari — atau seseorang menjalankan perintahnya manual — pengingat untuk tagihan yang sama tidak akan terkirim dua kali di hari yang sama.

**Jebakan antrian yang paling sering makan waktu:** notifikasi masuk antrian `notifications`, sementara job import/export masuk `default`. Menjalankan `php artisan queue:work` polos berarti antrian `notifications` **tidak pernah diproses** — dan tidak ada error apa pun yang muncul, email hanya diam. Selalu:

```bash
cd backend && composer run queue     # sudah memakai flag yang benar
# setara dengan:
php artisan queue:work --queue=notifications,default
```

### Lima jawaban yang harus lancar

**"Bagaimana kalau siswa tidak punya email?"**
Tidak ada yang gagal. `RecipientResolver` mencoba empat sumber berurutan (akun siswa, wali, ibu, ayah), dan kalau semuanya kosong, sistem mencatat `skipped` dengan alasan `no_email_available` lalu lanjut ke siswa berikutnya. Satu siswa tanpa email tidak menghentikan pengiriman batch.

**"Bagaimana kalau server email sedang mati?"**
Email tidak dikirim langsung dalam siklus request — semuanya masuk antrian. Job akan mencoba ulang tiga kali dengan jeda 10, 30, lalu 60 detik. Kalau tetap gagal, `failed()` menandai log sebagai `failed` dengan pesan errornya, dan admin bisa memilih baris-baris yang gagal di halaman Log Notifikasi lalu menjalankan aksi massal **Retry** (`POST /api/notification-logs/retry`).

**"Bagaimana mencegah email dobel?"**
Untuk email terjadwal, ada tabel `notification_sent_records` dengan kunci tagihan + jenis + tanggal, dicek sebelum kirim. Untuk email yang dipicu event, pemicunya memang sekali per kejadian. Ditambah pembatas 100 email per jam per cabang sebagai jaring pengaman terhadap perulangan tak terduga.

**"Kalau orang tua tidak mau lagi menerima email?"**
Jawab apa adanya, karena ini setengah jadi (lihat catatan di bawah). Yang **sudah jalan**: pemilik akun bisa mematikan tiap jenis notifikasi lewat halaman Preferensi Notifikasi di portal — `PUT /api/users/current/notification-preferences` membuat baris di `email_opt_outs`, dan `NotificationService` mengeceknya sebelum mengirim. Sifatnya **per jenis**, jadi bisa berhenti menerima pengingat tapi tetap menerima kwitansi.

**"Kenapa pengaturannya per cabang, bukan global?"**
Karena tiap cabang punya kebiasaan administrasi berbeda. `notification_settings` menyimpan empat sakelar (`tagihan_baru`, `reminder`, `kwitansi`, `overdue`) plus `reminder_days_before` — berapa hari sebelum jatuh tempo pengingat dikirim, defaultnya `[7, 3, 0]` — dan `overdue_interval_days`. Semuanya diubah lewat UI, tanpa deploy.

### Celah yang sudah diketahui: tautan berhenti berlangganan belum terpasang

**Ketahui ini sebelum ditanya, jangan sampai ketahuan tidak sadar.** Infrastruktur berhenti berlangganan lengkap tapi belum tersambung ujung ke ujung:

| Komponen | Status |
|---|---|
| Tabel `email_opt_outs` | Ada |
| Pengecekan sebelum kirim (`isOptedOut`) | Ada, dipakai di semua jenis email |
| Endpoint publik `GET\|POST /api/unsubscribe/{token}` | Ada, berfungsi |
| Halaman konfirmasi `emails/unsubscribe.blade.php` | Ada |
| Helper `EmailOptOut::generateUnsubscribeUrl()` | Ada, **tidak dipanggil di mana pun** |
| Tautan di dalam template email | **Belum ada** |

Akibatnya: pemilik akun bisa berhenti berlangganan lewat portal, tapi orang tua yang alamatnya dipakai tanpa punya akun (email wali/ibu/ayah) belum punya cara mandiri.

**Kalau penguji menyuruh menambahkannya di tempat**, ini perubahan dua baris — kandidat live coding yang bagus karena selesai cepat dan hasilnya kelihatan:

```php
// 1. BE/app/Notifications/TagihanBaruNotification.php — di toMail()
->view('emails.notifications.tagihan-baru', [
    'siswa' => $this->siswa,
    'tagihans' => $this->tagihans,
    'unsubscribeUrl' => \App\Models\EmailOptOut::generateUnsubscribeUrl(
        $notifiable->routes['mail'], 'tagihan_baru'
    ),
])
```

```blade
{{-- 2. BE/resources/views/emails/notifications/tagihan-baru.blade.php — di bagian bawah --}}
<p style="font-size:12px;color:#888">
    Tidak ingin menerima email ini lagi?
    <a href="{{ $unsubscribeUrl }}">Berhenti berlangganan</a>
</p>
```

`generateUnsubscribeUrl()` sudah membuat baris berikut tokennya lewat `firstOrCreate`, jadi tidak perlu migrasi maupun kode tambahan. Uji hasilnya lewat Mailpit, klik tautannya, lalu tunjukkan halaman konfirmasinya.

### Kalau diminta mengubah sesuatu

**Ubah isi/tampilan email** — paling aman dan paling kelihatan hasilnya:

```
BE/resources/views/emails/notifications/tagihan-baru.blade.php
```

Blade biasa. Ubah teksnya, refresh, kirim ulang, lihat hasilnya di Mailpit. Tidak butuh build asset.

**Ubah subjek email:** method `toMail()` di kelas notifikasi terkait, mis. `TagihanBaruNotification.php:36`.

**Ubah jadwal pengingat:** lewat UI Pengaturan Notifikasi (`reminder_days_before`), atau jam kirimnya di `BE/routes/console.php`:

```php
Schedule::command('notifications:send-reminders')->dailyAt('08:00');
```

**Ubah batas kirim:** `NotificationService::checkRateLimit()` — angka `100` dan jendela `3600` detik.

**Ubah urutan prioritas penerima:** `RecipientResolver::resolve()`. Kalau penguji bertanya "kenapa wali didahulukan daripada ayah", jawabannya urutan itu memang keputusan domain dan tinggal ditukar di satu tempat — semua jenis email ikut berubah karena semuanya memakai resolver yang sama.

**Menambah jenis notifikasi baru:** kelas di `app/Notifications/` (`implements ShouldQueue`, `onQueue('notifications')`), Blade di `resources/views/emails/notifications/`, satu kolom sakelar di `notification_settings` + case di `NotificationService::isEnabled()`, lalu method pengirim yang mengikuti rantai enam gerbang di atas.

### Demo email saat sidang

Jauh lebih aman daripada Midtrans, karena semua berjalan lokal.

1. **Pastikan Mailpit hidup** — SMTP di `127.0.0.1:1025`, antarmuka web di `http://localhost:8025`. Backend `.env` sudah menunjuk ke situ. Buka tab Mailpit sebelum sidang mulai.
2. **Pastikan queue worker jalan** dengan `composer run queue`. Tanpa ini email tidak akan pernah muncul, dan tidak ada pesan error yang menjelaskan kenapa.
3. **Alur demo yang rapi:** buat satu tagihan untuk siswa yang punya email → tunjukkan email masuk di Mailpit → tunjukkan barisnya di halaman Log Notifikasi berstatus `sent`.
4. **Demo penanganan kegagalan** (ini yang mengesankan): matikan salah satu sakelar di Pengaturan Notifikasi, buat tagihan lagi, lalu tunjukkan bahwa log mencatat `skipped` dengan alasan `disabled`. Sistem tidak diam-diam gagal — ia mencatat kenapa.
5. **Kalau diminta mengirim pengingat sekarang** tanpa menunggu jam 08:00:

```bash
cd backend && php artisan notifications:send-reminders
```

> Email dev tertangkap Mailpit, **tidak** terkirim ke alamat asli. Sebutkan ini kalau penguji bertanya apakah email betulan terkirim ke orang tua siswa saat pengujian.

## 9. Troubleshooting cepat

| Gejala | Penyebab paling mungkin | Perbaikan |
|---|---|---|
| Halaman admin kosong, tanpa pesan error | Backend tidak jalan atau salah port | `php artisan serve --port=8080` di `backend` |
| **Semua** halaman frontend 500, termasuk login | `SESSION_DRIVER=redis` tapi Redis mati | `SESSION_DRIVER=database` + `config:clear` (lihat §8.0) |
| Backend 500, log `Access denied for user 'root'` | `DB_PASSWORD` di `backend/.env` tidak cocok | Samakan dengan `frontend-v2/.env` (kosong), `config:clear` |
| Tampilan berantakan tanpa CSS | `public/hot` ada tapi Vite mati | Hapus `public/hot` lalu `npm run build` |
| 403 setelah menambah permission | Seeder belum jalan, atau cache RBAC 60 detik | `db:seed --class=RoleAndPermissionSeeder`, lalu logout–login |
| Tombol tidak muncul walau permission sudah ada | Resource key belum didaftarkan/di-bind di tab Resource & Page Registry | Daftarkan lewat UI RBAC |
| Halaman terbuka tapi data gagal dimuat | Lapis UI terdaftar, lapis endpoint belum | Tambahkan mapping di tab Endpoint Mapping |
| Semua request tiba-tiba 401 | Token Sanctum kedaluwarsa (8 jam) | Login ulang |
| Login `superadmin` ditolak padahal password benar | Harus pakai email | `superadmin@handayani.com` |
| Perubahan PHP tidak terlihat | `config:cache` aktif di dev | `php artisan config:clear` |
| Perubahan style tidak terlihat | Asset belum di-build | `npm run build` |
| Halaman tidak bisa diakses dari HP / tunnel | File `public/hot` masih ada | Hapus `public/hot`, lalu `npm run build` |
| Kolom baru selalu kosong | Belum ditambahkan di API Resource | Resep A langkah 4 |
| Data tidak tersimpan tanpa error | Belum masuk `$fillable` atau `rules()` | Resep A langkah 2 & 3 |
| Email tidak terkirim, tanpa error | Queue worker jalan tanpa `--queue=notifications` | `composer run queue` |
| Email tidak muncul, log bilang `skipped` | Baca kolom `reason`: `disabled`, `no_email_available`, `opted_out`, `invalid_email`, `rate_limited` | Perbaiki sesuai alasannya (§8.6) |
| Email tidak muncul, log bilang `sent` | `sent` = masuk antrian, bukan terkirim. Worker mati atau Mailpit mati | Cek `composer run queue` + `http://localhost:8025` |
| Pengingat tidak terkirim padahal jatuh tempo hari ini | Sudah terkirim hari ini (`notification_sent_records`), atau scheduler tidak jalan | `php artisan schedule:work`, atau panggil perintahnya manual |
| Tombol "Bayar Online" tidak muncul | `HANDAYANI_MIDTRANS_ENABLED=false`, atau tagihan sudah lunas / sudah punya transaksi pending | Cek `.env` kedua app + `config:clear` |
| Snap gagal terbuka | `MIDTRANS_CLIENT_KEY` backend dan frontend tidak sama | Samakan persis, `config:clear` |
| Bayar berhasil tapi status tagihan tidak berubah | Webhook tidak sampai (URL belum didaftarkan / ngrok mati) | Tekan tombol **Sync** di halaman Transaksi Midtrans |
| Webhook masuk tapi ditolak 403 | Tanda tangan tidak cocok — `MIDTRANS_SERVER_KEY` beda dengan merchant pengirim | Samakan server key, cek `midtrans_transaction_logs` |

---

## 10. Jangan dilakukan saat sidang

- **`php artisan migrate:fresh --env=testing`** — repo tidak punya `backend/.env.testing`, jadi Laravel jatuh ke `.env` biasa dan perintah ini **menghapus database dev**. Pernah terjadi di project ini.
- **`php artisan test` (seluruh suite backend)** — ada ±240 kegagalan pre-existing yang tidak ada hubungannya dengan perubahan hari itu. Jalankan `--filter=NamaTest` saja. Kalau penguji minta "buktikan dengan test", jalankan satu test file yang relevan.
- **`php artisan config:cache` di dev** — perubahan `.env` tidak akan terbaca sampai `config:clear`, dan error-nya muncul jauh dari penyebabnya.
- **Menambahkan middleware auth ke `POST /api/midtrans/notification`** — webhook itu memang publik agar Midtrans bisa memanggilnya; pengamanannya lewat verifikasi signature. Kalau penguji menyebutnya sebagai celah keamanan, jelaskan alasannya, jangan langsung "diperbaiki".
- **Membuat migrasi di `frontend-v2`** — hanya `backend` yang memiliki skema. Dua aplikasi menunjuk database yang sama, migrasi ganda akan bentrok.
- **Mengubah NIS siswa untuk demo** — `tagihans` join ke `siswas` lewat kolom `nis`, bukan `id`. Mengganti NIS akan memutus tagihan yang sudah ada.
- **`php artisan migrate:rollback --step=1`** — membatalkan satu batch penuh, bukan satu migrasi. Kalau ada migrasi lain yang ikut jalan di batch yang sama, semuanya ikut dibatalkan. Pakai `--path=<file migrasi>`.

---

## 11. Pertanyaan konsep + jawaban singkat

**Kenapa dipisah jadi dua aplikasi Laravel?**
Supaya lapisan data punya satu pemilik. Backend headless memegang skema, aturan bisnis, dan autentikasi; frontend murni konsumen API. Efeknya, aturan bisnis tidak bisa ditembus lewat UI, dan ke depan klien lain (mobile misalnya) bisa memakai API yang sama tanpa menduplikasi logika.

**Kenapa frontend tidak memakai Eloquent padahal databasenya sama?**
Justru itu yang dihindari. Kalau frontend query langsung ke database, validasi dan aturan bisnis di backend bisa dilewati, dan skema jadi punya dua pemilik. Semua akses data lewat `ApiService` dengan token Sanctum, sehingga setiap request tetap melewati middleware permission dan scoping cabang.

**Kenapa `tagihans` join ke `siswas` lewat `nis`, bukan `id`?**
NIS adalah identitas siswa yang dipakai pihak sekolah di dokumen fisik dan kwitansi, jadi tagihan direferensikan dengan NIS agar cocok dengan proses administrasi berjalan. Konsekuensinya sudah didokumentasikan: mengganti NIS memutus keterkaitan tagihan, jadi perubahan NIS harus diikuti pembaruan tagihan terkait.

**Kenapa webhook Midtrans publik tanpa autentikasi?**
Karena pemanggilnya server Midtrans, bukan user yang login — tidak ada token yang bisa dikirim. Keasliannya diverifikasi lewat signature key dari Midtrans, bukan lewat middleware auth. Ini pola standar webhook. Uraian lengkapnya di §8.5.

**Kenapa webhook tetap jalan walau `HANDAYANI_MIDTRANS_ENABLED=false`?**
Karena mematikan fitur tidak boleh menggantung transaksi yang terlanjur berjalan. Siswa yang sudah membayar tetap harus tercatat. Jadi toggle `enabled` hanya menghentikan **inisiasi** transaksi baru, sementara pemrosesan notifikasi masuk dikendalikan flag terpisah `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED` yang dicek di lapisan service, bukan di controller.

**Kenapa biaya admin dijadikan baris tersendiri di Snap, bukan ditambahkan diam-diam ke nominal?**
Supaya siswa melihat rincian yang jujur di halaman pembayaran: nominal tagihan dan biaya admin terpisah. Secara internal invariannya dijaga eksplisit, `gross_amount == amount_paid + fee_amount`, dan dilanggar sedikit pun akan melempar `AmountInternalInconsistentException` — jadi selisih akibat pembulatan persentase tidak bisa lolos diam-diam.

**Kenapa RBAC dua lapis, bukan satu?**
Karena UI dan API adalah dua permukaan serangan yang berbeda. Menyembunyikan tombol saja tidak mengamankan apa pun — endpoint-nya masih bisa dipanggil langsung. Sebaliknya, memproteksi endpoint saja membuat user melihat menu yang selalu gagal. `page_permissions` mengurus apa yang terlihat, `permission_endpoints` mengurus apa yang boleh dieksekusi.

**Kenapa nama permission dalam bahasa Indonesia?**
Mengikuti istilah domain yang dipakai sekolah (`view-tagihan`, `create-pengeluaran-request`), supaya admin sekolah yang mengelola role lewat UI bisa membacanya tanpa penerjemahan.

**Kenapa email dikirim lewat antrian, bukan langsung?**
Karena SMTP itu panggilan jaringan ke sistem luar yang bisa lambat atau mati. Kalau dikirim di dalam siklus request, admin yang membuat 200 tagihan akan menunggu 200 kali koneksi SMTP, dan satu server email yang mati akan menggagalkan pembuatan tagihannya juga — padahal keduanya urusan berbeda. Dengan antrian, pembuatan tagihan selesai seketika, pengiriman email berjalan di belakang dengan percobaan ulang sendiri, dan kegagalan email tidak pernah membatalkan transaksi bisnisnya. Detail di §8.6.

**Bagaimana multi-cabang dijamin tidak bocor?**
Tiga lapis: header `X-Branch-Id` dikirim `ApiService`, middleware `active.branch` menetapkan konteks cabang, dan setiap query controller memfilter `branch_id` milik user. Permission `view-all-branches` yang membuka akses lintas cabang.

**Kenapa halaman Filament-nya tipis dan isinya di komponen Livewire?**
Karena Filament Resource dirancang untuk model Eloquent lokal, sedangkan di sini sumber datanya API. Jadi Page hanya menangani rute dan otorisasi, sementara tabel/form dibangun dengan komponen Filament Tables & Schemas di dalam komponen Livewire yang datanya diisi manual dari respons API lewat closure `->records()`.
