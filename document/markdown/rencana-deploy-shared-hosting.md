# Rencana Deploy Production ke Shared Hosting

## Konteks

Solusi pindah ke VPS ditolak client — tetap di shared hosting cPanel. Batasan lingkungan:

- Tidak ada Redis
- Tidak ada NodeJS
- Tidak ada akses terminal/SSH
- Ada Cron Job (bebas set menit/jam/hari/bulan/weekday, custom command)
- Deploy via upload file compressed (.zip/.rar) ke cPanel File Manager, build manual di lokal dulu

## Temuan dari codebase

- `frontend-v2/.env.example`: `SESSION_DRIVER=redis` — satu-satunya pemakaian eksplisit Redis.
- `backend/.env.example`: sudah `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`.
- Tidak ada pemakaian `Cache::tags()` di kode manapun (backend maupun frontend-v2) — aman ganti ke driver `database`, karena driver ini tidak mendukung cache tags.
- `frontend-vite` di docker-compose cuma dev server (hot reload). Production Filament selalu serve compiled assets dari `public/build/` — Node tidak dibutuhkan di server production sama sekali.

## Rencana

### 1. Ganti Redis → database driver

- `frontend-v2/.env`: `SESSION_DRIVER=redis` → `database`
- Pastikan tabel `sessions` ada di kedua app (migrasi bawaan `create_sessions_table`, migrasi dipegang `backend` sesuai konvensi monorepo).
- `CACHE_STORE=database` di kedua `.env` kalau belum.
- Queue sudah `database` di keduanya — tidak perlu diubah.

### 2. Queue worker tanpa proses persisten

Tidak ada supervisor/terminal untuk long-running process. Pakai cron per menit dengan auto-exit:

```
* * * * * php /home/user/backend/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

`--stop-when-empty` bikin worker exit sendiri tiap jalan, gak numpuk proses. Menggantikan container `backend-queue`.

### 3. Scheduler

```
* * * * * php /home/user/backend/artisan schedule:run >> /dev/null 2>&1
```

Menggantikan container `backend-scheduler`. cPanel bebas set interval menit jadi behaviour identik dengan Docker.

### 4. Build asset frontend (Node lokal, bukan di server)

```bash
cd frontend-v2
npm run build
```

Masukkan `public/build/` ke dalam zip upload. Server tidak butuh Node di production — bukan blocker.

### 5. Deploy via zip upload

Build semua di lokal sebelum zip:

```bash
composer install --no-dev --optimize-autoloader
npm run build   # frontend-v2 saja
```

Perhatian: samakan versi PHP lokal dengan versi PHP di cPanel MultiPHP Manager — kalau beda minor version, extension/behaviour composer bisa mismatch.

Isi zip: seluruh app termasuk `vendor/`, exclude `.env`, `.git`, `node_modules`, `storage/logs/*`. Upload lalu extract via cPanel File Manager. `.env` upload/edit terpisah, jangan pernah ikut zip.

### 6. Command artisan tanpa terminal

Dua opsi, dipakai sesuai kebutuhan:

- **One-off command** (default, lebih aman): bikin cron entry sekali jalan (set menit beberapa saat ke depan) berisi command seperti `php artisan migrate --force`. Habis jalan, hapus entry cron-nya.
- **Kalau sering redeploy**: bikin route tersembunyi + token secret yang memanggil `Artisan::call()` langsung di dalam proses PHP (bukan `exec()`, jadi tidak kebentur `exec()` yang mungkin di-disable host). Wajib: token panjang/acak, batasi ke environment production, dan cabut/hapus route ini setelah tidak dipakai — kalau lupa dicabut jadi lubang RCE-ringan (bisa jalankan artisan command apapun).

Command penting tiap deploy: `migrate --force`, `config:cache`, `route:cache`, `view:cache`, `storage:link`.

### 7. Struktur hosting dua app

`backend` dan `frontend-v2` adalah app Laravel terpisah → butuh 2 subdomain, masing-masing document root diarahkan ke `public/` app-nya masing-masing (bukan root repo). Satu database MySQL dipakai bareng.

### 8. Checklist manual sebelum deploy

- Permission `storage/` dan `bootstrap/cache/` writable (755/775) setelah extract zip — cPanel File Manager biasa reset permission pas extract.
- PHP extension di MultiPHP Manager: mbstring, openssl, pdo_mysql, bcmath, fileinfo, curl, ctype, tokenizer, xml — dibutuhkan Laravel 12.
- SSL aktif (AutoSSL/Let's Encrypt cPanel) — webhook Midtrans (`POST /api/midtrans/notification`, publik tanpa auth) umumnya wajib HTTPS dari sisi Midtrans.
- Monitor volume kerja: shared hosting punya limit `max_execution_time` dan proses concurrent cron. Cron per-menit dengan `--stop-when-empty` realistis untuk skala sekolah, tapi perlu dipantau kalau job queue (email, dsb) mulai numpuk.

## Belum diputuskan / langkah selanjutnya

- Draf `.env` production untuk kedua app.
- Urutan command deploy pertama kali (composer install → build → zip → upload → migrate → cache).
