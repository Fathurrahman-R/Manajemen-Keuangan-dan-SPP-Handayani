# Testing

Framework dan setup database beda per aplikasi. Ini yang paling sering bikin bingung di awal.

| | backend | frontend-v2 |
|---|---|---|
| Framework | PHPUnit | Pest |
| Database test | MariaDB, DB terpisah `handayani_testing` | SQLite `:memory:` |
| Perlu setup manual | Ya | Tidak |

## Setup database test (backend)

`backend/phpunit.xml` nunjuk `DB_DATABASE=handayani_testing` di koneksi `mariadb`. Database ini tidak dibuat otomatis. Kalau belum ada, semua test gagal dengan error koneksi atau `Table ... doesn't exist`.

Bikin databasenya sekali di awal:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS handayani_testing"

# Docker
docker compose exec mysql mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS handayani_testing"
```

Skemanya diisi lewat `RefreshDatabase` waktu test jalan, jadi biasanya tidak perlu migrasi manual.

**Jangan pakai `--env=testing` buat migrasi.** Repo ini tidak punya `backend/.env.testing`, jadi Laravel jatuh balik ke `.env` biasa yang `DB_DATABASE=handayani`. Artinya:

```bash
php artisan migrate:fresh --env=testing    # ini MENGHAPUS database dev, bukan database test
```

Perintah itu pernah dijalankan di sini dan menghabisi seluruh isi DB dev. `phpunit.xml` mengarahkan test ke `handayani_testing` lewat env var proses test, mekanismenya beda dari flag `--env`.

Kalau butuh reset skema DB test secara manual, sebut databasenya eksplisit:

```bash
docker compose exec -e DB_DATABASE=handayani_testing backend php artisan migrate:fresh --force
```

Cek dulu targetnya bener sebelum jalan:

```bash
docker compose exec -e DB_DATABASE=handayani_testing backend \
  php artisan tinker --execute="echo config('database.connections.mariadb.database');"
```

`frontend-v2` tidak butuh setup apa-apa, SQLite in-memory dibikin ulang tiap run.

## Menjalankan test

```bash
# backend
cd backend
php artisan test
php artisan test --filter=SomeTest
vendor/bin/phpunit tests/Feature/SomeTest.php

# frontend-v2
cd frontend-v2
php artisan test
vendor/bin/pest tests/Unit/BrandingConfigTest.php
```

Pakai Docker, jalanin dari dalam container. Dari host `vendor/bin/phpunit` gagal connect karena `DB_HOST` nunjuk service Docker:

```bash
docker compose exec backend php artisan test
docker compose exec frontend php artisan test
```

## Suite backend belum hijau

Ada sekitar 220 test yang sudah gagal sebelum kamu clone. Sebagian karena data seeder berubah, sebagian test lama yang belum disesuaikan.

Jadi jumlah gagal yang bukan nol itu wajar. Yang penting: tidak ada kegagalan **baru**.

Bandingkan sebelum/sesudah:

```bash
# baseline, sebelum ubah kode
git stash push -u -m baseline
docker compose exec backend sh -c "php artisan test > /tmp/before.txt 2>&1"

# balikin perubahan, jalanin lagi
git stash pop
docker compose exec backend sh -c "php artisan test > /tmp/after.txt 2>&1"

# ambil nama test yang gagal, buang durasinya, bandingkan
docker compose exec backend sh -c "
  for f in before after; do
    grep -E '^\s+(FAILED|⨯)' /tmp/\$f.txt \
      | sed -E 's/[[:space:]]+[0-9]+\.[0-9]+s[[:space:]]*\$//; s/^[[:space:]]+//; s/[[:space:]]+\$//' \
      | sort -u > /tmp/\${f}_n.txt
  done
  echo 'regresi:'; comm -13 /tmp/before_n.txt /tmp/after_n.txt
  echo 'jadi lulus:'; comm -23 /tmp/before_n.txt /tmp/after_n.txt
"
```

Bandingkan **nama** testnya, bukan angka total. Angka gagal bisa lompat drastis gara-gara kondisi environment, bukan gara-gara kodemu. Pernah kejadian satu run nunjukin 354 gagal (129 di antaranya `Base table or view not found`), run berikutnya 221 gagal, padahal daftar nama yang gagal identik. Nol regresi, cuma noise.

Durasi wajib dibuang waktu normalisasi. Kalau tidak, semua nama keliatan "berubah" cuma karena selisih milidetik.

Output panjang arahkan ke file di dalam container (`sh -c "... > /tmp/x.txt 2>&1"`) seperti contoh di atas. Ditangkap dari luar sering kepotong.

## Format kode

Wajib sebelum commit, di kedua aplikasi:

```bash
vendor/bin/pint          # semua file
vendor/bin/pint --dirty  # cuma yang berubah
```
