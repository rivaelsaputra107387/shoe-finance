Shoe Workshop — Sistem Laporan Keuangan

**SHOE WORKSHOP** Sistem Informasi Laporan Keuangan

# **PRODUCT REQUIREMENTS DOCUMENT (PRD)**

_Sistem Informasi Laporan Keuangan_

| **Nama Proyek**   | Sistem Laporan Keuangan Shoe Workshop |
| ----------------------- | ------------------------------------- |
| **Versi Dokumen** | 1.0                                   |
| **Tanggal**       | 14 Juli 2026                          |
| **Disusun oleh**  | System Analyst                        |
| **Status**        | Draft untuk Review                    |

1

Shoe Workshop — Sistem Laporan Keuangan

## Daftar Isi

2

Shoe Workshop — Sistem Laporan Keuangan

## 1. Ringkasan Produk

Produk ini adalah aplikasi web akuntansi internal untuk Shoe Workshop yang mengotomasi siklus akuntansi penuh: dari input jurnal umum, posting ke buku besar, penyusunan neraca lajur, hingga laporan keuangan akhir (Laba Rugi, Perubahan Ekuitas, Neraca, Arus Kas) dan jurnal penutup. Dibangun dengan Laravel (backend + view) dan MySQL (database).

## <u>2. Tujuan Produk & Metrik Keberhasilan</u>

| **Tujuan**                         | **Metrik Ke**           | **berhasilan (Success Metric)**                                 |
| ---------------------------------------- | ----------------------------- | --------------------------------------------------------------------- |
| Menggantikan pencatatan manual Excel     | 100% transgo-live             | aksi tercatat via sistem dalam 1 bulan pertama                        |
| Mempercepat penyusunan laporan           | Laporan keperiode ditu        | uangan bulanan tersedia < 5 menit setelahtup                          |
| Meningkatkan akurasi                     | 0 selisih (undigenerate s     | balance) debet-kredit pada laporan yangistem                          |
| Transparansi ke Owner                    | Owner dapabantuan sta         | t mengakses dashboard & laporan tanpaff finance                       |
| 3. Target Pengguna & Peran**Role** | (Roles)**Deskripsi**    | **Akses Utama**                                                 |
| Admin                                    | Mengelola sistemsecara teknis | Master COA, user & role, buka/tutupperiode, semua fitur Staff Finance |
| Staff Finance                            | Operasional harianpencatatan  | Input jurnal, lihat buku besar, generatelaporan, export               |
| Owner/Manajemen                          | Pengambil keputusanbisnis     | Lihat dashboard & seluruh laporan (read-only), export laporan         |

## <u>3. Target Pengguna & Peran (Roles)</u>

## <u>4. Daftar Fitur</u>

| **Kode** | **Fitur**              | **Deskripsi Singkat**                    | **Prioritas (MoSCoW)** |
| -------------- | ---------------------------- | ---------------------------------------------- | ---------------------------- |
| F-01           | Master Chart of Account(COA) | CRUD akun,kategori, saldonormal, posisilaporan | Must                         |
| F-02           | Manajemen PeriodeAkuntansi   | Buka/tutup periode,saldo awal perperiode       | Must                         |
| F-03           | Input Jurnal Umum            | Form inputtransaksi denganvalidasidebet=kredit | Must                         |
| F-04           | Posting Jurnal               | Approval/postingjurnal dari draft kefinal      | Must                         |
| F-05           | Buku Besar Otomatis          | Rekap mutasi &saldo per akun, perperiode       | Must                         |
| F-06           | Neraca Lajur                 | Worksheet otomatisdari saldo bukubesar         | Must                         |
| F-07           | Laporan Laba Rugi            | Generate otomatisdari akun                     | Must                         |

3

Shoe Workshop — Sistem Laporan Keuangan

| **Kode** | **Fitur**                          | **Deskripsi Singkat**                            | **Prioritas (MoSCoW)** |
| -------------- | ---------------------------------------- | ------------------------------------------------------ | ---------------------------- |
|                |                                          | pendapatan & beban                                     |                              |
| F-08           | Laporan Perubahan Ekuitas                | Generate otomatisdari laba/rugi &modal                 | Must                         |
| F-09           | Laporan Neraca                           | Generate otomatis(aset = liabilitas +ekuitas)          | Must                         |
| F-10           | Laporan Arus Kas                         | Generateberdasarkankategorioperasi/investasi/pendanaan | Must                         |
| F-11           | Jurnal Penutup & NeracaSetelah Penutupan | Otomasi prosesclosing akunnominal                      | Must                         |
| F-12           | Export PDF/Excel                         | Semua laporandapat diunduh                             | Should                       |
| F-13           | Dashboard Ringkasan                      | Grafik & angkakunci untuk Owner                        | Should                       |
| F-14           | Manajemen User & Role                    | CRUD user, assignrole                                  | Must                         |
| F-15           | Audit Trail                              | Log siapamengubah apa dankapan                         | Should                       |
| F-16           | Notifikasi Validasi                      | Peringatan jikajurnal tidakseimbang / periodeterkunci  | Could                        |

## 5. User Stories per Fitur (Contoh Utama)

- Sebagai Staff Finance, saya ingin menginput jurnal umum dengan validasi otomatis debet=kredit, agar tidak terjadi kesalahan pencatatan.
- Sebagai Staff Finance, saya ingin melihat buku besar per akun secara otomatis, agar tidak perlu menghitung manual di Excel.
- Sebagai Admin, saya ingin menutup periode akuntansi, agar data historis tidak bisa diubah lagi setelah laporan difinalisasi.
- Sebagai Owner, saya ingin melihat dashboard ringkasan (laba rugi, kas, dan neraca) kapan saja, agar dapat mengambil keputusan cepat tanpa menunggu laporan dari staff.
- Sebagai Admin, saya ingin melihat log audit trail, agar dapat menelusuri siapa yang mengubah transaksi tertentu.

## 6. Di Luar Cakupan (Non-Goals) Fase 1

- Payroll dan perhitungan pajak otomatis.
- Integrasi perbankan langsung (open banking).
- Multi-cabang/multi-entitas.

## <u>7. Rencana Rilis (Release Plan)</u>

| **Rilis** | **Cakupan Fitur**       | **Target**                                |
| --------------- | ----------------------------- | ----------------------------------------------- |
| R1 - MVP        | F-01, F-02, F-03, F-04, F-05, | Fondasi pencatatan jurnal & buku besar berjalan |

4

Shoe Workshop — Sistem Laporan Keuangan

| **Rilis**    | **Cakupan Fitur**           | **Target**                                        |
| ------------------ | --------------------------------- | ------------------------------------------------------- |
|                    | F-14                              |                                                         |
| R2 - Laporan Inti  | F-06, F-07, F-08, F-09, F-10,F-11 | Seluruh siklus akuntansi otomatis (sesuai cakupanpenuh) |
| R3 - Penyempurnaan | F-12, F-13, F-15, F-16            | Export, dashboard, audit trail, notifikasi              |

Catatan: sesuai keputusan awal proyek, seluruh modul laporan (R1+R2) dikerjakan sekaligus dalam satu siklus pengembangan, bukan bertahap per modul; pembagian rilis di atas tetap berguna sebagai referensi urutan pengujian dan QA.

5
