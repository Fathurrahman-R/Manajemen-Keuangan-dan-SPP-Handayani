# Hal yang perlu diperbaiki
Perbaiki fitur di frontend dan backend bila perlu
## Halaman Tagihan
```
- [x] Filter jenjang: karena halaman tagihan sudah dipisah perjenjang, maka filter ini sudah tidak dibutuhkan. Tambahkan filter kelas saja
- [x] Keanehan pada tombol Tambah tagihan: saat pertama kali ditekan, tombol tidak menampilkan modal, setelah beberapa percobaan modal tampil, tapi selang beberapa saat tombol bayar untuk setiap card tagihan tiba tiba muncul. Sedangkan ketika mencentang checkbox tagihan tombol bayar tidak mau muncul.
- [x] Hapus tagihan: Saat pengguna menekan tombol hapus tagihan harusnya muncul modal konfirmasi bawaan filament.
- [x] Data yang tampil belum sesuai: Meskipun halaman tagihan sudah dipisah perjenjang, data yang ditampilkan di masing-masing halaman perjenjang masih data semua tagihan siswa dari berbagai jenjang.
- [x] Modal tambah tagihan: ketika pengguna akan menambahkan tagihan, tidak perlu lagi dropdown untuk memilih jenjang. Tambahkan dropdown untuk memilih periode ajaran saja. Perbaiki hal ini di frontend dan backend
- [x] Sertakan nama jenjang di label masing-masing halaman tagihan
```

### Ada beberapa issue baru dari perbaikan di atas
```
- [x] Filter kelas masih belum bekerja
- [x] Saat filter kelas dipilih, tombol bayar tiba tiba muncul
- [x] Mencentang checkbox tagihan tidak memunculkan tombol bayar dan jumlah total yang harus dibayarkan. Modal bayar juga tidak muncul ketika menekan tombol bayar.
- [x] Checkbox "pilih semua" tidak mencentang semua tagihan milik seorang siswa.
```

## Redesign card halaman tagihan dan pembayaran

```
Redesign komponen UI daftar tagihan siswa berikut agar lebih ergonomis di mobile (lebar ≤ 390px). Permasalahan saat ini:
- [x] Nama item, badge status, dan nominal harga sejajar horizontal — di layar sempit semuanya saling tumpang tindih
- [x] Tombol aksi (bayar & hapus) berupa ikon kecil di ujung kanan — sulit di-tap di mobile
- [x] Total sisa dan info profil berada di satu baris — terlalu padat untuk header mobile
Terapkan perubahan layout berikut:
- [x] Header: pisahkan info profil (avatar + nama + kelas) di baris atas, dan kotak "Total Sisa" menjadi blok penuh di baris bawahnya dengan background merah muda
- [x] Item tagihan: susun secara vertikal — baris 1: nama item + nominal (justify space-between), baris 2: tanggal + badge status, baris 3: tombol aksi full-width
- [x] Tombol aksi: ganti ikon kecil dengan dua tombol teks ("Bayar" dan "Hapus") berukuran tap-target minimal 44px, disusun row di bagian bawah setiap item
- [x] Badge status: pindahkan ke baris kedua di bawah nama, tidak sejajar horizontal dengan nominal
- [x] Item "belum lunas": tampilkan nominal sisa secara jelas di baris 1, dan tambahkan teks kecil "dari Rp. xxx" di bawahnya
- [x] Pertahankan checkbox "Pilih Semua" dan checkbox per-item, pastikan ukuran tap area ≥ 44×44px
Gunakan border-left merah sebagai aksen visual per item (pertahankan dari desain lama)
```

## Loading wheel atau skeleton
```
- [x] Tambahkan tampilan loading berupa loading wheel atau skeleton ketika sedang fetch data di semua halaman.
```

## Halaman Settings
```
- [x] Upload logo berhasil, preview tampil, tapi ketika save logo tidak berubah
```

## Halaman data siswa
```
- [x] Tombol update siswa error
```

```
- [x] Breadcrumb saat lihat detail siswa error 404
- [x] Berikan tampilan loading spinner saat berpindah page pagination table (untuk semua halaman yang memiliki pagination di table)
- [x] Berikan tampilan loading spinner saat ubah jumlah record per page pagination table (untuk semua halaman yang memiliki pagination di table)
- [x] Berikan auto scroll ke atas saat pindah page pagination table
```

## Halaman kenaikan kelas
```
- [x] Ganti konfirmasi "Proses kenaikan kelas" dari alert ke modal konfirmasi bawaan filament
- [x] Ketika menaikkan kelas atau meluluskan siswa, notifikasi berhasil malah menunjukkan Lulus: 0 siswa
- [x] Riwayat proses tercatat, tapi data "Kelas asal" kosong
- [x] Tombol detail riwayat proses tidak berfungsi, tidak menampilkan sebuah modal maupun halaman detail
- [x] Terapkan spinner loading saat: ganti tab jenjang daftar siswa, memilih kelas di daftar kelas.
```

## Bayar online midtrans
```
- [x] Ganti custom modal menjadi filament modal
- [x] Saat klik konfirmasi pembayaran muncul notifikasi "pembayaran sudah lunas", tidak redirect ke halaman snap
```

## Portal siswa
```
- [x] Saya tidak menemukan portal status pembayaran dan portal riwayat pembayaran di navigasi akun milik siswa
```

## Admin
```
- [x] Error saat klik halaman transaksi midtrans
```

## Gagal menampilkan snap
```
- [x] Saat konfirmasi pembayaran masih muncul notifikasi "Layanan pembayaran online sedang tidak tersedia" dan tidak redirect ke snap
```

## Login siswa dan Portal
```
- [x] Kenapa saat login menggunakan akun siswa tidak langsung masuk ke /portal/?, saat ini ketika siswa login masuk ke /tagihan-siswa. Atasi duplikat ini, gunakan /portal/ saja 
```

## Riwayat Pembayaran portal siswa
```
- [x] Halaman riwayat pembayaran yang dilakukan siswa melalui midtrans tidak tampil atau tidak tercatat
```

## Tagihan portal siswa
```
- [x] Ketika selesai membayar melalui midtrans status tagihan tidak menjadi pending melainkan masih Belum lunas/Belum dibayar dan tombol Bayar online masih muncul
```

## Halaman Transaksi Midtrans admin
```
- [x] Tombol sync di detail transaksi gagal hit API dan muncul notifikasi merah
```

## Halaman Card tagihan portal siswa
```
- [x] Implementasikan bayar batch di portal siswa
- [x] Sesuaikan tampilan card agar terlihat sama seperti card tagihan di admin agar mobile friendly
```

## Kas Harian dan Rekap Bulanan
```
- [x] Buat Halaman detail per record yang menampilkan tabel detail pemasukan dan pengeluaran
- [x] Ketika Kas harian atau Rekap Bulanan di export detail transaksi juga disertakan sebagai Keterangan untuk kas harian dan sebagai catatan untuk rekap bulanan
- [x] Pemasukan Yang disertakan dalam detail hanya data NIS/NISN, nama, nama tagihan dan jumlah transaksi
- [x] Pengeluaran yang disertakan dalam detail nama pengeluaran, jumlah pengeluaran, pengaju, dan penyetuju
```

## Hasil Pencarian fitur duplikat
```
- [x] 1. Hapus Tagihan dan Pembayaran versi tabel
- [x] 2. Hapus Dashboard page (admin panel)
- [x] 3. Gabungkan
- [x] 4. DRY ke trait/service
- [x] 5. Rencana saya aplikasi ini akan digunakan dari 0 kembali, jadi tidak akan ada migrasi
- [x] 7. Setau saya User.php dan sejenisnya tidak punya db lokal
- [x] Tambahan: Permission yang di list di modal saat edit/create role tidak update dengan permission baru yang ada di backend, kenapa tidak ambil data permission dari backend saja? 
```

## Halaman Kenaikan kelas
```
- [x] Ganti modal detail riwayat proses kenaikan kelas dengan menggunakan filament modal
- [x] Ganti tabel yang ada di modal detail riwayat proses kenaikan kelas dengan menggunakan filament table
```

# Checkpoint: Perbaikan bug dan layout

## Modal Tambah tagihan
```
- [x] Dropdown Jenis tagihan tidak menampilkan jenis tagihan berdasarkan periode ajaran yang dipilih
```

## Fitur Tagihan (admin)
```
- [x] Tambahkan fitur export pdf untuk laporan Tagihan, pengguna dapat memilih tagihan dengan status apa saja yang akan di export, dan sertakan nama cabang dalam laporan. Gunakan format pdf seperti kas harian/rekap bulanan
- [x] Tambahkan filter jatuh tempo
```

## Fitur Pembayaran (admin)
```
- [x] Tambahkan sortir (ex. pembayaran terbaru)
- [x] Tambahkan filter kelas
- [x] Tambahkan filter metode pembayaran
```

## Untuk semua data yang terkait pada periode ajaran
```
- [x] Buatkan sebuah dropdown untuk memilih periode ajaran di semua halaman yang datanya terkait dengan periode ajaran agar tidak perlu mengubah periode ajaran aktif untuk melihat data historis
```

## Halaman data siswa
```
- [x] Pindahkan filter kelas dari button header action menjadi didalam filter tabel seperti filter status
- [x] Tambahkan filter jenis kelamin dan agama
```

## Dashboard admin
```
- [x] Data widget stat masih belum mengikuti periode yang dipilih
- [x] Tambahkan widget stat (filament) total tagihan dari semua periode, total pemasukan dari semua periode, total pengeluaran dari semua periode.
- [x] Tambahkan widget stat (filament) total pemasukan periode yang dipilih, total pengeluaran periode yang dipilih.
- [x] Sesuaikan layout chart, pembayaran per Bulan dan pemasukan vs pengeluaran gunakan lebar 2 col, tunggakan perjenjang dan status tagihan tetap 1 col tapi buat berdampingan.
- [x] Terapkan spinner loading saat ubah periode
```

## Dashboard portal (beranda)
```
- [x] Ganti stat menggunakan filament widget stat, ganti tabel menggunakan filament table
```

## Halaman manajemen akun siswa, tab sudah terdaftar
```
- [x] Sesuaikan Modal lihat kredensial siswa, gunakan filament modal, dan filament table.
- [x] Fitur Cetak pdf error:
# Symfony\Component\Routing\Exception\RouteNotFoundException - Internal Server Error

Route [login] not defined.

PHP 8.4.18
Laravel 12.37.0
127.0.0.1:8080

## Stack Trace

0 - vendor\laravel\framework\src\Illuminate\Routing\UrlGenerator.php:526
1 - vendor\laravel\framework\src\Illuminate\Foundation\helpers.php:871
2 - vendor\laravel\framework\src\Illuminate\Foundation\Configuration\ApplicationBuilder.php:278
3 - vendor\laravel\framework\src\Illuminate\Auth\Middleware\Authenticate.php:117
4 - vendor\laravel\framework\src\Illuminate\Auth\Middleware\Authenticate.php:104
5 - vendor\laravel\framework\src\Illuminate\Auth\Middleware\Authenticate.php:87
6 - vendor\laravel\framework\src\Illuminate\Auth\Middleware\Authenticate.php:61
7 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
8 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:137
9 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:821
10 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:800
11 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:764
12 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:753
13 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:200
14 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:180
15 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php:21
16 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull.php:31
17 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
18 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php:21
19 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TrimStrings.php:51
20 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
21 - vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePostSize.php:27
22 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
23 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance.php:109
24 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
25 - vendor\laravel\framework\src\Illuminate\Http\Middleware\HandleCors.php:61
26 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
27 - vendor\laravel\framework\src\Illuminate\Http\Middleware\TrustProxies.php:58
28 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
29 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks.php:22
30 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
31 - vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePathEncoding.php:26
32 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
33 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:137
34 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:175
35 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:144
36 - vendor\laravel\framework\src\Illuminate\Foundation\Application.php:1220
37 - public\index.php:20
38 - vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php:23

## Request

GET /api/akun-siswa/credentials-pdf

## Headers

* **host**: 127.0.0.1:8080
* **connection**: keep-alive
* **sec-ch-ua**: "Brave";v="149", "Chromium";v="149", "Not)A;Brand";v="24"
* **sec-ch-ua-mobile**: ?0
* **sec-ch-ua-platform**: "Windows"
* **upgrade-insecure-requests**: 1
* **user-agent**: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36
* **accept**: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8
* **sec-gpc**: 1
* **accept-language**: en-US,en;q=0.8
* **sec-fetch-site**: same-site
* **sec-fetch-mode**: navigate
* **sec-fetch-user**: ?1
* **sec-fetch-dest**: document
* **referer**: http://127.0.0.1:8000/
* **accept-encoding**: gzip, deflate, br, zstd
* **cookie**: XSRF-TOKEN=eyJpdiI6Ik1EeDhiODJTRFBvbkJDeG4vVUhGY3c9PSIsInZhbHVlIjoidmpmd2tUOTdMbVpvZ04rSkFCbHo0ck9tMlVTTGplNmwxd2ZLUDlhbmNISnpSbU5rOEFQQWdIWXpadklmR0xEeW5uNGtXRnMyM1cwM244QTBGYjlVTE9aMDBraDFZZkJkUnBsdzNTamVRTSs1MWtKUEtORUcvZ0ZHYVpsZDFwVTciLCJtYWMiOiIyOGZmMTIzYzkzY2JiNTVhZWNkMjVlNjUxZWJlOGRhNjdmNWRhOTExNjI4NThlNzBmNmNiMzEwZWZkMzYwODNiIiwidGFnIjoiIn0%3D; handayani-session=eyJpdiI6IkxZaW9IUXpFTnUzNWN4clZvMTVsTUE9PSIsInZhbHVlIjoidVZUSXYvY0tiTkQ5cklqY3RCYVJHejZsMGdta3RMYlRMcnpLa1gwSC95TWdCSjZ2VnFIeEkwZC83RU1BWVJHYXlCc2ticG13TFFtemxhWE1WMEFuU2dIUWpWVDk1TWlWT3JpbW1Yb3NGcDZWVDBuSUhFenVUNFMrTnd6b21jUjIiLCJtYWMiOiIwN2I5NmI1NThkZmJhOWVhM2M5MmIwMmQ5MzU3NzVjNGNkZTQwZTZjNDk4MWJlNDAxYjczN2E3NmVkMzhkOTAzIiwidGFnIjoiIn0%3D

## Route Context

controller: App\Http\Controllers\AkunSiswaController@credentialsPdf
middleware: api, auth:sanctum, deny_siswa, permission:manage-akun-siswa

## Route Parameters

No route parameter data available.

## Database Queries

No database queries detected.
```

## Manajemen role
```
- [x] Kelompokkan lagi list permission di modal create/update dengan memisahkan permission untuk admin/karyawan dan permission untuk siswa agar pengguna mudah menentukan permission.
```

## Navigasi (admin)
```
- [x] Urutkan ulang navigasi sidebar, Dashboard berada paling atas, admin akan landing di dashboard saat login.
```

## Navigasi (admin dan portal)
```
- [x] Ada beberapa navigasi yang terlihat tidak aktif (ketika di klik), dan saat memilih navigasi yang urutannya dibawah, sidebar seperti kembali scroll ke atas (ini keluhan).
```

# Checkpoint: Bug dan sebelum credit habis

## Dashboard admin
```
- [x] pisahkan widget stat menggunakan section: semua periode dan periode ini, tempatkan stat semua periode di atas dropdown periode
- [x] Tambahkan stat jumlah siswa yang memiliki tagihan (berdasarkan periode)
- [x] Sesuaikan height widget chart Pembayaran per Bulan dan Pemasukan vs Pengeluaran
- [x] Table Tagihan jatuh tempo 7 hari menunjukkan data kosong
```

## Pengeluaran
```
- [x] apakah pengeluaran tidak terikat dengan periode ajaran? kenapa tidak ada dropdown periode di halaman pengeluaran? jika belum terikat, buat pengeluaran terikat ke periode
```

## Notifikasi jatuh tempo
```
- [x] Pastikan notifikasi email jatuh tempo menggunakan scheduler bawaan laravel
```

## Notifikasi kwitansi pembayaran
```
- [x] Kwitansi yang dikirimkan adalah kwitansi yang sudah saya buat sebelumnya (pdf)
```

## Dropdown periode ajaran
```
- [x] Untuk semua dropdown periode ajaran selain dashboard, tambah satu item "Semua periode" dan jadikan default
```

## Terapkan best practice spatie/laravel-permission
```
- [x] Terapkan best practice dimana khusus superadmin
```

## Manajemen role
```
- [x] Tombol aksi hapus role menghilang
- [x] Saat test menambahkan satu role muncul notifikasi role gagal ditambahkan, tapi role tercatat di record tanpa permission
```

## Manajemen User
```
- [x] Sesuaikan create user dengan menambahkan field email
- [x] Tampilkan lebih banyak kolom di tabel
- [x] Buat aksi untuk aktif dan nonaktifkan akun
```

## Permission frontend & backend
```
- [x] Gunakan bahasa indonesia
- [x] Definisikan permission dengan modular (aksi+resource) agar konsisten dan maintainable
- [x] Setiap aksi atau halaman memiliki permissionnya sendiri
```

## Konsistensi Saldo
```
- [x] Cegah request pengeluaran yang akan menyebabkan saldo mines
```

## Beranda Portal siswa
```
- [x] Tambahkan dropdown periode seperti dashboard admin
- [x] Tambah item dropdown untuk lihat data semua periode
```

## Riwayat pembayaran Portal siswa
```
- [x] Jika pembayaran melalui midtrans sedang di proses, tetap tampilkan di riwayat dengan status pembayarannya
- [x] Jika pembayaran sudah selesai tambahkan aksi download kwitansi
```

## Pembayaran midtrans (portal)
```
- [x] Sesuaikan fee berdasarkan dokumentasi midtrans (ada yang menggunakan persentase dan ada yang nilai tetap)
- [x] Terapkan debounce saat input jumlah bayar
```

## Penyesuaian
```
- [x] Ada beberapa form input/update yang masih belum sesuai dengan kolom databasenya
```

## Dropdown periode (lanjutan)
```
- [x] Dropdown periode di dashboard admin tidak muncul (slot blade yang salah)
- [x] Pilihan "Semua Periode" di halaman Tagihan, Pembayaran, Jenis Tagihan, dan Pengeluaran Request belum benar-benar menampilkan data semua periode (backend masih fallback ke periode aktif)
```
