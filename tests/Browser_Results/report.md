# Hasil Pengujian UI (UI Test Results) - Finlog

Laporan ini memuat tangkapan layar otomatis (*screenshot*) dari hasil pengujian UI pada Browser berdasarkan dokumen [UI Test Plan](../ui_test_plan.md) untuk Bagian 1 hingga 4.

## 1. Modul Autentikasi

**Kasus: Login Gagal dengan Kredensial Salah (TC-AUTH-03)**
Tampilan pesan *error* saat pengguna memasukkan kredensial yang salah.

![1784711542867](image/report/1784711542867.png)

*notes: pesan *error* buat menjadi bahasa indonesia (kata sandi atau password salah), buat jadi warna merah dan buat posisi nya ada di bawah button sign in

## 2. Dashboard (Beranda)

**Kasus: Memuat Widget Dashboard dengan Akun Owner (TC-DASH-01 s/d 04)**
Tampilan *dashboard* untuk akses tingkat Owner yang memuat semua *widget* informasi dan menu Laporan.
![Dashboard Widgets](images/dashboard_widgets_1784709412242.png)

## 3. Manajemen Data Induk (Master Data)

**Kasus: Membuat dan Memverifikasi Chart of Accounts (TC-MST-01)**
Mencari akun baru (misal: "Kas Test Baru") di dalam tabel *Chart of Accounts*.
![Account Created](images/final_account_created_and_verified_1784709988963.png)

## 4. Penjurnalan (Journal Entries)

**Kasus: Simpan Jurnal Seimbang / Balanced (TC-JRN-01)**
Menunjukkan baris transaksi dengan Total Debit & Kredit yang sama, sehingga indikator "Balance" tercentang hijau.

![1784711718676](image/report/1784711718676.png)

*notes: dibagian jurnal ini sorting default nya buat dari yang terbaru dibuat. Akun COA nya ini enggak ada harusnya ada.

**Kasus: Validasi Jurnal Tidak Seimbang / Unbalanced (TC-JRN-02)**
Tombol **"Simpan Jurnal"** dinonaktifkan karena nilai Debit dan Kredit berselisih (tidak balance).
![Unbalanced No Save](images/unbalanced_no_save_1784710516962.png)

## 5. UI

![1784712512037](image/report/1784712512037.png)

input jurnal baru enggak usah ada di sidebar, karena udah ada di daftar jurnal

![1784712470794](image/report/1784712470794.png)

ini buat jadi ada background (buat jadi button) karena ini kaya text biasa

![1784712238397](image/report/1784712238397.png)

error message nya ubah konsistensikan jadi bahasa indonesia
