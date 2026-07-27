# Revisi Teks Sub-bab 3.7 (Rencana Pengujian) & 4.3 (Pengujian Sistem)

> **Docx TIDAK disentuh.** File ini daftar instruksi manual: apa yang dihapus/diubah/ditambah + isi tabel siap salin. Terapkan sendiri di Word.
>
> Sumber: ekstraksi read-only `…-Laporan Tugas Akhir.docx` + `document/markdown/test-case-blackbox.md` (103 test case, 8 kelompok).
>
> **Prinsip seleksi (NECESSARY ONLY):** dari 103 test case, diambil **28** yang menjadi bukti langsung bagi tiga jenis pengujian yang dideklarasikan 3.7 dari proposal — *Functional*, *Validation*, *Integration* — dan mewakili seluruh fitur inti. Sisanya (varian tambahan, boundary detail, regression) tetap ada di dokumen pengujian terpisah, tidak semuanya perlu masuk laporan.
>
> **Pembagian fungsi tabel:**
> - **Tabel 3.40 (3.7)** = *rencana* → skenario + hasil yang diharapkan, **tanpa** kolom hasil aktual/status (karena bab perancangan belum dieksekusi).
> - **Tabel 4.2 (4.3, baru)** = *pelaksanaan* → skenario yang sama **+ Hasil Aktual + Status**.
> - **Tabel 4.1 (4.3, sudah ada)** = rekap keseluruhan 103 test case per kelompok fitur (tetap dipertahankan, hanya diperbaiki aritmetikanya).

---

## ⚠️ TEMUAN KRITIS 1 — referensi tabel di 3.7 salah

Caption pakai SEQ field (auto-nomor), kalimat pengantar diketik manual sehingga tertinggal.

**Cari:** `Skenario pengujian dapat dilihat pada Tabel 3.2.`
**Ganti jadi:** `Skenario pengujian dapat dilihat pada Tabel 3.40.`

---

## ⚠️ TEMUAN KRITIS 2 — Tabel 4.1 (rekap) tidak konsisten aritmetika

Tabel rekap tidak punya kolom Skipped, sehingga 1 skenario SKIPPED tidak terwadahi dan baris tidak menjumlah:

| Baris | Isi sekarang | Masalah |
|---|---|---|
| Kelompok 2 (Portal Siswa) | 10 TC, 9 Pass, 0 Fail, 0 Blocked, **100,00%** | 9+0+0=9, bukan 10. Persentase seharusnya **90,00%** |
| Total | 103 TC, 102 Pass, 0 Fail, 0 Blocked, **100,00%** | 102+0+0=102, bukan 103. Persentase seharusnya **99,03%** |

Perbaikan menyusul di bagian 4.3.

---

# SUB-BAB 3.7 — RENCANA PENGUJIAN

### ✏️ UBAH — kalimat pengantar sebelum tabel (opsional, agar tiga jenis pengujian tertelusur)

**Cari:** `Skenario pengujian dapat dilihat pada Tabel 3.40.` *(setelah perbaikan Temuan Kritis 1)*

**Ganti jadi:**
> Skenario pengujian disusun agar mewakili ketiga jenis pengujian yang telah diuraikan, yaitu *functional testing*, *validation testing*, dan *integration testing*, dengan cakupan menyeluruh terhadap fitur inti sistem. Daftar skenario beserta hasil yang diharapkan dapat dilihat pada Tabel 3.40.

---

### 🗑️ HAPUS + GANTI TOTAL — isi Tabel 3.40

Tabel skenario lama (15 fitur, kolom: No | Fitur | Skenario | Input | Output yang diharapkan | Hasil) **diganti seluruhnya** dengan tabel di bawah. Perhatikan: kolom **"Hasil" dihapus** dari Tabel 3.40 — bab perancangan hanya memuat rencana, hasil pelaksanaan pindah ke Tabel 4.2.

**Kolom baru Tabel 3.40:** No | Kode | Fitur yang Diuji | Jenis Pengujian | Skenario Pengujian | Hasil yang Diharapkan

| No | Kode | Fitur yang Diuji | Jenis Pengujian | Skenario Pengujian | Hasil yang Diharapkan |
|---|---|---|---|---|---|
| 1 | TC-01 | Pembayaran Daring | *Functional* | Siswa memulai pembayaran daring atas tagihan miliknya | Halaman pembayaran terbuka dengan nominal tagihan ditambah biaya admin, dan transaksi tercatat berstatus menunggu |
| 2 | TC-02 | Pembayaran Daring | *Integration* | Sistem menerima konfirmasi pembayaran berhasil dari *payment gateway* | Status transaksi berubah menjadi berhasil, tagihan menjadi lunas, dan pembayaran tercatat secara otomatis |
| 3 | TC-03 | Pembayaran Daring | *Functional* | Siswa membayar beberapa tagihan sekaligus dalam satu transaksi | Seluruh tagihan yang dipilih berubah menjadi lunas dan masing-masing tercatat pembayarannya |
| 4 | TC-04 | Pembayaran Daring | *Validation* | Siswa mencoba membayar dengan nominal di bawah batas minimum | Sistem menolak dan menampilkan pesan batas nominal minimum, tanpa membuat transaksi |
| 5 | TC-05 | Pembayaran Daring | *Integration* | Sistem menerima konfirmasi pembayaran dengan tanda tangan digital tidak sah | Konfirmasi ditolak dan status transaksi tidak berubah |
| 6 | TC-06 | Pembayaran Daring | *Integration* | Sistem menerima konfirmasi ulang untuk transaksi yang statusnya sudah final | Konfirmasi ditolak agar status final tidak berubah, dan kejadian tetap dicatat |
| 7 | TC-07 | Pengaturan Hak Akses | *Functional* | Pengguna tanpa wewenang mencoba membuka halaman pengaturan hak akses | Akses ditolak dan menu tidak ditampilkan pada navigasi |
| 8 | TC-08 | Persetujuan Pengeluaran | *Functional* | Mengirim pengajuan pengeluaran untuk disetujui | Status berubah menjadi menunggu persetujuan dan notifikasi terkirim ke penyetuju |
| 9 | TC-09 | Persetujuan Pengeluaran | *Functional* | Menyetujui pengajuan yang berstatus menunggu persetujuan | Status berubah menjadi disetujui dan notifikasi terkirim ke pengaju |
| 10 | TC-10 | Persetujuan Pengeluaran | *Functional* | Menolak pengajuan disertai alasan penolakan | Status berubah menjadi ditolak dan alasan terkirim ke pengaju |
| 11 | TC-11 | Persetujuan Pengeluaran | *Validation* | Menolak pengajuan tanpa mengisi alasan | Sistem menolak proses dan mewajibkan alasan penolakan diisi |
| 12 | TC-12 | Persetujuan Pengeluaran | *Validation* | Mengirim pengajuan dengan nominal melebihi saldo cabang yang tersedia | Pengajuan ditolak dengan pesan saldo tidak mencukupi |
| 13 | TC-13 | Persetujuan Pengeluaran | *Functional* | Pengajuan bernominal kecil pada cabang yang mengaktifkan persetujuan otomatis | Pengajuan langsung disetujui tanpa menunggu persetujuan manual |
| 14 | TC-14 | Notifikasi | *Integration* | Pengiriman notifikasi surel pada setiap perubahan status pengajuan | Isi dan penerima surel sesuai jenis kejadian dan peran penerima |
| 15 | TC-15 | Notifikasi | *Integration* | Pengguna yang menonaktifkan notifikasi tidak menerima surel | Surel tidak dikirim kepada pengguna yang telah memilih tidak menerima notifikasi |
| 16 | TC-16 | Kenaikan Kelas | *Functional* | Menjalankan kenaikan kelas satu rombongan belajar ke periode berikutnya | Siswa berpindah ke kelas berikutnya dan riwayat penempatan kelas tercatat |
| 17 | TC-17 | Kenaikan Kelas | *Functional* | Meluluskan siswa yang berada pada kelas tertinggi | Siswa berstatus lulus dan tidak lagi muncul pada kelas aktif periode baru |
| 18 | TC-18 | Impor Data | *Functional* | Mengimpor data siswa dari berkas yang valid | Data siswa berhasil ditambahkan dan riwayat impor tercatat |
| 19 | TC-19 | Impor Data | *Validation* | Mengimpor data siswa dengan nomor induk yang sudah terdaftar | Baris duplikat ditolak, baris valid tetap diproses, dan jumlah sukses/gagal tercatat |
| 20 | TC-20 | Impor Data | *Validation* | Mengimpor berkas dengan kolom wajib yang dikosongkan | Baris bermasalah ditolak disertai keterangan, baris lain tetap diproses |
| 21 | TC-21 | Impor Data | *Validation* | Mengunggah berkas dengan format yang tidak didukung | Sistem menolak unggahan dengan pesan format tidak valid tanpa gangguan |
| 22 | TC-22 | Ekspor Data | *Functional* | Mengekspor data siswa ke berkas | Berkas terunduh berisi data siswa dan riwayat ekspor tercatat |
| 23 | TC-23 | Portal Siswa | *Functional* | Siswa melihat daftar tagihan miliknya | Hanya tagihan milik siswa yang login yang ditampilkan sesuai statusnya |
| 24 | TC-24 | Dashboard | *Functional* | Menampilkan data keuangan pada dashboard sesuai cabang pengguna | Seluruh ringkasan menampilkan data cabang yang login tanpa menampilkan data cabang lain |
| 25 | TC-25 | Verifikasi Email | *Functional* | Memverifikasi alamat surel menggunakan kode konfirmasi | Kode dikirim ke surel; setelah kode benar dimasukkan status menjadi terverifikasi, kode salah atau kedaluwarsa ditolak |
| 26 | TC-26 | Keamanan Akun | *Functional* | Menonaktifkan akun pengguna yang tidak lagi berwenang | Seluruh sesi pengguna terputus dan percobaan login berikutnya ditolak dengan pesan akun nonaktif |
| 27 | TC-27 | Keamanan Akun | *Functional* | Pengguna mengajukan pemulihan kata sandi secara mandiri | Tautan pemulihan dikirim ke surel dengan respons seragam, dan kata sandi dapat diatur ulang |
| 28 | TC-28 | Portal Siswa | *Integration* | Siswa mencoba mengakses tagihan siswa lain melalui manipulasi alamat tautan | Akses ditolak; data milik siswa lain tidak dapat diakses |

> Catatan kode: kode TC-01..TC-28 dipakai agar Tabel 3.40 dan Tabel 4.2 saling merujuk. Boleh disesuaikan dengan format penomoran yang kamu pakai.

---

### ➕ TAMBAH — paragraf penutup 3.7 (setelah tabel)

> Skenario pada Tabel 3.40 merupakan skenario inti yang mewakili ketiga jenis pengujian. Pada pelaksanaannya, setiap skenario dijabarkan lebih lanjut menjadi kasus uji yang lebih rinci, termasuk kasus uji negatif dan kasus uji nilai batas, sehingga secara keseluruhan terkumpul 103 kasus uji yang hasilnya direkapitulasi pada BAB IV.

*Alasan: menjembatani 28 skenario inti di BAB III dengan 103 kasus uji terinci yang direkap di BAB IV, agar angka 103 tidak muncul tanpa dasar.*

---

# SUB-BAB 4.3 — PENGUJIAN SISTEM

### ✏️ UBAH — kalimat pengantar 4.3

**Cari:** `Pengujian sistem dilakukan menggunakan metode Black Box Testing secara manual terhadap seluruh skenario yang telah disusun pada BAB III, dilengkapi dengan pengujian otomatis sebagai pelengkap. Rekapitulasi hasil pengujian per kelompok fitur disajikan pada Tabel 4.1.`

**Ganti jadi:**
> Pengujian sistem dilakukan menggunakan metode *Black Box Testing* secara manual terhadap seluruh skenario yang telah disusun pada BAB III, dilengkapi dengan pengujian otomatis sebagai pelengkap. Setiap skenario pada Tabel 3.40 dijabarkan menjadi kasus uji yang lebih rinci sehingga secara keseluruhan terkumpul 103 kasus uji yang mencakup ketiga jenis pengujian yang direncanakan. Rekapitulasi hasil pengujian per kelompok fitur disajikan pada Tabel 4.1, sedangkan rincian pelaksanaan tiap skenario inti beserta hasil aktualnya disajikan pada Tabel 4.2.

---

### ✏️ UBAH — struktur & isi Tabel 4.1 (rekap, perbaikan aritmetika)

**Header sekarang:** `Kelompok Fitur | Jumlah TC | Pass | Fail | Blocked | % Pass`
**Ganti jadi (kolom Blocked → Skipped):** `Kelompok Fitur | Jumlah TC | Pass | Fail | Skipped | % Pass`

**Isi yang benar** (perubahan hanya baris Kelompok 2 dan Total, ditandai tebal):

| Kelompok Fitur | Jumlah TC | Pass | Fail | Skipped | % Pass |
|---|---|---|---|---|---|
| 1. Pembayaran Daring | 15 | 15 | 0 | 0 | 100,00% |
| 2. Portal Siswa & Landing Page | 10 | 9 | 0 | **1** | **90,00%** |
| 3. RBAC Dinamis | 14 | 14 | 0 | 0 | 100,00% |
| 4. Workflow Approval & Notifikasi | 19 | 19 | 0 | 0 | 100,00% |
| 5. Tahun Ajaran / Kenaikan Kelas / Akun Siswa | 14 | 14 | 0 | 0 | 100,00% |
| 6. Import/Export | 12 | 12 | 0 | 0 | 100,00% |
| 7. Detail Profil & UI | 6 | 6 | 0 | 0 | 100,00% |
| 8. Manajemen & Keamanan Akun | 13 | 13 | 0 | 0 | 100,00% |
| **Total** | **103** | **102** | **0** | **1** | **99,03%** |

---

### ➕ TAMBAH — Tabel 4.2 (baru): rincian pelaksanaan skenario inti

Sisipkan setelah Tabel 4.1. Kolom = skenario yang sama dengan Tabel 3.40 **+ Hasil Aktual + Status**. Beri caption Word: **Tabel 4.2. Rincian Hasil Pengujian Skenario Inti**.

**Kolom Tabel 4.2:** No | Kode | Jenis Pengujian | Skenario Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status

| No | Kode | Jenis Pengujian | Skenario Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| 1 | TC-01 | *Functional* | Siswa memulai pembayaran daring atas tagihan miliknya | Halaman pembayaran terbuka dengan nominal tagihan ditambah biaya admin, transaksi tercatat berstatus menunggu | Sesuai dengan hasil yang diharapkan | PASS |
| 2 | TC-02 | *Integration* | Sistem menerima konfirmasi pembayaran berhasil dari *payment gateway* | Status transaksi menjadi berhasil, tagihan menjadi lunas, pembayaran tercatat otomatis | Sesuai dengan hasil yang diharapkan | PASS |
| 3 | TC-03 | *Functional* | Siswa membayar beberapa tagihan sekaligus dalam satu transaksi | Seluruh tagihan yang dipilih menjadi lunas dan masing-masing tercatat pembayarannya | Sesuai dengan hasil yang diharapkan | PASS |
| 4 | TC-04 | *Validation* | Siswa mencoba membayar dengan nominal di bawah batas minimum | Sistem menolak dengan pesan batas minimum, tanpa membuat transaksi | Sistem menolak inisiasi pembayaran dengan pesan validasi pada kolom nominal, tidak ada transaksi dibuat | PASS |
| 5 | TC-05 | *Integration* | Sistem menerima konfirmasi pembayaran dengan tanda tangan digital tidak sah | Konfirmasi ditolak, status transaksi tidak berubah | Konfirmasi ditolak, status transaksi tetap menunggu, tidak terjadi gangguan sistem | PASS |
| 6 | TC-06 | *Integration* | Sistem menerima konfirmasi ulang untuk transaksi yang statusnya sudah final | Konfirmasi ditolak agar status final tidak berubah, kejadian tetap dicatat | Konfirmasi ditolak, status final tidak berubah, dan kejadian tercatat pada log | PASS |
| 7 | TC-07 | *Functional* | Pengguna tanpa wewenang mencoba membuka halaman pengaturan hak akses | Akses ditolak dan menu tidak ditampilkan | Akses ditolak dan menu tidak tampil pada navigasi | PASS |
| 8 | TC-08 | *Functional* | Mengirim pengajuan pengeluaran untuk disetujui | Status menjadi menunggu persetujuan dan notifikasi terkirim ke penyetuju | Sesuai dengan hasil yang diharapkan | PASS |
| 9 | TC-09 | *Functional* | Menyetujui pengajuan yang menunggu persetujuan | Status menjadi disetujui dan notifikasi terkirim ke pengaju | Sesuai dengan hasil yang diharapkan | PASS |
| 10 | TC-10 | *Functional* | Menolak pengajuan disertai alasan | Status menjadi ditolak dan alasan terkirim ke pengaju | Sesuai dengan hasil yang diharapkan | PASS |
| 11 | TC-11 | *Validation* | Menolak pengajuan tanpa mengisi alasan | Sistem menolak proses dan mewajibkan alasan diisi | Sistem menolak proses, kolom alasan wajib diisi terlebih dahulu, status tetap menunggu persetujuan | PASS |
| 12 | TC-12 | *Validation* | Mengirim pengajuan melebihi saldo cabang yang tersedia | Pengajuan ditolak dengan pesan saldo tidak mencukupi | Pengajuan ditolak dengan pesan saldo tidak mencukupi yang menampilkan saldo tersedia dan nominal dibutuhkan | PASS |
| 13 | TC-13 | *Functional* | Pengajuan bernominal kecil pada cabang dengan persetujuan otomatis | Pengajuan langsung disetujui tanpa persetujuan manual | Sesuai dengan hasil yang diharapkan | PASS |
| 14 | TC-14 | *Integration* | Pengiriman notifikasi surel pada setiap perubahan status pengajuan | Isi dan penerima surel sesuai jenis kejadian dan peran penerima | Sesuai dengan hasil yang diharapkan | PASS |
| 15 | TC-15 | *Integration* | Pengguna yang menonaktifkan notifikasi tidak menerima surel | Surel tidak dikirim ke pengguna yang memilih tidak menerima | Pengguna tidak menerima surel setelah menonaktifkan notifikasi melalui preferensi pada halaman profil | PASS |
| 16 | TC-16 | *Functional* | Menjalankan kenaikan kelas satu rombongan belajar ke periode berikutnya | Siswa berpindah ke kelas berikutnya dan riwayat penempatan tercatat | Siswa berpindah kelas dan penempatan kelasnya benar-benar diperbarui, bukan hanya tercatat pada riwayat | PASS |
| 17 | TC-17 | *Functional* | Meluluskan siswa pada kelas tertinggi | Siswa berstatus lulus dan tidak muncul di kelas aktif periode baru | Sesuai dengan hasil yang diharapkan | PASS |
| 18 | TC-18 | *Functional* | Mengimpor data siswa dari berkas yang valid | Data siswa ditambahkan dan riwayat impor tercatat | Data siswa berhasil ditambahkan dan riwayat impor tercatat | PASS |
| 19 | TC-19 | *Validation* | Mengimpor data siswa dengan nomor induk yang sudah terdaftar | Baris duplikat ditolak, baris valid tetap diproses, jumlah sukses/gagal tercatat | Sesuai dengan hasil yang diharapkan | PASS |
| 20 | TC-20 | *Validation* | Mengimpor berkas dengan kolom wajib kosong | Baris bermasalah ditolak disertai keterangan, baris lain tetap diproses | Sesuai dengan hasil yang diharapkan | PASS |
| 21 | TC-21 | *Validation* | Mengunggah berkas dengan format tidak didukung | Sistem menolak unggahan dengan pesan format tidak valid tanpa gangguan | Berkas ditolak karena dinilai sebagai jenis berkas yang tidak valid | PASS |
| 22 | TC-22 | *Functional* | Mengekspor data siswa ke berkas | Berkas terunduh berisi data siswa dan riwayat ekspor tercatat | Berkas berhasil terunduh berisi data siswa | PASS |
| 23 | TC-23 | *Functional* | Siswa melihat daftar tagihan miliknya | Hanya tagihan milik siswa yang login yang ditampilkan sesuai statusnya | Sesuai dengan hasil yang diharapkan | PASS |
| 24 | TC-24 | *Functional* | Menampilkan data keuangan pada dashboard sesuai cabang | Seluruh ringkasan menampilkan data cabang yang login tanpa data cabang lain | Sesuai dengan hasil yang diharapkan | PASS |
| 25 | TC-25 | *Functional* | Memverifikasi alamat surel menggunakan kode konfirmasi | Kode dikirim ke surel; setelah kode benar dimasukkan status menjadi terverifikasi, kode salah/kedaluwarsa ditolak | Sesuai dengan hasil yang diharapkan | PASS |
| 26 | TC-26 | *Functional* | Menonaktifkan akun pengguna | Seluruh sesi terputus dan login berikutnya ditolak dengan pesan akun nonaktif | Seluruh sesi pengguna terputus dan login berikutnya ditolak dengan pesan akun tidak aktif | PASS |
| 27 | TC-27 | *Functional* | Pengguna mengajukan pemulihan kata sandi | Tautan pemulihan dikirim ke surel dengan respons seragam, kata sandi dapat diatur ulang | Tautan pemulihan dikirim dengan respons seragam, dan kata sandi berhasil diatur ulang | PASS |
| 28 | TC-28 | *Integration* | Siswa mencoba mengakses tagihan siswa lain melalui manipulasi alamat tautan | Akses ditolak; data siswa lain tidak dapat diakses | Tidak dapat dieksekusi: penelusuran seluruh titik akses menunjukkan tidak ada titik akses yang menerima penanda data dari sisi pengguna tanpa pembatasan di sisi server, sehingga tidak ada permukaan yang dapat diuji | SKIPPED |

---

### ➕ TAMBAH — paragraf rekap per jenis pengujian (setelah Tabel 4.2, sebelum paragraf kesimpulan)

> Ditinjau dari jenisnya, pengujian terbagi ke dalam tiga kelompok sesuai rencana pada BAB III. *Functional testing* menguji jalannya fungsi utama sistem, meliputi pembayaran daring, pengaturan hak akses, persetujuan pengeluaran, kenaikan kelas dan kelulusan, dashboard monitoring, portal siswa, impor dan ekspor data, verifikasi surel, serta keamanan akun. *Validation testing* menguji ketahanan sistem terhadap masukan yang tidak semestinya, meliputi nominal pembayaran di bawah batas minimum, pengajuan tanpa alasan penolakan, pengajuan melebihi saldo, serta impor data dengan nomor induk duplikat, kolom wajib kosong, dan format berkas tidak didukung. *Integration testing* menguji keterhubungan sistem dengan layanan pihak ketiga, meliputi penerimaan konfirmasi pembayaran otomatis, penolakan konfirmasi bertanda tangan tidak sah, penanganan konfirmasi berulang pada transaksi final, serta pengiriman notifikasi surel beserta penghormatan preferensi pengguna. Seluruh skenario inti tersebut berstatus berhasil, kecuali satu skenario keamanan portal yang dinyatakan SKIPPED secara valid.

---

### ✏️ UBAH — paragraf kesimpulan 4.3 (sesuaikan angka)

**Cari:** `Dari 103 skenario pengujian yang dijalankan, 102 skenario dinyatakan berhasil (PASS) dan 1 skenario dinyatakan SKIPPED secara valid karena bersifat arsitektural dan tidak memiliki permukaan yang dapat dieksploitasi, tanpa ada satu pun skenario yang gagal (FAIL). Seluruh fitur utama maupun fitur tambahan yang dikembangkan telah berjalan sesuai kebutuhan yang ditetapkan.`

**Ganti jadi:**
> Dari 103 kasus uji yang dijalankan, 102 kasus dinyatakan berhasil (PASS) dengan tingkat keberhasilan 99,03%, tanpa ada satu pun kasus yang gagal (FAIL). Satu kasus uji sisanya dinyatakan SKIPPED secara valid, yaitu pengujian upaya mengakses data tagihan milik siswa lain melalui manipulasi alamat tautan. Kasus tersebut tidak dapat dieksekusi bukan karena kegagalan sistem, melainkan karena penelusuran terhadap seluruh titik akses data yang tersedia bagi pengguna siswa menunjukkan tidak adanya satu pun titik akses yang menerima penanda data dari sisi pengguna tanpa pembatasan di sisi server, sehingga tidak tersedia permukaan yang dapat diuji. Dengan demikian seluruh fitur utama maupun fitur tambahan yang dikembangkan telah berjalan sesuai kebutuhan yang ditetapkan.

---

### ℹ️ CATATAN — 4.4 sudah konsisten

Paragraf keterbatasan pada 4.4 sudah menyebut kasus SKIPPED dengan benar. Setelah 4.3 diperbaiki, kedua bagian selaras — tidak perlu diubah.

---

## Ringkasan jumlah perubahan

| Sub-bab | Hapus | Ubah | Tambah |
|---|---|---|---|
| 3.7 | 1 tabel skenario lama (diganti total) | 2 (referensi Tabel 3.2 → 3.40, kalimat pengantar) | Tabel 3.40 baru (28 baris) + 1 paragraf penutup |
| 4.3 | — | 3 (paragraf pengantar, Tabel 4.1 rekap, paragraf kesimpulan) | Tabel 4.2 baru (28 baris + hasil aktual + status) + 1 paragraf rekap per jenis |

**Setelah selesai:** `Ctrl+A` → `F9` untuk memperbarui SEQ, Daftar Tabel, dan Daftar Isi. Karena ada tabel baru (Tabel 4.2), periksa penomoran Tabel 4.x setelahnya tidak bergeser.
```
