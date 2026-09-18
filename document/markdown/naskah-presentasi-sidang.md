# Naskah Presentasi Sidang Tugas Akhir

**Muhammad Fathurrahman Rizki Perdana — 3202316079**
Target waktu: **10 menit**. Deck: `document/ppt/Sidang TA - Muhammad Fathurrahman Rizki Perdana.pptx`

---

## Cara memakai naskah ini

Jangan hafal seluruh paragraf. Hafal **kalimat jangkar** saja — satu per slide, ditandai 🔑. Kalimat jangkar itu pembuka slide; begitu keluar dari mulut, sisanya mengalir dari kata kunci.

Sisi kanan tiap bagian ada **kata kunci** — itu yang perlu diingat, bukan kalimatnya. Kalau lupa satu kata kunci, lewati. Penguji tidak memegang naskahmu.

Latihan yang berhasil: baca penuh 2x, lalu **tutup naskah dan ucapkan hanya kalimat jangkar 16 slide berurutan**. Kalau ke-16 jangkar lancar tanpa melihat, kamu siap.

---

## SLIDE 1 — Sampul · 30 detik

🔑 **"Bismillah. Assalamualaikum warahmatullahi wabarakatuh."**

> Selamat pagi Bapak Ibu penguji. Perkenalkan, saya Muhammad Fathurrahman Rizki Perdana, NIM 3202316079, dari Program Studi D3 Teknik Informatika.
>
> Pada kesempatan ini saya akan memaparkan hasil Tugas Akhir saya yang berjudul **Pengembangan Sistem Informasi Keuangan SPP Berbasis Web dengan RBAC dan Integrasi Payment Gateway pada LPA Handayani**, di bawah bimbingan Bapak Suheri, S.T., M.Cs.
>
> Izinkan saya memulai.

`Kata kunci: salam → nama, NIM, prodi → judul lengkap → pembimbing → izin mulai`

> ⚠️ Judul ucapkan **pelan dan lengkap**. Ini satu-satunya bagian yang wajib persis.

---

## SLIDE 2 — Latar Belakang · 60 detik

🔑 **"LPA Handayani sebenarnya sudah punya sistem keuangan SPP berbasis web. Tapi dari observasi dan wawancara, saya menemukan empat kendala."**

> **Pertama, pembayaran masih manual.** Wali siswa transfer atau bayar langsung ke sekolah, lalu pengelola mencatat ulang satu per satu. Akibatnya pencatatan sering terlambat dan rawan salah.
>
> **Kedua, hak akses tidak terkontrol.** Admin maupun bendahara bisa mencatat transaksi keuangan apa pun, tanpa pembatasan peran yang jelas.
>
> **Ketiga, pengeluaran tidak melalui persetujuan.** Transaksi pengeluaran langsung tercatat, tanpa verifikasi dari pihak yang berwenang. Ini berisiko pada kontrol internal keuangan.
>
> **Keempat, pemantauan belum terpadu.** Belum ada dashboard keuangan, belum ada notifikasi otomatis, dan belum ada fitur import maupun export data.
>
> Empat kendala inilah yang menjadi dasar pengembangan sistem ini.

`Kata kunci: sudah punya sistem TAPI 4 kendala → manual · akses · approval · pantau`

> 💡 Hitung dengan jari saat menyebut "pertama, kedua, ketiga, keempat". Membantu kamu dan penguji sama-sama tidak kehilangan hitungan.

---

## SLIDE 3 — Rumusan Masalah · 35 detik

🔑 **"Dari empat kendala tadi, saya menetapkan empat rumusan masalah."**

> Bagaimana **mengembangkan** sistem informasi keuangan SPP berbasis web agar pengelolaan keuangan lebih efektif dan terstruktur.
>
> Bagaimana **menerapkan Role Based Access Control** untuk mengatur hak akses pengguna.
>
> Bagaimana **mengintegrasikan payment gateway** untuk mendukung pembayaran SPP secara daring.
>
> Dan bagaimana **menyediakan fitur pendukung** berupa approval workflow, dashboard monitoring, notifikasi otomatis, serta import dan export data.

`Kata kunci: kembangkan · RBAC · payment gateway · fitur pendukung`

> 💡 Slide ini boleh cepat. Cukup baca inti tiap poin, jangan dibaca kata per kata dari layar.

---

## SLIDE 4 — Tujuan · 35 detik

🔑 **"Dari empat rumusan tersebut, diturunkan lima tujuan tugas akhir."**

> Satu, **mengembangkan** sistem informasi keuangan SPP berbasis web.
> Dua, **menerapkan RBAC** untuk mengatur hak akses sesuai peran.
> Tiga, **mengimplementasikan approval workflow** pada pencatatan transaksi keuangan.
> Empat, **mengintegrasikan payment gateway Midtrans**.
> Dan lima, **menyediakan dashboard monitoring, notifikasi email, serta import dan export data**.
>
> Kelima tujuan ini yang nanti saya evaluasi capaiannya di bagian hasil.

`Kata kunci: kembangkan · RBAC · approval · Midtrans · fitur pendukung`

> ⚠️ Kalimat penutup "kelima tujuan ini yang nanti saya evaluasi" **penting** — itu janji yang kamu tepati di slide 15. Jangan dilewatkan.

---

## SLIDE 5 — Batasan Masalah · 25 detik

🔑 **"Agar penelitian tetap terarah, ditetapkan sembilan batasan masalah."**

> Pada intinya, penelitian ini **mengembangkan sistem yang sudah berjalan, bukan membangun sistem baru dari nol**. Cakupannya terbatas pada pembayaran SPP dan pencatatan pengeluaran operasional lembaga.
>
> Payment gateway yang dipakai adalah Midtrans, notifikasi hanya melalui email, dan sistem juga mencakup pengelolaan tahun ajaran, kenaikan kelas, halaman informasi publik, serta keamanan akun mandiri.

`Kata kunci: bukan dari nol · SPP + pengeluaran · Midtrans · email saja · + tahun ajaran & publik`

> 💡 **Jangan bacakan sembilan-sembilannya.** Sebut inti, biarkan penguji yang membaca layar. Ini slide tercepat.

---

## SLIDE 6 — Manfaat · 20 detik

🔑 **"Manfaatnya menyasar tiga pihak."**

> Bagi **LPA Handayani**, pengelolaan keuangan menjadi lebih aman, terstruktur, dan mudah dipantau.
>
> Bagi **kampus**, menjadi referensi akademik pengembangan sistem informasi keuangan berbasis web di lingkungan pendidikan.
>
> Bagi **saya sendiri sebagai peneliti**, menambah pengalaman merancang sistem dengan pengaturan hak akses dan pembayaran digital.

`Kata kunci: lembaga · kampus · peneliti`

---

## SLIDE 7 — Metode · 40 detik

🔑 **"Metode yang saya gunakan adalah SDLC dengan pendekatan Waterfall, melalui enam tahap."**

> Dimulai dari **pengumpulan data** lewat wawancara, observasi, dan studi literatur. Lalu **analisis sistem** untuk mengidentifikasi masalah dan kebutuhan. Kemudian **perancangan** arsitektur, basis data, dan antarmuka.
>
> Berikutnya **implementasi** menggunakan Laravel, dengan backend berbasis API dan frontend Filament. Lalu **pengujian** Black Box secara manual, dilengkapi pengujian otomatis. Dan terakhir **dokumentasi** dalam bentuk laporan tugas akhir.
>
> Satu hal yang perlu saya sampaikan terbuka: **dalam pelaksanaannya, tahapan ini tidak murni berjalan satu arah.** Kebutuhan baru bermunculan justru saat implementasi, sehingga analisis dan perancangan sempat ditinjau ulang. Hal ini juga saya tuliskan apa adanya di laporan.

`Kata kunci: 6 tahap → data · analisis · rancang · implementasi · uji · dokumen ‖ lalu: jujur, nyatanya iteratif`

> ⚠️ **Paragraf terakhir jangan dihilangkan.** Penguji sering menyerang celah "katanya Waterfall, kok desainnya berubah-ubah?". Kalau kamu yang menyebut duluan, pertanyaan itu mati sebelum ditanyakan.

---

## SLIDE 8 — Hasil Implementasi · 50 detik

🔑 **"Sistem berhasil dibangun sebagai dua aplikasi Laravel yang terhubung lewat REST API, dengan satu basis data bersama."**

> Angka kuncinya: **dua aplikasi web**, **lima peran pengguna**, **103 izin akses**, dan **38 tabel basis data**.
>
> Wujud implementasinya empat. **RBAC** dengan lima peran, di mana pemetaan izin disimpan di basis data sehingga bisa diubah tanpa mengubah kode. **Approval pengeluaran** bertahap dari diajukan, disetujui atau ditolak, sampai dicairkan. **Pembayaran Midtrans Snap** beberapa kanal, bisa melunasi banyak tagihan sekaligus, dengan status diperbarui lewat webhook. Serta **fitur pendukung**: dashboard monitoring, empat jenis notifikasi email, dan import export berbasis file Excel.
>
> Selanjutnya izinkan saya menunjukkan tampilan aslinya.

`Kata kunci: 2 aplikasi · 5 peran · 103 izin · 38 tabel ‖ RBAC · approval · Midtrans · pendukung`

> 💡 Kalimat "izinkan saya menunjukkan tampilan aslinya" adalah **jembatan ke demo**. Hafalkan.

---

## SLIDE 9 — Demo 01 · RBAC · 45 detik

🔑 **"Ini halaman Manajemen RBAC."**

> Di sini terdapat **lima peran** dengan **103 izin akses** yang bisa dicentang per peran.
>
> Yang ingin saya tekankan: **pemetaan izin terhadap halaman dan endpoint disimpan di basis data**, bukan ditulis di dalam kode. Artinya kalau lembaga ingin mengubah siapa boleh mengakses apa, cukup diubah lewat antarmuka ini, tanpa perlu menyentuh kode program.
>
> Dan pemeriksaan izinnya **berlapis** — bukan hanya menyembunyikan tombol di tampilan, tapi diperiksa di sisi server pada setiap permintaan.

`Kata kunci: 5 peran, 103 izin → pemetaan di DB bukan kode → dicek di server, berlapis`

> ⚠️ Kata **"diperiksa di sisi server"** wajib keluar. Ini pembeda RBAC beneran vs RBAC tampilan doang, dan penguji keamanan pasti mencarinya.

---

## SLIDE 10 — Demo 02 · Approval · 45 detik

🔑 **"Ini alur persetujuan pengeluaran."**

> Pengeluaran tidak langsung masuk laporan keuangan. Statusnya bertahap: **diajukan, lalu disetujui atau ditolak, baru kemudian dicairkan**.
>
> Selama statusnya belum disetujui, **nilainya tidak dihitung dalam laporan keuangan**. Jadi laporan hanya memuat pengeluaran yang benar-benar sudah disahkan.
>
> Di sebelah kanan ini pengaturan **persetujuan otomatis per cabang**. Untuk pengeluaran rutin bernilai kecil, misalnya di bawah ambang tertentu, sistem menyetujui otomatis. Tujuannya supaya kontrol tetap ada untuk pengeluaran besar, tanpa menghambat operasional harian.

`Kata kunci: diajukan → disetujui/ditolak → dicairkan ‖ belum disetujui = tidak dihitung ‖ auto-approve per cabang untuk nominal kecil`

---

## SLIDE 11 — Demo 03 · Midtrans · 50 detik

🔑 **"Ini proses pembayaran daring menggunakan Midtrans."**

> Wali siswa memilih tagihan, lalu membayar lewat **Snap Midtrans** dengan beberapa pilihan kanal, seperti QRIS atau virtual account. **Biaya administrasi dihitung menyesuaikan kanal** yang dipilih.
>
> Beberapa tagihan bisa **dilunasi sekaligus dalam satu transaksi** — ini penting karena banyak wali yang punya tunggakan beberapa bulan atau punya lebih dari satu anak di lembaga yang sama.
>
> Setelah pembayaran berhasil, Midtrans mengirim **webhook** ke sistem. Sistem **memverifikasi tanda tangan** webhook tersebut, memastikan benar berasal dari Midtrans, baru memperbarui status tagihan secara otomatis dan mengirim kwitansi digital ke email.

`Kata kunci: Snap, banyak kanal · biaya admin per kanal · banyak tagihan sekaligus · webhook + verifikasi tanda tangan · kwitansi email`

> ⚠️ **"memverifikasi tanda tangan"** wajib disebut. Kalau tidak, penguji akan bertanya "berarti siapa saja bisa memalsukan notifikasi pembayaran?"

---

## SLIDE 12 — Demo 04 · Dashboard · 45 detik

🔑 **"Ini dashboard monitoring keuangan."**

> Bagian atas berisi **kartu ringkasan**: total tagihan, total pemasukan, total pengeluaran, dan saldo cabang.
>
> Di bawahnya ada **grafik tren** untuk memantau pergerakan keuangan, serta **daftar tunggakan** sehingga siswa yang belum melunasi langsung terlihat.
>
> Sebelumnya informasi ini harus dikumpulkan manual satu per satu. Sekarang pengelola bisa melihat kondisi keuangan lembaga **sewaktu-waktu, dalam satu tampilan**.

`Kata kunci: kartu ringkasan · grafik tren · daftar tunggakan ‖ dulu manual, kini satu layar`

---

## SLIDE 13 — Demo 05 · Publik & Portal · 45 detik

🔑 **"Terakhir, halaman publik dan portal siswa."**

> Di sebelah kiri, **halaman informasi publik** yang memuat profil, informasi, dan lokasi lembaga. Halaman ini sekaligus menjadi pintu masuk menuju portal pembayaran.
>
> Di sebelah kanan, **portal siswa**. Siswa atau wali dapat melihat daftar tagihannya, memilih tagihan yang akan dibayar, dan menelusuri riwayat pembayaran.
>
> Akun siswa dibuatkan oleh pengelola, dilengkapi **verifikasi email, pemulihan kata sandi, serta pengaturan preferensi notifikasi**.

`Kata kunci: kiri = wajah lembaga + pintu masuk · kanan = tagihan, bayar, riwayat · akun: verifikasi email, reset sandi`

---

## SLIDE 14 — Hasil Pengujian · 45 detik

🔑 **"Pengujian dilakukan dengan metode Black Box Testing."**

> Seluruh skenario yang saya susun pada BAB III dijabarkan menjadi **102 kasus uji**, mencakup **delapan kelompok fitur**.
>
> Hasilnya, **seluruh 102 kasus uji berstatus PASS**, dengan tingkat keberhasilan **100 persen**, **tanpa satu pun kasus yang gagal**.
>
> Kelompok terbesar adalah **approval dan notifikasi** dengan 19 kasus uji, disusul pembayaran daring dengan 15 kasus uji.
>
> Selain pengujian manual, saya juga menjalankan **pengujian otomatis** yang dieksekusi berulang setiap kali ada perubahan kode.

`Kata kunci: 102 kasus · 8 kelompok · 100% PASS · 0 FAIL · terbesar approval 19 · plus uji otomatis`

> 💡 Ucapkan **"102"** dan **"100 persen"** pelan dan jelas. Ini angka yang paling diingat penguji.

---

## SLIDE 15 — Kesimpulan · 45 detik

🔑 **"Kesimpulannya, seluruh lima tujuan tugas akhir telah tercapai."**

> Sistem berhasil dibangun sebagai dua aplikasi terhubung REST API. **RBAC** diterapkan dengan lima peran dan 103 izin akses. **Approval** pengeluaran berjalan bertahap sampai pencairan. **Midtrans** terintegrasi dan terbukti berfungsi lewat transaksi nyata di lingkungan sandbox. **Fitur pendukung** berupa dashboard, notifikasi, serta import export tersedia. Dan **pengujian** 102 kasus uji seluruhnya berstatus PASS.
>
> Namun sistem ini juga punya **keterbatasan** yang perlu saya sampaikan. Notifikasi baru mencakup email, belum menjangkau WhatsApp. Cakupannya masih satu lembaga dengan tiga cabang. Masih ada satu tabel sisa yang belum dibersihkan. Dan sistem belum ditempatkan pada server produksi.
>
> Keterbatasan ini menjadi **saran untuk pengembangan selanjutnya**.

`Kata kunci: 5 tujuan tercapai → 6 poin singkat ‖ LALU 4 keterbatasan: email saja · 1 lembaga · 1 tabel sisa · belum produksi`

> ⚠️ **Sebutkan keterbatasan dengan tenang, jangan minta maaf.** Menyebut batas sendiri = tanda peneliti yang sadar ruang lingkup. Menyembunyikannya = mengundang penguji menggali.

---

## SLIDE 16 — Penutup · 15 detik

🔑 **"Demikian pemaparan saya. Terima kasih atas perhatian Bapak dan Ibu penguji."**

> Saya persilakan apabila ada pertanyaan dan masukan.
>
> Wassalamualaikum warahmatullahi wabarakatuh.

---

## Ringkasan 16 kalimat jangkar

Hafalkan **ini saja**. Ucapkan berurutan tanpa melihat naskah — kalau lancar, kamu siap.

1. Bismillah. Assalamualaikum warahmatullahi wabarakatuh.
2. LPA Handayani sebenarnya sudah punya sistem — tapi ada **empat kendala**.
3. Dari empat kendala tadi, saya tetapkan **empat rumusan masalah**.
4. Dari empat rumusan tersebut, diturunkan **lima tujuan**.
5. Agar tetap terarah, ditetapkan **sembilan batasan**.
6. Manfaatnya menyasar **tiga pihak**.
7. Metodenya **SDLC Waterfall, enam tahap**.
8. Sistem dibangun sebagai **dua aplikasi terhubung REST API**.
9. Ini halaman **Manajemen RBAC**.
10. Ini **alur persetujuan pengeluaran**.
11. Ini **pembayaran daring Midtrans**.
12. Ini **dashboard monitoring keuangan**.
13. Terakhir, **halaman publik dan portal siswa**.
14. Pengujian dengan **Black Box Testing**.
15. Kesimpulannya, **seluruh lima tujuan tercapai**.
16. Demikian pemaparan saya. Terima kasih.

Polanya menaik rapi: **4 kendala → 4 rumusan → 5 tujuan → 9 batasan → 3 manfaat → 6 tahap**. Kalau ragu di tengah, ingat rantai angka itu.

---

## Kalau blank di tengah

Jangan diam dan jangan minta maaf. Pakai salah satu kalimat penyambung ini — sambil otak mengejar:

- "Baik, saya lanjutkan ke bagian berikutnya."
- "Pada slide ini, poin utamanya adalah…" *(lalu baca judul slide di layar)*
- "Seperti yang terlihat pada tampilan ini…" *(untuk slide demo — layar akan menuntunmu)*

Slide demo adalah **jaring pengaman**. Kalau hafalan buyar, cukup jelaskan apa yang terlihat di layar. Tidak ada yang tahu itu bukan rencana awal.

---

## Sebelum masuk ruang sidang

- Buka **Presenter View** (`Alt` + `F5`) — setiap slide sudah ada catatan pembicaranya.
- Cek deck jalan di laptop yang dipakai, jangan hari-H baru tahu font berubah.
- Latih **sekali penuh dengan timer**. Kalau tembus 12 menit, potong slide 5 dan 6.
- Minum air sebelum masuk. Bicara 10 menit tanpa jeda bikin tenggorokan kering di menit ke-6.
