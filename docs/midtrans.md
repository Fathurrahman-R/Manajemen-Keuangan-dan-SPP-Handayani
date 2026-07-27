# Setup Midtrans (sandbox)

Fitur pembayaran online butuh kredensial Midtrans. Tiap dev pakai akun sandbox sendiri: gratis, daftar sendiri, tidak perlu minta ke siapa-siapa. Sandbox terpisah total dari production, tidak ada uang beneran dan tidak nyentuh merchant account orang lain.

Jangan pakai atau minta `MIDTRANS_SERVER_KEY` orang lain. Server key setara password merchant, pemegangnya bisa bikin transaksi dan refund atas nama merchant itu. Jangan pernah masuk repo, chat, atau screenshot.

## 1. Daftar akun sandbox

Daftar di [dashboard.sandbox.midtrans.com](https://dashboard.sandbox.midtrans.com/register). Cukup email, tidak perlu dokumen usaha (itu buat akun production).

## 2. Ambil kredensial

Dashboard sandbox, **Settings → Access Keys**. Ada tiga nilai:

| Nilai | Dipakai di | Sifat |
|---|---|---|
| Merchant ID | `backend/.env` | Identitas merchant |
| Client Key | `backend/.env` dan `frontend-v2/.env` | Publik, terekspos ke browser |
| Server Key | `backend/.env` saja | Rahasia |

Pastikan semuanya berawalan `SB-`, misal `SB-Mid-server-abc123...`. Tanpa awalan itu berarti kunci production, jangan dipakai buat dev.

## 3. Isi `.env`

`backend/.env`:

```env
HANDAYANI_MIDTRANS_ENABLED=true
HANDAYANI_MIDTRANS_WEBHOOK_ENABLED=true
MIDTRANS_ENVIRONMENT=sandbox
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_MERCHANT_ID=G...
```

`frontend-v2/.env`. `MIDTRANS_CLIENT_KEY` harus sama persis dengan yang di backend, kalau beda Snap gagal kebuka:

```env
HANDAYANI_MIDTRANS_ENABLED=true
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/snap.js
```

Habis ubah `.env`, jalanin `php artisan config:clear` di kedua app. Pakai Docker: `docker compose exec backend php artisan config:clear`.

## 4. Daftarkan webhook

Midtrans ngirim status pembayaran ke `POST /api/midtrans/notification`. Endpoint ini butuh URL publik, jadi backend harus di-tunnel dulu, lihat [Tunneling](tunneling.md) mode `backend`.

Habis dapat URL ngrok, di dashboard sandbox buka **Settings → Configuration**, isi **Payment Notification URL**:

```
https://<subdomain>.ngrok-free.dev/api/midtrans/notification
```

URL ngrok ganti tiap container restart, jadi daftar ulang tiap sesi. Kalau langkah ini dilewat, pembayaran tetap bisa jalan tapi status tagihan di aplikasi tidak ikut berubah.

## 5. Uji pembayaran

Bikin tagihan, buka Portal siswa, klik bayar. Data uji di halaman Snap:

- Kartu sukses: `4811 1111 1111 1114`, CVV `123`, expiry bebas asal di masa depan (misal `12/30`), OTP 3DS `112233`.
- QRIS/GoPay/ShopeePay: pakai tombol **Simulate Payment** di Snap sandbox buat ubah status transaksi.
- Nomor uji lain (skenario `deny`, `pending`, dsb.) ada di [docs.midtrans.com](https://docs.midtrans.com/docs/testing-payment-on-sandbox). Nomornya bisa berubah, cek di sana sebelum dipakai.

Kalau webhook tidak kunjung masuk, kirim manual lewat menu **HTTP Notification Test** di dashboard sandbox pakai `order_id` transaksi yang diuji. Semua notifikasi yang masuk tercatat di tabel `midtrans_transaction_logs`, prune berkala pakai `php artisan midtrans:prune-logs --days=180`.

## Jalan tanpa Midtrans

Kalau tidak lagi ngerjain fitur pembayaran, set `HANDAYANI_MIDTRANS_ENABLED=false` di kedua `.env`. Sisa aplikasi jalan normal tanpa kredensial apa pun.
