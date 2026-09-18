# Kuesioner User Acceptance Test (Beta Test)

Sistem Informasi Pembayaran SPP Yayasan Pendidikan Islam Handayani

Instrumen pengujian penerimaan pengguna untuk Bab IV Laporan Tugas Akhir. Setiap berkas berisi satu tabel penilaian untuk satu kelompok responden, memakai skala SB / B / KB / TB.

## Daftar berkas

| Berkas | Role | Jumlah pernyataan | Responden yang disarankan |
|---|---|---|---|
| [uat-a-admin.md](uat-a-admin.md) | Admin | 34 | Petugas tata usaha / bendahara unit KB, TK, MI |
| [uat-b-kepala-yayasan.md](uat-b-kepala-yayasan.md) | Kepala Yayasan | 23 | Kepala yayasan dan pengurus yang menyetujui pengeluaran |
| [uat-c-developer.md](uat-c-developer.md) | Developer | 19 | Pengelola teknis sistem / operator RBAC |
| [uat-d-siswa.md](uat-d-siswa.md) | Siswa dan Wali Murid | 20 | Wali murid pengguna portal siswa |

Role `superadmin` tidak dibuatkan kuesioner karena tidak memiliki pengguna nyata di lapangan; hak aksesnya bersifat bypass penuh dan pengujiannya sudah tercakup pada pengujian black-box.

## Struktur tiap tabel

Setiap tabel disusun dua bagian:

1. **Pernyataan fitur** — satu pernyataan untuk satu menu atau fitur, mengikuti menu yang benar-benar tampil pada sidebar role tersebut.
2. **Pernyataan usability** — tujuh dimensi baku di akhir tabel: tampilan, tata letak, kemudahan akses, kemudahan dipelajari, kemudahan dipahami, kesesuaian urutan menu dengan alur kerja, dan performa.

Butir "nyaman digunakan pada tampilan mobile" hanya dipakai pada tabel Siswa dan Wali Murid, karena portal siswa memang dirancang untuk telepon genggam sedangkan panel admin dipakai pada komputer. Pada tiga role internal, posisi butir tersebut diisi butir kesesuaian urutan menu dengan alur kerja.

## Cara menghitung hasil

Perhitungan memakai indeks persentase gaya Skala Likert:

- Bobot: SB = 4, B = 3, KB = 2, TB = 1.
- Skor per pernyataan = Σ (jumlah responden pemilih skala × bobot skala).
- Skor maksimal = jumlah responden × 4.
- Persentase kelayakan = (skor per pernyataan ÷ skor maksimal) × 100%.

Rentang kategori interpretasi (misalnya 76–100% = Sangat Baik) dikonfirmasi dulu ke pembimbing atau Panduan Penulisan TA, karena angkanya dapat berbeda antar buku panduan.
