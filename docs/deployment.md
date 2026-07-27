# Deployment (production)

Catatan khusus deployment. Untuk perintah cache/optimize sebelum deploy, lihat [Command Reference](commands.md).

## Cron (scheduler)

Tambahkan satu baris ini di crontab server (jalan tiap menit, Laravel scheduler sendiri yang menentukan kapan tiap job benar-benar dieksekusi):

```bash
* * * * * cd /path/ke/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Queue worker

Jalankan `queue:work --queue=notifications,default` lewat process manager (Supervisor/systemd) — jangan pakai `nohup` manual, karena worker perlu auto-restart saat crash atau saat deploy kode baru.

Setelah deploy jalankan `php artisan queue:restart` agar worker memuat kode terbaru. Tanpa ini worker tetap menjalankan kode lama sampai prosesnya mati sendiri.

## Kredensial Midtrans

Untuk production, set `MIDTRANS_ENVIRONMENT=production` dan pakai access key production (tanpa awalan `SB-`) dari dashboard Midtrans production. Detail tiap variabel ada di [Setup Midtrans](midtrans.md).

Daftarkan juga Payment Notification URL production (domain asli, bukan ngrok) di dashboard Midtrans.

## Checklist singkat

- [ ] `FRONTEND_URL` ke domain asli — menentukan link reset password **dan** redirect setelah pembayaran
- [ ] `MIDTRANS_ENVIRONMENT=production` + access key production
- [ ] Payment Notification URL terdaftar di dashboard Midtrans
- [ ] Cron `schedule:run` aktif
- [ ] Queue worker jalan dengan `--queue=notifications,default` di bawah process manager
- [ ] `config:cache`, `route:cache`, `view:cache`, `event:cache` dijalankan ([Command Reference](commands.md))
- [ ] `queue:restart` setiap kali deploy kode baru
