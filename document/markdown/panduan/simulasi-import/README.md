# Data simulasi import — untuk rekaman video panduan

File di folder ini adalah **template asli hasil unduh dari aplikasi** (bukan buatan tangan), diisi dataset besar yang meniru kondisi impor sungguhan: mayoritas baris valid + beberapa baris **sengaja error**, satu baris satu jenis pelanggaran, supaya demo layar preview validasi kelihatan realistis.

**Sudah diuji nyata lewat API** (upload preview, bukan cuma dilihat manual) — setiap baris error menghasilkan pesan yang sesuai rencana, tidak ada yang salah target atau salah pesan. Detail di bawah.

Login yang dipakai saat menyiapkan & menguji: `admin_darma_putra` (branch_id 3). Semua NIS contoh memakai awalan **900xxx** — dicek 0 bentrok dengan data asli manapun di database.

## Isi folder & hasil uji

| File | Baris valid | Baris error (1 baris = 1 aturan) | Hasil uji |
|---|---|---|---|
| `template_import_siswa_MI.xlsx` | 22 | 15: nama kosong, NIS bukan angka, NISN salah panjang, jenis kelamin salah, jenjang salah, format tanggal salah, agama tak dikenal, kelas tak ditemukan, kategori tak ditemukan, status tak dikenal, kelas_diterima salah, tahun_diterima bukan 4 digit, NIS duplikat dalam file, NISN duplikat dalam file, NIS+NISN sudah ada di database | ✅ 22 valid / 15 error — pesan error 100% sesuai rencana |
| `template_import_siswa_TK.xlsx` | 15 | 11: subset aturan yang sama (tanpa field khusus MI) | ✅ 15 valid / 11 error |
| `template_import_siswa_KB.xlsx` | 10 | 6: subset aturan dasar | ✅ 10 valid / 6 error |
| `template_import_tagihan.xlsx` | 58 | 6: NIS tak terdaftar, jenis tagihan tak ada di periode aktif, kombinasi NIS+jenis sudah ada di database, duplikat dalam file, NIS kosong, jenis tagihan kosong | ✅ lihat catatan bug di bawah — 6 baris errornya sendiri sudah dipastikan benar |

Cara uji: 47 siswa (baris valid MI+TK+KB) benar-benar di-*confirm* masuk database lewat API, dipakai untuk menguji validasi tagihan (yang memang mensyaratkan NIS sudah terdaftar), lalu semuanya **di-rollback** lagi sehingga file-file ini tetap "segar" — bisa langsung dipakai ulang saat rekam video tanpa NIS bentrok.

## ✅ Bug ditemukan saat testing — sudah diperbaiki

Template tagihan (`template_import_tagihan.xlsx`) punya 2 sheet: **Data Import** (yang diisi) dan **Referensi** (daftar nama jenis tagihan, untuk dropdown — dibuat otomatis oleh `TagihanImportTemplate.php`). Sebelumnya, saat file dua-sheet ini diupload apa adanya, `TagihanImportValidator` ikut membaca sheet **Referensi** sebagai baris data tagihan (18 baris "hantu" jadi error palsu "NIS wajib diisi"), karena class itu cuma `implements ToCollection, WithHeadingRow` tanpa pembatas sheet.

**Diperbaiki** di [backend/app/Imports/TagihanImportValidator.php](../../../../backend/app/Imports/TagihanImportValidator.php) — tambah `implements WithMultipleSheets` dengan `sheets()` mengembalikan `[0 => $this]`, jadi hanya sheet fisik pertama (Data Import) yang diparse. Diverifikasi ulang lewat upload API: total baris kebaca sekarang tepat 64 (dulu 82), tidak ada lagi error palsu dari sheet Referensi.

## Alur rekam yang disarankan

1. **Import Siswa (skenario sukses)** — pakai baris valid saja dari `template_import_siswa_MI.xlsx` (atau biarkan file apa adanya untuk lanjut ke poin 2).
2. **Import Siswa (skenario ada baris error)** — upload apa adanya (15 baris error tercampur) → tunjukkan preview all-or-nothing menandai tiap baris dengan pesan spesifik → perbaiki salah satu langsung di preview → upload ulang.
3. **Import Tagihan** — upload `template_import_tagihan.xlsx` **setelah** siswa MI/TK/KB di atas berhasil dikonfirmasi masuk (tagihan mensyaratkan NIS sudah terdaftar).
4. **Rollback** — buka **Riwayat Import** → tombol **Rollback** untuk mendemokan pembatalan import. (Ada 3 entri riwayat rolled_back bekas testing saya di sana — bisa dipakai langsung untuk demo tampilan riwayat, atau diabaikan.)

## Catatan

- Semua data sintetis (nama, alamat, orang tua semua rekaan, email pakai domain `simulasi-video.test`) — aman ditampilkan di video publik.
- Kalau butuh reset (mis. sudah kepakai take pertama dan sempat di-confirm beneran), hapus siswa NIS 900xxx via database, lalu jalankan ulang unduh template dari menu Import di aplikasi kalau perlu kolom kelas/jenis tagihan ter-refresh.
