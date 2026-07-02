# Requirements Document

## Introduction

Fitur **Midtrans Payment Gateway** menambahkan jalur pembayaran online untuk Tagihan SPP/sekolah pada portal Siswa/Wali Handayani. Saat ini Pembayaran hanya dapat direkam manual oleh admin (offline). Dengan fitur ini, Siswa/Wali dapat membayar Tagihan langsung melalui portal menggunakan Snap Midtrans (sandbox secara default), dan sistem secara otomatis mencatat Pembayaran ketika Midtrans memberi notifikasi sukses.

Cakupan fitur:

- Konfigurasi kredensial Midtrans yang aman di backend (server key, client key, merchant ID, environment toggle).
- Inisiasi transaksi pembayaran online dari Portal Siswa/Wali untuk Tagihan yang belum lunas milik Siswa yang login.
- Penanganan webhook (HTTP Notification) Midtrans dengan validasi signature dan idempotency.
- Pencatatan otomatis `Pembayaran` saat status transaksi `settlement` atau `capture`, sehingga listener existing (`SendKwitansiNotification`) tetap berjalan.
- UI Admin untuk memantau, memfilter, dan menyinkronkan ulang status transaksi.
- Coexistence dengan alur Pembayaran offline existing tanpa breaking change.
- Feature flag agar fitur dapat dinyalakan/dimatikan tanpa redeploy.

Asumsi yang dikonfirmasi user pada fase klarifikasi:

- A1. Mode integrasi: **Midtrans Snap** (redirect/embed), bukan Core API.
- A2. **Pembayaran parsial diperbolehkan** dengan minimum **Rp 10.000** dan maksimum sebesar `Sisa_Tagihan` saat itu.
- A3. Kadaluarsa transaksi: **24 jam** sejak inisiasi.
- A4. Biaya admin payment gateway **dibebankan ke siswa**. Untuk v1 strategi yang dipakai adalah **flat fee dari config** (`HANDAYANI_MIDTRANS_FEE_FLAT`), strategi per-kanal ditunda ke v2.
- A5. Konfigurasi Midtrans bersifat **global**, bukan per-branch (v1).
- A6. **Tidak ada auto-retry** pada transaksi expired/failed; Siswa/Wali harus menginisiasi ulang.
- A7. Mata uang **IDR** saja.

## Glossary

- **Handayani_System** — Sistem Manajemen Keuangan & SPP secara keseluruhan (backend Laravel + frontend-v2 Filament).
- **Backend_API** — Layanan Laravel REST API pada workspace `backend/` yang menjadi sumber kebenaran data dan aturan bisnis.
- **Portal_Frontend** — Filament Portal Panel di workspace `frontend-v2/` (`app/Filament/Portal/Pages/`) untuk Siswa/Wali.
- **Admin_Frontend** — Filament Admin Panel di workspace `frontend-v2/` (`app/Filament/Pages/`) untuk superadmin/admin.
- **Siswa** — Pengguna dengan role `siswa` di sistem Handayani.
- **Wali** — Orang tua/wali siswa, mengakses portal melalui akun siswa terkait.
- **Tagihan** — Invoice yang diterbitkan untuk seorang Siswa (`backend/app/Models/Tagihan.php`).
- **Pembayaran** — Catatan pembayaran terhadap suatu Tagihan (`backend/app/Models/Pembayaran.php`).
- **Sisa_Tagihan** — Selisih antara nominal Tagihan (`tmp`) dan total `Pembayaran.jumlah` yang sudah tercatat untuk Tagihan tersebut. Tagihan dianggap **lunas** jika `Sisa_Tagihan == 0`. Pembayaran parsial mengurangi `Sisa_Tagihan` secara incremental.
- **Amount_Paid** — Nominal pembayaran yang dipilih Siswa/Wali untuk Transaksi_Midtrans tertentu, yaitu nominal yang akan dikreditkan ke `Pembayaran.jumlah`. `Rp 10.000 <= Amount_Paid <= Sisa_Tagihan` (asumsi A2).
- **Fee_Flat** — Biaya admin payment gateway flat yang dibebankan ke siswa, dibaca dari config `HANDAYANI_MIDTRANS_FEE_FLAT` (integer Rupiah, default `4000`). Tidak masuk ke pemasukan sekolah, hanya menutup biaya Midtrans.
- **Gross_Amount** — Nominal yang dikirim ke Midtrans untuk Transaksi_Midtrans tertentu, yaitu `Gross_Amount = Amount_Paid + Fee_Flat`.
- **Midtrans** — Penyedia payment gateway pihak ketiga.
- **Midtrans_Snap** — Produk Midtrans yang menampilkan halaman pembayaran multi-kanal melalui token `snap_token`.
- **Midtrans_Notification** — HTTP POST callback dari Midtrans ke `Backend_API` yang berisi status transaksi dan `signature_key`.
- **Transaksi_Midtrans** — Entitas baru `MidtransTransaction` yang menyimpan satu attempt pembayaran online (1 Tagihan dapat memiliki banyak Transaksi_Midtrans). Menyimpan `amount_paid`, `fee_amount`, dan `gross_amount` secara terpisah.
- **Order_ID** — Identifier unik transaksi yang dikirim ke Midtrans, di-generate `Backend_API`. Format yang diusulkan: `HDY-{kode_tagihan}-{epoch_ms}`.
- **Status_Transaksi** — Enum status `Transaksi_Midtrans`: `pending`, `settlement`, `capture`, `deny`, `cancel`, `expire`, `failure`, `refund`, `partial_refund`. Mapping ke status terminal: `success` (`settlement`/`capture`), `failed` (`deny`/`failure`), `cancelled` (`cancel`), `expired` (`expire`), `pending` (selain itu).
- **Signature_Key** — SHA-512 dari `order_id + status_code + gross_amount + server_key` yang divalidasi pada setiap `Midtrans_Notification`.
- **Server_Key, Client_Key, Merchant_ID** — Kredensial Midtrans yang disimpan di `.env` `Backend_API`.
- **Sandbox_Mode** — Environment Midtrans untuk pengujian (`https://api.sandbox.midtrans.com`).
- **Production_Mode** — Environment Midtrans live (`https://api.midtrans.com`).
- **Feature_Flag_Midtrans** — Toggle `HANDAYANI_MIDTRANS_ENABLED` di `frontend-v2/config/handayani.php` dan padanannya di backend (`config/midtrans.php`) untuk menyalakan/mematikan fitur tanpa redeploy.
- **Permission_Pay_Online** — Permission baru `pay-tagihan-online` untuk Siswa/Wali.
- **Permission_View_Transactions** — Permission baru `view-midtrans-transactions` untuk admin.
- **Permission_Sync_Transactions** — Permission baru `sync-midtrans-transactions` untuk admin.
- **Permission_Manage_Config** — Permission baru `manage-midtrans-config` untuk superadmin.
- **PembayaranRecorded_Event** — Event existing `App\Events\PembayaranRecorded` yang memicu `SendKwitansiNotification`.
- **Metode_Pembayaran** — Field `metode` pada model `Pembayaran`, akan menerima dua nilai: `offline` dan `online_midtrans`.

## Requirements

### Requirement 1: Konfigurasi Kredensial Midtrans

**User Story:** Sebagai superadmin, saya ingin mengonfigurasi kredensial Midtrans dan environment-nya melalui variabel environment, sehingga kredensial tersimpan aman dan dapat di-toggle antara sandbox dan production tanpa mengubah kode.

#### Acceptance Criteria

1. THE Backend_API SHALL membaca `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_MERCHANT_ID`, dan `MIDTRANS_ENVIRONMENT` dari file `.env`.
2. WHERE `MIDTRANS_ENVIRONMENT` tidak diset, THE Backend_API SHALL menggunakan nilai default `sandbox`.
3. THE Backend_API SHALL menerima nilai `MIDTRANS_ENVIRONMENT` hanya berupa `sandbox` atau `production`.
4. IF `MIDTRANS_ENVIRONMENT` bernilai selain `sandbox` atau `production`, THEN THE Backend_API SHALL menolak booting konfigurasi Midtrans dengan pesan error yang menyebut nama variabel dan nilai yang diterima.
5. WHEN `MIDTRANS_ENVIRONMENT` bernilai `sandbox`, THE Backend_API SHALL menggunakan base URL `https://api.sandbox.midtrans.com` untuk semua panggilan API Midtrans.
6. WHEN `MIDTRANS_ENVIRONMENT` bernilai `production`, THE Backend_API SHALL menggunakan base URL `https://api.midtrans.com` untuk semua panggilan API Midtrans.
7. THE Backend_API SHALL TIDAK pernah menyertakan `MIDTRANS_SERVER_KEY` dalam respons HTTP apa pun ke klien.
8. THE Backend_API SHALL menyertakan `MIDTRANS_CLIENT_KEY` hanya pada respons endpoint inisiasi transaksi yang sudah ter-otentikasi sebagai Siswa pemilik Tagihan.
9. IF salah satu dari `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, atau `MIDTRANS_MERCHANT_ID` kosong saat `HANDAYANI_MIDTRANS_ENABLED` bernilai `true`, THEN THE Backend_API SHALL menolak request inisiasi transaksi dengan HTTP 503 dan kode error `MIDTRANS_NOT_CONFIGURED`.
10. IF `HANDAYANI_MIDTRANS_ENABLED` bernilai `false`, THEN THE Backend_API SHALL menolak request inisiasi transaksi dengan HTTP 404 (sesuai Requirement 2 AC 4) dan TIDAK mengembalikan kode `MIDTRANS_NOT_CONFIGURED`, terlepas dari status pengisian kredensial.

### Requirement 2: Feature Flag Midtrans

**User Story:** Sebagai superadmin, saya ingin menyalakan dan mematikan fitur pembayaran online Midtrans melalui feature flag, sehingga fitur dapat di-rollout bertahap atau dimatikan saat insiden tanpa redeploy.

#### Acceptance Criteria

1. THE Backend_API SHALL membaca toggle `HANDAYANI_MIDTRANS_ENABLED` (boolean, default `false`) dari `.env`.
2. THE Portal_Frontend SHALL membaca toggle `HANDAYANI_MIDTRANS_ENABLED` melalui `config/handayani.php` di `frontend-v2/`.
3. WHILE `HANDAYANI_MIDTRANS_ENABLED` bernilai `false`, THE Portal_Frontend SHALL TIDAK menampilkan tombol "Bayar Online" pada halaman Tagihan.
4. WHILE `HANDAYANI_MIDTRANS_ENABLED` bernilai `false`, THE Backend_API SHALL menolak semua endpoint inisiasi transaksi Midtrans dengan HTTP 404.
5. WHILE `HANDAYANI_MIDTRANS_ENABLED` bernilai `false`, THE Backend_API SHALL tetap menerima webhook Midtrans yang masuk untuk `Transaksi_Midtrans` yang sudah terlanjur dibuat sebelumnya, namun TIDAK mengizinkan inisiasi transaksi baru.
6. WHILE `HANDAYANI_MIDTRANS_ENABLED` bernilai `false`, THE Admin_Frontend SHALL menyembunyikan halaman daftar Transaksi Midtrans dari navigasi DAN memblokir akses langsung via URL dengan respons HTTP 404, sehingga admin tidak dapat memuat halaman tersebut bahkan dengan menebak path-nya.
7. THE Backend_API SHALL membaca toggle terpisah `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED` (boolean, default `true`) yang dapat dimatikan secara independen dari `HANDAYANI_MIDTRANS_ENABLED`.
8. WHILE `HANDAYANI_MIDTRANS_WEBHOOK_ENABLED` bernilai `false`, THE Backend_API SHALL menolak semua request ke endpoint webhook dengan HTTP 503 dan kode error `WEBHOOK_DISABLED`, sehingga insiden teknis pada handler dapat dimitigasi tanpa mempengaruhi inisiasi pembayaran baru.

### Requirement 3: RBAC Permission Pembayaran Online

**User Story:** Sebagai admin keamanan, saya ingin akses ke fitur Midtrans dijaga oleh permission yang spesifik, sehingga hanya peran yang sesuai yang dapat menginisiasi pembayaran, melihat transaksi, atau mengubah konfigurasi.

#### Acceptance Criteria

1. THE Backend_API SHALL mendaftarkan permission baru `pay-tagihan-online`, `view-midtrans-transactions`, `sync-midtrans-transactions`, dan `manage-midtrans-config` melalui `App\Constant\Permissions` dan `App\Enum\Permission`.
2. THE Backend_API SHALL memberikan permission `pay-tagihan-online` secara default kepada role `siswa` saat seeding.
3. THE Backend_API SHALL memberikan permission `view-midtrans-transactions` dan `sync-midtrans-transactions` secara default kepada role `admin` dan `superadmin` saat seeding.
4. THE Backend_API SHALL memberikan permission `manage-midtrans-config` hanya kepada role `superadmin` saat seeding.
5. WHEN sebuah request inisiasi transaksi diterima dari pengguna tanpa permission `pay-tagihan-online`, THE Backend_API SHALL menolak request dengan HTTP 403.
6. WHEN sebuah request ke endpoint daftar Transaksi Midtrans diterima dari pengguna tanpa permission `view-midtrans-transactions`, THE Backend_API SHALL menolak request dengan HTTP 403.
7. WHEN sebuah request sinkronisasi manual diterima dari pengguna tanpa permission `sync-midtrans-transactions`, THE Backend_API SHALL menolak request dengan HTTP 403.

### Requirement 4: Inisiasi Transaksi oleh Siswa

**User Story:** Sebagai Siswa atau Wali, saya ingin menekan tombol "Bayar Online" pada Tagihan saya, memilih nominal yang ingin dibayar (parsial atau penuh), dan diarahkan ke halaman pembayaran Midtrans, sehingga saya dapat melunasi tagihan baik sekaligus maupun bertahap.

#### Acceptance Criteria

1. THE Portal_Frontend SHALL menampilkan tombol "Bayar Online" pada baris Tagihan Siswa yang login HANYA jika `Sisa_Tagihan` lebih besar atau sama dengan `10000`.
2. WHEN Siswa menekan tombol "Bayar Online" untuk sebuah Tagihan, THE Portal_Frontend SHALL menampilkan form pemilihan nominal pembayaran dengan default value `Sisa_Tagihan` dan field input numerik untuk pembayaran parsial.
3. THE Portal_Frontend SHALL melakukan validasi client-side: `Amount_Paid` harus integer Rupiah, `>= 10000`, dan `<= Sisa_Tagihan`.
4. WHEN Siswa mengonfirmasi nominal, THE Portal_Frontend SHALL mengirim request inisiasi ke `Backend_API` dengan menyertakan `kode_tagihan` dan `amount_paid`.
5. WHEN Backend_API menerima request inisiasi yang valid dari Siswa pemilik Tagihan, THE Backend_API SHALL membuat satu record `Transaksi_Midtrans` baru dengan status `pending`, `Order_ID` unik, `amount_paid` sesuai input, `fee_amount = Fee_Flat` saat itu, `gross_amount = amount_paid + fee_amount`, dan referensi ke `kode_tagihan`.
6. WHEN Transaksi_Midtrans dibuat, THE Backend_API SHALL memanggil Midtrans Snap API untuk mendapatkan `snap_token` dan `redirect_url`, lalu mengembalikan keduanya beserta `Order_ID`, `amount_paid`, `fee_amount`, dan `gross_amount` ke Portal_Frontend.
7. THE Backend_API SHALL menyertakan dalam payload Snap data customer berupa `nis`, nama Siswa, dan email Wali (jika tersedia).
8. THE Backend_API SHALL menyertakan dalam payload Snap dua line items: (a) `id = kode_tagihan`, `name` mengandung jenis tagihan dan periode, `quantity = 1`, `price = amount_paid`; (b) `id = "FEE_MIDTRANS"`, `name = "Biaya Admin Pembayaran Online"`, `quantity = 1`, `price = fee_amount`; sehingga jumlah line items sama dengan `gross_amount`.
9. THE Backend_API SHALL menetapkan `expiry` Transaksi_Midtrans `24 jam` (asumsi A3) sejak waktu pembuatan transaksi.
10. IF Tagihan yang diinisiasi tidak ditemukan pada Backend_API, THEN THE Backend_API SHALL mengembalikan HTTP 404 dengan kode error `TAGIHAN_NOT_FOUND`.
11. IF Tagihan yang diinisiasi bukan milik Siswa yang sedang login, THEN THE Backend_API SHALL mengembalikan HTTP 403 dengan kode error `TAGIHAN_FORBIDDEN`.
12. IF `Sisa_Tagihan` untuk Tagihan yang diinisiasi adalah nol, THEN THE Backend_API SHALL mengembalikan HTTP 422 dengan kode error `TAGIHAN_SUDAH_LUNAS`.
13. IF `amount_paid` pada request kurang dari `10000`, THEN THE Backend_API SHALL menolak request dengan HTTP 422 dan kode error `AMOUNT_BELOW_MINIMUM`.
14. IF `amount_paid` pada request lebih besar dari `Sisa_Tagihan` saat ini, THEN THE Backend_API SHALL menolak request dengan HTTP 422 dan kode error `AMOUNT_EXCEEDS_SISA`.
15. IF panggilan ke Midtrans Snap API gagal karena network atau response non-2xx, THEN THE Backend_API SHALL menandai Transaksi_Midtrans yang baru dibuat dengan status `failure`, mencatat log error, dan mengembalikan HTTP 502 dengan kode error `MIDTRANS_UNAVAILABLE`.
16. WHEN sebuah Transaksi_Midtrans Tagihan masih berstatus `pending` dan belum kadaluarsa, THE Backend_API SHALL menolak inisiasi baru untuk Tagihan yang sama dengan HTTP 409 dan kode error `TAGIHAN_HAS_PENDING_TRANSACTION`, sambil mengembalikan `Order_ID`, `amount_paid`, `fee_amount`, `gross_amount`, `snap_token`, dan `redirect_url` transaksi pending tersebut, sehingga klik berulang oleh Siswa selalu mengembalikan transaksi yang sama (idempotent inisiasi).

### Requirement 5: Penanganan Webhook Midtrans (Notification Handler)

**User Story:** Sebagai sistem, saya ingin menerima notifikasi status transaksi dari Midtrans dan memperbarui status `Transaksi_Midtrans` secara aman dan idempotent, sehingga status pembayaran selalu konsisten dengan Midtrans tanpa risiko duplikasi.

#### Acceptance Criteria

1. THE Backend_API SHALL menyediakan endpoint webhook `POST /api/midtrans/notification` yang TIDAK memerlukan autentikasi Sanctum.
2. WHEN Midtrans_Notification diterima, THE Backend_API SHALL menghitung Signature_Key sebagai SHA-512 dari konkatenasi `order_id + status_code + gross_amount + MIDTRANS_SERVER_KEY` lalu membandingkannya dengan field `signature_key` dari payload.
3. IF Signature_Key yang dihitung tidak cocok dengan `signature_key` pada payload, THEN THE Backend_API SHALL menolak notifikasi dengan HTTP 403, mencatat log keamanan, dan TIDAK mengubah state apa pun.
4. WHEN signature valid dan `order_id` pada notifikasi tidak ditemukan dalam Transaksi_Midtrans, THE Backend_API SHALL mengembalikan HTTP 404 dengan kode error `ORDER_NOT_FOUND`.
5. WHEN signature valid dan `order_id` ditemukan, THE Backend_API SHALL memetakan kombinasi `transaction_status` dan `fraud_status` dari payload ke Status_Transaksi internal sesuai mapping berikut:
   - `capture` + `accept` → `capture`
   - `settlement` → `settlement`
   - `pending` → `pending`
   - `deny` → `deny`
   - `cancel` → `cancel`
   - `expire` → `expire`
   - `failure` → `failure`
   - `refund` → `refund`
   - `partial_refund` → `partial_refund`
6. THE Backend_API SHALL memperbarui Transaksi_Midtrans dengan Status_Transaksi yang baru, payment type, dan timestamp pembaruan.
7. THE Backend_API SHALL memperlakukan notifikasi yang menghasilkan Status_Transaksi yang sama dengan status saat ini sebagai no-op: TIDAK mengubah data Transaksi_Midtrans, TIDAK membuat Pembayaran tambahan, namun tetap mencatat log notifikasi sesuai Requirement 11. Kode HTTP respons untuk no-op diatur oleh AC 9.
8. THE Backend_API SHALL menolak transisi status mundur dari status terminal (`settlement`, `capture`, `deny`, `cancel`, `expire`, `failure`, `refund`) ke status `pending` atau status terminal lain yang tidak konsisten, dengan mencatat log dan mengembalikan HTTP 409 dengan kode error `INVALID_STATUS_TRANSITION`.
9. THE Backend_API SHALL mengembalikan HTTP 200 untuk notifikasi valid yang berhasil diproses (termasuk no-op) agar Midtrans tidak melakukan retry tidak perlu.
10. THE Backend_API SHALL menyimpan raw payload notifikasi (request body) ke tabel log audit untuk setiap notifikasi yang diterima, terlepas dari hasil validasi.

### Requirement 6: Pencatatan Pembayaran Otomatis

**User Story:** Sebagai admin keuangan, saya ingin Pembayaran tercatat otomatis di sistem Handayani saat pembayaran online sukses, sehingga kwitansi terbit dan saldo Tagihan terupdate tanpa intervensi manual. Fee flat tidak boleh ikut terhitung sebagai pelunasan tagihan.

#### Acceptance Criteria

1. WHEN Transaksi_Midtrans bertransisi dari status non-terminal ke `settlement` atau `capture`, THE Backend_API SHALL membuat satu record `Pembayaran` baru dengan `kode_tagihan` sesuai Transaksi_Midtrans.
2. THE Backend_API SHALL mengisi field `metode` pada Pembayaran yang dibuat dengan nilai `online_midtrans`.
3. THE Backend_API SHALL mengisi field `jumlah` pada Pembayaran yang dibuat sama dengan `amount_paid` Transaksi_Midtrans (BUKAN `gross_amount`), sehingga `Fee_Flat` tidak mengurangi `Sisa_Tagihan`.
4. THE Backend_API SHALL mengisi field `tanggal` pada Pembayaran yang dibuat dengan timestamp `settlement_time` (jika tersedia di payload Midtrans) atau timestamp pemrosesan notifikasi sebagai fallback.
5. THE Backend_API SHALL mengisi field `pembayar` pada Pembayaran yang dibuat dengan nama Siswa atau Wali yang menginisiasi transaksi.
6. THE Backend_API SHALL mengisi field `branch_id` pada Pembayaran yang dibuat sama dengan `branch_id` Tagihan terkait.
7. THE Backend_API SHALL menyimpan referensi `Order_ID` Midtrans pada Pembayaran (kolom baru `midtrans_order_id`) untuk traceability.
8. WHEN Pembayaran berhasil dibuat dari notifikasi Midtrans, THE Backend_API SHALL men-dispatch `PembayaranRecorded_Event` agar listener existing `SendKwitansiNotification` dijalankan tanpa modifikasi.
9. WHEN Notifikasi yang sama diterima ulang oleh Backend_API untuk Transaksi_Midtrans yang sudah memiliki Pembayaran tercatat, THE Backend_API SHALL TIDAK membuat Pembayaran duplikat dan TIDAK men-dispatch ulang `PembayaranRecorded_Event`.
10. WHEN penjumlahan Pembayaran melebihi `tmp` Tagihan akibat race condition, THE Backend_API SHALL menggagalkan pencatatan Pembayaran yang melampaui batas, mencatat log error `OVERPAYMENT_BLOCKED`, dan mengembalikan HTTP 409 ke caller (Midtrans), sehingga konsistensi `Sisa_Tagihan >= 0` selalu terjaga.
11. WHEN sebuah Tagihan memiliki Transaksi_Midtrans berstatus `pending` yang belum kadaluarsa, THE Backend_API SHALL memperlakukan `amount_paid` transaksi pending tersebut sebagai dana yang sudah dialokasikan (in-flight), sehingga inisiasi transaksi tambahan untuk Tagihan yang sama ditolak (Requirement 4 AC 16) dan tidak ada celah race antara dua transaksi paralel yang sama-sama melunasi.
12. THE Backend_API SHALL TIDAK membuat record Pembayaran terpisah untuk `fee_amount`; fee dianggap pengeluaran sekolah ke Midtrans dan TIDAK masuk ke kas masuk sekolah.

### Requirement 7: Idempotency dan Konsistensi Data

**User Story:** Sebagai sistem, saya ingin operasi inisiasi dan penanganan webhook bersifat idempotent dan thread-safe, sehingga tidak ada Pembayaran ganda meskipun Midtrans mengirim notifikasi berkali-kali atau Siswa mengklik "Bayar Online" beberapa kali berurutan.

#### Acceptance Criteria

1. THE Backend_API SHALL memberlakukan unique constraint pada kolom `order_id` Transaksi_Midtrans di level database.
2. THE Backend_API SHALL memberlakukan unique constraint pada kolom `midtrans_order_id` Pembayaran di level database.
3. WHEN dua notifikasi Midtrans dengan `order_id` yang sama tiba bersamaan, THE Backend_API SHALL menggunakan database lock (mis. `SELECT ... FOR UPDATE` dalam transaksi) sehingga hanya satu request yang melakukan transisi state, dan request lain melihat state final.
4. THE Backend_API SHALL memastikan untuk satu Transaksi_Midtrans dengan status `settlement` atau `capture`, paling banyak satu Pembayaran yang tercatat (invariant `count(Pembayaran where midtrans_order_id = X) <= 1`).
5. WHEN Siswa menekan "Bayar Online" dua kali pada Tagihan yang sama dalam waktu singkat, THE Backend_API SHALL menjalankan kebijakan Requirement 4 AC 12 sehingga klik kedua mengembalikan Order_ID yang sama, bukan membuat transaksi duplikat.
6. THE Backend_API SHALL menjamin: untuk seluruh Transaksi_Midtrans dengan Status_Transaksi `settlement` atau `capture`, total `Pembayaran.jumlah` terkait sama persis dengan jumlah `amount_paid` (BUKAN `gross_amount`) Transaksi_Midtrans tersebut (round-trip property: `sum(Pembayaran.jumlah where midtrans_order_id in S) == sum(Transaksi_Midtrans.amount_paid where order_id in S and status in {settlement, capture})`).

### Requirement 8: Sinkronisasi Manual Status oleh Admin

**User Story:** Sebagai admin keuangan, saya ingin dapat menyinkronkan ulang status sebuah Transaksi_Midtrans secara manual dari Midtrans, sehingga transaksi yang stuck pada `pending` dapat diselesaikan tanpa harus menunggu webhook.

#### Acceptance Criteria

1. THE Admin_Frontend SHALL menampilkan tombol "Sinkronisasi Status" pada baris Transaksi_Midtrans HANYA ketika status transaksi adalah `pending`; untuk transaksi non-`pending` tombol disembunyikan sepenuhnya (bukan ditampilkan dalam keadaan disabled).
2. WHEN admin menekan tombol "Sinkronisasi Status", THE Backend_API SHALL memanggil endpoint Midtrans `GET /v2/{order_id}/status` menggunakan Server_Key sebagai Basic Auth.
3. WHEN Backend_API menerima respons sukses dari Midtrans status API, THE Backend_API SHALL memproses respons tersebut menggunakan jalur penanganan yang sama dengan Requirement 5 (mapping status, idempotency, pencatatan Pembayaran jika `settlement`/`capture`).
4. IF panggilan ke Midtrans status API gagal untuk transaksi non-terminal, THEN THE Backend_API SHALL mengembalikan HTTP 502 ke Admin_Frontend dengan kode error `MIDTRANS_STATUS_UNAVAILABLE` dan tidak mengubah Status_Transaksi.
5. THE Backend_API SHALL melakukan validasi status Transaksi_Midtrans SEBELUM memanggil Midtrans status API; IF Transaksi_Midtrans sudah berstatus terminal, THEN THE Backend_API SHALL mengembalikan HTTP 409 dengan kode error `TRANSACTION_ALREADY_FINAL` tanpa pernah memanggil Midtrans status API.

### Requirement 9: UI Portal Siswa/Wali

**User Story:** Sebagai Siswa atau Wali, saya ingin pengalaman pembayaran online yang jelas: dari memilih tagihan, memilih kanal pembayaran di Midtrans, hingga melihat hasilnya, sehingga saya yakin tagihan saya sudah terbayar.

#### Acceptance Criteria

1. THE Portal_Frontend SHALL menampilkan tombol "Bayar Online" pada `PortalTagihanPage` untuk setiap Tagihan dengan `Sisa_Tagihan >= 10000` ketika `HANDAYANI_MIDTRANS_ENABLED` bernilai `true`.
2. WHEN Siswa menekan "Bayar Online", THE Portal_Frontend SHALL menampilkan dialog input nominal `amount_paid` dengan default `Sisa_Tagihan`, batas minimum `10000`, dan batas maksimum `Sisa_Tagihan`, beserta preview `Fee_Flat` dan total `Gross_Amount` yang akan dibayarkan ke Midtrans.
3. WHEN Siswa berhasil menginisiasi transaksi, THE Portal_Frontend SHALL membuka halaman Snap Midtrans (redirect atau embed) menggunakan `snap_token` yang diterima dari Backend_API.
4. WHEN Siswa kembali ke Portal_Frontend dari Snap Midtrans melalui jalur apa pun (selesai, batal, tutup tab kemudian buka kembali, atau redirect kembali setelah kehilangan koneksi), THE Portal_Frontend SHALL menampilkan halaman Status Pembayaran yang menunjukkan status terkini Transaksi_Midtrans (`pending`, `success`, `failed`, `expired`, `cancelled`) berdasarkan `Order_ID`.
5. THE Portal_Frontend SHALL melakukan polling status Transaksi_Midtrans setiap 5 detik selama maksimal 2 menit pada halaman Status Pembayaran ketika status masih `pending`. WHEN status berubah ke status terminal apa pun (`success`/`failed`/`expired`/`cancelled`), THE Portal_Frontend SHALL menghentikan polling segera, memperbarui tampilan ke status terminal, dan TIDAK melanjutkan polling. WHEN batas 2 menit tercapai sementara status masih `pending`, THE Portal_Frontend SHALL menghentikan polling dan menampilkan instruksi untuk mengecek `PortalRiwayatPembayaranPage`.
6. THE Portal_Frontend SHALL menampilkan riwayat Pembayaran online di `PortalRiwayatPembayaranPage`, ditandai dengan badge `Online` untuk Pembayaran dengan `metode = online_midtrans`, dan badge `Offline` untuk lainnya.
7. WHERE pembayaran online sukses, THE Portal_Frontend SHALL menyediakan tautan untuk mengunduh kwitansi PDF yang sudah ada.
8. IF Backend_API mengembalikan kode error `TAGIHAN_HAS_PENDING_TRANSACTION` saat inisiasi, THEN THE Portal_Frontend SHALL menampilkan pesan dalam Bahasa Indonesia yang mengarahkan Siswa untuk melanjutkan pembayaran sebelumnya, beserta tombol "Lanjutkan Pembayaran" yang menggunakan `snap_token` lama (dari respons error).

### Requirement 10: UI Admin Daftar Transaksi Midtrans

**User Story:** Sebagai admin keuangan, saya ingin melihat dan memfilter daftar Transaksi_Midtrans, sehingga saya dapat memantau pembayaran online dan menindaklanjuti yang bermasalah.

#### Acceptance Criteria

1. THE Admin_Frontend SHALL menyediakan halaman Filament `TransaksiMidtransPage` di `app/Filament/Pages/` yang hanya dapat diakses oleh pengguna dengan permission `view-midtrans-transactions`.
2. THE Admin_Frontend SHALL menampilkan kolom: `order_id`, `kode_tagihan`, `nama_siswa`, `amount_paid`, `fee_amount`, `gross_amount`, `status`, `payment_type`, `created_at`, `updated_at` pada daftar Transaksi.
3. THE Admin_Frontend SHALL menyediakan filter berdasarkan `status`, rentang tanggal `created_at`, dan `branch_id`.
4. THE Admin_Frontend SHALL menyediakan halaman detail per Transaksi_Midtrans yang menampilkan riwayat status (audit log), payload Midtrans yang sudah di-mask (server_key tidak boleh muncul), dan tombol "Sinkronisasi Status" yang mengikuti aturan AC 1 (hanya tampak untuk status `pending`).
5. THE Admin_Frontend SHALL menyembunyikan field sensitif (`signature_key`, `server_key`) pada tampilan detail; nilai di-mask menjadi `***`.

### Requirement 11: Logging dan Audit

**User Story:** Sebagai admin DevOps, saya ingin semua interaksi Midtrans tercatat untuk troubleshooting, sehingga saya dapat menelusuri masalah pembayaran tanpa kehilangan jejak data.

#### Acceptance Criteria

1. THE Backend_API SHALL menyimpan tabel `midtrans_transaction_logs` yang mencatat: timestamp, `order_id`, arah komunikasi (`outbound_charge`, `outbound_status`, `inbound_notification`), HTTP status, dan raw payload.
2. THE Backend_API SHALL me-mask field `server_key` dan `signature_key` sebelum menulis raw payload ke log, terlepas dari nilai aktualnya (masking selalu diterapkan, bukan hanya saat nilai cocok dengan MIDTRANS_SERVER_KEY).
3. THE Backend_API SHALL menjalankan pemeriksaan tambahan untuk memastikan kolom raw payload pada log TIDAK mengandung nilai literal `MIDTRANS_SERVER_KEY` saat ini, sebagai safety net atas masking di AC 2; jika substring tersebut ditemukan, log entry SHALL ditolak dan dicatat sebagai incident.
4. THE Backend_API SHALL menyimpan log selama minimal 180 hari, dengan command Artisan terpisah untuk pruning data lebih lama.
5. WHEN sebuah notifikasi Midtrans gagal divalidasi (signature mismatch atau order tidak ditemukan), THE Backend_API SHALL mencatat log dengan level `warning` dan menyertakan `order_id` (jika ada) serta IP pengirim.

### Requirement 12: Coexistence dengan Pembayaran Offline

**User Story:** Sebagai admin keuangan, saya ingin alur pembayaran offline existing tetap utuh dan dapat dibedakan dari pembayaran online, sehingga tidak ada disruption pada operasional saat ini.

#### Acceptance Criteria

1. THE Backend_API SHALL menambahkan kolom `metode` pada tabel `pembayarans` (jika belum ada nilai default) dengan enum `offline` dan `online_midtrans`, default `offline` untuk record existing.
2. THE Backend_API SHALL TIDAK mengubah signature method existing `PembayaranController` untuk pencatatan offline.
3. THE Backend_API SHALL menjamin endpoint pencatatan Pembayaran offline existing tetap menghasilkan `Pembayaran` dengan `metode = offline` dan TIDAK mengubah Transaksi_Midtrans apa pun.
4. WHEN admin menghapus sebuah Pembayaran dengan `metode = online_midtrans`, THE Backend_API SHALL mengembalikan HTTP 409 dengan kode error `CANNOT_DELETE_ONLINE_PEMBAYARAN`, kecuali pengguna memiliki permission `delete-pembayaran` DAN permission tambahan `manage-midtrans-config`.

### Requirement 13: Validasi Mata Uang dan Nominal

**User Story:** Sebagai sistem, saya ingin memastikan transaksi Midtrans hanya dijalankan dalam IDR dan dengan nominal yang konsisten dengan Tagihan dan fee, sehingga tidak ada perbedaan amount antara Handayani dan Midtrans.

#### Acceptance Criteria

1. THE Backend_API SHALL mengirim parameter `currency = IDR` (atau implisit melalui Snap default IDR) pada setiap charge ke Midtrans.
2. THE Backend_API SHALL mengirim `gross_amount` ke Midtrans sebagai integer Rupiah tanpa desimal.
3. THE Backend_API SHALL menjamin `gross_amount = amount_paid + fee_amount` pada saat persist Transaksi_Midtrans dan saat dikirim ke Midtrans; ketidakcocokan ditolak dengan HTTP 422 dan kode error `AMOUNT_INTERNAL_INCONSISTENT`.
4. WHEN Midtrans_Notification diterima, THE Backend_API SHALL memverifikasi bahwa `gross_amount` pada payload sama persis dengan `gross_amount` Transaksi_Midtrans di database.
5. IF `gross_amount` notifikasi berbeda dari `gross_amount` Transaksi_Midtrans tersimpan, THEN THE Backend_API SHALL menolak notifikasi dengan HTTP 422 dan kode error `AMOUNT_MISMATCH`. Penulisan log dengan severity `error` SHALL dilakukan terlebih dahulu dan TIDAK boleh menggagalkan penolakan: jika penulisan log gagal, Backend_API tetap mengembalikan respons error tersebut, sehingga aksi rejection tetap konsisten meskipun side-effect log mengalami partial failure.
6. THE Backend_API SHALL membaca `Fee_Flat` dari config `HANDAYANI_MIDTRANS_FEE_FLAT` (integer, default `4000`) saat inisiasi transaksi; perubahan config setelah Transaksi_Midtrans dibuat TIDAK mempengaruhi `fee_amount` transaksi tersebut (snapshot at create time).

### Requirement 14: Properti Korektnes (Correctness Properties)

**User Story:** Sebagai engineer QA, saya ingin sistem mempertahankan properti korektnes utama di seluruh siklus pembayaran online, sehingga uji properti dapat memvalidasi kontrak inti.

#### Acceptance Criteria

1. THE Backend_API SHALL menjaga invariant: untuk setiap Tagihan, `sum(Pembayaran.jumlah) <= Tagihan.tmp` setelah operasi inisiasi maupun penanganan webhook apa pun.
2. THE Backend_API SHALL menjaga invariant: untuk setiap Transaksi_Midtrans dalam status terminal, jumlah Pembayaran yang terkait melalui `midtrans_order_id` adalah 0 jika status `deny`/`cancel`/`expire`/`failure`, dan tepat 1 jika status `settlement`/`capture`.
3. THE Backend_API SHALL menjaga properti idempotency: pemrosesan satu payload Midtrans_Notification N kali (N >= 1) menghasilkan state database yang identik dengan pemrosesan 1 kali, untuk semua kombinasi status valid.
4. THE Backend_API SHALL menjaga properti signature validity: untuk setiap payload yang diterima endpoint webhook dengan signature_key tidak valid, tidak ada perubahan state pada Transaksi_Midtrans maupun Pembayaran (read-only effect: log saja).
5. THE Backend_API SHALL menjaga properti round-trip nominal: jika Transaksi_Midtrans X bertransisi ke `settlement` dengan `amount_paid = A` dan `fee_amount = F`, maka Pembayaran yang dihasilkan untuk X memiliki `jumlah = A`, dan `Sisa_Tagihan` Tagihan terkait berkurang tepat sebesar A (BUKAN A+F).
6. THE Backend_API SHALL menjaga properti transisi status valid: hanya transisi yang termasuk dalam himpunan transisi sah yang diperbolehkan; himpunan transisi sah didefinisikan eksplisit di design phase, contoh awal: `pending → {settlement, capture, deny, cancel, expire, failure, pending}`, status terminal hanya boleh berlanjut ke `refund`/`partial_refund`.
