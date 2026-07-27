# Gotchas — hal yang bikin dev baru tersesat

Kumpulan perilaku yang **disengaja tapi tidak intuitif**, plus jebakan yang sudah pernah memakan waktu orang. Baca sekali di awal; sisanya jadi rujukan saat ada yang aneh.

## Arsitektur

**Backend jalan di port 8080, bukan 8000.**
`frontend-v2/.env` mengharapkan API di `http://127.0.0.1:8080/api`. Jalankan backend dengan `php artisan serve --port=8080`. Kalau lupa, seluruh halaman admin kosong/error tanpa pesan yang jelas.

**Hanya `backend` yang punya migrasi.**
`frontend-v2` mengakses data lewat HTTP ke backend, bukan Eloquent langsung. Jangan pernah membuat migrasi di `frontend-v2` — keduanya menunjuk database yang sama, jadi migrasi ganda akan bentrok.

**`frontend-v2` menyimpan token Sanctum di session.**
Bukan di database atau cookie terpisah. Session hilang (mis. `SESSION_DRIVER` berubah, atau `php artisan session:clear`) berarti user harus login ulang.

## Database

**`Tagihan` ↔ `Siswa` di-join lewat kolom `nis`, bukan `siswa.id`.**
Mengubah NIS seorang siswa memutus tautan ke tagihannya. Kalau butuh mengubah NIS, pastikan tagihan terkait ikut di-update.

**Database test terpisah dan tidak dibuat otomatis.**
Backend test memakai `handayani_testing` di MariaDB nyata. Lihat [Testing](testing.md) untuk cara membuatnya — tanpa langkah itu semua test gagal.

## RBAC

**Nama permission berbahasa Indonesia.**
Contoh: `view-tagihan`, `create-pengeluaran-request`. `PermissionHelper` di frontend mencocokkan string persis dari session.

**Tidak ada command `permissions:sync`.**
Sinkronisasi RBAC hanya lewat seeder. Setelah menambah/mengubah case di `App\Enum\Permission`, jalankan:

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=PermissionEndpointSeeder   # kalau mapping endpoint ikut berubah
```

Keduanya memakai `firstOrCreate`/`updateOrCreate` — aman dijalankan berulang. Kalau dilewat, permission baru belum punya baris di tabel `permissions` dan middleware Spatie akan menolak semua akses ke situ.

**Ada dua lapis pengecekan yang harus sama-sama diurus.**
UI (halaman/komponen Filament) dicek lewat `page_perms` + `PermissionHelper`; API dicek lewat `permission_endpoints` + middleware. Mendaftarkan satu saja bikin halaman terlihat tapi datanya gagal dimuat, atau sebaliknya. Detail: [RBAC](rbac.md).

**Cache RBAC ±60 detik.**
Hasil `/rbac/user-resources` di-cache (`RBAC_CACHE_TTL`, default 60). Setelah mengubah permission, tunggu sebentar atau clear cache — jangan langsung menyimpulkan perubahannya tidak jalan.

## Midtrans

**Webhook `POST /api/midtrans/notification` sengaja publik tanpa auth.**
Midtrans harus bisa memanggilnya dari luar. Ini bukan bug — jangan "diperbaiki" dengan menambah middleware auth. Verifikasi keamanannya lewat signature, bukan lewat auth middleware.

**Webhook tetap diproses walau `HANDAYANI_MIDTRANS_ENABLED=false`.**
Controller-nya sengaja tidak mengecek toggle itu, supaya transaksi yang sudah terlanjur berjalan tetap bisa diselesaikan. Yang dicek adalah `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED` di service layer.

> [!WARNING]
> **`expiry_minutes` saat ini bernilai `1`, bukan 1440.**
> Di `backend/config/midtrans.php` nilainya di-hardcode `'expiry_minutes' => 1` sementara komentar tepat di atasnya menyebut *"default 1440 = 24 jam"*. Efek nyatanya: setiap transaksi Midtrans kedaluwarsa **1 menit** setelah dibuat, jadi pembayaran yang tidak langsung diselesaikan akan expired. Nilai ini juga tidak bisa di-override lewat `.env` (tidak dibungkus `env()`).
>
> Kemungkinan besar ini sisa setelan uji coba yang belum dikembalikan. Kalau memang disengaja, komentarnya perlu diperbaiki; kalau tidak, kembalikan ke `1440`.

**`finish_url` default menunjuk `127.0.0.1:8000`.**
Setelah selesai/batal di halaman Snap, siswa diarahkan ke `MIDTRANS_FINISH_URL` (default `http://127.0.0.1:8000/portal/beranda`). Di production wajib di-set ke domain asli, kalau tidak pembayar akan dilempar ke localhost mereka sendiri.

**Setiap dev pakai akun sandbox sendiri.**
Jangan meminta atau memakai `MIDTRANS_SERVER_KEY` orang lain — server key setara password merchant. Cara daftar sendiri: [Setup Midtrans](midtrans.md).

## Queue & notifikasi

**Semua notifikasi email masuk queue bernama `notifications`, bukan `default`.**
Kalau menjalankan `php artisan queue:work` polos, job notifikasi **tidak akan pernah diproses** — tidak ada error, email hanya diam tidak terkirim. Jalankan dengan nama queue-nya:

```bash
php artisan queue:work --queue=notifications,default
```

Stack Docker sudah benar (`backend-queue` memakai flag itu); yang perlu hati-hati adalah saat menjalankan worker manual.

**Email dev ditangkap Mailpit, bukan dikirim sungguhan.**
Di Docker, `MAIL_HOST` diarahkan ke `mailpit:1025`. Semua email bisa dilihat di `http://localhost:8025` — jangan bingung kalau inbox asli kosong.

## Autentikasi

**Token Sanctum kedaluwarsa 8 jam.**
`backend/config/sanctum.php` menetapkan `'expiration' => 480` (menit) dan nilainya di-hardcode, tidak lewat `.env`. Sesi yang dibiarkan lebih lama akan menolak request dengan 401 — ini perilaku normal, bukan bug.

## Frontend & asset

**Vite dev server tidak ikut nyala di `docker compose up`.**
Service `frontend-vite` ada di profile `dev`. Konsekuensinya asset harus di-`npm run build`, atau nyalakan profile-nya secara eksplisit. Lihat [Docker](docker.md).

**File `public/hot` menentukan sumber asset.**
Selama file itu ada, `@vite` menunjuk `localhost:5173` — tidak reachable dari HP atau lewat tunnel. Hapus file-nya dan `npm run build` untuk kembali ke asset statis.

**Konten landing page ada di config, bukan di Blade.**
Semua teks halaman publik ada di `frontend-v2/config/handayani-public.php`. Beberapa key **wajib array** (`about.misi`, `nav_links`, `ekstrakurikuler.kegiatan`, `fasilitas.sarana`, `hero.stats`, `jenjang.levels`) karena dirender lewat `@foreach` — mengisinya dengan string tunggal bikin halaman error.

## Tooling

**ngrok free plan cuma satu tunnel.**
Frontend dan backend tidak bisa di-tunnel bersamaan; sifatnya saling tukar. Cara menukar: [Tunneling](tunneling.md).

**Jangan `config:cache` saat development.**
Perubahan `.env` tidak akan terbaca sampai `config:clear`. Ini sering menyesatkan karena error-nya muncul jauh dari penyebabnya.
