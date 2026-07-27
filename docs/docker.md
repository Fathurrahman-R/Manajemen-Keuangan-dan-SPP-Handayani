# Menjalankan dengan Docker

Alternatif dari [setup manual](setup.md). Satu `docker compose up` menghidupkan stack dev: `backend` (8080), `frontend-v2` (8000), MariaDB, Redis, queue worker, scheduler, dan tunnel ngrok. Source code di-bind-mount, jadi edit kode PHP/Blade langsung kepakai tanpa restart.

Vite dev server (5173) sengaja tidak ikut nyala. Service `frontend-vite` ada di profile `dev`, jadi dilewati `docker compose up` biasa. Konsekuensinya asset CSS/JS tidak otomatis ter-compile, pilih salah satu mode di bawah.

```bash
cp .env.example .env        # isi NGROK_AUTHTOKEN (https://dashboard.ngrok.com/get-started/your-authtoken)
docker compose up --build
```

Siapkan dulu `backend/.env` dan `frontend-v2/.env` (copy dari `.env.example` masing-masing). Nilai `DB_HOST`, `REDIS_HOST`, `API_URL`, `CACHE_STORE` di dalamnya otomatis di-override `docker-compose.yml` supaya nunjuk service Docker (`mysql`, `redis`, `backend`), tidak perlu diedit manual.

Habis `up`, container `backend` otomatis `composer install` (kalau perlu), `migrate --seed`, dan sinkron RBAC lewat seeder (`RoleAndPermissionSeeder`, `PermissionResourceSeeder`, `PermissionMetadataSeeder`, `PermissionEndpointSeeder`). Tunggu statusnya `healthy` (`docker compose ps`) sebelum diakses.

| Akses | URL |
|---|---|
| Frontend (admin panel/portal) | `http://localhost:8000` |
| Backend API | `http://localhost:8080/api` |
| ngrok inspector | `http://localhost:4040` |
| MariaDB (buat HeidiSQL native Windows) | `127.0.0.1:3306`, user `root`, password sesuai `MYSQL_ROOT_PASSWORD` di root `.env` |
| Mailpit (nangkep semua email dev) | `http://localhost:8025`, SMTP di `mailpit:1025` |

Buat login pertama kali, akun hasil seeder ada di [Setup & Menjalankan](setup.md#akun-hasil-seeder). Singkatnya: `superadmin@handayani.com` / `!handayani123`, dan harus pakai email, bukan username.

## Asset CSS/JS

Dua mode, pilih sesuai kebutuhan:

| Mode | Perintah | Kapan |
|---|---|---|
| Build statis (default) | `docker compose exec frontend npm run build` | Jalanin app biasa, atau testing lewat tunnel/HP |
| Hot reload | `docker compose --profile dev up -d frontend-vite` | Ngoprek CSS/JS di laptop, akses lewat `localhost:8000` |

Mode statis butuh `npm run build` ulang tiap ubah CSS/JS. Mode hot reload tidak, tapi ada efek sampingnya.

`frontend-vite` nulis file `frontend-v2/public/hot`. Selama file itu ada, `@vite` ngarahin asset ke `http://localhost:5173`. Dari laptop jalan karena ada port-forward, tapi dari HP `localhost` artinya HP itu sendiri, jadi asset gagal load dan halaman tampil tanpa style.

Balik ke mode statis:

```bash
docker compose stop frontend-vite
docker exec handayani-frontend-1 rm -f public/hot
docker exec handayani-frontend-1 npm run build
```

Jangan nyalain `frontend-vite` barengan sama sesi testing di HP. Begitu nyala, `public/hot` ditulis ulang dan CSS di HP putus lagi.

## Perintah harian

```bash
docker compose logs -f backend-queue        # pantau job notifikasi/import-export
docker compose logs -f backend-scheduler    # pantau schedule:work
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan test
docker compose down                         # stop semua service, data DB tetap di volume
docker compose down -v                      # stop + hapus volume, DB/vendor/node_modules reset total
```
