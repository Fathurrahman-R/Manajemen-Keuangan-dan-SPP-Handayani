# Tunneling (ngrok)

Ada dua kebutuhan yang rebutan tunnel yang sama:

- Webhook Midtrans butuh `backend:8080` publik. Endpoint `POST /api/midtrans/notification` sengaja publik tanpa auth karena Midtrans harus bisa ngirim notifikasi ke instance lokal.
- Akses aplikasi dari HP butuh `frontend:8000` publik.

Stack Docker punya service `ngrok` sendiri. Konfigurasinya kesebar di dua tempat yang harus cocok:

- `docker/ngrok/ngrok.yml` mendefinisikan tunnel dan targetnya.
- `docker-compose.yml` service `ngrok`, `command:` nyebut nama tunnel mana yang dijalanin.

Kalau `command:` nyebut nama yang tidak ada di `ngrok.yml`, container langsung exit.

ngrok free cuma dapat satu ephemeral domain per sesi. Frontend dan backend tidak bisa jalan bareng, jadi ini sifatnya tukeran, bukan nambah. Default sekarang `backend`.

## Tukar target tunnel

**1. Edit `docker/ngrok/ngrok.yml`.** Aktifkan blok yang dituju, komentari yang lain:

```yaml
# Mode BACKEND (default), buat webhook Midtrans
tunnels:
  backend:
    proto: http
    addr: backend:8080
  # frontend:
  #   proto: http
  #   addr: frontend:8000
```

```yaml
# Mode FRONTEND, buat akses dari HP
tunnels:
  frontend:
    proto: http
    addr: frontend:8000
  # backend:
  #   proto: http
  #   addr: backend:8080
```

**2. Edit `docker-compose.yml`, service `ngrok`.** Samakan nama tunnel di `command:` dan `depends_on:`. Kalau `depends_on` nungguin service yang tidak relevan, startup nggantung tanpa sebab jelas:

```yaml
# Mode backend
command: ["start", "backend", "--config", "/etc/ngrok.yml", "--log=stdout", "--log-format=logfmt"]
depends_on:
  backend:
    condition: service_healthy
```

```yaml
# Mode frontend
command: ["start", "frontend", "--config", "/etc/ngrok.yml", "--log=stdout", "--log-format=logfmt"]
depends_on:
  frontend:
    condition: service_healthy
```

**3. Recreate container, ambil URL-nya:**

```bash
docker compose up -d ngrok --force-recreate
docker logs handayani-ngrok-1 --tail 20 | grep "started tunnel"
```

Outputnya:

```
msg="started tunnel" obj=tunnels name=backend addr=http://backend:8080 url=https://<subdomain>.ngrok-free.dev
```

`url=` itu alamat publiknya, `name=` buat mastiin mode yang aktif sudah bener. Bisa juga lihat di inspector `http://localhost:4040`.

## Catatan per mode

**Mode backend.** Daftarkan `https://<subdomain>.ngrok-free.dev/api/midtrans/notification` sebagai Payment Notification URL di dashboard Midtrans Sandbox. URL-nya ganti tiap container restart kecuali pakai reserved domain berbayar, jadi daftar ulang tiap sesi.

**Mode frontend.** Tinggal buka `url=` di HP. Pastikan asset sudah di-`npm run build`, karena mode hot reload nunjuk `localhost:5173` yang tidak kejangkau dari HP. Detailnya di [Docker](docker.md).

**Paid plan + reserved domain.** Dua tunnel bisa hidup bareng. Isi `domain:` di masing-masing blok `ngrok.yml`, lalu ganti `command:` jadi `["start", "--all", ...]`.

**Tanpa Docker.** `ngrok http 8080` buat backend, `ngrok http 8000` buat frontend.
