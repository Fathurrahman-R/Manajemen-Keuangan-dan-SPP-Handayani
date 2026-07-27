# Setup Midtrans (sandbox)

Fitur pembayaran online butuh kredensial Midtrans. **Setiap dev pakai akun Sandbox-nya sendiri** — gratis, daftar sendiri dalam beberapa menit, dan tidak perlu minta kredensial ke siapa pun. Sandbox terpisah total dari production: tidak ada uang sungguhan, tidak menyentuh merchant account orang lain.

> **Jangan pernah memakai atau meminta `MIDTRANS_SERVER_KEY` milik orang lain.** Server key setara password merchant — pemegangnya bisa membuat transaksi dan refund atas nama merchant tersebut. Kunci ini tidak boleh masuk repo, chat, atau screenshot.

## 1. Daftar akun sandbox

Buka [dashboard.sandbox.midtrans.com](https://dashboard.sandbox.midtrans.com/register) dan daftar. Cukup email, tidak perlu dokumen usaha (itu hanya untuk akun production).

## 2. Ambil kredensial

Di dashboard sandbox: **Settings → Access Keys**. Ambil tiga nilai:

| Nilai | Dipakai di | Sifat |
|---|---|---|
| Merchant ID | `backend/.env` | Identitas merchant |
| Client Key | `backend/.env` **dan** `frontend-v2/.env` | Publik (terekspos ke browser) |
| Server Key | `backend/.env` saja | **Rahasia** |

Pastikan semuanya berawalan `SB-` (mis. `SB-Mid-server-abc123...`). Tanpa awalan itu berarti kunci **production** — jangan dipakai untuk dev.

## 3. Isi `.env`

`backend/.env`:

```env
HANDAYANI_MIDTRANS_ENABLED=true
HANDAYANI_MIDTRANS_WEBHOOK_ENABLED=true
MIDTRANS_ENVIRONMENT=sandbox
MIDTRANS_SERVER_KEY=SB-Mid-server-...      # punya sendiri
MIDTRANS_CLIENT_KEY=SB-Mid-client-...      # punya sendiri
MIDTRANS_MERCHANT_ID=G...                  # punya sendiri
```

`frontend-v2/.env` — `MIDTRANS_CLIENT_KEY` **harus sama persis** dengan yang di backend, kalau beda Snap gagal terbuka:

```env
HANDAYANI_MIDTRANS_ENABLED=true
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/snap.js
```

Jalankan `php artisan config:clear` di kedua app setelah mengubah `.env` (kalau pakai Docker: `docker compose exec backend php artisan config:clear`).

## 4. Daftarkan webhook

Midtrans mengirim status pembayaran ke `POST /api/midtrans/notification` — endpoint ini butuh URL publik, jadi backend harus di-tunnel lebih dulu (lihat [Tunneling](tunneling.md), mode `backend`).

Setelah dapat URL ngrok, di dashboard sandbox buka **Settings → Configuration** dan isi **Payment Notification URL**:

```
https://<subdomain>.ngrok-free.dev/api/midtrans/notification
```

URL ngrok berubah tiap container restart — daftar ulang tiap sesi dev. Tanpa langkah ini pembayaran tetap bisa dilakukan, tapi status tagihan di aplikasi tidak akan ikut berubah.

## 5. Uji pembayaran

Buat tagihan, buka Portal siswa, klik bayar. Di halaman Snap gunakan data uji sandbox:

- **Kartu sukses:** `4811 1111 1111 1114`, CVV `123`, expiry bebas di masa depan (mis. `12/30`), OTP 3DS `112233`.
- **QRIS / GoPay / ShopeePay:** pakai tombol **"Simulate Payment"** di Snap sandbox untuk mengubah status transaksi.
- Nomor uji lain (skenario `deny`, `pending`, dsb.) ada di [docs.midtrans.com/docs/testing-payment-on-sandbox](https://docs.midtrans.com/docs/testing-payment-on-sandbox) — nomor bisa berubah, verifikasi di sana sebelum dipakai.

Kalau webhook tidak kunjung masuk, kirim manual lewat menu **HTTP Notification Test** di dashboard sandbox dengan `order_id` transaksi yang diuji. Semua notifikasi yang masuk tercatat di tabel `midtrans_transaction_logs` (prune berkala dengan `php artisan midtrans:prune-logs --days=180`).

**Menjalankan tanpa Midtrans.** Kalau tidak sedang menggarap fitur pembayaran, set `HANDAYANI_MIDTRANS_ENABLED=false` di kedua `.env` — sisa aplikasi jalan normal tanpa kredensial apa pun.

