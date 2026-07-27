# Gambar 3.5 — Perancangan Algoritma / Proses Bisnis (Mermaid, Sequence Diagram)

> Sumber kebenaran: kode aktual di `backend/app/Services/Midtrans/`, `backend/app/Services/WorkflowService.php`, `backend/app/Services/AutoApprovalService.php`, `backend/app/Services/KenaikanKelasService.php`, `backend/app/Http/Controllers/UserController.php` (OTP), `backend/app/Http/Controllers/AuthController.php` (login), `backend/app/Http/Controllers/RbacController.php` (`userResources`/`userGroups`), `backend/app/Http/Middleware/EndpointPermission.php`, `frontend-v2/app/Helpers/PermissionHelper.php`, `backend/routes/api.php`. Ikut diperiksa silang terhadap `document/tracking-perubahan.md` dan `document/komparasi-proposal-vs-implementasi.md`. Menggantikan Gambar 3.26-3.30 (versi Pillow box+panah) di `document/plan-laporan-ta.md` §Task 3.5.
>
> **Docx laporan TIDAK disentuh** — file ini murni referensi mermaid untuk sub-bab 3.5. Validasi/guard ditulis singkat sebagai `Note` (bukan bercabang `alt/else`) supaya diagram fokus ke alur utama; daftar penolakan lengkap ada di teks penjelasan tiap diagram.

---

## Gambar 3.5.1 — Alur Pembayaran Daring (Midtrans)

```mermaid
sequenceDiagram
    actor S as Siswa/Wali
    participant Sys as Sistem
    participant Snap as Midtrans Snap API
    participant MT as Midtrans (Webhook)

    S->>Sys: Pilih tagihan, klik Bayar Online
    Note over Sys: Endpoint digerbangi RBAC (resource_key `midtrans.pay`, lihat Gambar 3.5.5) sebelum masuk sini
    Note over Sys: Validasi: fitur aktif, kepemilikan tagihan,<br/>sisa tagihan > 0, batas nominal, tidak ada transaksi pending lain
    Sys->>Sys: Hitung biaya admin, simpan transaksi (status: pending)
    Sys->>Snap: Buat Snap transaction
    Snap-->>Sys: token & redirect_url
    Sys-->>S: Tampilkan halaman pembayaran Snap
    S->>Snap: Selesaikan pembayaran

    Note over MT,Sys: Async — konfirmasi terpisah dari sesi pembayaran
    MT->>Sys: POST notifikasi webhook
    Note over Sys: Validasi: webhook aktif, signature valid,<br/>nominal cocok, transisi status diizinkan, idempoten, anti-overpayment
    Sys->>Sys: Update status transaksi
    Sys->>Sys: Catat Pembayaran + update status Tagihan (Lunas/Belum Lunas)
    Sys--)S: Notifikasi pembayaran berhasil
    Sys-->>MT: 200 OK
```

**Penjelasan singkat:** Dua fase — inisiasi Snap (ditolak kalau fitur nonaktif, bukan pemilik tagihan, sudah lunas, nominal di luar batas, atau masih ada transaksi pending; kalau Snap API gagal transaksi ditandai *Failure*) dan konfirmasi webhook (ditolak kalau webhook nonaktif, signature invalid, nominal tidak cocok, transisi status tidak valid, sudah pernah dicatat/idempoten, atau nominal melebihi sisa tagihan/*overpayment*). Pembayaran baru tercatat setelah semua guard lolos.

---

## Gambar 3.5.2 — Alur Persetujuan Pengeluaran (Approval Workflow)

```mermaid
sequenceDiagram
    actor R as Requester
    participant Sys as Sistem
    participant Auto as Auto-Approval
    actor Ap as Approver

    R->>Sys: Submit pengajuan (dari draft/rejected)
    Note over Sys: Tiap endpoint digerbangi RBAC resource_key sendiri (pengeluaran.create/approve/disburse, lihat Gambar 3.5.5)
    Note over Sys: Validasi saldo cabang mencukupi
    Sys->>Sys: Status → submitted, catat log
    Sys->>Auto: Cek pengaturan auto-approval cabang

    alt nominal ≤ threshold & auto-approval aktif
        Auto->>Sys: Set status → approved otomatis
    else perlu approval manual
        Sys--)Ap: Notifikasi pengajuan menunggu
        Ap->>Sys: Kirim keputusan (setuju/tolak + alasan bila tolak)
        Sys->>Sys: Status → approved / rejected, catat log
    end
    Sys--)R: Notifikasi hasil keputusan

    R->>Sys: Ajukan pencairan (dari status approved)
    Note over Sys: Validasi ulang saldo cabang saat ini
    Sys->>Sys: Catat Pengeluaran aktual (periode aktif)
    Sys->>Sys: Status → disbursed, catat log
    Sys--)R: Notifikasi dicairkan
```

**Penjelasan singkat:** Submit dan pencairan sama-sama ditolak kalau saldo cabang tidak mencukupi. Request yang ditolak approver (wajib isi alasan) bisa direvisi dan disubmit ulang oleh requester. Tiap transisi status hanya sah dari status sebelumnya yang tepat dan selalu tercatat di log approval.

---

## Gambar 3.5.3 — Alur Kenaikan Kelas / Kelulusan (Batch, dengan Undo)

```mermaid
sequenceDiagram
    actor P as Pengelola
    participant Sys as Sistem

    P->>Sys: Pilih kelas/siswa, tahun ajaran tujuan, jenis aksi
    Note over Sys: Endpoint digerbangi RBAC (resource_key `kenaikan-kelas.process`/`.undo`, lihat Gambar 3.5.5)
    Note over Sys: Validasi periode tujuan ≠ periode aktif (sumber)

    Note over Sys: Naik Kelas/Tinggal Kelas/Kelulusan = BATCH multi-siswa sekaligus
    Note over Sys: Khusus Naik Kelas: tolak dulu jika tidak ada kelas berikutnya dlm hierarki atau tidak ada siswa eligible
    loop tiap siswa terpilih (Naik Kelas / Tinggal Kelas / Kelulusan)
        Sys->>Sys: Terapkan aksi (skip siswa yang tidak memenuhi syarat)
        Sys->>Sys: Jika periode tujuan = periode aktif, sinkronkan siswa.kelas_id juga (bukan cuma tercatat di riwayat batch)
    end

    Note over Sys: Pindah Jenjang BEDA MEKANISME — 1 request hanya utk 1 siswa, TIDAK ada loop/multi-select
    Sys->>Sys: Validasi siswa berstatus Lulus & transisi jenjang diizinkan (KB→TK, TK→MI), lalu terapkan

    Sys->>Sys: Buat 1 record Batch Promosi + rincian (per siswa, atau 1 baris utk Pindah Jenjang)
    Sys-->>P: Selesai (status: completed)

    Note over P,Sys: Kapan saja setelah batch selesai
    P->>Sys: Ajukan Undo Batch
    Note over Sys: Validasi status batch masih completed
    loop tiap detail batch
        Sys->>Sys: Kembalikan status/jenjang/kelas sebelumnya (skip jika sudah diubah manual)
    end
    Sys->>Sys: Tandai batch: undone
    Sys-->>P: Undo selesai
```

**Penjelasan singkat:** Naik Kelas, Tinggal Kelas, dan Kelulusan menerima daftar siswa (`array $siswaIds`) dan diproses sebagai satu batch — Naik Kelas ditolak di muka kalau tidak ada kelas berikutnya dalam hierarki atau tidak ada siswa eligible sama sekali, lalu per-siswa di-skip kalau sudah punya penempatan di periode tujuan (Naik Kelas), tidak punya kelas di periode sumber (Tinggal Kelas), atau berstatus non-aktif/bukan kelas tertinggi (Kelulusan). **Pindah Jenjang berbeda mekanisme** — service-nya (`processCrossLevelTransfer`) hanya menerima satu `siswa_id` per pemanggilan, bukan array, jadi tidak ada loop multi-siswa; ditolak kalau siswa bukan berstatus Lulus atau transisi jenjangnya tidak diizinkan. Semua 4 aksi tetap dibungkus record Batch Promosi yang sama (agar bisa di-*undo* kolektif lewat mekanisme yang sama); undo melewati siswa yang datanya sudah diubah manual setelah batch berjalan. Kolom `siswa.kelas_id` ikut disinkronkan hanya kalau periode tujuan = periode tahun ajaran aktif — kalau tidak, perubahan cuma tercatat di riwayat (`siswa_kelas`/`batch_promosi_details`) tanpa mengubah kelas siswa yang sedang berjalan (`kelas_id` yang tidak ikut ter-update pernah jadi bug, sudah diperbaiki di commit `55352e9`).

---

## Gambar 3.5.4 — Alur Verifikasi Email via OTP

```mermaid
sequenceDiagram
    actor U as User
    participant Sys as Sistem
    participant Mail as Layanan Email

    U->>Sys: Isi alamat email baru
    Note over Sys: Endpoint hanya perlu login (auth:sanctum) — TIDAK digerbangi RBAC resource_key seperti 3 alur lain
    Note over Sys: Validasi rate-limit (maks 3x/10 menit) & email unik di cabang
    Sys->>Sys: Buat kode OTP 6 digit, simpan (kedaluwarsa 10 menit)
    Sys->>Mail: Kirim email berisi OTP
    Mail-->>U: Terima email OTP
    U->>Sys: Masukkan kode OTP
    Note over Sys: Validasi kode cocok & belum kedaluwarsa
    Sys->>Sys: Set email user + tandai terverifikasi, hapus OTP
    Sys-->>U: Email berhasil terverifikasi
```

**Penjelasan singkat:** Ditolak kalau melebihi rate-limit, email sudah dipakai user lain di cabang, atau kode OTP salah/kedaluwarsa. OTP langsung dihapus setelah verifikasi berhasil agar tidak bisa dipakai ulang. Pola sama dipakai untuk verifikasi email orang tua/wali.

---

## Gambar 3.5.5 — Alur Pengaturan Hak Akses (RBAC Dinamis)

Sistem punya **dua jalur proteksi resource_key yang berdiri sendiri-sendiri**, masing-masing dengan tabel pemetaannya sendiri — bukan satu mekanisme tunggal:

| | Sisi UI (Filament/Livewire) | Sisi Endpoint API |
|---|---|---|
| Tabel pemetaan | `page_permissions` (kolom `permission_name` string) | `permission_endpoints` (kolom `permission_id` FK) |
| Titik cek | `PermissionHelper::hasResource()` dipanggil dari `shouldRegisterNavigation()` (sembunyikan menu) & `mount()` (abort 403 akses langsung via URL) tiap Filament Page | Middleware `endpoint.permission:{resource_key}` di route API |
| Sumber data | Hasil `GET /rbac/user-resources` (`RbacController::userResources()`), di-cache ~60 detik (request-static + Redis via `ApiService::cachedGet()`) | Query langsung `PermissionEndpoint` tiap request, tidak di-cache |
| Superadmin bypass | Eksplisit: `hasRole('superadmin')` → semua `resource_key` aktif dikembalikan | Implisit: lewat `Gate::before` saat `user->can()` dipanggil |

```mermaid
sequenceDiagram
    actor C as User (Browser)
    participant Auth as Sistem (Login)
    participant UI as Frontend (Filament Page/Livewire)
    participant RbacApi as Backend: RbacController
    participant MW as Backend: Middleware Endpoint Permission

    C->>Auth: Login (identifier + password)
    Note over Auth: Validasi kredensial & akun aktif
    Auth->>Auth: Cabut token lama, kumpulkan permission dari role, terbitkan Sanctum token
    Auth-->>C: Token + daftar permission & role (disimpan di session frontend)

    Note over C,UI: === Sisi UI: render menu & buka halaman ===
    C->>UI: Buka aplikasi / navigasi ke halaman tertentu
    UI->>UI: PermissionHelper::hasResource('xxx.view') dipanggil (shouldRegisterNavigation & mount)
    UI->>RbacApi: GET /rbac/user-resources (cached ~60 detik)
    Note over RbacApi: Superadmin → semua resource_key aktif,<br/>user biasa → cocokkan page_permissions.permission_name dengan permission user
    RbacApi-->>UI: Daftar resource_key yang boleh diakses
    alt resource_key ada di daftar
        UI-->>C: Tampilkan menu / render halaman
    else tidak ada
        UI-->>C: Sembunyikan menu dari navigasi, atau abort 403 kalau akses URL langsung
    end

    Note over C,MW: === Sisi API: user klik aksi/submit di halaman ===
    C->>MW: Request ke endpoint tertentu (bawa Bearer token)
    Note over MW: resource_key endpoint ini TIDAK dikirim client — sudah terikat statis di kode route (`middleware('endpoint.permission:xxx')`), dicek ulang dari nol terhadap permission_endpoints (independen dari hasil cek UI di atas)
    MW->>MW: Cari pemetaan resource_key di permission_endpoints (aktif)
    Note over MW: Tidak terdaftar → tolak 403 (strict-by-default), belum terikat permission → izinkan langsung
    MW->>MW: Cek otorisasi via Gate: user->can(permission) — Gate::before meloloskan superadmin di sini
    alt lolos (punya izin / superadmin)
        MW-->>C: Izinkan akses
    else tidak punya izin
        MW-->>C: Tolak 403
    end
```

**Penjelasan singkat:** Alur dimulai dari login (kredensial diverifikasi, token lama dicabut, Sanctum token baru diterbitkan bawa daftar permission). Setelahnya proteksi RBAC jalan di **dua tempat independen**: (1) **UI** — tiap Filament Page manggil `PermissionHelper::hasResource()` yang ambil daftar `resource_key` yang boleh diakses dari endpoint `/rbac/user-resources` (di-cache, dicocokkan lewat tabel `page_permissions`), dipakai buat sembunyiin menu dan nge-block akses URL langsung; (2) **API** — tiap endpoint data (create/update/delete/aksi) digerbangi ulang oleh middleware `endpoint.permission` yang cek independen ke tabel `permission_endpoints` + Gate `can()`. Dua tabel ini **terpisah** dan bisa saja tidak sinkron sesaat (mis. UI cache 60 detik belum refresh) — tapi karena API selalu cek ulang dari nol tiap request, keamanan data tetap terjaga meski UI sempat menampilkan menu yang telat di-hide. Superadmin bypass eksplisit di sisi UI (query langsung semua resource aktif) dan implisit di sisi API (`Gate::before`).
