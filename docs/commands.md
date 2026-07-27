# Command Reference

Perintah dev dan production buat kedua aplikasi.

## Backend (`backend/`)

### Semua service dev sekaligus

```bash
composer run dev      # serve --port=8080 + queue listener + Vite, paralel
```

### Queue worker

Wajib jalan. Dipakai job import/export (`ProcessImportJob`, `ProcessExportJob`) dan semua notifikasi email. `QUEUE_CONNECTION=database`, jadi tabel `jobs` harus sudah dimigrasi.

Notifikasi masuk queue `notifications`, job import/export masuk `default`. Worker harus dengerin dua-duanya:

```bash
cd backend
composer run queue                                       # flag sudah bener
php artisan queue:work --queue=notifications,default
php artisan queue:listen --queue=notifications,default   # reload tiap request, lebih lambat
```

Jalanin `queue:work` tanpa `--queue=notifications,default` bikin semua notifikasi email tidak pernah kekirim, dan tidak ada error yang muncul. Kalau ragu pakai `composer run queue`.

### Scheduler

Didefinisikan di `backend/routes/console.php`:

- `notifications:send-reminders`, tiap hari jam 08:00
- `midtrans:prune-logs`, tiap hari

```bash
php artisan schedule:work         # dev, jalan terus di foreground
php artisan schedule:run          # prod, dipanggil cron tiap menit
```

### RBAC & permission sync

Tidak ada command `permissions:sync*`. Semua lewat seeder, idempotent (`firstOrCreate`/`updateOrCreate`). Jalanin tiap nambah/ubah/hapus case di `App\Enum\Permission` atau mapping endpoint:

```bash
php artisan db:seed --class=RoleAndPermissionSeeder     # tambah permission baru dari enum + refresh role
php artisan db:seed --class=PermissionEndpointSeeder    # sinkron mapping resource_key → permission
php artisan db:seed --class=PermissionMetadataSeeder    # refresh label/group/audience permission
php artisan permissions:backfill-groups                 # isi ulang kolom group pada page_permissions yang kosong
```

### Maintenance

```bash
php artisan midtrans:prune-logs               # hapus log transaksi Midtrans lama
php artisan midtrans:prune-logs --days=180    # atur retensi
```

### Migrasi & seeding

```bash
php artisan migrate                # dev/staging
php artisan migrate --seed         # dev, migrasi + seed awal (roles, permissions, resource registry, dsb)
php artisan migrate --force        # prod, wajib --force karena APP_ENV bukan local
php artisan db:seed --class=PermissionResourceSeeder   # re-seed resource registry aja
```

### Server

```bash
php artisan serve --port=8080      # dev, wajib 8080 karena frontend-v2 nunjuk ke situ
```

Di production pakai web server (Nginx/Apache + PHP-FPM) yang nunjuk ke `backend/public/index.php`, bukan `artisan serve`.

## frontend-v2 (`frontend-v2/`)

```bash
cd frontend-v2
npm install
npm run dev              # Vite dev server, hot reload
npm run build            # build asset final ke public/build

php artisan serve                        # port 8000, aman karena bukan yang dituju frontend
php artisan filament:optimize            # prod, cache komponen Filament setelah deploy
php artisan filament:optimize-clear      # kebalikannya, buat debug/deploy ulang
```

## Redis cache — dashboard `frontend-v2`

Widget dashboard (`app/Filament/Widgets/*`) polling API tiap 5 detik (default Filament `CanPoll`). Backend punya endpoint gabungan `GET /dashboard/overview` (`DashboardController::overview()`) yang membundel 9 endpoint dashboard jadi 1 response — semua widget baca dari situ lewat `ApiService::dashboardOverviewSlice()`, jadi 1 HTTP round-trip per load, bukan 9. `App\Services\ApiService::cachedGet()` cache respons lewat Redis (cache-aside, TTL `DASHBOARD_CACHE_TTL` detik, default 60) di atas itu supaya polling murah; tombol "Refresh" di dashboard bypass cache lewat `ApiService::bustDashboardCache()`. `PermissionHelper::getUserResources()/getUserGroups()` (RBAC, dipanggil di **setiap** navigasi halaman) juga lewat cache yang sama (`RBAC_CACHE_TTL`, default 60).

Client `predis/predis` sudah terinstall di `composer.json` (pure PHP, tidak butuh ekstensi C). Setup Redis server (belum ada di environment ini secara default, pilih salah satu):

```bash
# WSL/Linux
sudo apt install redis-server && redis-server --daemonize yes

# Docker
docker run -d --name handayani-redis -p 6379:6379 redis

# Windows tanpa WSL/Docker: pakai Memurai (https://www.memurai.com/) sebagai pengganti Redis
```

Aktifkan di `frontend-v2/.env`:

```env
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_CACHE_DB=1
DASHBOARD_CACHE_TTL=60
RBAC_CACHE_TTL=60
MASTER_DATA_CACHE_TTL=300
```

Sebelum Redis server jalan, biarkan `CACHE_STORE=database` (default aman, sudah diverifikasi jalan) — jangan set `CACHE_STORE=redis` kalau belum ada Redis server nyala, semua `Cache::` call bakal gagal connect. Redis cache layer ada di `frontend-v2` (cache respons `ApiService`); `backend` sendiri sudah punya `Cache::remember()` per-method di `DashboardService` (TTL 5 menit, lihat bagian OPcache di bawah soal `CACHE_STORE` backend).

Selain dashboard & RBAC, opsi dropdown master data (kelas, kategori, jenis-tagihan) juga di-cache TTL lebih panjang (`MASTER_DATA_CACHE_TTL`, default 300s) — data ini di-refetch berulang tiap modal/wizard step berbeda dibuka padahal isinya sama & jarang berubah (mis. `DataSiswa.php` sebelumnya punya 9 panggilan `/kelas`/`/kategori` identik di form create/edit/wizard yang beda). **Sengaja tidak di-cache**: list utama CRUD (siswa, tagihan, pembayaran — resiko staleness setelah create/update/delete sendiri) dan `/rbac/permissions` di halaman RBAC (admin bisa bikin permission baru lalu langsung coba pilih di dropdown yang sama — self-referential, cache bikin permission barunya gak langsung muncul).

```bash
php artisan config:clear   # wajib setelah ubah CACHE_STORE/REDIS_* di .env
redis-cli ping              # cek server hidup, harus balas PONG
redis-cli -n 1 keys 'dashboard-cache:*'   # lihat entry cache dashboard (db 1 = REDIS_CACHE_DB)
```

## OPcache (backend & frontend-v2, Windows dev)

`php artisan serve` di Windows tanpa OPcache re-compile SELURUH source PHP (Laravel + Filament + Livewire + vendor, puluhan ribu file) dari nol di **setiap** request — ini kontributor terbesar untuk loading lama, lebih besar dari data-fetching itu sendiri. Default `php.ini` Windows tidak mengaktifkan OPcache.

Cek dulu aktif atau belum:

```bash
php -m | grep -i opcache   # kosong = belum aktif
```

Edit `php.ini` (cari lokasinya dengan `php --ini`), di section `[opcache]`:

```ini
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=65407
opcache.validate_timestamps=1
opcache.revalidate_freq=0
```

`validate_timestamps=1` + `revalidate_freq=0` wajib tetap aktif di dev. Ini yang bikin OPcache otomatis pakai versi terbaru file begitu di-save, tanpa perlu restart server atau `opcache_reset()` manual. Jangan dimatikan kecuali di production, di situ baru `validate_timestamps=0` masuk akal dengan `opcache_reset()` manual tiap deploy.

`max_accelerated_files=65407` sengaja jauh di atas default (10.000) — `frontend-v2/vendor` sendiri punya ~23.800 file PHP (Filament besar), default akan undersized dan OPcache diam-diam tidak menyimpan semua script.

Restart `php artisan serve` (backend & frontend-v2) setelah ubah `php.ini` — perubahan `php.ini` baru kepakai saat proses PHP baru dimulai, bukan hot-reload. Verifikasi aktif dari dalam request (bukan CLI, karena `enable_cli=0`):

```bash
php -r "var_dump(opcache_get_status(false)['opcache_statistics']['num_cached_scripts'] ?? null);"  # ini CLI, hasilnya NULL — expected
# verifikasi yang benar: tambah route sementara yang panggil opcache_get_status(), curl, lalu hapus lagi
```

Dampak terukur (dashboard `frontend-v2`, request pertama setelah login, Redis baru di-flush): **3122ms → 711ms**. Kombinasi dengan Redis cache di atas, navigasi berulang (dalam TTL) turun ke **~530ms** — dari baseline awal 5,7-8 detik.

## Cache & config (kedua aplikasi, jalankan setelah pull kode baru / sebelum deploy prod)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# kebalikannya — wajib dipakai saat development, jangan sampai config:cache aktif di dev
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize:clear    # clear semua sekaligus
```

