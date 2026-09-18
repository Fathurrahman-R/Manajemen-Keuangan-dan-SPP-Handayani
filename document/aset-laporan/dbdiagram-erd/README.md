# ERD versi dbdiagram.io (DBML)

Versi DBML dari enam ERD yang ada di `../mermaid-erd/`. Bedanya dengan mermaid:
garis relasi menempel tepat pada baris kolom PK dan FK, bukan sekadar
menyambung antar kotak tabel.

## Isi

| Berkas | Modul | Tabel | Relasi |
|---|---|---:|---:|
| `data-master.dbml` | Data Master | 8 | 10 |
| `transaksi-keuangan.dbml` | Transaksi Keuangan | 9 | 15 |
| `rbac.dbml` | RBAC & Autentikasi | 10 | 8 |
| `approval-notifikasi.dbml` | Approval & Notifikasi | 10 | 10 |
| `import-export-midtrans.dbml` | Impor, Ekspor, Midtrans | 8 | 13 |
| `tahun-ajaran-kenaikan-kelas.dbml` | Tahun Ajaran & Kenaikan Kelas | 8 | 18 |

## Cara membuat diagram

1. Buka <https://dbdiagram.io/d>.
2. Hapus contoh bawaan di panel kiri, lalu tempel seluruh isi salah satu berkas `.dbml`.
3. Diagram muncul otomatis di panel kanan. Geser kotak tabel untuk merapikan tata letak.
4. Ekspor lewat menu **Export** — tersedia PNG, PDF, dan SVG.

Untuk kebutuhan Laporan TA, pilih PNG lalu resample ke 300 DPI pada ukuran
tampilnya, mengikuti standar gambar yang dipakai di dokumen laporan.

## Dasar penyusunan

Isi berkas disusun dari file `.mmd` di `../mermaid-erd/`, lalu setiap tabel
diperiksa silang terhadap migrasi di `backend/database/migrations/`. Beberapa
hal yang berbeda dari versi mermaid:

- **Tipe data dikoreksi.** `siswas.tanggal_lahir` adalah `date` bukan string,
  `siswas.tahun_diterima` adalah `year`, dan `pengeluaran_requests.uraian`
  adalah `varchar(255)` bukan `text`.
- **Panjang kolom dicantumkan**, mis. `varchar(100)`, `char(36)`, `decimal(12,2)`.
- **Unique key komposit ditulis eksplisit** di blok `indexes`. Contoh:
  `users.email` unik per `branch_id`, bukan unik global; `kelas` unik pada
  kombinasi `jenjang + branch_id + level + nama`.
- **Relasi lebih lengkap.** Setiap foreign key yang benar-benar ada di migrasi
  ikut digambar selama kedua tabelnya hadir di berkas itu — termasuk relasi
  `branch_id` yang di versi mermaid sebagian dihilangkan.
- **Perilaku `ON DELETE` dicantumkan** pada relasi (`cascade`, `set null`,
  `restrict`).

## Catatan penting

- Relasi `tagihans.nis` menunjuk ke `siswas.nis`, **bukan** `siswas.id`.
  Mengubah NIS seorang siswa memutus keterkaitan tagihannya.
- Kolom `created_at` dan `updated_at` tidak ditampilkan agar diagram tetap
  terbaca, mengikuti konvensi versi mermaid. Pengecualian pada tabel yang
  memang hanya punya `created_at` (`approval_logs`, `notifications`,
  `midtrans_transaction_logs`) — di sana kolomnya ditulis karena bermakna.
- Kolom FK yang tabel tujuannya berada di ERD modul lain hanya ditulis sebagai
  kolom biasa dan diberi catatan rujukan, tanpa garis relasi.
- Beberapa relasi tidak punya foreign key di level basis data dan hanya berupa
  keterkaitan logis. Ini ditandai dengan komentar
  `// Relasi logis tanpa foreign key di level basis data`, mis.
  `pembayarans.midtrans_order_id` ke `midtrans_transactions.order_id`.
- `midtrans_transactions.branch_id` bertipe `int` tanpa foreign key, sesuai
  migrasi aslinya.

## Validasi

Sintaks keenam berkas sudah diverifikasi dengan parser resmi DBML:

```bash
npm i @dbml/core --no-save
node -e "const {Parser}=require('@dbml/core');const fs=require('fs');fs.readdirSync('.').filter(f=>f.endsWith('.dbml')).forEach(f=>{Parser.parse(fs.readFileSync(f,'utf8'),'dbml');console.log('OK',f)})"
```
