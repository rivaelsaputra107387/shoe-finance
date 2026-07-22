```
```

# PRD — Sistem Informasi Akuntansi (SIA) Shoe Workshop

**Versi:** 1.0
**Tanggal:** 14 Juli 2026
**Disusun untuk:** Muhammad Rivael Saputra
**Metode pengembangan:** Vibe coding (AI agent) dengan stack Laravel + Filament + custom Livewire
**Sumber referensi:** File Excel "LAPORAN KEUANGAN SHOE WORKSHOP" (12 sheet: MENU, COA, CONTROL, JURNAL, BUKU BESAR, NRL, LR, LPE, NRC, LAK, JP, NRC STLH PENUTUPAN)

---

## 1. Latar belakang & tujuan

### 1.1 Masalah saat ini

Pencatatan keuangan Shoe Workshop saat ini dilakukan manual di Excel dengan 12 sheet terpisah yang saling bergantung. Setiap transaksi harus dicatat di sheet JURNAL, lalu manual direkap ke BUKU BESAR, lalu manual disusun ke NRL (Neraca Lajur), baru kemudian laporan LR/NRC/LPE/LAK disusun. Risiko dari alur ini:

- Human error saat memindahkan angka antar sheet
- Tidak ada validasi otomatis bahwa debit = kredit
- Tidak ada kontrol akses (siapa saja bisa mengubah semua sheet)
- Laporan tidak real-time — harus ditutup manual per periode

### 1.2 Tujuan sistem

Membangun aplikasi web SIA yang:

1. Menggantikan seluruh alur pencatatan manual di atas menjadi satu alur digital
2. Staff hanya input transaksi di satu tempat (form jurnal), sisanya otomatis
3. Owner bisa melihat laporan keuangan real-time kapan saja
4. Sistem menjamin integritas akuntansi (debit = kredit, saldo konsisten)
5. Mendukung siklus penuh: dari pencatatan harian sampai jurnal penutup otomatis per periode

### 1.3 Non-tujuan (out of scope untuk versi ini)

- Modul inventori/stok barang detail (persediaan dicatat sebagai akun, bukan sistem stok bertingkat)
- Integrasi pembayaran online atau rekonsiliasi bank otomatis
- Multi-tenant (multi-perusahaan dalam satu instalasi)
- Aplikasi mobile native (cukup web responsive)

---

## 2. Target pengguna & peran

| Peran                        | Deskripsi                                                                          | Hak akses                                                                                |
| ---------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| **Staff**              | Karyawan yang mencatat transaksi harian (kas masuk/keluar, HPP, beban operasional) | Input jurnal baru, lihat jurnal miliknya, lihat COA (read-only)                          |
| **Owner/Admin**        | Pemilik bisnis, pengambil keputusan                                                | Semua akses staff + lihat semua laporan keuangan, kelola COA, kelola user, tutup periode |
| **(Opsional) Finance** | Jika ke depan ada staff finance khusus                                             | Sama seperti owner tapi tanpa hak kelola user                                            |

Catatan: role diimplementasikan dengan `spatie/laravel-permission` + `filament-shield` agar terintegrasi otomatis dengan Filament Resource policies.

---

## 3. Cakupan fitur (scope MVP: full siklus akuntansi)

Berdasarkan diskusi sebelumnya, MVP mencakup seluruh siklus akuntansi manual, dipetakan dari 12 sheet Excel:

| #  | Modul                                       | Sumber (sheet Excel)   | Tipe implementasi                         |
| -- | ------------------------------------------- | ---------------------- | ----------------------------------------- |
| 1  | Master Chart of Accounts (COA)              | COA, CONTROL           | Filament Resource                         |
| 2  | Manajemen user & role                       | — (baru)              | Filament Resource + Shield                |
| 3  | Input jurnal umum                           | JURNAL                 | Custom Livewire component                 |
| 4  | Buku besar per akun                         | BUKU BESAR             | Custom Filament Page (query, bukan input) |
| 5  | Neraca lajur (trial balance)                | NRL                    | Custom Filament Page                      |
| 6  | Laporan Laba Rugi (LR)                      | LR                     | Custom Filament Page + export PDF         |
| 7  | Laporan Perubahan Ekuitas (LPE)             | LPE                    | Custom Filament Page + export PDF         |
| 8  | Neraca (NRC)                                | NRC                    | Custom Filament Page + export PDF         |
| 9  | Laporan Arus Kas (LAK)                      | LAK                    | Custom Filament Page + export PDF         |
| 10 | Penutupan periode & jurnal penutup otomatis | JP, NRC STLH PENUTUPAN | Custom Livewire action + service class    |
| 11 | Dashboard ringkasan                         | MENU                   | Filament Widgets                          |

---

## 4. Arsitektur & alur data

### 4.1 Prinsip inti

Hanya **satu titik input manusia**: form jurnal. Semua modul lain (Buku Besar, Neraca Lajur, LR, NRC, LPE, LAK) adalah **hasil kalkulasi/query**, bukan tabel yang diisi manual. Ini menghindari duplikasi data dan inkonsistensi seperti yang berpotensi terjadi di Excel.

### 4.2 Alur proses (siklus akuntansi)

```
Master COA (setup awal)
   ↓
Staff input jurnal (form dgn validasi debit = kredit)
   ↓
Sistem posting otomatis ke saldo per akun
   ↓
Buku besar per akun (query, real-time)
   ↓
Neraca lajur / trial balance (agregasi semua akun)
   ↓
Laporan keuangan: LR, NRC, LPE, LAK (generate dari neraca lajur)
   ↓
Jurnal penutup otomatis (akhir periode, nolkan akun nominal)
   ↓
Neraca setelah penutupan (saldo awal periode berikutnya)
```

### 4.3 Skema database (ERD)

**Tabel utama:**

1. **`users`**

   - `id`, `name`, `email`, `password`, `role` (via spatie permission)
2. **`fiscal_periods`**

   - `id`, `name` (mis. "Juni 2026"), `start_date`, `end_date`, `status` (`open` / `closed`)
3. **`accounts`** (master COA)

   - `id`, `code` (string, unique, mis. "4110"), `name`, `type` (Aset/Kewajiban/Ekuitas/Pendapatan/Beban), `normal_balance` (Debet/Kredit), `report_category` (Neraca/Laba Rugi), `parent_id` (nullable, self-reference untuk grouping)
4. **`journal_entries`** (header transaksi)

   - `id`, `entry_date`, `reference` (nomor referensi), `description`, `fiscal_period_id` (FK), `created_by` (FK ke users), `is_closing` (boolean, true khusus jurnal penutup otomatis)
5. **`journal_entry_lines`** (detail baris debit/kredit)

   - `id`, `journal_entry_id` (FK), `account_id` (FK), `debit` (decimal), `credit` (decimal)

**Aturan integritas penting:**

- Satu `journal_entries` wajib punya ≥ 2 baris di `journal_entry_lines`
- `SUM(debit)` harus sama dengan `SUM(credit)` dalam satu `journal_entry_id` — divalidasi di level aplikasi (Livewire) sebelum simpan, dan idealnya juga di level database (check constraint atau observer)
- Setiap baris hanya boleh isi salah satu dari `debit` atau `credit` (tidak keduanya di baris yang sama)

---

## 5. Detail modul & alur kerja

### 5.1 Master COA

**Actor:** Owner
**Alur:**

1. Owner buka menu "Chart of Accounts"
2. Tabel menampilkan semua akun dengan filter berdasarkan `type`
3. Owner bisa tambah/edit akun baru (kode, nama, tipe, saldo normal, kategori laporan, parent akun)
4. Validasi: kode akun harus unik, mengikuti pola 4-digit sesuai konvensi (1xxx Aset, 2xxx Kewajiban, dst — lihat lampiran)

**Implementasi:** Filament Resource standar (`AccountResource`), tidak perlu Livewire custom.

### 5.2 Input jurnal umum

**Actor:** Staff, Owner
**Alur:**

1. User membuka form "Input Jurnal Baru"
2. Isi tanggal transaksi, deskripsi, referensi (opsional)
3. Tambah baris dinamis: pilih akun (dropdown/searchable dari COA), isi nominal di kolom debit ATAU kredit
4. Bisa tambah baris lagi (`+ Tambah baris`) untuk transaksi majemuk (contoh dari data Excel: satu hari bisa banyak baris HPP berbeda akun)
5. Sistem menampilkan total debit dan total kredit secara live — kalau tidak sama, tombol "Simpan" nonaktif dan muncul selisih yang harus dikoreksi
6. Setelah simpan, transaksi masuk ke `journal_entries` + `journal_entry_lines`, terkunci ke `fiscal_period_id` yang sedang aktif

**Validasi wajib:**

- Total debit = total kredit (real-time, sebelum submit)
- Tanggal transaksi harus berada dalam rentang `fiscal_periods` yang berstatus `open`
- Tidak bisa edit/hapus jurnal yang sudah masuk periode `closed`

**Implementasi:** Custom Livewire component (bukan Filament Resource form standar) karena butuh dynamic rows + live calculation. Bisa di-embed sebagai Filament Custom Page agar tetap satu ekosistem UI.

### 5.3 Buku besar per akun

**Actor:** Staff (lihat akun sendiri), Owner (semua akun)
**Alur:**

1. Pilih akun dari dropdown (atau dari klik akun di COA)
2. Sistem menampilkan riwayat mutasi akun tersebut (tanggal, keterangan, debit, kredit, saldo berjalan) — dihitung dari `journal_entry_lines` yang difilter per `account_id`, urut tanggal
3. Bisa difilter per rentang tanggal / per periode

**Implementasi:** Custom Filament Page dengan tabel read-only, query langsung (tidak ada input).

### 5.4 Neraca lajur (trial balance)

**Actor:** Owner
**Alur:**

1. Pilih periode
2. Sistem tampilkan semua akun dengan saldo debit/kredit masing-masing (hasil agregasi seluruh jurnal di periode tersebut)
3. Total kolom debit harus sama dengan total kolom kredit — jika tidak, ini indikasi ada bug atau data tidak konsisten (harus ditampilkan sebagai warning)

**Implementasi:** Custom Filament Page, hasil query agregasi (`GROUP BY account_id`).

### 5.5 Laporan Laba Rugi (LR)

**Alur:** Ambil semua akun dengan `report_category = 'Laba Rugi'`, kelompokkan jadi Pendapatan dan Beban (termasuk HPP), hitung total masing-masing, tampilkan Laba/Rugi Bersih = Total Pendapatan − Total Beban.

**Implementasi:** Custom Filament Page + tombol export PDF (`barryvdh/laravel-dompdf` atau `spatie/laravel-pdf`).

### 5.6 Neraca (NRC)

**Alur:** Ambil semua akun `report_category = 'Neraca'`, kelompokkan jadi Aset (kiri) vs Kewajiban + Ekuitas (kanan). Total kedua sisi harus balance — kalau tidak, tampilkan warning visual.

**Implementasi:** Custom Filament Page dengan layout 2 kolom custom Blade/Livewire (bukan Table Builder Filament standar, karena formatnya beda).

### 5.7 Laporan Perubahan Ekuitas (LPE)

**Alur:** Modal awal periode + Laba/Rugi periode berjalan − Prive (jika ada akun prive) = Modal akhir periode.

**Implementasi:** Custom Filament Page, kalkulasi sederhana dari data LR + saldo akun Modal.

### 5.8 Laporan Arus Kas (LAK)

**Alur:** Kelompokkan mutasi akun Kas & Bank berdasarkan 3 kategori: Operasi, Investasi, Pendanaan (mapping kategori ini mengacu ke sheet CONTROL yang sudah ada di file Excel kamu — kolom "Arus kas: Type").

**Catatan:** Ini modul paling kompleks secara logic. Perlu tabel mapping tambahan (`cash_flow_category` di tabel `accounts`) supaya tiap akun tahu masuk kategori arus kas yang mana.

**Implementasi:** Custom Filament Page + service class terpisah (`CashFlowReportService`) karena logikanya paling rumit dibanding laporan lain.

### 5.9 Jurnal penutup otomatis

**Actor:** Owner
**Alur:**

1. Owner klik "Tutup Periode [Juni 2026]"
2. Sistem konfirmasi (modal peringatan: aksi ini tidak bisa dibatalkan)
3. Sistem otomatis generate `journal_entries` baru dengan `is_closing = true`:
   - Nolkan semua akun Pendapatan (debit sejumlah saldo kredit masing-masing)
   - Nolkan semua akun Beban/HPP (kredit sejumlah saldo debit masing-masing)
   - Selisih (laba/rugi bersih) dipindahkan ke akun "Ikhtisar Laba Rugi" lalu ke akun "Modal"
4. `fiscal_periods.status` diubah jadi `closed`
5. Periode baru otomatis dibuat dengan status `open`, saldo awal = saldo akhir periode sebelumnya (untuk akun neraca — aset, kewajiban, ekuitas tidak dinolkan)

**Implementasi:** Livewire action (tombol) yang memanggil service class `ClosingEntryService` — ini bagian paling kritis, wajib ditest manual dengan data riil dari file Excel sebagai pembanding.

### 5.10 Dashboard

**Actor:** Owner (utama), Staff (versi terbatas)
**Isi:** Widget saldo kas hari ini, grafik pendapatan vs beban bulan berjalan, daftar jurnal terakhir, status periode aktif.

**Implementasi:** Filament Dashboard Widgets bawaan (`ChartWidget`, `StatsOverviewWidget`).

---

## 6. Kebutuhan non-fungsional

| Aspek                              | Kebutuhan                                                                                                                                                        |
| ---------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Akurasi**                  | Zero toleransi untuk selisih debit/kredit — validasi wajib di form dan idealnya juga di level model (observer/event)                                            |
| **Auditability**             | Setiap`journal_entries` mencatat `created_by` dan timestamp — tidak boleh ada hard delete, gunakan soft delete jika transaksi perlu dibatalkan              |
| **Performa**                 | Query laporan (LR, NRC, LAK) harus dioptimasi dengan index di`account_id` dan `fiscal_period_id`, karena akan agregasi ratusan/ribuan baris jurnal per bulan |
| **Keamanan**                 | Role-based access control ketat — staff tidak boleh bisa lihat laporan keuangan penuh, hanya input & lihat jurnal miliknya                                      |
| **Kompatibilitas data lama** | Sistem harus bisa import data historis dari file Excel (minimal COA dan saldo awal) saat migrasi pertama kali                                                    |

---

## 7. Rencana pengembangan bertahap (untuk vibe coding)

Urutan ini disusun supaya tiap tahap bisa langsung diverifikasi sebelum lanjut ke tahap berikutnya — penting karena kesalahan logic akuntansi menumpuk kalau tidak dicek bertahap.

| Tahap | Yang dikerjakan                                                                                              | Cara verifikasi                                                                            |
| ----- | ------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| 1     | Setup project Laravel + Filament + Shield, migrasi tabel dasar (`users`, `accounts`, `fiscal_periods`) | Cek tabel ter-migrate, role admin bisa login                                               |
| 2     | Seeder COA dari data Excel kamu (~85 akun)                                                                   | Jumlah akun & kode cocok dengan file Excel                                                 |
| 3     | CRUD COA via Filament Resource                                                                               | Tambah/edit akun berhasil, validasi kode unik jalan                                        |
| 4     | Tabel`journal_entries` + `journal_entry_lines`, migrasi                                                  | Struktur FK benar                                                                          |
| 5     | Form input jurnal custom Livewire + validasi balance                                                         | Coba input 5-10 transaksi dari data Excel, hasil tersimpan benar                           |
| 6     | Halaman Buku Besar (query per akun)                                                                          | Bandingkan saldo salah satu akun dengan sheet BUKU BESAR di Excel                          |
| 7     | Halaman Neraca Lajur                                                                                         | Bandingkan total dengan sheet NRL                                                          |
| 8     | Laporan LR                                                                                                   | Bandingkan laba/rugi bersih dengan sheet LR                                                |
| 9     | Laporan NRC                                                                                                  | Cek saldo Aset = Kewajiban + Ekuitas                                                       |
| 10    | Laporan LPE                                                                                                  | Bandingkan modal akhir dengan sheet LPE                                                    |
| 11    | Laporan LAK (paling kompleks)                                                                                | Bandingkan 3 kategori arus kas dengan sheet LAK                                            |
| 12    | Jurnal penutup otomatis + service class                                                                      | Jalankan closing satu periode uji, bandingkan hasil dengan sheet JP dan NRC STLH PENUTUPAN |
| 13    | Role & permission (staff vs owner)                                                                           | Login sebagai staff, pastikan tidak bisa akses laporan keuangan                            |
| 14    | Export PDF tiap laporan                                                                                      | PDF ter-generate rapi, angka cocok dengan halaman web                                      |
| 15    | Dashboard & widget ringkasan                                                                                 | Widget menampilkan data real dari periode aktif                                            |

**Prinsip untuk tiap tahap saat vibe coding:** beri agent context COA dan contoh data jurnal riil dari file Excel kamu, minta agent tulis test/perbandingan otomatis (atau kamu cek manual) sebelum lanjut ke tahap berikutnya. Jangan lanjut ke laporan kalau input jurnal + buku besar belum 100% akurat — semua laporan di atasnya bergantung pada dua fondasi ini.

---

## 8. Lampiran — konvensi kode akun (dari COA Excel)

| Awalan kode | Kategori                         | Posisi laporan | Saldo normal |
| ----------- | -------------------------------- | -------------- | ------------ |
| 1xxx        | Aset (1100: lancar, 1200: tetap) | Neraca         | Debet        |
| 2xxx        | Kewajiban                        | Neraca         | Kredit       |
| 3xxx        | Ekuitas                          | Neraca         | Kredit       |
| 4xxx        | Pendapatan                       | Laba Rugi      | Kredit       |
| 5xxx        | HPP & beban gaji produksi        | Laba Rugi      | Debet        |
| 6xxx        | Beban operasional umum           | Laba Rugi      | Debet        |
| 7xxx        | Pendapatan/beban lain-lain       | Laba Rugi      | Campuran     |
| 8xxx        | Beban admin bank & pajak         | Laba Rugi      | Debet        |

**Catatan pembersihan data sebelum seeding:** akun 6120 "kompensasi" dan 7000 "Pendapatan Bunga Bank" di file Excel sumber memiliki nilai saldo normal/posisi laporan yang tidak konsisten — perlu diperjelas dulu (satu nilai pasti) sebelum dimasukkan ke seeder `accounts`.

---

## 9. Kriteria keberhasilan (Definition of Done)

Sistem dianggap selesai untuk versi MVP jika:

1. Semua transaksi dari sheet JURNAL bulan Juni 2026 bisa diinput dan menghasilkan saldo Buku Besar yang identik dengan sheet Excel
2. Laporan LR, NRC, LPE, LAK yang di-generate sistem menghasilkan angka yang sama dengan laporan manual di Excel untuk periode yang sama
3. Proses tutup periode otomatis menghasilkan jurnal penutup dan neraca setelah penutupan yang identik dengan sheet JP dan NRC STLH PENUTUPAN
4. Staff tidak bisa mengakses laporan keuangan, hanya modul input jurnal
5. Semua laporan bisa di-export PDF dengan format yang rapi dan terbaca
