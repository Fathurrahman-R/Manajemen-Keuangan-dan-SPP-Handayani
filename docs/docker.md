# Menjalankan dengan Docker


Alternatif dari setup manual di atas — satu `docker compose up` menghidupkan stack dev: `backend` (port 8080), `frontend-v2` (port 8000), MariaDB, Redis, queue worker, scheduler, dan tunnel ngrok. Source code di-bind-mount, jadi edit kode PHP/Blade langsung kepakai tanpa restart.

**Vite dev server (port 5173) sengaja tidak ikut nyala** — service `frontend-vite` ada di profile `dev`, jadi dilewati oleh `docker compose up` biasa. Asset CSS/JS **tidak** otomatis ter-compile; pilih salah satu mode di bawah.

```bash
cp .env.example .env        # isi NGROK_AUTHTOKEN (https://dashboard.ngrok.com/get-started/your-authtoken)
docker compose up --build
```

Yang perlu disiapkan lebih dulu: `backend/.env` dan `frontend-v2/.env` (copy dari `.env.example` masing-masing seperti biasa) — nilai `DB_HOST`, `REDIS_HOST`, `API_URL`, `CACHE_STORE` di dalamnya **otomatis di-override** oleh `docker-compose.yml` supaya mengarah ke service Docker (`mysql`, `redis`, `backend`), jadi tidak perlu diedit manual.

Setelah `up`, container `backend` otomatis `composer install` (kalau perlu), `migrate --seed`, dan sinkron RBAC lewat seeder (`RoleAndPermissionSeeder`, `PermissionResourceSeeder`, `PermissionMetadataSeeder`, `PermissionEndpointSeeder`) — tunggu sampai statusnya `healthy` (`docker compose ps`) sebelum akses.

| Akses | URL |
|---|---|
| Frontend (admin panel/portal) | `http://localhost:8000` |
| Backend API | `http://localhost:8080/api` |
| ngrok inspector (lihat URL publik) | `http://localhost:4040` |
| MariaDB (buat HeidiSQL native Windows) | `127.0.0.1:3306`, user `root`, password sesuai `MYSQL_ROOT_PASSWORD` di root `.env` |
| Mailpit (tangkap semua email dev, ganti Mailtrap) | `http://localhost:8025` — SMTP di `mailpit:1025` (sudah otomatis jadi `MAIL_HOST` container backend) |

## Asset CSS/JS — dua mode

| Mode | Perintah | Kapan dipakai |
|---|---|---|
| **Build statis** (default) | `docker compose exec frontend npm run build` | Menjalankan app biasa, atau testing lewat tunnel/HP |
| **Hot reload** | `docker compose --profile dev up -d frontend-vite` | Ngoprek CSS/JS di laptop, akses lewat `localhost:8000` |

Mode build statis butuh `npm run build` ulang tiap kali ubah CSS/JS. Mode hot reload tidak, tapi ada konsekuensinya:

> **Gotcha — CSS tidak muncul saat diakses dari HP/tunnel.**
> `frontend-vite` menulis file `frontend-v2/public/hot`. Selama file itu ada, `@vite` di Blade mengarahkan asset ke `http://localhost:5173`. Dari laptop itu jalan (ada port-forward), tapi dari HP `localhost` berarti HP itu sendiri — asset gagal load, halaman tampil tanpa style.
>
> Balik ke mode statis:
> ```bash
> docker compose stop frontend-vite
> docker exec handayani-frontend-1 rm -f public/hot
> docker exec handayani-frontend-1 npm run build
> ```
> Jangan jalankan `frontend-vite` bersamaan dengan sesi testing di HP — begitu nyala, `public/hot` ditulis ulang dan CSS di HP putus lagi.

Perintah harian yang berguna:

```bash
docker compose logs -f backend-queue        # pantau job notifikasi/import-export
docker compose logs -f backend-scheduler    # pantau schedule:work
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan test
docker compose down                         # stop semua service (data DB tetap ada di volume)
docker compose down -v                      # stop + hapus volume (DB/vendor/node_modules reset total)
```

