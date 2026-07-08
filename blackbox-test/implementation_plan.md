# Master Test Plan: Aplikasi SPP Handayani

Berdasarkan *deep scan* tingkat akhir ke struktur **Database Models** (yang berisi 30 Entitas), berikut adalah daftar **100% EXHAUSTIVE** fitur dan entitas yang terangkum dalam Sistem Handayani. Pengelompokan kini membedah secara eksplisit **seluruh Data Master** sesuai permintaan Anda:

## 1. Modul Autentikasi & Keamanan ✅ *(Selesai Diuji)*
- **Model Terkait:** `User`, `PasswordResetToken`, `EmailOptOut`.
- **Fitur:** Login Admin, Lupa Password Admin, First Login Siswa (OTP), Lupa Password Siswa, Opt-out Notifikasi Email.

## 2. Modul Data Master (Inti Referensi Data)
Berikut adalah keseluruhan pilar Data Master yang menjadi fondasi sistem (tidak ada yang tertinggal):
- **Master Cabang (`Branch`):** CRUD identitas cabang sekolah.
- **Master Tahun Ajaran (`TahunAjaran`):** CRUD periode akademik, *state management* (hanya 1 aktif).
- **Master Kategori (`Kategori`):** CRUD jenjang pendidikan (misal: KB, TK, MI).
- **Master Kelas (`Kelas`):** CRUD pembagian ruang/rombongan belajar per jenjang.
- **Master Jenis Tagihan (`JenisTagihan`):** CRUD referensi harga/komponen tagihan SPP (Tipe Wajib/Opsional, per Kategori).
- **Master Ayah (`Ayah`):** Entitas mandiri untuk data identitas Ayah kandung (Pencarian Autocomplete/API `ParentSearchController`).
- **Master Ibu (`Ibu`):** Entitas mandiri untuk data identitas Ibu kandung (Pencarian Autocomplete/API).
- **Master Wali (`Wali`):** Entitas mandiri untuk penanggung jawab selain orang tua (CRUD dan relasi).
- **Master Siswa (`Siswa`):** CRUD inti data siswa, relasi ke Ayah, Ibu, Wali, Kategori, Kelas, dan Cabang.
- **Master Pengguna Admin (`User`):** CRUD manajemen identitas staf tata usaha/admin.
- **Pengaturan Approval Cabang ⚠️ [HANYA BACKEND]:** Rute API `/branch-approval-settings` tersedia, namun memang diatur tanpa UI (Backend-only) sesuai kebijakan sistem.

## 3. Modul Manajemen Pengguna & Hak Akses
- **Role Management:** Menambah/mengubah *Role* dan menetapkan *Permissions* (Granular access).
- **Role Attach/Detach ⚠️ [HANYA BACKEND]:** Rute API `/roles/attach` dan `/roles/detach` murni fitur *backend* (utilitas API/CLI), sementara UI *Frontend* secara eksklusif menggunakan mekanisme sinkronisasi massal via *CRUD User*.
- **User Management:** CRUD untuk staf/admin, pembatasan akses berdasarkan peran. (Assign Role dilakukan via CRUD User ini).
- **Akun Siswa:** Mekanisme pemblokiran, reset sandi manual, *generate* massal akun, serta **Cetak Kredensial Massal PDF** (*credentialsPdf*).

## 4. Modul Master Data Akademik Lanjutan
- **Tahun Ajaran:** Menambah, mengedit, dan mengaktifkan periode akademik (Fitur *Activate* otomatis menonaktifkan periode sebelumnya).
- **Nonaktifkan Tahun Ajaran Manual ⚠️ [HANYA BACKEND]:** Rute API `/tahun-ajaran/{id}/deactivate` tersedia, namun *Frontend* hanya menyediakan tombol UI untuk *Activate* (mengaktifkan).
- **Riwayat Penempatan Siswa (`SiswaKelas`):** Validasi integritas data histori penempatan kelas siswa tiap tahun.
- **Kenaikan Kelas Massal (`BatchPromosi` & `BatchPromosiDetail`):** Skenario kelulusan, naik kelas reguler, penahanan (*retention*), transfer silang jenjang, serta pembatalan massal (*Undo Batch*).
- **Ekspor & Impor Massal Siswa (`ImportBatch`):** Menguji *upload* template Excel, sinkronisasi *Job Queue*, konfirmasi *batch*, dan fitur **Rollback/Undo Import**.

## 5. Modul Keuangan & Transaksi SPP
- **Penetapan Tagihan (`Tagihan`):** Menguji *assign* jenis tagihan ke siswa, baik tunggal maupun *batch*. Ekspor/impor tagihan.
- **Pembayaran Manual (`Pembayaran`):** Skenario pembayaran tunai (Kas/Bank), pencetakan Kwitansi PDF, dan pembatalan bayar.
- **Manajemen Pengeluaran (`PengeluaranRequest` & `Pengeluaran`):** Pengujian *workflow approval* bertingkat (*Draft -> Submit -> Approve -> Reject -> Disburse*).
- **Pengeluaran Langsung (Bypass CRUD) ⚠️ [HANYA BACKEND]:** Rute API `/pengeluaran` tersedia di backend secara struktural untuk *direct expense*, tanpa UI *frontend* untuk mencegah *bypass*.
- **Log Persetujuan Cabang (`ApprovalLog` & `BranchApprovalSetting`):** Menguji syarat batas nominal (*threshold*) pengeluaran yang butuh persetujuan khusus cabang.

## 6. Modul Integrasi Pembayaran Online (Midtrans)
- **Rekaman Transaksi (`MidtransTransaction`):** Tes SNAP *checkout* via Portal Siswa (Tunggal & *Batch* keranjang tagihan).
- **Penerimaan Webhook (`MidtransTransactionLog`):** Uji *callback/notification* dari Sandbox Midtrans (merubah status *Pending* ke *Paid*).
- **Midtrans Admin (Manual Sync):** Admin memaksa sinkronisasi status transaksi dari *gateway*. Memeriksa *Cron/Prune logs*.

## 7. Modul Dasbor, Laporan & Pengaturan
- **Dasbor Admin:** Widget analitik, chart tunggakan, dan grafik *cashflow*.
- **Laporan:** Akurasi Kas Harian, Rekap Bulanan, Laporan Detail Mutasi Pemasukan & Pengeluaran, Pengecekan *ExportJob Queue*.
- **Pengaturan Aplikasi (`AppSetting`):** *Upload* logo, konfigurasi branding.
- **Sistem Notifikasi (`Notification`, `NotificationSetting`, `NotificationLog`, `NotificationSentRecord`):** 
  - Uji *Scheduler* (*artisan notifications:send-reminders*).
  - **Pengaturan Notifikasi:** Konfigurasi *scheduler* dan Mailpit, serta halaman Log Notifikasi (Riwayat Kirim). 
- **Interaksi Notifikasi Sistem ❌ [DEPRECATED / TO-BE-DELETED]:** Rute API untuk *Notification Bell* (`/notifications/unread-count`, `/notifications/mark-all-read`, dll). *Fitur ini telah digantikan sepenuhnya oleh Notifikasi Email dan akan dihapus dari backend pada iterasi berikutnya.*

## 8. Modul Portal User-Facing (Siswa & Publik)
- **Portal Siswa:** Validasi akses mandiri, edit profil terbatas, cek tagihan, riwayat transaksi, simulasi *checkout*.
- **Portal Publik (Landing Page Publik):** Menguji fungsionalitas UI *Landing Page* (Tailwind/Alpine.js). *(Telah terimplementasi utuh menggunakan arsitektur Blade Components `x-public.*` di frontend).*

## 9. Logika Bisnis & Fitur Tersembunyi (*Hidden Logic*)
Dari kedalaman *Backend Services*, ditemukan serangkaian otomasi cerdas yang bergerak di belakang layar:
- **Deteksi Saudara Kandung (*Sibling Detection Service*):** Menguji apakah sistem mampu mendeteksi siswa yang berstatus kakak-adik secara presisi berdasarkan entitas ortu.
- **Persetujuan Otomatis (*Auto Approval Service*):** Skenario pengajuan pengeluaran di bawah *threshold* yang langsung tervalidasi tanpa sentuhan manajer.
- **Generator Kwitansi Berantai (*Generate Sejumlah Kwitansi*):** Pembuatan dokumen kwitansi secara *batch* untuk pembayaran massal.
- **Notifikasi Alur Kerja (*Workflow Notification Service*):** Uji *trigger* email terotomatisasi saat proposal pengeluaran beralih status.
- **Validasi Email Ekstra (*Email Validation Service*):** Sistem pelindung anti-spam dan salah ketik pada saat ortu/siswa mendaftarkan email.
- **Email Opt-Out / Unsubscribe:** Halaman manajemen notifikasi email publik. *(Catatan QA: UI halaman ini di-*render* langsung oleh Backend/SSR via `EmailOptOutController` tanpa bergantung pada Frontend).*

---

## User Review Required
> [!IMPORTANT]
> Pemetaan fitur ini sudah berstatus Final. Semua celah desain, fitur *orphan*, dan rute khusus *backend* telah diberi peringatan secara eksplisit.

Untuk memulai penyusunan Skenario Pengujian (Tahap 1), **Modul nomor berapa yang ingin Anda prioritaskan untuk kita eksekusi selanjutnya?**
