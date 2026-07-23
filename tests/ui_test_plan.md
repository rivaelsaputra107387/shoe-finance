# Skenario Pengujian Antarmuka (UI Test Plan) - Finlog

Berikut adalah daftar lengkap kasus uji (*test cases*) untuk menguji UI aplikasi di sisi *browser*, tanpa mengeksekusi tes tersebut. Skenario ini dipetakan berdasarkan struktur halaman dan *resource* Filament yang ada di aplikasi kamu.

## 1. Modul Autentikasi & Hak Akses
- [ ] **TC-AUTH-01:** *Login* sukses menggunakan akun Owner (`owner@shoeworkshop.com`).
- [ ] **TC-AUTH-02:** *Login* sukses menggunakan akun Staff (`staff@shoeworkshop.com`).
- [ ] **TC-AUTH-03:** *Login* gagal dan muncul peringatan yang tepat ketika menggunakan kredensial yang salah.
- [ ] **TC-AUTH-04:** Verifikasi visibilitas menu berdasarkan Role (Misalnya: Staff mungkin tidak dapat mengakses menu Laporan tingkat atas atau menu Penutupan Periode).

## 2. Dashboard (Beranda)
- [ ] **TC-DASH-01:** Widget `ActivePeriodWidget` menampilkan periode fiskal yang saat ini aktif tanpa *error*.
- [ ] **TC-DASH-02:** Widget `CashBalanceWidget` menampilkan saldo akhir total kas secara akurat berdasarkan agregasi data akun.
- [ ] **TC-DASH-03:** Widget `RevenueExpenseChartWidget` sukses merender grafik batang/garis perbandingan Pendapatan vs Beban.
- [ ] **TC-DASH-04:** Tabel `RecentJournalsWidget` memuat dan menampilkan 5-10 daftar transaksi jurnal terbaru secara *real-time*.

## 3. Manajemen Data Induk (Master Data)
### Chart of Accounts (Akun)
- [ ] **TC-MST-01:** Membuka `AccountResource` dan menambahkan data Akun baru dengan validasi format kode unik berhasil disimpan.
- [ ] **TC-MST-02:** Melakukan edit (*Update*) pada nama/tipe akun yang sudah ada.
- [ ] **TC-MST-03:** Menghapus (*Delete*) akun; memastikan akun yang *sudah memiliki riwayat transaksi jurnal* tidak bisa dihapus (*restricted*).

### Fiscal Period (Periode Fiskal)
- [ ] **TC-MST-04:** Membuka `FiscalPeriodResource` dan membuat Periode Fiskal baru (Misal: Juli 2026).
- [ ] **TC-MST-05:** Mengedit status periode dari "Inactive" menjadi "Active" atau "Closed".

## 4. Penjurnalan (Journal Entries)
- [ ] **TC-JRN-01:** Menambah Jurnal Baru dengan form dinamis, di mana Total Debit dan Total Kredit **Seimbang (Balance)** -> Jurnal berhasil disimpan.
- [ ] **TC-JRN-02:** Menambah Jurnal Baru dengan Total Debit dan Kredit **TIDAK Seimbang** -> UI memblokir penyimpanan dan memunculkan error validasi "Debit dan Kredit tidak balance".
- [ ] **TC-JRN-03:** Menambah baris (*lines*) jurnal lebih dari dua (misal: 1 akun Debit, 2 akun Kredit) melalui form `CreateJournalEntry`.
- [ ] **TC-JRN-04:** Mencari dan memfilter daftar jurnal yang sudah ada berdasarkan tanggal atau referensi di tabel.

## 5. Laporan Keuangan (Reports)
- [ ] **TC-RPT-01 (General Ledger / Buku Besar):** Memilih satu akun di *dropdown*, lalu memverifikasi rincian tabel mutasi debet/kredit muncul dengan benar.
- [ ] **TC-RPT-02 (Trial Balance / Neraca Saldo):** Membuka halaman neraca saldo dan memvalidasi apakah baris Total akhir (Debit = Kredit) seimbang.
- [ ] **TC-RPT-03 (Income Statement / Laba Rugi):** Membuka halaman ini untuk memastikan komponen Pendapatan terhitung secara otomatis dikurangi Total Beban untuk menghasilkan Laba/Rugi Bersih.
- [ ] **TC-RPT-04 (Balance Sheet / Neraca):** Memverifikasi perenderan struktur laporan, memastikan rumusan Aset = Kewajiban + Ekuitas terpenuhi.
- [ ] **TC-RPT-05 (Cash Flow / Arus Kas):** Memeriksa bahwa tabel aktivitas operasional, pendanaan, dan investasi tampil dan dapat diekspor.
- [ ] **TC-RPT-06 (Equity Statement / Perubahan Modal):** Memastikan halaman menampilkan mutasi saldo laba bersih ke penambahan/pengurangan ekuitas.

## 6. Fitur Administratif Khusus
- [ ] **TC-ADM-01 (Audit Trail):** Membuka menu `AuditTrailPage` dan memverifikasi jejak rekam terekam dengan baik (Misal: terlihat "User Owner membuat Jurnal #123").
- [ ] **TC-ADM-02 (Period Closing):** Membuka halaman `PeriodClosing` dan memverifikasi tombol "Tutup Buku" bekerja dan memunculkan *modal* konfirmasi sebelum tereksekusi.
