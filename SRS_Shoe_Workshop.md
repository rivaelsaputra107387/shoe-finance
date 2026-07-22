Shoe Workshop — Sistem Laporan Keuangan 

**SHOE WORKSHOP** Sistem Informasi Laporan Keuangan 

# **SOFTWARE REQUIREMENTS SPECIFICATION (SRS)** 

_Sistem Informasi Laporan Keuangan_ 

|**Nama Proyek**|Sistem Laporan Keuangan Shoe Workshop|
|---|---|
|**Versi Dokumen**|1.0|
|**Tanggal**|14 Juli 2026|
|**Disusun oleh**|System Analyst|
|**Referensi Standar**|IEEE 830 (disederhanakan)|



1 

Shoe Workshop — Sistem Laporan Keuangan 

## Daftar Isi 

2 

Shoe Workshop — Sistem Laporan Keuangan 

## 1. Pendahuluan 

### 1.1 Tujuan Dokumen 

Dokumen ini menjabarkan kebutuhan fungsional dan non-fungsional secara teknis untuk tim developer dalam membangun Sistem Laporan Keuangan Shoe Workshop menggunakan Laravel dan MySQL, sebagai turunan langsung dari BRD dan PRD. 

### 1.2 Ruang Lingkup Sistem 

Sistem mencakup pencatatan jurnal umum, buku besar, neraca lajur, laporan laba rugi, laporan perubahan ekuitas, neraca, laporan arus kas, jurnal penutup, manajemen periode akuntansi, manajemen user & role, serta audit trail. 

### <u>1.3 Definisi & Istilah</u> 

|**Istilah**|**Definisi**|
|---|---|
|COA|Chart of Account / Daftar Akun|
|Jurnal Umum|Pencatatan transaksi harian berbasis debet-kredit|
|Posting|Proses mengunci jurnal dari status draft menjadi final|
|Neraca Lajur|Worksheet penyesuaian saldo sebelum laporan akhir disusun|
|Periode Akuntansi|Rentang waktu pelaporan (umumnya bulanan)|
|Closing Period|Proses penutupan periode agar data historis terkunci|



### 1.4 Referensi 

- Business Requirements Document (BRD) - Sistem Laporan Keuangan Shoe Workshop, v1.0 

- Product Requirements Document (PRD) - Sistem Laporan Keuangan Shoe Workshop, v1.0 

- File referensi struktur akuntansi: LAPORAN_KEUANGAN_SHOE_WORKSHOP.xlsx 

## 2. Deskripsi Umum 

### 2.1 Perspektif Produk 

Sistem berupa aplikasi web berbasis Laravel (MVC) dengan database MySQL, diakses melalui browser oleh 3 peran pengguna: Admin, Staff Finance, dan Owner/Manajemen. Sistem berdiri sendiri (standalone) pada fase 1, dengan struktur data yang dirancang agar dapat diintegrasikan ke sistem lain di masa depan. 

### 2.2 Karakteristik Pengguna 

- Admin: memahami struktur akuntansi dasar dan pengelolaan sistem, akses penuh. 

- Staff Finance: memahami pencatatan akuntansi (debet/kredit), pengguna harian utama. 

- Owner/Manajemen: awam teknis, membutuhkan tampilan ringkas dan mudah dibaca (dashboard, laporan). 

### 2.3 Batasan Umum 

- Framework backend: Laravel (versi LTS terbaru saat pengembangan). 

- Database: MySQL 8.x. 

- Aplikasi web responsif, diakses melalui desktop browser (prioritas utama) dan tablet/mobile (sekunder). 

### 2.4 Asumsi dan Ketergantungan 

- Server hosting mendukung PHP versi yang kompatibel dengan Laravel yang dipilih. 

- Struktur akun (COA) awal akan dimigrasikan dari file Excel yang sudah berjalan. 

## 3. Kebutuhan Fungsional (Functional Requirements) 

### <u>3.1 Modul Master Data</u> 

|**ID**|**Kebutuhan**|**Detail Input/Proses/Output**|
|---|---|---|
|FR01|Sistem menyediakan CRUD Chart of Account|Input: kode akun, nama akun, tipe, saldo|
|-|(COA)|normal, pos laporan. Proses: validasi kode|



3 

Shoe Workshop — Sistem Laporan Keuangan 

|**ID**|**Kebutuhan**|**Detail Input/Proses/Output**|
|---|---|---|
|||unik. Output: daftar akun aktif|
|FR-02|Sistem menyediakan manajemen Periode<br>Akuntansi|Input: nama periode, tanggal mulai/selesai.<br>Proses: cegah tumpang tindih tanggal.<br>Output: status periode open/closed|
|FR-03|Sistem menyediakan input Saldo Awal per akun<br>per periode|Input: akun, nominal, posisi debet/kredit.<br>Output: saldo awal tersimpan sebagai basis<br>buku besar|
|FR-04|Sistem menyediakan manajemen User & Role|Input: nama, email, role. Proses: hash<br>password, assign role. Output: user aktif<br>dengan hak akses sesuai role|



### <u>3.2 Modul Transaksi</u> 

|**ID**|**Kebutuhan**|**Detail Input/Proses/Output**|
|---|---|---|
|FR-05|Sistem menyediakan form input Jurnal Umum<br>multi-baris|Input: tanggal, keterangan, baris<br>akun+debet/kredit. Proses: validasi total<br>debet = total kredit sebelum simpan.<br>Output: jurnal berstatus draft|
|FR-06|Sistem menyediakan proses posting jurnal|Proses: ubah status draft menjadi posted,<br>jurnal terposting tidak dapat diedit langsung<br>(harus jurnal koreksi). Output: saldo akun<br>ter-update|
|FR-07|Sistem mencegah input transaksi pada periode<br>berstatus closed|Proses: validasi status periode sebelum<br>simpan jurnal. Output: pesan error jika<br>periode tertutup|



### <u>3.3 Modul Pelaporan</u> 

|**ID**|**Kebutuhan**|**Detail Input/Proses/Output**|
|---|---|---|
|FR-08|Sistem menghasilkan Buku Besar per akun|Proses: agregasi saldo awal + seluruh<br>mutasi jurnal posted pada periode<br>terpilih. Output: mutasi & saldo akhir per<br>akun|
|FR-09|Sistem menghasilkan Neraca Lajur|Proses: menyusun saldo seluruh akun dari<br>buku besar ke format worksheet. Output:<br>neraca lajur per periode|
|FR-10|Sistem menghasilkan Laporan Laba Rugi|Proses: total pendapatan dikurangi total<br>beban dari akun pos_laporan=laba_rugi.<br>Output: laba/rugi bersih periode|
|FR-11|Sistem menghasilkan Laporan Perubahan Ekuitas|Proses: modal awal + laba/rugi berjalan -<br>prive/dividen. Output: modal akhir<br>periode|
|FR-12|Sistem menghasilkan Laporan Neraca|Proses: menyusun akun<br>pos_laporan=neraca (aset, liabilitas,<br>ekuitas). Output: neraca dengan validasi<br>total aset = liabilitas + ekuitas|
|FR-13|Sistem menghasilkan Laporan Arus Kas|Proses: klasifikasi mutasi kas berdasar<br>kategori operasi/investasi/pendanaan<br>pada akun. Output: laporan arus kas<br>periode|
|FR-14|Sistem menghasilkan Jurnal Penutup & Neraca Setelah<br>Penutupan|Proses: nolkan akun nominal<br>(pendapatan/beban) ke akun modal saat|



4 

Shoe Workshop — Sistem Laporan Keuangan 

|**ID**|**Kebutuhan**|**Detail Input/Proses/Output**|
|---|---|---|
|||closing period. Output: jurnal penutup<br>otomatis + neraca akhir periode|
|FR-15|Sistem menyediakan export laporan ke PDF dan Excel|Proses: render laporan ke format file.<br>Output: file terunduh|
|FR-16|Sistem menyediakan dashboard ringkasan|Proses: agregasi angka kunci (kas, laba<br>rugi, total aset). Output: grafik & kartu<br>ringkasan untuk Owner|



|3.4 Modul Keamanan & Audit|
|---|



|**ID**|**Kebutuhan**|**Detail Input/Proses/Output**|
|---|---|---|
|FR-17|Sistem membatasi akses fitur berdasarkan role|Proses: middleware otorisasi per route.<br>Output: akses ditolak (403) jika role tidak<br>sesuai|
|FR-18|Sistem mencatat audit trail setiap<br>create/update/delete/post/closing|Proses: simpan data lama & baru dalam<br>format JSON. Output: log riwayat<br>perubahan yang dapat ditelusuri|



## - - <u>4. Kebutuhan Non Fungsional (Non Functional Requirements)</u> 

|<br>**Kategori**|<br>**Kebutuhan**|
|---|---|
|Kinerja (Performance)|Generate laporan (LR/Neraca/Arus Kas) untuk 1 periode selesai dalam<br>< 5 detik untuk volume hingga 5.000 baris jurnal|
|Keamanan (Security)|Password di-hash (bcrypt), sesi login timeout otomatis, hak akses<br>berbasis role, proteksi CSRF & SQL Injection bawaan Laravel|
|Keandalan (Reliability)|Validasi debet=kredit mencegah data tidak seimbang tersimpan ke<br>database|
|Kegunaan (Usability)|Antarmuka form jurnal semudah mengisi tabel Excel yang sudah<br>familiar bagi Staff Finance|
|Maintainability|Mengikuti struktur MVC Laravel standar, kode terdokumentasi,<br>migration untuk seluruh skema tabel|
|Portabilitas|Dapat dijalankan pada hosting shared/VPS yang mendukung PHP &<br>MySQL standar|
|Skalabilitas|Struktur database mendukung penambahan cabang/entitas di fase<br>berikutnya tanpa migrasi besar|
|Ketersediaan (Availability)|Target uptime 99% pada jam kerja operasional|



## 5. Kebutuhan Antarmuka Eksternal 

### 5.1 Antarmuka Pengguna 

- Web browser modern (Chrome, Edge, Firefox terbaru); layout responsif Bootstrap/Tailwind. 

### 5.2 Antarmuka Perangkat Lunak 

- Laravel 11.x + PHP 8.2+, MySQL 8.x, opsional queue (Laravel Queue) untuk proses export laporan besar. 

### 5.3 Antarmuka Komunikasi 

- HTTPS untuk seluruh komunikasi client-server. 

## 6. Lampiran 

Detail struktur tabel database dijelaskan pada dokumen ERD (Entity Relationship Diagram) dan skrip schema.sql yang menyertai dokumen ini. 

5 

