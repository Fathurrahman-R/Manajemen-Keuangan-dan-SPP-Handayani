# ERD Lengkap dengan Atribut (untuk 3.3 Perancangan Basis Data)

Beda dengan `document/erd-modul-diagram.md` (hanya relasi entitas, tanpa kolom), dokumen ini adalah **ERD klasik lengkap**: tiap kotak entitas berisi daftar atribut (PK/FK/UK) sesuai notasi `erDiagram` Mermaid, dan tiap garis relasi diberi label makna hubungan. Sumber kolom: `document/erd-modul-diagram.md` bagian "Struktur Tabel — 38 Tabel Aplikasi" (diverifikasi dari migrasi real), dikurangi `permission_resources` (transisi) dan `filament_notifications` (drop commit `26e9339`).

Render tiap blok mermaid di viewer yang mendukung (VS Code + extension Mermaid, GitHub, Obsidian) lalu zoom/screenshot per modul untuk dokumen laporan.

---

## 1a. Modul Inti — Data Master (Branch, User, Orang Tua, Siswa)

> Atribut dipangkas ke kolom kunci (PK/FK/UK) — kolom generik non-relasional diringkas jadi baris `...`. Kolom lengkap tetap ada di `document/erd-modul-diagram.md` §Struktur Tabel.

```mermaid

erDiagram
    BRANCHES {
        bigint id PK
        string location
    }
    USERS {
        bigint id PK
        string username UK
        string email UK "unique per branch"
        bigint branch_id FK
        bigint siswa_id FK "nullable, akun portal"
        string lainnya "password, name, is_active, dst"
    }
    AYAH {
        bigint id PK
        string lainnya "nama, pendidikan, pekerjaan, email"
    }
    IBU {
        bigint id PK
        string lainnya "nama, pendidikan, pekerjaan, email"
    }
    WALIS {
        bigint id PK
        string lainnya "nama, pekerjaan, alamat, no_hp, email"
    }
    KATEGORIS {
        bigint id PK
        bigint branch_id FK
        string nama
    }
    KELAS {
        bigint id PK
        enum jenjang "MI, TK, KB"
        bigint branch_id FK
        string lainnya "nama, level"
    }
    SISWAS {
        bigint id PK
        string nis UK
        string nisn UK "nullable"
        bigint ayah_id FK "nullable"
        bigint ibu_id FK "nullable"
        bigint wali_id FK "nullable"
        bigint kelas_id FK "nullable"
        bigint kategori_id FK "nullable"
        bigint branch_id FK
        enum jenjang "TK, MI, KB"
        enum status "Aktif, Lulus, Pindah, Keluar"
        string lainnya "nama, ttl, agama, alamat, dst"
    }

    BRANCHES ||--o{ USERS : "memiliki"
    BRANCHES ||--o{ SISWAS : "memiliki"
    BRANCHES ||--o{ KELAS : "memiliki"
    BRANCHES ||--o{ KATEGORIS : "memiliki"
    AYAH ||--o{ SISWAS : "ayah dari"
    IBU ||--o{ SISWAS : "ibu dari"
    WALIS ||--o{ SISWAS : "wali dari"
    KELAS ||--o{ SISWAS : "menampung"
    KATEGORIS ||--o{ SISWAS : "mengelompokkan"
    SISWAS ||--o| USERS : "punya akun portal"
```

---

## 1b. Modul Inti — Transaksi Keuangan (Tagihan, Pembayaran, Pengeluaran)

```mermaid

erDiagram
    BRANCHES {
        bigint id PK
    }
    SISWAS {
        bigint id PK
        string nis UK
    }
    JENIS_TAGIHANS {
        bigint id PK
        bigint branch_id FK
        bigint tahun_ajaran_id FK "NOT NULL"
        string lainnya "nama, jatuh_tempo, jumlah"
    }
    TAGIHANS {
        char kode_tagihan PK
        bigint jenis_tagihan_id FK
        string nis FK "cascade on delete"
        bigint branch_id FK
        bigint tahun_ajaran_id FK "nullable"
        enum status "Lunas, Belum Lunas, Belum Dibayar"
        char batch_reference "nullable"
    }
    PEMBAYARANS {
        char kode_pembayaran PK
        char kode_tagihan FK
        bigint branch_id FK
        string midtrans_order_id UK "nullable"
        enum metode "offline, online_midtrans"
        string lainnya "tanggal, jumlah, pembayar"
    }
    APP_SETTINGS {
        bigint id PK
        bigint branch_id FK
        string lainnya "profil sekolah"
    }
    PENGELUARANS {
        bigint id PK
        bigint branch_id FK
        bigint tahun_ajaran_id FK "nullable"
        bigint pengeluaran_request_id FK "nullable"
        string lainnya "tanggal, uraian, jumlah"
    }
    TAHUN_AJARANS {
        bigint id PK
    }
    PENGELUARAN_REQUESTS {
        bigint id PK
    }

    BRANCHES ||--o{ JENIS_TAGIHANS : "memiliki"
    BRANCHES ||--o{ APP_SETTINGS : "memiliki"
    BRANCHES ||--o{ PEMBAYARANS : "memiliki"
    BRANCHES ||--o{ PENGELUARANS : "memiliki"
    BRANCHES ||--o{ TAGIHANS : "memiliki"
    SISWAS ||--o{ TAGIHANS : "ditagih lewat nis"
    JENIS_TAGIHANS ||--o{ TAGIHANS : "menentukan jenis"
    TAGIHANS ||--o{ PEMBAYARANS : "dibayar lewat kode_tagihan"
    TAHUN_AJARANS ||--o{ JENIS_TAGIHANS : "berlaku pada periode"
    TAHUN_AJARANS ||--o{ TAGIHANS : "berlaku pada periode"
    TAHUN_AJARANS ||--o{ PENGELUARANS : "berlaku pada periode"
    PENGELUARAN_REQUESTS ||--o{ PENGELUARANS : "direalisasikan sebagai"
```

---

## 2. Modul RBAC Dinamis & Auth

```mermaid

erDiagram
    PERMISSIONS {
        bigint id PK
        string name UK "unique bersama guard_name"
        string guard_name
        string group "nullable"
        string audience "nullable"
        string label "nullable"
    }
    ROLES {
        bigint id PK
        bigint team_foreign_key "opsional"
        string name UK "unique(name, guard_name)"
        string guard_name
    }
    MODEL_HAS_PERMISSIONS {
        bigint permission_id FK
        string model_type
        bigint model_id
    }
    MODEL_HAS_ROLES {
        bigint role_id FK
        string model_type
        bigint model_id
    }
    ROLE_HAS_PERMISSIONS {
        bigint permission_id FK
        bigint role_id FK
    }
    PERMISSION_ENDPOINTS {
        bigint id PK
        bigint permission_id FK "nullable"
        string resource_key UK
        string group "nullable"
        text description "nullable"
        boolean is_active
    }
    PAGE_PERMISSIONS {
        bigint id PK
        string permission_name "nullable"
        string guard_name
        string group "nullable"
        text description "nullable"
        string resource_key UK
        boolean is_active
    }
    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        string name
        string token UK
        text abilities "nullable"
        timestamp expires_at "nullable"
        timestamp last_used_at "nullable"
    }
    PASSWORD_RESET_TOKENS {
        bigint id PK
        string email
        string token UK
        boolean used
        timestamp expires_at
    }
    USERS {
        bigint id PK
    }

    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : "diberikan ke role via"
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : "memiliki permission via"
    PERMISSIONS ||--o{ MODEL_HAS_PERMISSIONS : "diberikan langsung ke user via"
    ROLES ||--o{ MODEL_HAS_ROLES : "diberikan ke user via"
    PERMISSIONS ||--o{ PERMISSION_ENDPOINTS : "dipetakan ke endpoint (nullable)"
    PERMISSIONS ||--o{ PAGE_PERMISSIONS : "dipetakan lewat resource_key"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "punya token Sanctum"
    USERS ||--o{ PASSWORD_RESET_TOKENS : "minta reset lewat email"
```

---

## 3. Modul Tahun Ajaran & Kenaikan Kelas

```mermaid

erDiagram
    TAHUN_AJARANS {
        bigint id PK
        string nama "format YYYY/YYYY"
        date tanggal_mulai
        date tanggal_selesai
        enum status "Aktif, Non-Aktif"
        bigint branch_id FK "unique(nama, branch_id)"
    }
    SISWA_KELAS {
        bigint id PK
        bigint siswa_id FK
        bigint kelas_id FK
        bigint tahun_ajaran_id FK "unique(siswa_id, tahun_ajaran_id)"
    }
    BATCH_PROMOSIS {
        char id PK "uuid"
        enum batch_type "bulk_promotion, individual_promotion*, kelulusan, tinggal_kelas, pindah_jenjang"
        bigint source_tahun_ajaran_id FK
        bigint target_tahun_ajaran_id FK
        bigint kelas_id FK "nullable"
        bigint processed_by FK
        timestamp processed_at
        enum status "completed, undone"
        bigint branch_id FK
    }
    BATCH_PROMOSI_DETAILS {
        bigint id PK
        char batch_id FK
        bigint siswa_id FK
        enum action "naik_kelas, tinggal_kelas, lulus, pindah_jenjang"
        bigint source_kelas_id FK
        bigint target_kelas_id FK "nullable"
        string previous_status
        string previous_jenjang "nullable"
    }
    BRANCHES { bigint id PK }
    SISWAS { bigint id PK }
    KELAS { bigint id PK }
    USERS { bigint id PK }

    BRANCHES ||--o{ TAHUN_AJARANS : "memiliki"
    SISWAS ||--o{ SISWA_KELAS : "punya histori penempatan"
    KELAS ||--o{ SISWA_KELAS : "ditempati pada periode"
    TAHUN_AJARANS ||--o{ SISWA_KELAS : "berlaku pada periode"
    BRANCHES ||--o{ BATCH_PROMOSIS : "memiliki"
    TAHUN_AJARANS ||--o{ BATCH_PROMOSIS : "jadi source & target"
    USERS ||--o{ BATCH_PROMOSIS : "memproses (processed_by)"
    BATCH_PROMOSIS ||--o{ BATCH_PROMOSI_DETAILS : "punya rincian per siswa"
    SISWAS ||--o{ BATCH_PROMOSI_DETAILS : "dipromosikan pada"
    KELAS ||--o{ BATCH_PROMOSI_DETAILS : "jadi kelas source & target"
```

---

## 4. Modul Approval Pengeluaran & Notifikasi

> Catatan: `email_opt_outs` dan `notification_sent_records` tidak punya FK (lookup by string), tetap ditampilkan sebagai entitas mandiri dengan atribut lengkap.

```mermaid

erDiagram
    PENGELUARAN_REQUESTS {
        bigint id PK
        text uraian
        decimal jumlah
        date tanggal_kebutuhan
        string kategori_pengeluaran "nullable"
        string lampiran "nullable"
        enum status "draft, submitted, approved, rejected, disbursed"
        bigint requester_id FK
        bigint branch_id FK
    }
    APPROVAL_LOGS {
        bigint id PK
        bigint pengeluaran_request_id FK
        string previous_status
        string new_status
        bigint user_id FK
        text note "nullable"
    }
    BRANCH_APPROVAL_SETTINGS {
        bigint id PK
        bigint branch_id FK "1:1"
        boolean auto_approval_enabled
        decimal auto_approval_threshold
    }
    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        string type
        string title
        text message
        json data "nullable"
        boolean is_read
    }
    NOTIFICATION_SETTINGS {
        bigint id PK
        bigint branch_id FK UK
        boolean tagihan_baru_enabled
        boolean reminder_enabled
        boolean kwitansi_enabled
        boolean overdue_enabled
        json reminder_days_before "nullable"
        int overdue_interval_days
    }
    NOTIFICATION_LOGS {
        bigint id PK
        bigint branch_id FK
        string recipient_email
        enum notification_type "tagihan_baru, reminder, kwitansi, overdue, workflow"
        string tagihan_kode "nullable"
        bigint pengeluaran_request_id FK "nullable"
        string workflow_event "nullable"
        enum status "sent, failed, skipped"
        string reason "nullable"
        text error_message "nullable"
        timestamp sent_at "nullable"
    }
    EMAIL_OPT_OUTS {
        bigint id PK
        string email
        enum notification_type "tagihan_baru, reminder, kwitansi, overdue, workflow, all"
        string token UK
    }
    NOTIFICATION_SENT_RECORDS {
        bigint id PK
        string tagihan_kode
        enum notification_type "tagihan_baru, reminder, kwitansi, overdue"
        date sent_date
    }
    USERS { bigint id PK }
    BRANCHES { bigint id PK }

    USERS ||--o{ PENGELUARAN_REQUESTS : "mengajukan (requester_id)"
    BRANCHES ||--o{ PENGELUARAN_REQUESTS : "memiliki"
    PENGELUARAN_REQUESTS ||--o{ APPROVAL_LOGS : "dicatat riwayat statusnya di"
    USERS ||--o{ APPROVAL_LOGS : "melakukan aksi approval"
    BRANCHES ||--o| BRANCH_APPROVAL_SETTINGS : "punya pengaturan auto-approval"
    USERS ||--o{ NOTIFICATIONS : "menerima"
    BRANCHES ||--o| NOTIFICATION_SETTINGS : "punya pengaturan notifikasi"
    BRANCHES ||--o{ NOTIFICATION_LOGS : "memiliki"
    PENGELUARAN_REQUESTS ||--o{ NOTIFICATION_LOGS : "memicu notifikasi (nullable)"
```

---

## 5. Modul Import/Export & Midtrans

```mermaid

erDiagram
    IMPORT_BATCHES {
        bigint id PK
        char batch_reference UK
        bigint user_id FK
        enum import_type "siswa, tagihan"
        string file_name
        int total_rows
        int success_count
        int error_count
        enum status "processing, completed, failed, rolled_back"
        text error_message "nullable"
        timestamp rolled_back_at "nullable"
        bigint rolled_back_by FK "nullable"
        bigint branch_id FK
    }
    EXPORT_JOBS {
        bigint id PK
        char job_reference UK
        bigint user_id FK
        string export_type
        json filters "nullable"
        string format
        enum status "processing, completed, failed"
        string file_path "nullable"
        text error_message "nullable"
        timestamp expires_at "nullable"
        bigint branch_id FK
    }
    MIDTRANS_TRANSACTIONS {
        bigint id PK
        string order_id UK
        string kode_tagihan FK "restrict on delete"
        json batch_items "nullable"
        string nis
        bigint amount_paid
        bigint fee_amount
        bigint gross_amount
        char currency "default IDR"
        enum status "pending, settlement, capture, deny, cancel, expire, failure, refund, partial_refund"
        string payment_type "nullable"
        string snap_token "nullable"
        string snap_redirect_url "nullable"
        datetime expired_at
        datetime paid_at "nullable"
        bigint initiator_user_id FK "nullable"
        int branch_id "nullable"
        json last_raw_response "nullable"
    }
    MIDTRANS_TRANSACTION_LOGS {
        bigint id PK
        string order_id "nullable"
        enum direction "outbound_charge, outbound_status, inbound_notification"
        int http_status "nullable"
        longtext raw_payload "nullable"
        string remote_ip "nullable"
    }
    USERS { bigint id PK }
    BRANCHES { bigint id PK }
    TAGIHANS { char kode_tagihan PK }
    PEMBAYARANS { char kode_pembayaran PK }

    USERS ||--o{ IMPORT_BATCHES : "menjalankan"
    BRANCHES ||--o{ IMPORT_BATCHES : "memiliki"
    USERS ||--o{ EXPORT_JOBS : "menjalankan"
    BRANCHES ||--o{ EXPORT_JOBS : "memiliki"
    TAGIHANS ||--o{ MIDTRANS_TRANSACTIONS : "dibayar lewat kode_tagihan"
    USERS ||--o{ MIDTRANS_TRANSACTIONS : "menginisiasi (initiator_user_id)"
    MIDTRANS_TRANSACTIONS ||--o{ MIDTRANS_TRANSACTION_LOGS : "dicatat lognya via order_id"
    MIDTRANS_TRANSACTIONS ||--o| PEMBAYARANS : "sinkron ke kolom midtrans_*"
```

---

**File ini:** `document/erd-lengkap-atribut.md` — pelengkap `document/erd-modul-diagram.md` (yang hanya relasi entitas tanpa atribut). Cara pakai untuk BAB III laporan: render tiap blok mermaid, screenshot per modul, tempel sebagai Gambar 3.x "ERD Modul [nama]".
