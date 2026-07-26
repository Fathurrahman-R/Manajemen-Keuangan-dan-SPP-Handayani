# Rencana Penyusunan Laporan Tugas Akhir (Penyesuaian Proposal → Implementasi)

> **Untuk pelaksana:** Dokumen ini rencana kerja penulisan Laporan TA, bukan plan kode. Tiap task = satu bagian bab. Verifikasi tiap task = `validate.py` lolos + render PDF dicek visual. Langkah pakai checkbox (`- [ ]`).

**Goal:** Menyesuaikan seluruh isi Laporan TA (yang sudah menyalin proposal verbatim) terhadap implementasi nyata sistem, berdasarkan `komparasi-proposal-vs-implementasi.md` + `tracking-perubahan.md` + diff kode `37ff85a9`→HEAD.

**Arsitektur kerja:** Edit `word/document.xml` pada folder unpacked template (`scratchpad/work/unpacked`), splice per-block pakai `chunker.py`, remap bookmark id, rezip via `rezip.py`, validasi `validate.py --original`. Sumber fakta = 2 dokumen tracking + diff git. Tidak ada data/sitasi/screenshot dikarang.

**Tech stack:** Python (chunker/splice/rezip/validate), git (diff UI baseline vs HEAD), WebSearch (kandidat sitasi 2.2).

## Global Constraints

- Bahasa Indonesia ilmiah formal; istilah asing *italic*.
- **General, non-teknis**: tidak menyebut nama class/file/method/kolom DB/potongan kode. Bicara fitur & alur dari sudut pengguna.
- Font Times New Roman 12, spasi 1.5, justified, indent alinea 1 cm (ikut style paragraf proposal yang sudah ada).
- Penomoran tabel/gambar per-bab (`Tabel 3.x`, `Gambar 3.x`), caption Word field — jangan ketik manual di Daftar Tabel/Gambar.
- Sitasi IEEE `[n]` urut kemunculan; Daftar Pustaka lewat field Mendeley yang sudah ada — sitasi baru hanya yang bisa diverifikasi.
- **Larangan mengarang**: hasil uji, angka, screenshot, sitasi — semua dari sumber nyata atau ditandai `PLACEHOLDER — user isi`.
- File kerja: `scratchpad/work/unpacked/word/document.xml`. Output: `document/3202316079-...-Laporan Tugas Akhir.docx`.
- Tabel 3.3 Jadwal & Lampiran Turnitin: JANGAN diisi otomatis (toleransi plagiarisme 20% vs 30% konflik — user konfirmasi ke pembimbing).

---

## Fakta kunci yang WAJIB tercermin (anti-halusinasi)

Referensi cepat sumber, dipakai lintas task:

- **Auto-create akun siswa DIHAPUS** (commit `a71bdd0`, tracking §1.6/§7). Akun siswa sekarang **manual** lewat halaman Manajemen Akun Siswa. → koreksi 1.1 latar belakang (paragraf sisipan lama SALAH).
- **Switch-sibling portal DIHAPUS** (commit `52a8f25`, §7). Satu akun portal = satu siswa.
- **Opsi "semua periode" dashboard admin DIHAPUS** (commit `7ceff1a`). All-time jadi widget terpisah.
- **Tampilan tagihan/pembayaran: tabel → card** (commit `50e38cb`, §1.9).
- **Metodologi**: proposal klaim Waterfall murni; commit aktual pola iteratif/checkpoint (§Lampiran commit "checkpoint: ..."). Komparasi §(b) sudah tandai.
- **Pengujian**: rencana Black Box manual + tambahan automated PHPUnit/Pest + property-based (`eris`). Rekap `test-case-blackbox.md`: **102/103 PASS, 1 SKIPPED arsitektural (TC-PORTAL-008), 0 FAIL**.
- **Dependency nyata baru** (§5): `spatie/laravel-permission`, `maatwebsite/excel`, `midtrans/midtrans-php`, `predis/predis`+Redis, `barryvdh/laravel-dompdf` (kwitansi PDF, sudah ada sejak baseline), Docker+Mailpit+ngrok, `giorgiosironi/eris`.
- **Arsitektur tambah**: Sanctum (ganti token custom), Redis cache-aside dashboard, Docker 9-service, OPcache. (§8.3d, §8.5)
- **RBAC**: bukan Spatie standar saja — lapisan `resource_key` dinamis, binding via UI (§1.3, §4).
- **ERD**: baseline 13 tabel → 39 tabel sekarang (§9). Tapi **3.3 di-SKIP** (user mau cleanup unused table dulu).
- **Diff UI sidebar** (`AdminPanelProvider` `37ff85a9`→HEAD): navigasi statik role-check → dinamis permission-based; +~13 menu baru; branding dinamis; dark mode; breadcrumbs; komponen `sidebar-scroll-preserve`.

---

## Keputusan terbuka (resolve saat mulai Phase 3 Task 3.4)

**3.4 wireframe** — SELESAI. 22 wireframe schematic (box+label, generated via Pillow) di-generate & di-embed: 18 halaman baru (RBAC, Cabang, User, Akun Siswa, Tahun Ajaran, Kenaikan Kelas, Approval, Log Notifikasi, Pengaturan Notifikasi, Midtrans, Pengeluaran Request, Profil, 5× Portal Siswa terpisah, Landing publik) + 4 revisi (Tagihan card, Pembayaran card, Dashboard, Detail Siswa). Gambar 3.4-3.25.

**3.5 flowchart** — SELESAI. 5 alur inti dipilih (Pembayaran, Approval Pengeluaran, Kenaikan Kelas, Verifikasi OTP, RBAC) & di-generate via Pillow (box+panah+diamond keputusan). Gambar 3.26-3.30.

---

## Phase 0 — Persiapan sitasi & aset

### Task 0.1: Kandidat sitasi 2.2 (WebSearch → approval user)

**Files:** tidak ada (riset). Output: daftar kandidat di chat.

**Konsep butuh sumber ilmiah:** (1) Laravel Sanctum / token-based API auth, (2) Redis / in-memory caching, (3) OTP / email verification, (4) Docker / containerization, (5) RBAC dinamis berbasis resource/policy.

- [x] Step 1: WebSearch tiap konsep, cari jurnal/artikel/buku yang kredibel (ada penulis, tahun, venue/DOI).
- [x] Step 2: Susun tabel kandidat: konsep → sitasi IEEE lengkap → link verifikasi.
- [x] Step 3: Tampilkan ke user, minta approve per item. Semua 5 di-approve.
- [x] Step 4: Kunci daftar final sitasi [15]-[19] untuk dipakai di Task 2.2 + entri Daftar Pustaka.

**Verifikasi:** user eksplisit approve tiap sitasi sebelum masuk dokumen.

---

## Phase 1 — BAB I Pendahuluan

### Task 1.1: Koreksi Latar Belakang (auto-create akun siswa → manual)

**Files:** Modify `scratchpad/work/unpacked/word/document.xml` (paragraf sisipan INS2 yang ditulis sesi lalu).

**Masalah:** Sisipan latar belakang menyebut "pembuatan akun siswa secara otomatis beserta kredensial awalnya". Fakta: auto-create dihapus (`a71bdd0`), akun manual.

- [x] Step 1: Cari paragraf sisipan INS2 via grep+chunker.
- [x] Step 2: Ganti frasa jadi mencerminkan pembuatan akun manual oleh pengelola.
- [x] Step 3: Rezip + validate (lolos, digabung Phase 1 batch).
- [ ] Step 4: Render PDF halaman BAB I, cek visual paragraf. **(belum — render PDF butuh LibreOffice, tidak tersedia di mesin ini)**
- [x] Step 5: Konfirmasi ke user (via ringkasan progres).

### Task 1.2: Perluas Batasan Masalah (1.3)

**Files:** Modify `document.xml` (blok daftar Batasan Masalah proposal, sudah tersalin).

**Tambahan** (modul di luar 6 poin proposal, ditulis sebagai batasan/ruang lingkup nyata): pengelolaan tahun ajaran & kenaikan kelas/kelulusan; halaman informasi publik lembaga; keamanan akun mandiri (verifikasi email, lupa/ganti kata sandi, pembaruan email, preferensi notifikasi, nonaktif akun). Diframe sebagai perluasan lingkup yang dikerjakan.

- [x] Step 1-3: Selesai — 2 poin baru ditambahkan (tahun ajaran/kenaikan kelas; halaman publik + keamanan akun mandiri). Rezip+validate lolos.

**Catatan:** Rumusan Masalah & Tujuan (1.2/1.4) TIDAK diubah kecuali user minta — batasan cukup menampung perluasan lingkup.

### Task 1.3: Catatan penyesuaian Metodologi (1.6)

**Files:** Modify `document.xml` (akhir sub-bab Metodologi Waterfall).

**Tambahan:** 1 paragraf yang mengakui pelaksanaan berjalan iteratif (tahapan Waterfall tetap kerangka, tapi implementasi–pengujian–perbaikan berlangsung berulang), dan pengujian menggabungkan Black Box manual + pengujian otomatis. Non-teknis.

- [x] Step 1-3: Selesai — paragraf penyesuaian iteratif + pengujian ganda ditambahkan.

---

## Phase 2 — BAB II Dasar Teori

### Task 2.1: Isi kelebihan/kekurangan Tabel 2.1 (baris "Sistem yang diusulkan")

**Files:** Modify `document.xml` (sel kosong Tabel Perbandingan baris 4).

**Isi (fitur UTAMA saja):**
- Kelebihan: menggabungkan RBAC, dashboard monitoring, pembayaran daring (payment gateway), approval pengeluaran, dan notifikasi dalam satu sistem manajemen keuangan terpadu (pembeda dari 3 penelitian terdahulu yang parsial).
- Kekurangan: cakupan terbatas pada keuangan SPP satu lembaga; notifikasi hanya email; bergantung layanan pihak ketiga (Midtrans/SMTP).

- [x] Step 1-3: Selesai — 2 sel diisi (fitur utama saja, tanpa modul pendukung).

### Task 2.2: Tambah Dasar Teori konsep baru (pakai sitasi Task 0.1)

**Files:** Modify `document.xml` (akhir sub-bab 2.2, sebelum "Hubungan dengan Tugas Akhir"). **Prasyarat: Task 0.1 selesai + approved.**

**Sub-konsep baru (hanya yang sitasinya di-approve):** Autentikasi API berbasis token (Sanctum), Caching (Redis), Verifikasi email/OTP, Kontainerisasi (Docker), RBAC dinamis berbasis resource. Tiap konsep 1 paragraf + sitasi IEEE.

- [x] Step 1-3: Selesai — 5 subseksi baru (Sanctum [15], Redis [16], OTP [17], Docker [18], RBAC dinamis [19]) + kalimat penutup "Hubungan dengan Tugas Akhir".
- [x] Step 4: **Temuan tak terduga**: Daftar Pustaka laporan ternyata masih placeholder dummy template (field BIBLIOGRAPHY bawaan Word, bukan hasil salin proposal — proposal pakai field Mendeley terpisah yang tidak ikut tersalin di sesi sebelumnya). Diperbaiki: field rapuh itu diganti 19 entri statis (14 proposal verbatim + 5 baru), supaya tidak balik ke isi dummy saat user klik "Update Field" di Word.
- [x] Step 5: Rezip + validate lolos.

---

## Phase 3 — BAB III Perancangan Sistem

### Task 3.1: Perluas Kebutuhan Fungsional (Tabel 3.1)

**Files:** Modify `document.xml` (Tabel 3.1, tambah baris).

**Baris fungsional baru:** pengelolaan tahun ajaran & kenaikan kelas/kelulusan; halaman informasi publik; keamanan akun mandiri (verifikasi email, lupa/ganti kata sandi, pembaruan email, preferensi notifikasi); nonaktif/aktif akun; pembayaran beberapa tagihan sekaligus; persetujuan otomatis per cabang; penerbitan kwitansi digital.

- [x] Step 1-2: Selesai — 7 baris kebutuhan fungsional baru ditambahkan.

### Task 3.2: Perbarui Arsitektur Sistem (3.2)

**Files:** Modify `document.xml` (sub-bab 3.2, setelah penjelasan komponen existing).

**Tambahan komponen** (non-teknis, level arsitektur): autentikasi token (Sanctum) menggantikan token lama; lapisan cache (Redis) untuk mempercepat penyajian data; lingkungan terkontainer (Docker) untuk konsistensi pengembangan/penyajian. **SKIP** detail "endpoint gabungan dashboard". Diagram arsitektur (Gambar 3.2) proposal: tandai perlu update jika user mau, atau tambah paragraf penjelas komponen baru tanpa mengubah gambar.

- [x] Step 1: Selesai — paragraf Sanctum/Redis/Docker ditambahkan, endpoint dashboard di-skip.
- [ ] Step 2: Gambar 3.2 belum diupdate (proposal punya diagram asli, dibiarkan apa adanya untuk saat ini).
- [x] Step 3: Rezip + validate lolos.

### Task 3.3: Perancangan Basis Data — **SKIP**

Di-skip atas permintaan user (menunggu pembersihan unused table & dead code). Biarkan placeholder template 3.3 apa adanya. Catat sebagai TODO di ringkasan akhir.

### Task 3.4: Perancangan Antarmuka (3.4) — wireframe per halaman

**Files:** Modify `document.xml` (sub-bab 3.4). **Prasyarat: keputusan terbuka 3.4 di-resolve.**

**Halaman baru (wireframe baru):** Manajemen RBAC, Manajemen Cabang, Manajemen User, Manajemen Akun Siswa (+modal kredensial), Tahun Ajaran, Kenaikan Kelas (+detail batch), Pengaturan Approval, Log Notifikasi, Pengaturan Notifikasi, Transaksi Midtrans (+detail), Pengeluaran (form+approval), Portal Siswa (Beranda/Tagihan/Riwayat/Status/Profil), Landing publik, Profil (EditProfile).
**Halaman berubah (revisi wireframe):** Tagihan (tabel→card + filter multi-select), Pembayaran (tabel→card), Dashboard (opsi semua-periode dihapus, widget baru), Detail Siswa/Wali (+field email ortu). Plus perubahan global: sidebar dinamis, dark mode, breadcrumbs.

- [ ] Step 1: Resolve keputusan terbuka (konsolidasi + sumber aset wireframe).
- [ ] Step 2: Per halaman: 1 paragraf pengantar (menyebut `Gambar 3.x`) + caption gambar + deskripsi komponen antarmuka (non-teknis).
- [ ] Step 3: Sisipkan aset wireframe (user-provided atau generate mockup) + registrasi image rels/content-types (pola sama seperti 3 diagram proposal).
- [ ] Step 4: Rezip + validate + render + konfirmasi bertahap (per kelompok halaman, jangan sekaligus 14).

### Task 3.5: Perancangan Proses Bisnis/Algoritma (3.5)

**Files:** Modify `document.xml` (isi placeholder 3.5 yang kosong).

**Alur inti (flowchart/narasi, non-teknis):** (1) alur pembayaran daring Midtrans (inisiasi → bayar → konfirmasi otomatis via webhook → status ter-update); (2) alur persetujuan pengeluaran (ajukan → menunggu → setuju/tolak → cairkan, + persetujuan otomatis per cabang); (3) alur kenaikan kelas/kelulusan batch (+ undo); (4) alur verifikasi email OTP; (5) alur pengaturan hak akses (RBAC pointer). Pilih 2-4 alur terpenting (konfirmasi mana ke user), tiap alur = flowchart + penjelasan.

- [ ] Step 1: Konfirmasi ke user alur mana yang wajib (hindari BAB III membengkak).
- [ ] Step 2: Per alur: paragraf pengantar (sebut `Gambar 3.x`) + flowchart + penjelasan langkah.
- [ ] Step 3: Sisipkan flowchart (aset user atau generate). Registrasi image.
- [ ] Step 4: Rezip + validate + render + konfirmasi.

### Task 3.6: Lengkapi Spesifikasi Teknologi (3.6)

**Files:** Modify `document.xml` (sub-bab 3.6 — bahasa/framework/basis data/tools).

**Tambahan** (dependency nyata §5, disebut sebagai tools/library): paket RBAC (Spatie), library import/export Excel (Maatwebsite), SDK Midtrans, cache Redis (client Predis), library PDF kwitansi (DomPDF), Docker + Mailpit + ngrok (lingkungan), library pengujian (Pest/eris). Sanctum (auth). Ditulis sebagai daftar teknologi pendukung, ringkas.

- [x] Step 1-2: Selesai — 7 entri tools baru ditambahkan (Sanctum, Spatie, Redis, Maatwebsite Excel, DomPDF, Docker).

### Task 3.7: Perbarui Rencana Pengujian (3.7) — pengujian INTI, non-teknis

**Files:** Modify `document.xml` (sub-bab 3.7 + Tabel 3.2 Skenario). Sumber: `test-case-blackbox.md` (ambil **inti** saja).

**Tambahan:** sebut pengujian menggunakan Black Box (functional/validation/integration) DAN pengujian otomatis sebagai pelengkap. Tabel 3.2 skenario: pertahankan yang inti (login, RBAC, pembayaran, notifikasi, approval, dashboard, import/export) + tambah skenario inti modul baru (tahun ajaran/kenaikan kelas, keamanan akun, verifikasi email) — bahasa non-teknis (tanpa nama endpoint/TC-code).

- [x] Step 1-4: Selesai — 7 baris skenario inti baru (Portal, Tahun Ajaran, Verifikasi Email, Keamanan Akun x2, Pembayaran Sekaligus, Persetujuan Otomatis) + paragraf metode ganda.

---

## Phase 4 — BAB IV Hasil dan Pembahasan (bab baru)

### Task 4.1-4.4: SEMUA SELESAI

- [x] 4.1 Gambaran Umum Sistem — arsitektur final + PLACEHOLDER untuk spek hardware/versi (user isi).
- [x] 4.2 Implementasi Sistem — 8 paragraf per kelompok fitur, tiap paragraf ada slot `[Gambar 4.x — PLACEHOLDER]` untuk screenshot.
- [x] 4.3 Pengujian Sistem — Tabel 4.1 dengan angka rekap nyata (102/103 PASS, 1 SKIPPED, 0 FAIL, per 8 kelompok fitur).
- [x] 4.4 Analisis Hasil dan Evaluasi — capaian tujuan BAB I + keterbatasan (email-only, satu lembaga, 1 skenario arsitektural).
- [x] Rezip + validate lolos.

---

## Phase 5 — BAB V Penutup (bab baru)

### Task 5.1-5.2: SEMUA SELESAI

- [x] 5.1 Kesimpulan — capaian tujuan TA + deviasi metodologi (iteratif) + cakupan membengkak + keterbatasan notifikasi email-only. **sectPr halaman (header/footer/page numbering start=43) dipertahankan utuh saat replace.**
- [x] 5.2 Saran — WhatsApp, cleanup skema DB, perluasan multi-lembaga.
- [x] Rezip + validate lolos.

---

## Phase 6 — Verifikasi akhir

### Task 6.1: Update field & cek menyeluruh

- [x] Step 1: Heading/caption pakai style Word asli (Heading1/2, Caption + SEQ field) — dicek via dump struktur, urut BAB I-V lengkap.
- [x] Step 2: `validate.py --original` final lolos (572→1015 paragraf, 0 error).
- [ ] Step 3: Render PDF visual — **tidak bisa dilakukan, mesin ini tidak punya LibreOffice/Word CLI**. User perlu buka di Word dan klik "Update Field" (Ctrl+A lalu F9) agar TOC/Daftar Tabel/Daftar Gambar dan seluruh nomor Gambar 3.x/caption SEQ ter-render final.
- [x] Step 4: Diserahkan + ringkasan TODO user (lihat bawah).

---

## Phase 7 — Audit & Perbaikan Format (sesi lanjutan, sesuai panduan_formatting.md)

- [x] **Font/size**: dikonfirmasi Times New Roman 12pt konsisten seluruh dokumen (via inheritance style `Normal`→`NormalWeb`, tidak ada override liar). Tidak perlu perbaikan.
- [x] **Indent alinea**: ditemukan 82 paragraf tersebar (BAB I-IV) pakai indent 720/360/357/709/698/363/11 twips, seharusnya 567 (1cm) per panduan. Semua dinormalkan ke 567.
- [x] **Margin halaman**: ditemukan 7 section (`sectPr`) pakai margin atas 3cm (seharusnya 4cm) — termasuk 1 section dengan margin terbalik total (top/right/bottom/left tertukar). Berasal dari section break yang tertanam di tengah paragraf salinan proposal asli (bukan dari editan sesi ini). Semua dinormalkan ke top/left=4cm, bottom/right=3cm.
- [x] **Penomoran halaman (romawi/arab, posisi)**: diaudit via header/footer XML — mekanisme sudah benar dari template asli (header berisi field PAGE rata-kanan untuk halaman biasa; footer field PAGE rata-tengah untuk halaman pertama tiap bab, via `titlePg`). Tidak disentuh, sudah sesuai panduan.
- [x] **Penomoran & caption tabel/gambar**: dikonfirmasi semua tabel (2.1, 3.1, 3.2, 4.1) dan gambar (3.1-3.30) pakai SEQ field per-bab, caption tabel di atas/gambar di bawah, kalimat pengantar menyebut nomor — semua sesuai.
- [x] **Heading multilevel numbering**: ditemukan bug besar dari TEMPLATE ASLI — heading 1.1-1.6 (BAB I) pakai auto-numbering Word, tapi 1.7 dan SELURUH BAB II/III + 4.1/4.2 (12 heading) diketik manual sebagai teks statis (bukan dari editan sesi ini, murni bawaan template/proposal). Diperbaiki: dibuat 3 definisi multilevel list baru di `numbering.xml` (kloning pola abstractNum id=20 milik BAB I, ganti prefix lvlText jadi "2.%1"/"3.%1"/"4.%1"), lalu semua 12 heading dikonversi ke auto-numbering + prefix angka manual dihapus dari teks. Sempat ketemu 1 bug urutan elemen XML (`numIdMacAtCleanup` harus di akhir setelah semua `<w:num>`), sudah diperbaiki dan divalidasi lolos.

## Phase 8 — 3.3 Perancangan Basis Data (sesi lanjutan, setelah cleanup dead code selesai)

- [x] **Update `tracking-perubahan.md` & `komparasi-proposal-vs-implementasi.md`**: dokumentasi commit `26e9339` (audit dead code: `TagihanController::lunas()`, `KenaikanKelasService::processIndividualPromotion()`, klaster `DetailWali`, tabel `filament_notifications` di-drop) ditambahkan sebagai Bagian 10 baru di tracking-perubahan.md; jumlah tabel aplikasi dikoreksi 39→38 di §9.5. komparasi.md dapat catatan singkat — dikonfirmasi tidak ada baris komparasi yang berubah kesimpulan.
- [x] **ERD**: 5 diagram (Modul Inti/Baseline, RBAC Dinamis & Auth, Tahun Ajaran & Kenaikan Kelas, Approval Pengeluaran & Notifikasi, Import/Export & Midtrans) di-generate via PIL (gaya sama seperti wireframe/flowchart sebelumnya), transkripsi langsung dari relasi yang sudah diverifikasi di `tracking-perubahan.md` §9.1/9.2. Gambar 3.31-3.35.
- [x] **Struktur Tabel — full detail**: 38 tabel aplikasi (kolom lengkap, mengadaptasi data terverifikasi di §9.3/9.4, dikurangi `permission_resources` transisi dan `filament_notifications` yang sudah di-drop), masing-masing sebagai tabel Word dengan caption SEQ. Tabel 3.3-3.40.
- [x] Placeholder template lama (instruksi generik "Sertakan diagram ERD...", "Jelaskan field-field...") dihapus total, diganti konten nyata.
- [x] Validasi XSD lolos (2051 paragraf, 0 error — sempat false-positive charmap decode error karena locale terminal, hilang setelah paksa UTF-8).

## TODO tersisa untuk user

1. **Screenshot BAB IV** — 8 slot `[Gambar 4.x — PLACEHOLDER]` di 4.2 perlu diganti tangkapan layar asli.
2. **Update Field di Word** — buka file, Ctrl+A → F9, agar TOC/Daftar Tabel/Daftar Gambar dan penomoran Gambar 3.x (35 gambar) + Tabel 3.x (40 tabel) ter-render final.
3. **Spesifikasi hardware/software** — placeholder di 4.1 perlu diisi versi PHP/server sungguhan.
4. **Lampiran** — Tabel 3.3 Jadwal & hasil cek Turnitin belum disentuh (di luar scope; toleransi plagiarisme 20% vs 30% masih perlu dikonfirmasi ke pembimbing, lihat `panduan_formatting.md`).
5. **Gambar 3.2 Arsitektur Sistem** — masih diagram asli proposal, belum diperbarui menampilkan Sanctum/Redis/Docker (hanya dijelaskan via teks).

---

## Self-Review (cek plan vs keputusan user)

- Semua keputusan user tercakup: 1.1 ✓, 1.3 ✓, 2.1 (kelebihan/kekurangan, fitur utama) ✓, 2.2 (sitasi jurnal/buku) ✓, 3.1 ✓, 3.2 (skip dashboard endpoint) ✓, 3.3 skip ✓, 3.4 (wireframe per halaman baru/berubah) ✓, 3.5/3.6 ✓, 3.7 (blackbox inti, non-teknis) ✓, BAB IV ✓, BAB V ✓.
- Anti-halusinasi: koreksi auto-create ✓, angka uji nyata ✓, sitasi approval ✓, screenshot PLACEHOLDER ✓.
- Keputusan terbuka ditandai jelas (3.4 konsolidasi+aset, 3.5 pilih alur, 3.2 diagram opsional).
- Urutan: Phase 0 (sitasi) sebelum 2.2; keputusan 3.4 sebelum eksekusi 3.4.
