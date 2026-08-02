# Gotchas

Perilaku yang disengaja tapi tidak kelihatan dari kodenya, plus beberapa jebakan yang sudah pernah makan waktu. Baca sekali, sisanya jadi tempat balik kalau ada yang aneh.

## Arsitektur

**Backend jalan di port 8080, bukan 8000.**
`frontend-v2/.env` nyari API di `http://127.0.0.1:8080/api`. Pakai `php artisan serve --port=8080`. Kalau salah port, halaman admin kosong tanpa error yang jelas.

**Cuma `backend` yang punya migrasi.**
`frontend-v2` ambil data lewat HTTP ke backend, bukan Eloquent. Jangan bikin migrasi di `frontend-v2`. Dua-duanya nunjuk database yang sama, jadi migrasi dobel bakal bentrok.

**Token Sanctum disimpan di session frontend.**
Bukan di database atau cookie sendiri. Kalau session hilang (ganti `SESSION_DRIVER`, `session:clear`), user harus login lagi.

## Database

**`Tagihan` join ke `Siswa` lewat kolom `nis`, bukan `siswa.id`.**
Ganti NIS siswa = tagihannya lepas. Kalau memang harus ganti, update tagihan terkait juga.

**Database test terpisah, dan tidak dibuat otomatis.**
Backend test pakai `handayani_testing` di MariaDB beneran. Tanpa dibuat duluan, semua test gagal. Caranya ada di [Testing](testing.md).

**`--env=testing` tidak nunjuk database test.**
Tidak ada `backend/.env.testing`, jadi Laravel jatuh balik ke `.env` yang `DB_DATABASE=handayani`. `php artisan migrate:fresh --env=testing` bakal ngehapus **database dev**, bukan database test. Sudah pernah kejadian. Kalau perlu reset skema DB test, sebut databasenya eksplisit lewat `-e DB_DATABASE=handayani_testing`.

## RBAC

**Nama permission pakai bahasa Indonesia.**
`view-tagihan`, `create-pengeluaran-request`, dst. `PermissionHelper` cocokkan string persis dari session, jadi typo tidak ketahuan sampai runtime.

**Tidak ada command `permissions:sync`.**
Sinkronisasi cuma lewat seeder. Habis nambah/ganti case di `App\Enum\Permission`:

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=PermissionEndpointSeeder   # kalau mapping endpoint ikut berubah
```

Dua-duanya pakai `firstOrCreate`/`updateOrCreate`, aman diulang. Kalau kelewat, permission baru belum punya baris di tabel `permissions` dan middleware Spatie nolak semua akses ke situ.

**Ada dua lapis pengecekan, dua-duanya harus diurus.**
UI dicek lewat `page_perms` + `PermissionHelper`, API lewat `permission_endpoints` + middleware. Daftar satu doang hasilnya halaman kebuka tapi datanya gagal load, atau sebaliknya. Detail di [RBAC](rbac.md).

**Cache RBAC 60 detik.**
`/rbac/user-resources` di-cache (`RBAC_CACHE_TTL`). Habis ubah permission, tunggu sebentar atau clear cache dulu sebelum menyimpulkan perubahannya tidak ngefek.

## Midtrans

**Webhook `POST /api/midtrans/notification` sengaja publik tanpa auth.**
Midtrans harus bisa manggil dari luar. Ini bukan bug, jangan ditambahin middleware auth. Pengamanannya lewat verifikasi signature.

**Webhook tetap jalan walau `HANDAYANI_MIDTRANS_ENABLED=false`.**
Controllernya sengaja tidak cek toggle itu supaya transaksi yang terlanjur jalan masih bisa selesai. Yang dicek `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED`, di service layer.

**Transaksi expired 24 jam sejak dibuat.**
Atur lewat `MIDTRANS_EXPIRY_MINUTES` (default 1440). Kalau diturunkan buat nguji skenario expired, balikin lagi setelahnya.

**`finish_url` ngikut `FRONTEND_URL`.**
Habis selesai/batal di Snap, siswa dilempar ke `FRONTEND_URL` + `/portal/status-pembayaran`. Set `FRONTEND_URL` ke domain asli waktu deploy. `MIDTRANS_FINISH_URL` cuma perlu diisi kalau tujuannya beda.

**Pakai akun sandbox sendiri.**
Jangan minta atau pakai `MIDTRANS_SERVER_KEY` orang lain, itu setara password merchant. Cara daftar: [Setup Midtrans](midtrans.md).

## Queue & notifikasi

**Notifikasi email masuk queue `notifications`, bukan `default`.**
Jalanin `php artisan queue:work` polos = job notifikasi tidak pernah kepegang. Tidak ada error, emailnya diam saja. Sebutkan queue-nya:

```bash
composer run queue                                     # flag sudah bener
php artisan queue:work --queue=notifications,default
```

`composer run dev` dan service `backend-queue` di Docker sudah pakai flag itu.

**Email dev ketangkep Mailpit.**
Di Docker `MAIL_HOST` diarahkan ke `mailpit:1025`. Cek di `http://localhost:8025`, bukan di inbox asli.

## Autentikasi

**User yang punya email tidak bisa login pakai username.**
`IdentifierService` sengaja matiin login-by-username buat user non-siswa yang emailnya keisi. Jadi `superadmin` ditolak 401 walau passwordnya benar, harus pakai `superadmin@handayani.com`. Admin cabang hasil seeder tidak punya email, jadi mereka justru pakai username. Daftar lengkapnya di [Setup](setup.md#akun-hasil-seeder).

**Token Sanctum expired 8 jam.**
Atur lewat `SANCTUM_TOKEN_EXPIRATION` (menit, default 480). Lewat dari itu request ditolak 401, ini normal.

**`FRONTEND_URL` dipakai link reset password dan redirect pembayaran.**
Defaultnya localhost. Kalau lupa diganti waktu deploy, link reset password di email dan redirect habis bayar bakal nunjuk localhost penerima.

## Frontend & asset

**Vite dev server tidak ikut nyala di `docker compose up`.**
Service `frontend-vite` ada di profile `dev`. Jadi asset harus di-`npm run build`, atau nyalain profilenya manual. Lihat [Docker](docker.md).

**File `public/hot` nentuin sumber asset.**
Selama file itu ada, `@vite` nunjuk `localhost:5173`, yang tidak kejangkau dari HP atau lewat tunnel. Hapus filenya lalu `npm run build` buat balik ke asset statis.

**Konten landing page ada di config, bukan di Blade.**
Semua teks halaman publik di `frontend-v2/config/handayani-public.php`. Beberapa key harus array karena dirender pakai `@foreach`: `about.misi`, `nav_links`, `ekstrakurikuler.kegiatan`, `fasilitas.sarana`, `hero.stats`, `jenjang.levels`. Diisi string tunggal = halaman error.

## Tooling

**ngrok free cuma dapat satu tunnel.**
Frontend dan backend tidak bisa jalan bareng, harus tukeran. Caranya di [Tunneling](tunneling.md).

**Jangan `config:cache` waktu development.**
Perubahan `.env` tidak kebaca sampai `config:clear`. Gampang bikin bingung karena errornya muncul jauh dari penyebabnya.
