# Revisi Teks Sub-bab 3.2, 3.3, 3.5 (menyesuaikan gambar Mermaid baru)

> **Docx TIDAK disentuh.** File ini daftar instruksi manual: apa yang dihapus, diubah, ditambah. Terapkan sendiri di Word.
>
> Sumber: ekstraksi read-only `document/…-Laporan Tugas Akhir.docx` (2077 paragraf) + aset final di `document/aset-laporan/mermaid-*/export/` + `document/diagram-3.2-arsitektur-sistem.md` + `document/diagram-3.5-proses-bisnis.md`, di-cross-check ke `tracking-perubahan.md` & `komparasi-proposal-vs-implementasi.md`.

---

## ⚠️ TEMUAN KRITIS — 10 referensi nomor gambar di teks SALAH SEMUA

Caption di dokumen pakai *SEQ field* (auto-nomor) dan sudah ter-update ke penomoran baru. Tapi kalimat pengantar di badan teks diketik **manual**, jadi masih menyebut nomor lama. Akibatnya pembaca diarahkan ke gambar yang salah — ini wajib diperbaiki lebih dulu, terpisah dari perubahan konten apa pun.

| Sub-bab | Kalimat menyebut | Caption sebenarnya | Ganti jadi |
|---|---|---|---|
| 3.3 | Gambar 3.31 | Gambar 3.3 + Gambar 3.4 | **Gambar 3.3 dan Gambar 3.4** |
| 3.3 | Gambar 3.32 | Gambar 3.5 ERD RBAC | **Gambar 3.5** |
| 3.3 | Gambar 3.33 | Gambar 3.6 ERD Tahun Ajaran | **Gambar 3.6** |
| 3.3 | Gambar 3.34 | Gambar 3.7 ERD Approval | **Gambar 3.7** |
| 3.3 | Gambar 3.35 | Gambar 3.8 ERD Import/Export | **Gambar 3.8** |
| 3.5 | Gambar 3.26 | Gambar 3.32 Alur Pembayaran | **Gambar 3.32** |
| 3.5 | Gambar 3.27 | Gambar 3.33 Alur Persetujuan | **Gambar 3.33** |
| 3.5 | Gambar 3.28 | Gambar 3.34 Alur Kenaikan Kelas | **Gambar 3.34** |
| 3.5 | Gambar 3.29 | Gambar 3.35 Alur Verifikasi OTP | **Gambar 3.35** |
| 3.5 | Gambar 3.30 | Gambar 3.36 Alur Hak Akses | **Gambar 3.36** |

**Penyebab:** 6 ERD dipindah ke posisi lebih awal (sebelum wireframe), jadi SEQ menomori ulang ERD ke 3.3–3.8, wireframe bergeser ke 3.9–3.31, dan diagram alur ke 3.32–3.36. Teks manual tidak ikut bergeser.

**Saran:** setelah semua revisi selesai, tekan `Ctrl+A` lalu `F9` di Word untuk memastikan SEQ, Daftar Gambar, dan Daftar Isi konsisten. Cek ulang tabel di atas kalau nomornya bergeser lagi.

---

# SUB-BAB 3.2 — ARSITEKTUR SISTEM

Gambar 3.2 diganti hasil render `document/aset-laporan/mermaid-architecture/export/sistem.png`. Diagram baru menampilkan komponen yang **tidak ada** di diagram lama: Sanctum, Redis, Queue Worker, Scheduler, serta pemisahan grup Client/Frontend/Backend/External. Teks harus menyusul.

### ✏️ UBAH — sub-judul komponen RBAC

**Cari:** `RBAC (Spatie Laravel Permission)`

**Ganti jadi:**
> RBAC (Spatie Laravel Permission dengan Pemetaan Dinamis)

---

### ✏️ UBAH — isi paragraf RBAC

**Cari paragraf yang diawali:** `RBAC (Role Based Access Control ) diimplementasikan menggunakan package Spatie Laravel Permission. Komponen ini berfungsi untuk mengatur hak akses…`

**Ganti seluruh paragraf jadi:**
> RBAC (*Role Based Access Control*) diimplementasikan menggunakan *package* Spatie Laravel Permission sebagai fondasi pengelolaan peran dan izin akses. Komponen ini berfungsi untuk mengatur hak akses pengguna berdasarkan peran yang dimiliki, seperti admin, bendahara, dan pimpinan. Pada sistem ini mekanisme tersebut diperluas dengan lapisan pemetaan dinamis: setiap halaman maupun titik akses data diberi penanda sumber daya (*resource key*) yang disimpan di basis data, kemudian dikaitkan dengan izin akses tertentu melalui antarmuka administrasi. Dengan pendekatan ini pengelola dapat mengatur ulang proteksi suatu halaman atau fitur tanpa perlu mengubah kode program. Proteksi tersebut diterapkan pada dua lapisan yang berdiri sendiri, yaitu lapisan antarmuka yang menentukan tampil atau tidaknya suatu menu, dan lapisan layanan data yang memeriksa ulang izin akses pada setiap permintaan. Rincian alur pemeriksaan hak akses ini disajikan pada Gambar 3.36.

*Alasan: diagram baru dan sub-bab 3.5 sama-sama menunjukkan RBAC bukan Spatie polos, melainkan dua jalur proteksi terpisah. Teks lama menyamakan sistem ini dengan RBAC standar, tidak sesuai implementasi — konsisten dengan kategori "Berubah/menyimpang" pada tabel komparasi.*

---

### ✏️ UBAH — sub-judul komponen notifikasi

**Cari:** `Notification Service (Google SMTP)`

**Ganti jadi:**
> Notification Service (SMTP)

*Alasan: pada lingkungan pengembangan layanan email diarahkan ke penampung surel lokal, bukan Google SMTP. Judul lama terlalu mengikat ke satu penyedia.*

---

### ➕ TAMBAH — kalimat penutup pada paragraf Notification Service

**Cari paragraf yang diawali:** `Layanan notifikasi menggunakan Google SMTP (Simple Mail Transfer Protocol)…`

**Tambahkan kalimat di akhir paragraf tersebut:**
> Pada lingkungan pengembangan, pengiriman surel diarahkan ke layanan penampung email lokal agar pengujian tidak mengirimkan surel sungguhan kepada pengguna, sedangkan pada lingkungan operasional pengiriman dilakukan melalui layanan SMTP sebenarnya.

---

### ➕ TAMBAH — dua komponen baru (sisipkan SETELAH paragraf "External Layer", SEBELUM paragraf terakhir)

**Sub-judul + paragraf 1:**
> **Antrean Proses Latar Belakang (*Queue Worker*)**
>
> Sebagian proses pada sistem membutuhkan waktu penyelesaian yang relatif lama, seperti pengiriman surel notifikasi dan penyusunan berkas ekspor data dalam jumlah besar. Proses semacam ini tidak dijalankan langsung pada saat pengguna menekan tombol, melainkan dititipkan ke antrean dan dikerjakan oleh proses tersendiri di latar belakang. Dengan cara ini pengguna tidak perlu menunggu proses selesai untuk dapat melanjutkan pekerjaan, dan kegagalan pengiriman pada satu notifikasi tidak mengganggu jalannya permintaan lain.

**Sub-judul + paragraf 2:**
> **Penjadwal Tugas Berkala (*Scheduler*)**
>
> Sebagian tugas sistem perlu dijalankan secara berkala tanpa dipicu oleh pengguna, seperti pengiriman pengingat tagihan yang telah mendekati jatuh tempo dan pembersihan catatan transaksi lama agar penyimpanan tetap ringkas. Tugas-tugas tersebut dijalankan oleh komponen penjadwal yang bekerja secara otomatis pada interval waktu yang telah ditentukan.

---

### 🗑️ HAPUS + ✏️ GANTI — paragraf terakhir 3.2 (terlalu kabur)

**Hapus seluruh paragraf yang diawali:** `Selain komponen-komponen di atas, arsitektur sistem turut diperkuat dengan tiga komponen tambahan yang teridentifikasi kebutuhannya selama pengembangan…`

**Ganti dengan tiga sub-komponen berikut** (paragraf lama menyebut "token akses", "caching", dan "terkontainer" tanpa nama dan tanpa penjelasan memadai, padahal ketiganya sekarang tampil eksplisit di diagram):

> **Autentikasi Token (Laravel Sanctum)**
>
> Autentikasi antara *frontend* dan *backend* menggunakan mekanisme token akses berbasis Laravel Sanctum. Setiap kali pengguna berhasil masuk, sistem menerbitkan sebuah token beserta daftar izin akses yang dimiliki pengguna tersebut dan batas waktu berlakunya. Token inilah yang disertakan pada setiap permintaan berikutnya, sehingga keaslian permintaan dapat diverifikasi tanpa perlu menyimpan status sesi di sisi server. Token lama akan dicabut setiap kali pengguna masuk kembali, sehingga hanya ada satu sesi aktif per pengguna.

> **Lapisan Penyimpanan Sementara (Redis)**
>
> Data yang sering diakses namun jarang berubah, khususnya ringkasan angka pada dashboard monitoring dan daftar hak akses pengguna, disimpan sementara pada lapisan penyimpanan cepat menggunakan Redis. Permintaan berikutnya untuk data yang sama dilayani langsung dari penyimpanan sementara tersebut sehingga tidak perlu mengulang perhitungan ke basis data. Penerapan lapisan ini menurunkan waktu penyajian halaman dashboard secara signifikan.

> **Lingkungan Terkontainer (Docker)**
>
> Seluruh komponen sistem — layanan *backend*, *frontend*, basis data, penyimpanan sementara, penampung surel, proses antrean, dan penjadwal — dijalankan sebagai layanan terpisah dalam lingkungan terkontainer. Pendekatan ini menjaga konsistensi konfigurasi antar-lingkungan dan menyeragamkan proses instalasi, sehingga sistem dapat dijalankan dengan perilaku yang sama pada perangkat pengembangan maupun pada lingkungan operasional.

---

### ➕ TAMBAH — kalimat pengantar sebelum daftar komponen (opsional tapi disarankan)

**Cari:** `Penjelasan Komponen:`

**Sisipkan paragraf sebelum baris tersebut:**
> Arsitektur sistem terbagi menjadi empat kelompok, yaitu kelompok *client* sebagai titik akses pengguna, kelompok *frontend* yang menangani antarmuka, kelompok *backend* yang memuat logika bisnis beserta seluruh komponen pendukungnya, serta kelompok layanan eksternal berupa layanan pihak ketiga yang terintegrasi dengan sistem. Penjelasan masing-masing komponen diuraikan sebagai berikut.

*Alasan: diagram baru mengelompokkan komponen secara eksplisit dalam empat kotak, teks perlu menjelaskan pengelompokan itu.*

---

# SUB-BAB 3.3 — PERANCANGAN BASIS DATA

Enam ERD diganti hasil render `document/aset-laporan/mermaid-erd/export/`, dengan pemetaan: `data-master.png` → Gambar 3.3, `transaksi-keuangan.png` → Gambar 3.4, `rbac.png` → Gambar 3.5, `tahun-ajaran-kenaikan-kelas.png` → Gambar 3.6, `approval-notifikasi.png` → Gambar 3.7, `import-export-midtrans.png` → Gambar 3.8.

Struktur, jumlah tabel (38), dan seluruh Tabel 3.2–3.39 **tidak berubah**. Yang perlu diperbaiki hanya penomoran gambar dan satu paragraf yang belum mencerminkan pemecahan modul inti menjadi dua diagram.

### ✏️ UBAH — paragraf pengantar ERD Modul Inti

**Cari paragraf yang diawali:** `Modul inti mencakup entitas dasar yang sudah digunakan sejak sebelum pengembangan Tugas Akhir ini… Diagram disajikan pada Gambar 3.31.`

**Ganti seluruh paragraf jadi:**
> Modul inti mencakup entitas dasar yang sudah digunakan sejak sebelum pengembangan Tugas Akhir ini, yaitu data siswa, kelas, tagihan, pembayaran, pengeluaran, dan cabang sebagai penghubung antarunit lembaga. Karena jumlah entitas pada modul ini cukup banyak, diagramnya dipecah menjadi dua bagian agar tetap terbaca, yaitu diagram data master yang memuat entitas acuan seperti siswa, kelas, dan data orang tua pada Gambar 3.3, serta diagram transaksi keuangan yang memuat entitas tagihan, pembayaran, dan pengeluaran pada Gambar 3.4.

*Alasan: teks lama menyebut satu diagram tunggal, padahal modul inti sudah dipecah jadi dua gambar terpisah.*

---

### ✏️ UBAH — empat kalimat penunjuk ERD lainnya (ganti nomornya saja)

| Cari kalimat berakhiran | Ganti jadi |
|---|---|
| `…serta autentikasi token Sanctum. Diagram disajikan pada Gambar 3.32.` | `…serta autentikasi token Sanctum. Diagram disajikan pada Gambar 3.5.` |
| `…secara batch, lengkap dengan opsi undo. Diagram disajikan pada Gambar 3.33.` | `…secara batch, lengkap dengan opsi undo. Diagram disajikan pada Gambar 3.6.` |
| `…notifikasi email yang dikirim sistem. Diagram disajikan pada Gambar 3.34.` | `…notifikasi email yang dikirim sistem. Diagram disajikan pada Gambar 3.7.` |
| `…melalui payment gateway Midtrans. Diagram disajikan pada Gambar 3.35.` | `…melalui payment gateway Midtrans. Diagram disajikan pada Gambar 3.8.` |

---

# SUB-BAB 3.5 — PERANCANGAN ALGORITMA ATAU PROSES BISNIS

Lima gambar diganti hasil render `document/aset-laporan/mermaid-seq-diagram/export/`: `pembayaran-daring.png` → Gambar 3.32, `approval.png` → Gambar 3.33, `kenaikan-kelas-kelulusan.png` → Gambar 3.34, `verif-email.png` → Gambar 3.35, `pengaturan-hak-akses.png` → Gambar 3.36.

**Perubahan bentuk diagram:** gambar lama berupa **diagram alir** (*flowchart*), gambar baru berupa **diagram sekuen** (*sequence diagram*). Ini mengubah cara membaca diagram — bukan lagi percabangan keputusan, melainkan urutan interaksi antarpelaku dan komponen sepanjang waktu. Kalimat pengantar wajib menyesuaikan.

### ✏️ UBAH — kalimat pembuka sub-bab (2 masalah sekaligus: bentuk diagram + klaim kwitansi)

**Cari paragraf:** `Bagian ini menjelaskan alur proses bisnis utama yang diterapkan pada sistem, digambarkan dalam bentuk diagram alir. Alur proses pembayaran daring, mulai dari pemilihan tagihan hingga penerbitan kwitansi digital, disajikan pada Gambar 3.26.`

**Ganti jadi:**
> Bagian ini menjelaskan alur proses bisnis utama yang diterapkan pada sistem, digambarkan dalam bentuk diagram sekuen (*sequence diagram*). Diagram sekuen dipilih karena proses-proses yang dijelaskan melibatkan lebih dari satu pihak, seperti pengguna, sistem, dan layanan pihak ketiga, sehingga urutan pertukaran informasi antarpihak beserta pemeriksaan yang dilakukan pada tiap tahap dapat ditampilkan secara runtut. Alur proses pembayaran daring, mulai dari pemilihan tagihan hingga pembaruan status tagihan setelah pembayaran terkonfirmasi, disajikan pada Gambar 3.32.

*Alasan dua koreksi: (1) bentuk diagram berubah jadi sekuen; (2) diagram pembayaran berakhir pada pencatatan pembayaran dan pembaruan status tagihan — penerbitan kwitansi digital bukan bagian dari alur yang digambarkan, sehingga klaim lama tidak didukung gambar.*

---

### ➕ TAMBAH — kalimat pada paragraf penjelas pembayaran (perkuat isi diagram)

**Cari paragraf:** `Setelah pengguna menyelesaikan pembayaran pada payment gateway, sistem menerima konfirmasi secara otomatis dan memperbarui status tagihan tanpa perlu campur tangan pengelola.`

**Ganti jadi:**
> Setelah pengguna menyelesaikan pembayaran pada *payment gateway*, sistem menerima konfirmasi secara otomatis dan memperbarui status tagihan tanpa perlu campur tangan pengelola. Konfirmasi tersebut diterima melalui jalur terpisah dari sesi pembayaran pengguna, sehingga status tetap dapat diperbarui meskipun pengguna telah menutup halaman pembayaran. Sebelum pembayaran benar-benar dicatat, sistem melakukan serangkaian pemeriksaan, meliputi keabsahan pesan konfirmasi, kesesuaian nominal dengan transaksi yang tercatat, kewajaran perubahan status, serta pencegahan pencatatan ganda dan pembayaran yang melebihi sisa tagihan.

---

### ✏️ UBAH — kalimat penunjuk Gambar persetujuan pengeluaran

**Cari:** `Alur proses persetujuan pengeluaran, termasuk jalur persetujuan otomatis untuk nominal kecil, disajikan pada Gambar 3.27.`

**Ganti jadi:**
> Alur proses persetujuan pengeluaran, termasuk jalur persetujuan otomatis untuk nominal kecil, disajikan pada Gambar 3.33.

---

### ➕ TAMBAH — kalimat pada paragraf penjelas persetujuan

**Cari paragraf:** `Pengajuan yang memenuhi syarat nominal pada cabang tertentu dapat disetujui secara otomatis, sedangkan pengajuan lainnya tetap menunggu persetujuan dari pihak berwenang sebelum dana dicairkan.`

**Tambahkan kalimat di akhir paragraf:**
> Ketersediaan saldo cabang diperiksa dua kali, yaitu pada saat pengajuan dikirim dan pada saat dana hendak dicairkan, sehingga pencairan tidak dapat menyebabkan saldo cabang menjadi negatif. Setiap perpindahan status pengajuan dicatat sebagai riwayat persetujuan yang dapat ditelusuri kembali.

---

### ✏️ UBAH — kalimat penunjuk Gambar kenaikan kelas

**Cari:** `Alur proses kenaikan kelas dan kelulusan siswa secara massal antarperiode tahun ajaran disajikan pada Gambar 3.28.`

**Ganti jadi:**
> Alur proses kenaikan kelas dan kelulusan siswa antarperiode tahun ajaran disajikan pada Gambar 3.34.

*Alasan: kata "secara massal" dihapus karena tidak seluruhnya benar — dari empat jenis aksi, hanya kenaikan kelas, tinggal kelas, dan kelulusan yang diproses untuk banyak siswa sekaligus, sedangkan perpindahan jenjang diproses satu siswa per pengajuan.*

---

### ✏️ UBAH — paragraf penjelas kenaikan kelas (lengkapi 4 jenis aksi)

**Cari paragraf:** `Proses ini dijalankan oleh pengelola pada setiap pergantian periode, dengan opsi pembatalan apabila hasil pemrosesan perlu dikoreksi.`

**Ganti jadi:**
> Proses ini dijalankan oleh pengelola pada setiap pergantian periode dan mencakup empat jenis tindakan, yaitu kenaikan kelas, tinggal kelas, kelulusan, serta perpindahan jenjang bagi siswa yang telah dinyatakan lulus. Tiga tindakan pertama dapat diterapkan pada banyak siswa sekaligus, sedangkan perpindahan jenjang diproses untuk satu siswa pada setiap pengajuan. Seluruh tindakan dicatat sebagai satu kesatuan riwayat pemrosesan sehingga menyediakan opsi pembatalan apabila hasilnya perlu dikoreksi. Pembatalan tersebut hanya mengembalikan data siswa yang penempatan kelasnya belum diubah secara manual setelah pemrosesan berlangsung, agar perubahan yang tidak berkaitan tidak ikut tertimpa.

---

### ✏️ UBAH — kalimat penunjuk Gambar verifikasi OTP

**Cari:** `Alur proses verifikasi alamat email menggunakan kode konfirmasi (OTP) disajikan pada Gambar 3.29.`

**Ganti jadi:**
> Alur proses verifikasi alamat email menggunakan kode konfirmasi (*One-Time Password*/OTP) disajikan pada Gambar 3.35.

---

### ➕ TAMBAH — kalimat pada paragraf penjelas OTP

**Cari paragraf:** `Kode konfirmasi yang dikirim ke email pengguna hanya berlaku untuk satu kali penggunaan dalam rentang waktu tertentu, sehingga proses verifikasi tetap aman.`

**Tambahkan kalimat di akhir paragraf:**
> Jumlah permintaan pengiriman kode juga dibatasi dalam rentang waktu tertentu untuk mencegah penyalahgunaan, dan kode yang telah berhasil digunakan langsung dihapus dari penyimpanan sementara agar tidak dapat dipakai ulang. Alur yang sama diterapkan pada verifikasi alamat email orang tua atau wali siswa.

---

### ✏️ UBAH — kalimat penunjuk Gambar hak akses

**Cari:** `Alur proses pengaturan hak akses berbasis keterkaitan halaman/fitur dengan izin akses disajikan pada Gambar 3.30.`

**Ganti jadi:**
> Alur pemeriksaan hak akses, yang berbasis keterkaitan antara halaman atau fitur dengan izin akses pengguna, disajikan pada Gambar 3.36.

---

### ✏️ UBAH — paragraf penutup hak akses (jelaskan dua lapisan)

**Cari paragraf:** `Pendekatan ini memungkinkan pengelola mengatur ulang hak akses langsung melalui antarmuka administrasi, tanpa perlu melibatkan perubahan pada kode program sistem.`

**Ganti jadi:**
> Alur pemeriksaan dimulai sejak pengguna berhasil masuk ke sistem, yaitu ketika sistem menerbitkan token akses beserta daftar izin yang dimiliki pengguna tersebut. Selanjutnya pemeriksaan berlangsung pada dua lapisan yang berdiri sendiri. Pada lapisan antarmuka, sistem menentukan menu dan halaman apa saja yang boleh ditampilkan kepada pengguna, sekaligus menolak akses apabila halaman dibuka secara langsung melalui alamat tautan. Pada lapisan layanan data, setiap permintaan diperiksa ulang dari awal terhadap pemetaan izin yang tersimpan di basis data. Pemeriksaan ganda ini membuat keamanan data tetap terjaga meskipun tampilan antarmuka sempat belum diperbarui. Pendekatan tersebut juga memungkinkan pengelola mengatur ulang hak akses langsung melalui antarmuka administrasi, tanpa perlu melibatkan perubahan pada kode program sistem.

---

## Ringkasan jumlah perubahan

| Sub-bab | Hapus | Ubah | Tambah |
|---|---|---|---|
| 3.2 | 1 paragraf | 3 (2 sub-judul, 1 paragraf RBAC) | 3 komponen baru + 1 pengantar + 2 sisipan kalimat |
| 3.3 | — | 5 (1 paragraf, 4 nomor gambar) | — |
| 3.5 | — | 8 (5 nomor gambar + 3 paragraf) | 3 sisipan kalimat |

**Setelah selesai:** tekan `Ctrl+A` → `F9` untuk memperbarui SEQ, Daftar Gambar, dan Daftar Isi. Lalu cek ulang bahwa nomor gambar di badan teks masih cocok dengan caption (tabel Temuan Kritis di bagian atas).
