# Deployment (production)

Perintah cache/optimize sebelum deploy ada di [Command Reference](commands.md).

## Cron (scheduler)

Satu baris di crontab server. Jalan tiap menit, Laravel yang nentuin job mana yang benar-benar dieksekusi:

```bash
* * * * * cd /path/ke/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Queue worker

Jalanin `queue:work --queue=notifications,default` di bawah process manager (Supervisor/systemd). Jangan `nohup` manual, worker perlu auto-restart kalau crash atau habis deploy.

Habis deploy, jalanin `php artisan queue:restart`. Tanpa itu worker masih pakai kode lama sampai prosesnya mati sendiri.

## Kredensial Midtrans

Set `MIDTRANS_ENVIRONMENT=production` dan pakai access key production (tanpa awalan `SB-`) dari dashboard production. Penjelasan tiap variabel ada di [Setup Midtrans](midtrans.md).

Daftarkan Payment Notification URL production di dashboard, pakai domain asli, bukan ngrok.

## Checklist

- [ ] `FRONTEND_URL` ke domain asli. Ini yang nentuin link reset password dan redirect habis bayar
- [ ] `MIDTRANS_ENVIRONMENT=production` + access key production
- [ ] Payment Notification URL terdaftar di dashboard Midtrans
- [ ] Cron `schedule:run` aktif
- [ ] Queue worker jalan dengan `--queue=notifications,default` di bawah process manager
- [ ] `config:cache`, `route:cache`, `view:cache`, `event:cache` sudah dijalankan
- [ ] `queue:restart` tiap deploy kode baru
