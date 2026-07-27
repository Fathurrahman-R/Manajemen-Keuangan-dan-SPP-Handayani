# Tunneling (ngrok)

Dua kebutuhan berbeda memakai tunnel yang sama:

- **Webhook Midtrans** → butuh `backend:8080` publik. Endpoint `POST /api/midtrans/notification` **sengaja publik tanpa auth** karena Midtrans harus bisa mengirim notifikasi status pembayaran ke instance lokal.
- **Akses aplikasi dari HP** → butuh `frontend:8000` publik.

Stack Docker sudah punya service `ngrok` sendiri. Konfigurasinya ada di **dua tempat yang harus cocok**:

- `docker/ngrok/ngrok.yml` — mendefinisikan tunnel beserta target-nya.
- `docker-compose.yml`, service `ngrok` — `command:` menyebut *nama* tunnel mana yang dijalankan.

Kalau `command:` menyebut nama yang tidak ada di `ngrok.yml`, container langsung exit.

> **ngrok free plan hanya mengalokasikan satu ephemeral domain per sesi agent.** Frontend dan backend **tidak bisa** jalan bersamaan — tunnel di sini sifatnya saling tukar, bukan tambah. Default saat ini: `backend` (webhook Midtrans).

## Menukar target tunnel (backend ↔ frontend)

**Langkah 1** — `docker/ngrok/ngrok.yml`, aktifkan blok yang dituju dan komentari yang lain:

```yaml
# --- Mode BACKEND (default) — webhook Midtrans ---
tunnels:
  backend:
    proto: http
    addr: backend:8080
  # frontend:
  #   proto: http
  #   addr: frontend:8000
```

```yaml
# --- Mode FRONTEND — akses aplikasi dari HP ---
tunnels:
  frontend:
    proto: http
    addr: frontend:8000
  # backend:
  #   proto: http
  #   addr: backend:8080
```

**Langkah 2** — `docker-compose.yml`, service `ngrok`. Samakan nama tunnel di `command:` **dan** `depends_on:` (kalau `depends_on` menunggu service yang tidak relevan, startup jadi menggantung tanpa alasan):

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

**Langkah 3** — recreate container dan ambil URL publiknya:

```bash
docker compose up -d ngrok --force-recreate
docker logs handayani-ngrok-1 --tail 20 | grep "started tunnel"
```

Outputnya berbentuk:

```
msg="started tunnel" obj=tunnels name=backend addr=http://backend:8080 url=https://<subdomain>.ngrok-free.dev
```

Nilai `url=` itu alamat publiknya; `name=` memastikan mode yang aktif sudah benar. Bisa juga dilihat lewat inspector `http://localhost:4040`.

## Catatan per mode

- **Mode backend:** daftarkan `https://<subdomain>.ngrok-free.dev/api/midtrans/notification` sebagai Payment Notification URL di dashboard Midtrans Sandbox. URL berubah tiap container di-restart (kecuali pakai reserved domain berbayar) — daftar ulang tiap sesi dev.
- **Mode frontend:** cukup buka `url=` di HP. Pastikan asset sudah di-`npm run build` — lihat gotcha `public/hot` di [Docker](docker.md), karena mode hot reload mengarah ke `localhost:5173` yang tidak reachable dari HP.
- **Punya paid plan + reserved domain:** dua tunnel bisa hidup bersamaan — isi `domain:` di masing-masing blok `ngrok.yml`, lalu ganti `command:` jadi `["start", "--all", ...]`.
- **Tanpa Docker:** `ngrok http 8080` (backend) atau `ngrok http 8000` (frontend).

