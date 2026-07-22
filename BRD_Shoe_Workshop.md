Shoe Workshop — Sistem Laporan Keuangan 

**SHOE WORKSHOP** Sistem Informasi Laporan Keuangan 

# **BUSINESS REQUIREMENTS DOCUMENT (BRD)** 

_Sistem Informasi Laporan Keuangan_ 

|**Nama Proyek**|Sistem Laporan Keuangan Shoe<br>Workshop|
|---|---|
|**Versi Dokumen**|1.0|
|**Tanggal**|14Juli 2026|
|**Disusun oleh**|System Analyst|
|**Status**|Draft untuk Review|



1 

Shoe Workshop — Sistem Laporan Keuangan 

## Daftar Isi 

2 

Shoe Workshop — Sistem Laporan Keuangan 

## 1. Latar Belakang 

Shoe Workshop saat ini menyusun laporan keuangan (Jurnal Umum, Buku Besar, Neraca Lajur, Laporan Laba Rugi, Laporan Perubahan Ekuitas, Neraca, Laporan Arus Kas, hingga Jurnal Penutup) secara manual menggunakan Microsoft Excel. Proses ini rawan salah hitung, sulit ditelusuri riwayat perubahannya (audit trail), dan bergantung pada satu orang yang memahami struktur file. 

Untuk mendukung pertumbuhan bisnis dan kebutuhan pelaporan yang lebih cepat serta akurat, perusahaan berencana membangun sistem informasi laporan keuangan berbasis web menggunakan Laravel dan MySQL, yang mengotomasi siklus akuntansi mulai dari input jurnal hingga penyusunan laporan keuangan akhir. 

## 2. Tujuan Bisnis 

- Mengurangi kesalahan input dan perhitungan manual dalam proses pencatatan akuntansi. 

- Mempercepat waktu penyusunan laporan keuangan bulanan dari hitungan hari menjadi hitungan menit. 

- Menyediakan visibilitas kondisi keuangan bagi Owner/Manajemen kapan saja (real-time). 

- Membangun jejak audit (audit trail) yang jelas atas setiap transaksi dan siapa yang melakukannya. 

- Menjadi fondasi data keuangan yang rapi untuk kebutuhan integrasi lain di masa depan (contoh: sinkronisasi data order pelanggan yang sedang dibangun tim Denata). 

## 3. Ruang Lingkup 

### 3.1 Termasuk dalam Ruang Lingkup 

   - Master data: Chart of Account (COA), Periode Akuntansi, User & Role. 

   - Input dan approval Jurnal Umum (debet/kredit) dengan validasi keseimbangan. 

   - Buku Besar otomatis per akun (tergenerate dari jurnal). 

   - Neraca Lajur (worksheet) otomatis. 

   - Laporan Laba Rugi (LR), Laporan Perubahan Ekuitas (LPE), Neraca (NRC), Laporan Arus Kas (LAK). 

   - Jurnal Penutup (JP) dan Neraca setelah penutupan. 

   - Proses tutup buku (closing period) yang mengunci periode dari perubahan lebih lanjut. 

   - Export laporan ke PDF/Excel. 

   - Dashboard ringkasan keuangan untuk Owner/Manajemen. 

- 3.2 Tidak Termasuk dalam Ruang Lingkup (Fase 1) 

   - Modul penggajian (payroll). 

   - Modul perpajakan otomatis (e-Faktur, SPT). 

   - Integrasi langsung dengan sistem perbankan (open banking). 

   - Modul multi-cabang/multi-entitas (dapat menjadi fase berikutnya). 

   - Integrasi dengan sistem sinkronisasi order pelanggan (SleekFlow/Google Sheets) — berjalan sebagai proyek terpisah, namun struktur data akun keuangan dirancang agar kompatibel untuk integrasi di masa depan. 

## <u>4. Pemangku Kepentingan (Stakeholders)</u> 

|**Peran**|**Nama/Fungsi**|**Kepentingan Utama**|
|---|---|---|
|Sponsor Proyek / Owner|Pemilik Shoe<br>Workshop|Melihat laporan keuangan real-time, memastikan ROI<br>proyek|
|System Analyst|Denata|Menyusun requirement, BRD/PRD/SRS/ERD, jembatan<br>bisnis-teknis|
|Tim Developer|Tim Laravel|Membangun sistem sesuai spesifikasi teknis|
|Staff Finance|Pengguna harian|Input jurnal, rekonsiliasi, cetak laporan|



3 

Shoe Workshop — Sistem Laporan Keuangan 

|**Peran**<br>**Nama/Fungsi**|**Kepentingan Utama**|
|---|---|
|Admin<br>Pengguna teknis|Kelola master data, user, dan tutup periode|
|5. Proses Bisnis Saat Ini vs Diusulkan<br>**Aspek**<br>**Kondisi Saat Ini**<br>**(Excel)**|**Kondisi Diusulkan (Sistem Web)**|
|Input transaksi<br>Manual ketik di<br>sheet JURNAL|Form input jurnal dengan validasi debet=kredit|
|Buku Besar<br>Dihitung manual per<br>akun|Otomatis tergenerate dari jurnal|
|Laporan (LR, NRC, LPE, LAK)<br>Rumus Excel<br>manual, rawan rusak|Digenerate otomatis dari data jurnal tervalidasi|
|Akses<br>File dibagi lewat<br>email/drive, 1 orang<br>paham strukturnya|Akses berbasis role melalui web, kapan saja|
|Audit trail<br>Tidak ada, sulit tahu<br>siapa mengubah apa|Tercatat otomatis setiap create/update/post|
|Tutup buku<br>Manual, rawan data<br>periode lanjut masih<br>tercampur|Sistem mengunci periode setelah closing|



|5. Proses Bisnis Saat Ini vs Diusulkan|
|---|



|6. Kebutuhan Bisnis (Business Requirements)|
|---|



|**ID**|**Kebutuhan Bisnis**|**Prioritas**|
|---|---|---|
|BR-01|Sistem dapat<br>mencatat transaksi<br>jurnal umum dengan<br>validasi debet dan<br>kredit harus<br>seimbang|Tinggi|
|BR-02|Sistem dapat<br>menghasilkan Buku<br>Besar otomatis per<br>akun berdasarkan<br>jurnal yang sudah<br>diposting|Tinggi|
|BR-03|Sistem dapat<br>menghasilkan<br>Neraca Lajur, Laba<br>Rugi, Perubahan<br>Ekuitas, Neraca, dan<br>Arus Kas secara<br>otomatis|Tinggi|
|BR-04|Sistem mendukung<br>proses jurnal<br>penutup dan neraca<br>setelah penutupan|Tinggi|
|BR-05|Sistem membedakan<br>hak akses Admin,<br>Staff Finance, dan<br>Owner/Manajemen|Tinggi|
|BR-06|Sistem mencatat|Sedang|



4 

Shoe Workshop — Sistem Laporan Keuangan 

|**ID**|**Kebutuhan Bisnis**|**Prioritas**|
|---|---|---|
||jejak audit (siapa,<br>kapan, perubahan<br>apa) untuk setiap<br>transaksi||
|BR-07|Sistem dapat<br>mengekspor laporan<br>ke format PDF dan<br>Excel|Sedang|
|BR-08|Sistem menyediakan<br>dashboard ringkasan<br>kondisi keuangan<br>bagi Owner|Sedang|
|BR-09|Sistem dapat<br>mengunci<br>(menutup) periode<br>akuntansi agar data<br>historis tidak<br>berubah|Tinggi|



## 7. Asumsi dan Batasan 

### 7.1 Asumsi 

- Struktur akun (COA) yang digunakan mengikuti daftar akun pada file Excel yang sudah berjalan saat ini. 

- Pengguna memiliki koneksi internet stabil untuk mengakses aplikasi berbasis web. 

- Tim developer memiliki pengalaman dengan framework Laravel dan database MySQL. 

### 7.2 Batasan 

- Versi pertama (MVP) berfokus pada satu entitas bisnis (belum multi-cabang). 

- Laporan keuangan mengikuti standar akuntansi yang saat ini dipakai perusahaan (belum tentu PSAK penuh/audited). 

## 8. Manfaat yang Diharapkan (Expected Benefits) 

- Efisiensi waktu penyusunan laporan bulanan hingga lebih dari 70%. 

- Penurunan risiko kesalahan pencatatan akibat human error. 

- Keputusan bisnis yang lebih cepat karena data keuangan tersedia real-time. 

- Dasar data yang solid untuk audit maupun pengajuan pembiayaan (bank/investor). 

## 9. Kriteria Keberhasilan Proyek 

- Seluruh siklus akuntansi (Jurnal - Buku Besar - Laporan - Jurnal Penutup) berjalan otomatis dan hasilnya sesuai dengan hasil manual di Excel (uji paralel 1-2 periode). 

- Owner dapat mengakses laporan keuangan kapan saja tanpa bergantung pada staff finance. 

- Tidak ada selisih (unbalance) antara total debet dan kredit pada laporan yang dihasilkan sistem. 

5 

