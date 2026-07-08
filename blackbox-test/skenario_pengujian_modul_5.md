# Skenario Pengujian Blackbox — Modul 5: Manajemen Keuangan

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin) — khusus login portal siswa menggunakan NIS
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, State Transition Testing, Error Guessing, Exploratory Testing
> **Aspek:** Functional (wajib), UI/UX, Security/RBAC

---

## Daftar Sub-Modul

| No | Fitur | Halaman (Frontend) | Backend Controller | Backend Model | Prioritas |
|----|-------|-------------------|--------------------|---------------|-----------|
| 1 | **Jenis Tagihan (CRUD)** | Data Master → Jenis Tagihan | `JenisTagihanController` | `JenisTagihan` | High |
| 2 | **Tagihan (CRUD + Pay)** | Transaksi → Tagihan-{KB/TK/MI} | `TagihanController`, `PembayaranController` | `Tagihan`, `Pembayaran` | High |
| 3 | **Pembayaran (List + Hapus + Kwitansi)** | Transaksi → Pembayaran | `PembayaranController` | `Pembayaran` | High |
| 4 | **Pengeluaran Request (Workflow)** | Laporan → Pengeluaran Request | `PengeluaranRequestController`, `WorkflowService` | `PengeluaranRequest`, `ApprovalLog` | High |
| 5 | **Pengeluaran** | Laporan → Detail Pengeluaran | `PengeluaranController`, `KasController` | `Pengeluaran` | Medium |
| 6 | **Midtrans (Online Payment)** | Laporan → Transaksi Midtrans | `MidtransAdminController`, `MidtransTransactionController` | `MidtransTransaction` | High |

---

## 1. Fitur: Jenis Tagihan (JTG)

**Halaman:** Data Master → Jenis Tagihan
**Fitur:** CRUD Jenis Tagihan (nama, jatuh_tempo, jumlah) + Bulk Delete

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| JTG-001 | Tambah jenis tagihan — sukses | Equivalence Partitioning | Functional | Login sbg Superadmin | 1. Buka Data Master → Jenis Tagihan<br>2. Klik "Tambah"<br>3. Isi nama, jatuh tempo, jumlah<br>4. Klik "Simpan" | Nama: "SPP Juli 2026", Jatuh Tempo: 2026-07-15, Jumlah: 150000 | Notifikasi "Berhasil Ditambahkan". Item muncul di tabel. | High |
| JTG-002 | Tambah jenis tagihan — jumlah 0 | Boundary Value Analysis | Functional | - | 1. Klik Tambah<br>2. Isi jumlah = 0 | Jumlah: 0 | Form menolak (minValue: 1) atau 422. | Medium |
| JTG-003 | Tambah jenis tagihan — nama kosong | Error Guessing | Functional | - | 1. Klik Tambah<br>2. Nama kosong | Nama: "" | Form validasi client: "Nama Tagihan wajib diisi". | Medium |
| JTG-004 | Lihat daftar jenis tagihan — filter periode | Equivalence Partitioning | Functional | Ada data lintas periode | 1. Buka Jenis Tagihan<br>2. Ganti filter periode | N/A | Data berubah sesuai periode yang dipilih. | Medium |
| JTG-005 | Ubah jenis tagihan — sukses | Equivalence Partitioning | Functional | Ada satu jenis tagihan | 1. Klik ikon edit (pensil)<br>2. Ubah nama & jumlah<br>3. Simpan | Nama baru: "SPP Agustus 2026", Jumlah: 200000 | Notifikasi "Berhasil Diubah". Data berubah di tabel. | High |
| JTG-006 | Ubah jenis tagihan — batal | Exploratory | UI/UX | Modal edit terbuka | 1. Klik "Batal" | N/A | Modal tertutup, data tidak berubah. | Low |
| JTG-007 | Hapus jenis tagihan — sukses | Equivalence Partitioning | Functional | Jenis tagihan tanpa tagihan terkait | 1. Klik ikon hapus (trash)<br>2. Konfirmasi "Ya" | N/A | Notifikasi "Berhasil Dihapus". Item hilang dari tabel. | High |
| JTG-008 | Hapus jenis tagihan — sudah dipakai tagihan | Error Guessing | Functional | Jenis tagihan sudah memiliki tagihan | 1. Coba hapus | N/A | Backend tolak (409/422) — "tidak dapat dihapus karena masih digunakan". | High |
| JTG-009 | Bulk delete jenis tagihan — sukses | Equivalence Partitioning | Functional | ≥2 jenis tagihan belum dipakai | 1. Centang 2 item<br>2. Klik "Hapus Terpilih"<br>3. Konfirmasi | N/A | Notifikasi "2 berhasil dihapus". Item hilang. | Medium |
| JTG-010 | RBAC: tombol Tambah tanpa `create-jenis-tagihan` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman | N/A | Tombol "Tambah" tidak tampil. | High |
| JTG-011 | RBAC: tombol Edit tanpa `update-jenis-tagihan` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman | N/A | Ikon edit (pensil) tidak tampil. | High |
| JTG-012 | RBAC: tombol Hapus tanpa `delete-jenis-tagihan` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman | N/A | Ikon hapus (trash) tidak tampil. | High |

---

## 2. Fitur: Tagihan (TGH)

**Halaman:** Transaksi → Tagihan - {KB/TK/MI}
**Fitur:** Daftar tagihan per siswa (card view), filter, cari, tambah tagihan batch per kelas, bayar batch, cicil, export PDF, export Excel (via trait), hapus tagihan

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| TGH-001 | Tampilkan tagihan per jenjang | Equivalence Partitioning | Functional | Ada data tagihan di KB | 1. Buka Transaksi → Tagihan - KB | N/A | Tabel card siswa muncul dengan daftar tagihan masing-masing. | High |
| TGH-002 | Filter tagihan — berdasarkan status | Equivalence Partitioning | Functional | Ada tagihan berbagai status | 1. Pilih filter status "Belum Dibayar" | Status: Belum Dibayar | Hanya siswa dengan tagihan "Belum Dibayar" yang tampil. | Medium |
| TGH-003 | Filter tagihan — berdasarkan kelas | Equivalence Partitioning | Functional | Ada beberapa kelas | 1. Pilih filter kelas tertentu | Kelas: misal "KB-A" | Hanya siswa di kelas tersebut yang tampil. | Medium |
| TGH-004 | Cari siswa di tagihan | Equivalence Partitioning | Functional | Data siswa ada | 1. Ketik nama siswa di search | Nama: "Ahmad" | Tabel terfilter hanya menampilkan siswa yang cocok. | Medium |
| TGH-005 | Tambah tagihan batch per kelas — sukses | Equivalence Partitioning | Functional | Kelas memiliki siswa, jenis tagihan tersedia, periode aktif | 1. Klik "Tambah Tagihan"<br>2. Pilih Periode, Jenis Tagihan, Kelas, Kategori<br>3. Simpan | N/A | Notifikasi "Berhasil Ditambahkan". Semua siswa di kelas mendapat tagihan baru. | High |
| **TGH-018** | Cek email tagihan baru via Mailpit | Functional Integration | Functional | Selesai TGH-005 (tambah tagihan batch sukses) | 1. Buka Mailpit http://127.0.0.1:8025<br>2. Cari email dengan subjek mengandung "Tagihan Baru" / "Pemberitahuan Tagihan"<br>3. Buka dan verifikasi isi | N/A | Mailpit menampilkan email baru berisi info tagihan (nama siswa, jenis tagihan, jumlah). Dikirim ke alamat email wali siswa. | High |
| TGH-006 | Tambah tagihan — tanpa periode aktif | Error Guessing | Functional | Tidak ada periode aktif | 1. Nonaktifkan semua periode<br>2. Tambah tagihan | N/A | Error 422: "Periode aktif harus diatur terlebih dahulu." | High |
| TGH-007 | Hapus tagihan — sukses | Equivalence Partitioning | Functional | Tagihan status Belum Dibayar | 1. Buka detail siswa<br>2. Klik hapus di salah satu tagihan<br>3. Konfirmasi | Kode tagihan: misal TAG-001 | Notifikasi "Berhasil Dihapus". Tagihan hilang. | High |
| TGH-008 | Hapus tagihan — sudah dibayar | Error Guessing | Functional | Tagihan sudah memiliki pembayaran | 1. Coba hapus tagihan yang sudah dibayar | N/A | Error 409: "tagihan sudah dibayar dan tidak dapat dihapus." | High |
| TGH-009 | Bayar tagihan — batch lunas (offline) | Equivalence Partitioning | Functional | Siswa memiliki ≥1 tagihan belum lunas | 1. Checklist tagihan<br>2. Klik "Bayar"<br>3. Pilih metode Offline<br>4. Isi pembayar<br>5. Bayar | Metode: offline, Pembayar: "Tunai" | Notifikasi "Berhasil". Status tagihan berubah jadi Lunas. | High |
| TGH-010 | Bayar tagihan — batch lunas via Midtrans | Equivalence Partitioning | Functional | Midtrans enabled, siswa memiliki tagihan | 1. Pilih tagihan, metode Online (Midtrans) | Metode: online_midtrans | Redirect ke halaman Midtrans Snap. | Medium |
| TGH-011 | Bayar cicilan tagihan — jumlah valid | Equivalence Partitioning | Functional | Tagihan belum lunas, sisa > 0 | 1. Klik "Bayar Cicilan"<br>2. Isi jumlah (misal 50000)<br>3. Metode offline<br>4. Bayar | Jumlah: 50000, Metode: offline | Notifikasi "Pembayaran Cicilan Berhasil". Status tagihan jadi "Belum Lunas" (jika masih sisa) atau "Lunas". | High |
| TGH-012 | Bayar cicilan — jumlah melebihi sisa | Boundary Value Analysis | Functional | Tagihan sisa 100000 | 1. Isi jumlah 150000 | Jumlah: 150000 (sisa: 100000) | Error: "jumlah pembayaran melebihi sisa/jumlah biaya tagihan." | High |
| TGH-013 | Bayar tagihan — tagihan sudah lunas | Error Guessing | Functional | Tagihan status Lunas | 1. Coba bayar tagihan Lunas | N/A | Error: "tagihan sudah dibayar lunas." Tidak bisa bayar duplikat. | High |
| TGH-014 | Export PDF tagihan — dengan filter | Equivalence Partitioning | Functional | Ada tagihan | 1. Klik "Export PDF"<br>2. Pilih status, kategori, jatuh tempo<br>3. Export | Status: Belum Dibayar, Lunas | File PDF `laporan-tagihan-*.pdf` terdownload. | High |
| TGH-015 | Export Excel tagihan — format xlsx | Equivalence Partitioning | Functional | Ada data tagihan | 1. Klik "Export"<br>2. Pilih format xlsx<br>3. Export | Format: xlsx | File terdownload (via trait `HasImportExport`). | High |
| TGH-016 | RBAC: tombol Tambah Tagihan tanpa `create-tagihan` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman Tagihan | N/A | Tombol "Tambah Tagihan" tidak tampil. | High |
| TGH-017 | RBAC: akses halaman tanpa `view-tagihan` | Security (dasar) | Security | Admin tanpa permission | 1. Paksa buka URL tagihan | N/A | 403 Forbidden. | High |

---

## 3. Fitur: Pembayaran (BYR)

**Halaman:** Transaksi → Pembayaran
**Fitur:** Daftar pembayaran per siswa (card view), filter, search, hapus pembayaran, download kwitansi PDF, export Excel

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| BYR-001 | Tampilkan daftar pembayaran — per siswa grouped | Equivalence Partitioning | Functional | Ada data pembayaran | 1. Buka Transaksi → Pembayaran | N/A | Card per siswa muncul, masing-masing dengan daftar pembayaran. | High |
| BYR-002 | Filter pembayaran — berdasarkan jenjang | Equivalence Partitioning | Functional | Ada pembayaran di KB & TK | 1. Pilih filter jenjang "TK" | Jenjang: TK | Hanya siswa TK yang tampil. | Medium |
| BYR-003 | Filter pembayaran — berdasarkan metode | Equivalence Partitioning | Functional | Ada pembayaran offline & online | 1. Pilih filter "offline" | Metode: offline | Hanya pembayaran offline yang tampil. | Medium |
| BYR-004 | Filter pembayaran — berdasarkan sort | Equivalence Partitioning | Functional | - | 1. Ubah sort ke "Terlama" | Sort: oldest | Urutan berubah (terlama dulu). | Low |
| BYR-005 | Cari pembayaran — berdasarkan nama siswa | Equivalence Partitioning | Functional | - | 1. Ketik nama di search | Nama: "Ahmad" | Hasil terfilter. | Medium |
| BYR-006 | Hapus pembayaran — offline sukses | Equivalence Partitioning | Functional | Pembayaran offline | 1. Klik ikon hapus (trash)<br>2. Konfirmasi | N/A | Notifikasi "Berhasil Dihapus". Status tagihan kembali (tmp berkurang). | High |
| BYR-007 | Hapus pembayaran — online midtrans | Error Guessing | Security | Pembayaran via Midtrans | 1. Coba hapus pembayaran online_midtrans | N/A | Gagal: "CannotDeleteOnlinePembayaranException" kecuali user punya kedua permission: delete-pembayaran + manage-midtrans-config. | High |
| BYR-008 | Download kwitansi PDF | Equivalence Partitioning | Functional | Pembayaran ada | 1. Klik ikon kwitansi (PDF) pada baris pembayaran | N/A | File `kwitansi-{kode_pembayaran}.pdf` terdownload. | High |
| **BYR-012** | Cek email kwitansi pembayaran via Mailpit | Functional Integration | Functional | Selesai TGH-009 (bayar lunas offline sukses) | 1. Buka Mailpit http://127.0.0.1:8025<br>2. Cari email dengan subjek mengandung "Kwitansi" / "Pembayaran" / "Bukti Bayar"<br>3. Buka dan verifikasi | N/A | Mailpit menampilkan 1 email baru. Body mengandung info pembayaran (jumlah, metode, kode tagihan). | High |
| BYR-009 | Export pembayaran — format Excel | Equivalence Partitioning | Functional | Ada data | 1. Klik "Export"<br>2. Format xlsx<br>3. Export | Format: xlsx | File terdownload. | High |
| BYR-010 | RBAC: tombol Hapus tanpa `delete-pembayaran` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman | N/A | Ikon hapus tidak tampil. | High |
| BYR-011 | RBAC: akses halaman tanpa `view-pembayaran` | Security (dasar) | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403 Forbidden. | High |

---

## 4. Fitur: Pengeluaran Request — Approval Workflow (PRQ)

**Halaman:** Laporan → Pengeluaran Request
**Fitur:** Full workflow: Buat Request (Draft) → Submit → Approve / Reject (dengan catatan) → Disburse (Cairkan). Audit trail via ApprovalLog. Lihat alasan tolak, catatan approval, info pencairan. Hanya pembuat bisa edit/hapus (saat draft). Hanya pembuat bisa cairkan.

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| PRQ-001 | Buat request baru — isi valid | Equivalence Partitioning | Functional | Login sbg user dgn permission create-pengeluaran-request | 1. Klik "Buat Request"<br>2. Isi uraian, jumlah, tanggal kebutuhan<br>3. Simpan | Uraian: "Beli ATK", Jumlah: 500000, Tgl: 2026-07-10 | Notifikasi "Request berhasil dibuat". Tabel muncul item status "draft". | High |
| PRQ-002 | Buat request — jumlah = 0 | Boundary Value Analysis | Functional | - | 1. Isi jumlah 0 | Jumlah: 0 | Form menolak (minValue: 1). | Medium |
| PRQ-003 | Buat request — uraian kosong | Error Guessing | Functional | - | 1. Uraian kosong | Uraian: "" | Validasi client: wajib diisi. | Medium |
| PRQ-004 | Submit request — dari status draft | State Transition | Functional | Request status draft milik sendiri | 1. Klik "Submit" pada item draft<br>2. Konfirmasi | N/A | Notifikasi "Request berhasil disubmit". Status berubah jadi "submitted". | High |
| PRQ-005 | Submit request — dari status rejected (re-submit) | State Transition | Functional | Request status rejected milik sendiri | 1. Klik "Submit" pada item rejected<br>2. Konfirmasi | N/A | Status berubah jadi "submitted" kembali. Bisa diproses ulang. | High |
| PRQ-006 | Submit request — bukan milik sendiri | Security (dasar) | Security | Ada request punya user lain | 1. Coba submit request milik org lain | N/A | Tombol Submit tidak muncul (visible hanya jika requester_id == session user id). | High |
| PRQ-007 | Approve request — dari status submitted | State Transition | Functional | Ada request submitted (bisa milik siapa saja) | 1. Klik "Approve"<br>2. (Opsional) isi catatan<br>3. Approve | Catatan: "Setuju, segera cairkan" | Notifikasi "Request disetujui". Status berubah jadi "approved". | High |
| PRQ-008 | Approve request — tanpa permission `approve-pengeluaran` | Security (dasar) | Security & UI | User tanpa permission approve | 1. Buka request submitted | N/A | Tombol Approve tidak tampil. | High |
| PRQ-009 | Reject request — dengan alasan | State Transition | Functional | Ada request submitted | 1. Klik "Reject"<br>2. Isi alasan (wajib)<br>3. Reject | Alasan: "Dana belum tersedia" | Notifikasi "Request ditolak". Status berubah jadi "rejected". | High |
| PRQ-010 | Reject request — alasan kosong | Error Guessing | Functional | - | 1. Klik Reject<br>2. Alasan kosong | Alasan: "" | Form meminta alasan (required). | High |
| PRQ-011 | Lihat alasan ditolak — modal terbuka | Equivalence Partitioning | UI/UX | Request status rejected | 1. Klik "Alasan Ditolak" pada item rejected | N/A | Modal menampilkan alasan, siapa yang menolak, dan kapan. | High |
| PRQ-012 | Lihat catatan approval — modal terbuka | Equivalence Partitioning | UI/UX | Request approved dengan catatan | 1. Klik "Catatan Approval" pada item approved | N/A | Modal menampilkan catatan, approver, dan waktu. | Medium |
| PRQ-013 | Disburse (cairkan) — sukses | State Transition | Functional | Request approved | 1. Klik "Cairkan"<br>2. Konfirmasi | N/A | Notifikasi "Pencairan berhasil". Status berubah jadi "disbursed". Record Pengeluaran dibuat. | High |
| PRQ-014 | Disburse — tanpa permission `disburse-pengeluaran` | Security (dasar) | Security & UI | User tanpa permission disburse | 1. Buka request approved | N/A | Tombol "Cairkan" tidak tampil. | High |
| PRQ-015 | Disburse — bukan milik sendiri | Security (dasar) | Security | Ada request approved milik org lain | 1. Coba disburse request org lain | N/A | Tombol Cairkan tidak muncul untuk request org lain (visible hanya jika requester_id == session). | High |
| PRQ-016 | Info pencairan — modal terbuka | Equivalence Partitioning | UI/UX | Request status disbursed | 1. Klik "Info Pencairan" | N/A | Modal menampilkan info siapa mencairkan dan kapan. | Low |
| PRQ-017 | Ubah request draft — sukses | State Transition | Functional | Request draft milik sendiri | 1. Klik Edit (jika ada) | N/A | Data berubah. Status tetap draft. | High |
| PRQ-018 | Hapus request draft — sukses | State Transition | Functional | Request draft milik sendiri | 1. Klik Hapus | N/A | Request terhapus. | High |
| PRQ-019 | Hapus request — bukan status draft | State Transition | Functional | Request submitted/approved | 1. Coba hapus request submitted via API | N/A | Backend tolak 422: "Request hanya bisa dihapus saat status draft." | High |
| PRQ-020 | Filter status request | Equivalence Partitioning | Functional | Ada request berbagai status | 1. Pilih filter status "approved" | Status: approved | Hanya request approved yang tampil. | Medium |
| PRQ-021 | RBAC: tombol Buat Request tanpa `create-pengeluaran-request` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman | N/A | Tombol "Buat Request" tidak tampil. | High |

---

## 5. Fitur: Pengeluaran (PNG)

**Halaman:** Laporan → (Detail Pengeluaran via modal dari Kas Harian / Rekap Bulanan)
**Fitur:** Lihat daftar pengeluaran yang sudah dicairkan per tanggal/periode

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| PNG-001 | Lihat daftar pengeluaran per tanggal (dari Kas Harian) | Equivalence Partitioning | Functional | Ada pengeluaran yg sudah dicairkan | 1. Buka Laporan → Kas Harian<br>2. Klik "Detail" di baris tanggal tertentu | N/A | Modal menampilkan tabel pengeluaran: Nama, Pengaju, Penyetuju, Jumlah. | High |
| PNG-002 | Lihat daftar pengeluaran per bulan (dari Rekap Bulanan) | Equivalence Partitioning | Functional | Ada pengeluaran di bulan tsb | 1. Buka Rekap Bulanan<br>2. Klik "Detail" baris bulan tertentu | N/A | Modal menampilkan tabel pengeluaran di bulan tsb. | Medium |
| PNG-003 | Daftar pengeluaran — label status benar | Exploratory | UI/UX | Ada item disbursed | 1. Buka modal detail | N/A | Kolom Penyetuju terisi. Nama pengeluaran jelas. | Low |

---

## 6. Fitur: Midtrans — Transaksi Online (MDT)

**Halaman:** Laporan → Transaksi Midtrans
**Fitur:** Daftar transaksi Midtrans, filter status, filter tanggal, filter branch, search, auto-refresh (poll 5s), lihat detail transaksi

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| MDT-001 | Tampilkan daftar transaksi Midtrans | Equivalence Partitioning | Functional | Ada transaksi Midtrans (settlement, pending) | 1. Buka Laporan → Transaksi Midtrans | N/A | Tabel muncul dengan kolom: Order ID, Kode Tagihan, Nama Siswa, Jumlah Bayar, Biaya Admin, Total, Status, Metode, Tgl Dibuat, Tgl Diperbarui. | High |
| MDT-002 | Filter transaksi — status settlement | Equivalence Partitioning | Functional | Ada transaksi settlement | 1. Pilih filter "Settlement" | Status: settlement | Hanya transaksi settlement tampil. | Medium |
| MDT-003 | Filter transaksi — range tanggal | Equivalence Partitioning | Functional | Ada transaksi di rentang | 1. Isi "Dari Tanggal" dan "Sampai Tanggal" | Dari: 2026-07-01, Sampai: 2026-07-08 | Hanya transaksi dalam rentang tampil. | Medium |
| MDT-004 | Search transaksi — order ID | Equivalence Partitioning | Functional | - | 1. Ketik order ID di search | Order ID: "MID-2026..." | Tabel terfilter. | Medium |
| MDT-005 | Klik baris — navigasi ke detail transaksi | Exploratory | UI/UX | Ada transaksi | 1. Klik baris transaksi | N/A | Redirect ke `/transaksi-midtrans/{order_id}` halaman detail. | High |
| MDT-006 | Polling 5s — data baru muncul | Exploratory | UI/UX | Ada background transaksi | 1. Buka halaman<br>2. Tunggu 5 detik | N/A | Tabel auto-refresh setiap 5 detik. | Low |
| MDT-007 | RBAC: akses halaman tanpa permission | Security (dasar) | Security | Admin tanpa permission midtrans | 1. Paksa buka URL | N/A | 403 atau tidak ada menu (tergantung panel provider). | Medium |

---

## 7. Fitur: Transaksi via Portal Siswa (PTS)

**Halaman:** Portal Siswa → Tagihan Saya / Riwayat Pembayaran (hanya sebagai catatan — sisi siswa)
**Fitur:** Siswa melihat tagihan sendiri + sibling, bayar online via Midtrans, lihat riwayat pembayaran

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| PTS-001 | Login portal — lihat tagihan sendiri | Equivalence Partitioning | Functional | Akun siswa aktif, ada tagihan | 1. Login sebagai siswa (`000001`/pass)<br>2. Buka halaman Tagihan | N/A | Daftar tagihan milik siswa muncul. | High |
| PTS-002 | Portal — ganti sibling | Equivalence Partitioning | Functional | Siswa memiliki sibling | 1. Pilih sibling dari dropdown | N/A | Tagihan sibling muncul. | High |
| PTS-003 | Portal — bayar online via Midtrans | Equivalence Partitioning | Functional | Midtrans enabled, tagihan belum lunas | 1. Pilih tagihan<br>2. Klik "Bayar Online"<br>3. Pilih channel<br>4. Bayar | Kode tagihan: TAG-xxx | Redirect ke Midtrans Snap. | High |
| PTS-004 | Portal — batch payment online | Equivalence Partitioning | Functional | Midtrans enabled, beberapa tagihan | 1. Checklist beberapa tagihan<br>2. Klik bayar batch | N/A | Midtrans Snap dengan gross_amount = total semua tagihan. | High |
| PTS-005 | Portal — riwayat pembayaran | Equivalence Partitioning | Functional | Ada pembayaran | 1. Buka "Riwayat Pembayaran" | N/A | Riwayat pembayaran muncul. Termasuk transaksi pending (Midtrans). | Medium |

---

## 8. Fitur: Scheduled Email Reminder (RMD)

**Trigger:** `php artisan notifications:send-reminders` (dijadwalkan dailyAt('08:00') via Laravel Scheduler)
**Fitur:** Mengirim email reminder untuk tagihan yang mendekati jatuh tempo dan email overdue untuk tagihan yang melewati jatuh tempo

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RMD-001** | Jalankan reminder tagihan — cek email via Mailpit | Equivalence Partitioning | Functional | Ada tagihan dengan jatuh tempo dalam 3 hari ke depan | 1. Buka terminal<br>2. Jalankan `php artisan notifications:send-reminders`<br>3. Buka Mailpit http://127.0.0.1:8025 | N/A | Mailpit menampilkan email reminder dengan subjek "Pengingat Tagihan" / "Reminder". Body berisi info tagihan yang akan jatuh tempo. | High |
| **RMD-002** | Jalankan overdue tagihan — cek email via Mailpit | Equivalence Partitioning | Functional | Ada tagihan dengan jatuh tempo sudah lewat (overdue) | 1. Buka terminal<br>2. Jalankan `php artisan notifications:send-reminders`<br>3. Buka Mailpit http://127.0.0.1:8025 | N/A | Mailpit menampilkan email overdue dengan subjek "Tagihan Jatuh Tempo" / "Overdue". Body berisi info tagihan yang sudah melewati jatuh tempo. | High |

---

## Ringkasan

| Sub-Fitur | Jumlah TC | Teknik Utama | Prioritas Tinggi |
|-----------|----------|-------------|-----------------|
| Jenis Tagihan (JTG) | 12 | EP, BVA, EG | 6 |
| Tagihan (TGH) | 18 | EP, BVA, EG, ST | 10 |
| Pembayaran (BYR) | 12 | EP, EG | 7 |
| Pengeluaran Request (PRQ) | 21 | EP, ST, EG, BVA | 12 |
| Pengeluaran (PNG) | 3 | EP, Exp | 2 |
| Midtrans (MDT) | 7 | EP, EG, Exp | 3 |
| Portal Siswa (PTS) | 5 | EP, EG | 4 |
| Scheduled Reminder (RMD) | 2 | EP | 2 |
| **Total** | **80** | - | **46** |

**Keterangan Teknik:**
- **EP** = Equivalence Partitioning
- **BVA** = Boundary Value Analysis
- **ST** = State Transition Testing
- **EG** = Error Guessing
- **Exp** = Exploratory Testing

**Aspek yang Dicakup:**
- Functional: 100% test case
- UI/UX: ~8% (modal, tombol visibility, card view)
- Security (RBAC): ~18% (tombol/permission checks)
- Business Workflow: ~22% (approval workflow state transitions)
- **Email Integration: ~10% (Mailpit verification)**

---

*Dokumen siap untuk direview. Setelah approval, akan dibuatkan file hasil pengujian (template agregasi tabel hasil kosong) untuk pengisian manual.*
