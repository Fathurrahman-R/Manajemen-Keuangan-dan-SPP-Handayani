# Testing

Framework test berbeda per aplikasi, dan **setup database-nya juga berbeda** — ini sumber kebingungan paling umum.

| | backend | frontend-v2 |
|---|---|---|
| Framework | PHPUnit | Pest |
| Database test | MariaDB nyata, DB terpisah `handayani_testing` | SQLite `:memory:` |
| Perlu setup manual? | **Ya** (lihat di bawah) | Tidak |

## Setup database test (backend)

`backend/phpunit.xml` mengarahkan test ke `DB_DATABASE=handayani_testing` pada koneksi `mariadb`. Database ini **tidak dibuat otomatis** — kalau belum ada, seluruh test gagal dengan error koneksi atau `Table ... doesn't exist`.

Buat dan migrasikan sekali di awal:

```bash
# buat database kosong
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS handayani_testing"

# isi skemanya
cd backend
php artisan migrate:fresh --database=mariadb --env=testing --no-interaction
```

Dengan Docker:

```bash
docker compose exec mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS handayani_testing"
docker compose exec backend php artisan migrate:fresh --database=mariadb --env=testing --no-interaction
```

> [!WARNING]
> Kalau muncul error semacam `Table 'handayani_testing.model_has_roles' doesn't exist` **padahal** `php artisan migrate:status --env=testing` menampilkan semua migrasi sebagai *Ran*, berarti state database test tidak konsisten (baris di tabel `migrations` ada, tabelnya tidak). Perbaikannya: `migrate:fresh --env=testing` lagi. Ini pernah terjadi dan bikin ratusan test gagal tanpa sebab yang jelas dari kodenya.

`frontend-v2` tidak butuh langkah apa pun — SQLite in-memory dibuat ulang tiap run.

## Menjalankan test

```bash
# backend (PHPUnit)
cd backend
php artisan test
php artisan test --filter=SomeTest
vendor/bin/phpunit tests/Feature/SomeTest.php

# frontend-v2 (Pest)
cd frontend-v2
php artisan test
vendor/bin/pest tests/Unit/BrandingConfigTest.php
```

Dengan Docker, jalankan lewat container agar koneksi database benar (dari host, `vendor/bin/phpunit` gagal connect karena `DB_HOST` mengarah ke service Docker):

```bash
docker compose exec backend php artisan test
docker compose exec frontend php artisan test
```

## Sebagian test memang gagal — jangan panik

Suite backend saat ini **tidak hijau seluruhnya**. Ada sejumlah test yang sudah gagal sejak sebelum kamu clone, sebagian karena data seeder yang berubah, sebagian karena test lama yang belum disesuaikan.

Artinya: **jumlah gagal yang bukan nol bukan berarti kamu merusak sesuatu.** Yang perlu dipastikan adalah tidak ada kegagalan **baru**.

Cara membandingkan sebelum/sesudah perubahan:

```bash
# 1. simpan baseline SEBELUM mengubah kode
git stash push -u -m baseline
docker compose exec backend sh -c "php artisan test > /tmp/before.txt 2>&1"
docker compose exec backend grep -E "^\s+(FAILED|⨯)" /tmp/before.txt | sort > /tmp/before_names.txt

# 2. kembalikan perubahan, jalankan lagi
git stash pop
docker compose exec backend sh -c "php artisan test > /tmp/after.txt 2>&1"
docker compose exec backend grep -E "^\s+(FAILED|⨯)" /tmp/after.txt | sort > /tmp/after_names.txt

# 3. bandingkan NAMA test-nya, bukan cuma jumlahnya
docker compose exec backend comm -13 /tmp/before_names.txt /tmp/after_names.txt   # gagal baru → masalah
docker compose exec backend comm -23 /tmp/before_names.txt /tmp/after_names.txt   # jadi lulus → bagus
```

> [!TIP]
> Bandingkan **nama** test, bukan angka totalnya. Jumlah gagal bisa melonjak drastis hanya karena database test perlu di-`migrate:fresh`, padahal daftar nama yang gagal identik — artinya nol regresi.

Kalau perlu menyimpan output panjang, arahkan langsung ke file **di dalam container** (`sh -c "... > /tmp/x.txt 2>&1"`) seperti contoh di atas. Menangkap output panjang dari luar sering terpotong.

## Format kode

Wajib sebelum commit, di kedua aplikasi:

```bash
vendor/bin/pint          # perbaiki semua
vendor/bin/pint --dirty  # hanya file yang berubah
```
