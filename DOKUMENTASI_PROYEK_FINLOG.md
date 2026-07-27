# 📊 DOKUMENTASI PROGRESS PROYEK

## Sistem Informasi Akuntansi (SIA) — Shoe Workshop (Finlog)

**Tanggal Laporan:** 22 Juli 2026
**Disusun oleh:** Muhammad Rivael Saputra
**Stack Teknologi:** Laravel 11 + Filament v3 + Livewire v3 + Tailwind CSS v4
**Referensi Dokumen:** BRD v1.0 | ERD v1.0 | SRS v1.0 | PRD v1.0
**QA / Reviewer:** Radit / Dena

---

## 📌 1. RINGKASAN EKSEKUTIF

Proyek **Finlog** bertujuan menggantikan seluruh proses pencatatan keuangan manual di Microsoft Excel (12 sheet) menjadi sistem informasi akuntansi berbasis web yang terotomasi, real-time, dan aman.

| Aspek                                | Detail                                      |
| ------------------------------------ | ------------------------------------------- |
| **Total Progress Keseluruhan** | **~53%**                              |
| **Periode Pengembangan**       | 14 Juli — sekarang (22 Juli 2026)          |
| **Fase Saat Ini**              | R1 (MVP) dalam penyelesaian → menuju R2    |
| **Database**                   | SQLite (lokal) → siap migrasi ke MySQL 8.x |

---

## 🗺️ 2. PEMETAAN FITUR vs STATUS IMPLEMENTASI

| Kode PRD       | Fitur                             | Prioritas | Status           | Keterangan                                       |
| -------------- | --------------------------------- | --------- | ---------------- | ------------------------------------------------ |
| **F-01** | Master Chart of Account (COA)     | Must      | ✅**100%** | CRUD lengkap, filter tipe, kode unik             |
| **F-02** | Manajemen Periode Akuntansi       | Must      | ✅**100%** | CRUD, open/closed, validasi overlap tanggal      |
| **F-03** | Input Jurnal Umum                 | Must      | ✅**90%**  | Livewire multi-baris, validasi real-time         |
| **F-04** | Posting Jurnal                    | Must      | ✅**100%** | Status draft→posted, proteksi edit posted       |
| **F-05** | Buku Besar Otomatis               | Must      | ✅**100%** | Running balance, pagination, filter akun+tanggal |
| **F-06** | Neraca Lajur (Trial Balance)      | Must      | ✅**100%** | Agregasi saldo debit/kredit seluruh akun         |
| **F-07** | Laporan Laba Rugi                 | Must      | ✅**100%** | Pure Blade+Tailwind, kalkulasi LR lengkap        |
| **F-08** | Laporan Perubahan Ekuitas         | Must      | 🟡**70%**  | Halaman & service ada, perlu verifikasi          |
| **F-09** | Neraca (Balance Sheet)            | Must      | 🟡**70%**  | Halaman & service ada, perlu verifikasi          |
| **F-10** | Laporan Arus Kas                  | Must      | 🟡**60%**  | Service ada, perlu verifikasi 3 kategori         |
| **F-11** | Jurnal Penutup & Neraca Penutupan | Must      | 🟡**70%**  | Service ada, perlu pengujian menyeluruh          |
| **F-12** | Export PDF/Excel                  | Should    | 🔴**10%**  | Blade view PDF ada, endpoint belum aktif         |
| **F-13** | Dashboard Ringkasan               | Should    | ✅**100%** | 3 Widget: Kas+Laba, Grafik 6 bln, Jurnal         |
| **F-14** | Manajemen User & Role             | Must      | ✅**100%** | Spatie + Filament Shield, 3 role aktif           |
| **F-15** | Audit Trail                       | Should    | ✅**90%**  | Log otomatis via AuditObserver                   |
| **F-16** | Notifikasi Validasi               | Could     | ✅**100%** | Flash notification & validasi visual aktif       |

---

## 🏗️ 3. ARSITEKTUR & WORKFLOW SISTEM

### 3.1 Alur Siklus Akuntansi

```
[1] SETUP AWAL
    Owner mengisi Master COA (Chart of Accounts)
    Owner membuka Periode Akuntansi Baru (status: open)
           ↓
[2] PENCATATAN HARIAN (oleh Staff/Owner/Finance)
    Input Jurnal Baru via form Livewire
    → Validasi real-time: Total Debit = Total Kredit
    → Tombol "Simpan" NONAKTIF jika selisih ≠ 0
    → Jurnal tersimpan & terkunci ke periode yang open
           ↓
[3] MONITORING REAL-TIME (kapan saja)
    ├── Buku Besar  → mutasi & saldo berjalan per akun
    ├── Neraca Lajur → saldo agregat semua akun
    └── Dashboard   → ringkasan visual (Kas, Laba, Grafik 6 Bulan)
           ↓
[4] PEMBUATAN LAPORAN KEUANGAN (End of Period)
    ├── Laporan Laba Rugi (LR)
    ├── Laporan Perubahan Ekuitas (LPE)
    ├── Neraca/Balance Sheet (NRC)
    └── Laporan Arus Kas (LAK)
           ↓
[5] PENUTUPAN PERIODE (Owner saja)
    Owner klik "Tutup Periode" → konfirmasi modal
    → ClosingEntryService otomatis:
       a. Nolkan akun Pendapatan (4xxx) → Debit
       b. Nolkan akun HPP & Beban (5,6xxx) → Kredit
       c. Selisih (Laba/Rugi) dipindah ke Modal (3xxx)
    → Status periode: open → closed (terkunci permanen)
```

### 3.2 Arsitektur Teknis (Ringkasan)

```
Browser → Filament Admin Panel
    ├── Livewire: JournalEntryForm.php (form real-time)
    ├── Filament Pages: GeneralLedger, TrialBalance, IncomeStatement,
    │                   BalanceSheet, EquityStatement, CashFlowStatement,
    │                   PeriodClosing, AuditTrailPage
    ├── Filament Resources: AccountResource, FiscalPeriodResource,
    │                       JournalEntryResource, UserResource
    ├── Service Classes: LedgerService, IncomeStatementService,
    │                    BalanceSheetService, EquityStatementService,
    │                    CashFlowReportService, TrialBalanceService,
    │                    ClosingEntryService
    └── Database: users, fiscal_periods, accounts,
                  journal_entries, journal_entry_lines, audit_trails
```

---

## 👥 4. USER ACCESS ROLE (Hak Akses Per Peran)

Menggunakan **Spatie Laravel Permission** + **Filament Shield** (RBAC).

### 4.1 Role yang Aktif (3 Role)

| Role              | Akun Demo                | Deskripsi                 |
| ----------------- | ------------------------ | ------------------------- |
| **owner**   | owner@shoeworkshop.com   | Full access               |
| **finance** | finance@shoeworkshop.com | Laporan + input jurnal    |
| **staff**   | staff@shoeworkshop.com   | Input & lihat jurnal saja |

### 4.2 Matriks Hak Akses

| Fitur / Menu                    | 👑 Owner | 💼 Finance |      👷 Staff      |
| ------------------------------- | :------: | :--------: | :----------------: |
| Dashboard                       |    ✅    |     ✅     |         ✅         |
| Input Jurnal Baru               |    ✅    |     ✅     |         ✅         |
| Daftar Jurnal (semua)           |    ✅    |     ✅     | 🟡 (milik sendiri) |
| Edit/Hapus Jurnal               |    ✅    |     ✅     |         ❌         |
| Chart of Accounts (Lihat)       |    ✅    |     ✅     |         ✅         |
| Chart of Accounts (Tambah/Edit) |    ✅    |     ✅     |         ❌         |
| Hapus Akun COA                  |    ✅    |     ❌     |         ❌         |
| Buku Besar (General Ledger)     |    ✅    |     ✅     |         ❌         |
| Neraca Lajur (Trial Balance)    |    ✅    |     ✅     |         ❌         |
| Laporan Laba Rugi               |    ✅    |     ✅     |         ❌         |
| Neraca (Balance Sheet)          |    ✅    |     ✅     |         ❌         |
| Laporan Perubahan Ekuitas       |    ✅    |     ✅     |         ❌         |
| Laporan Arus Kas                |    ✅    |     ✅     |         ❌         |
| Tutup Periode                   |    ✅    |     ❌     |         ❌         |
| Audit Trail                     |    ✅    |     ❌     |         ❌         |
| Manajemen User                  |    ✅    |     ❌     |         ❌         |
| Manajemen Periode Akuntansi     |    ✅    | 🟡 (lihat) |         ❌         |

---

## 🧮 5. RUMUS & LOGIKA PERHITUNGAN

### 5.1 Validasi Input Jurnal (Double-Entry)

```
ATURAN: SUM(debit semua baris) = SUM(kredit semua baris)

$difference = abs($totalDebit - $totalCredit)
→ if $difference > 0.01  → Simpan NONAKTIF (ditolak)
→ if $difference <= 0.01 → Simpan AKTIF (✓ Seimbang)
```

### 5.2 Saldo Berjalan Buku Besar (Running Balance)

```
Akun Saldo Normal DEBIT (Aset 1xxx, Beban 5,6,8xxx):
    Saldo = Saldo Awal + Σ(Debit) - Σ(Kredit)

Akun Saldo Normal KREDIT (Hutang 2xxx, Modal 3xxx, Pendapatan 4xxx):
    Saldo = Saldo Awal + Σ(Kredit) - Σ(Debit)

⚠️ Query wajib: ORDER BY tanggal ASC, id ASC (tie-breaker!)
```

### 5.3 Laporan Laba Rugi

```
Total Pendapatan = Σ akun 4xxx
Total HPP        = Σ akun 5xxx
Total Beban      = Σ akun 6xxx
Pendapatan Lain  = Σ akun 7xxx
Beban Lain       = Σ akun 8xxx

Laba/Rugi Bersih = Total Pendapatan - Total HPP - Total Beban
                   + Pendapatan Lain - Beban Lain
```

### 5.4 Neraca / Balance Sheet

```
Total Aktiva  = Σ saldo akun 1xxx

Total Pasiva  = Σ akun 2xxx (Kewajiban)
              + Σ akun 3xxx (Ekuitas)
              + Laba/Rugi Bersih periode berjalan

⚠️ VALIDASI: Total Aktiva HARUS = Total Pasiva
```

### 5.5 Laporan Perubahan Ekuitas

```
Modal Awal + Laba/Rugi Bersih - Prive (3120) = Modal Akhir
```

### 5.6 Laporan Arus Kas

```
OPERASI:    Arus dari Pendapatan (4xxx) & Beban (5,6xxx)
INVESTASI:  Arus dari Peralatan/Aset Tetap (1200-1299)
PENDANAAN:  Arus dari Modal (3xxx), Prive (3120), Hutang (2xxx)

Saldo Kas Akhir = Kas Awal + Net Operasi + Net Investasi + Net Pendanaan
```

### 5.7 Jurnal Penutup Otomatis

```
1. Debit: Akun Pendapatan (4xxx) → Kredit: Ikhtisar Laba Rugi
2. Debit: Ikhtisar Laba Rugi → Kredit: Akun Beban (5,6,8xxx)
3. Selisih (Laba) → Kredit: Modal (3xxx)
   Selisih (Rugi) → Debit: Modal (3xxx)
4. Debit: Modal → Kredit: Prive (3120)

Hasil: Semua akun 4-8xxx bersaldo 0 → siap periode baru
```

---

## ✅ 6. VALIDASI YANG DIIMPLEMENTASIKAN

| #  | Validasi                                     | Lokasi                     | Status |
| -- | -------------------------------------------- | -------------------------- | ------ |
| 1  | Total Debit = Total Kredit real-time         | `JournalEntryForm.php`   | ✅     |
| 2  | Tanggal dalam rentang periode`open`        | `JournalEntryForm.php`   | ✅     |
| 3  | Blokir input jika tidak ada periode`open`  | `JournalEntryForm.php`   | ✅     |
| 4  | Blokir edit jurnal pada periode`closed`    | `JournalEntryPolicy.php` | ✅     |
| 5  | Kode akun COA harus unik                     | `AccountResource.php`    | ✅     |
| 6  | Minimal 2 baris per jurnal                   | `JournalEntryForm.php`   | ✅     |
| 7  | Satu baris: Debit OR Kredit (tidak keduanya) | Model level                | ✅     |
| 8  | Running balance: tie-breaker`id ASC`       | `GeneralLedger.php`      | ✅     |
| 9  | Reset pagination saat filter berubah         | `GeneralLedger.php`      | ✅     |
| 10 | Konfirmasi modal sebelum tutup periode       | `PeriodClosing.php`      | ✅     |
| 11 | CSRF protection, hash bcrypt                 | Laravel framework          | ✅     |
| 12 | Audit trail otomatis                         | `AuditObserver.php`      | ✅     |

---

## 🗄️ 7. KESESUAIAN DATABASE DENGAN ERD

| Tabel di ERD          | Tabel Aktual               | Status | Catatan                        |
| --------------------- | -------------------------- | ------ | ------------------------------ |
| `roles`             | *(Spatie: `roles`)*    | ✅     | Library Spatie, bukan manual   |
| `users`             | `users`                  | ✅     | Identik                        |
| `periode_akuntansi` | `fiscal_periods`         | ✅     | Nama beda (English)            |
| `coa`               | `accounts`               | ✅     | Nama beda, kolom lebih lengkap |
| `saldo_awal`        | *(belum tabel terpisah)* | 🟡     | Via jurnal awal periode        |
| `jurnal_umum`       | `journal_entries`        | ✅     | Nama beda (English)            |
| `jurnal_detail`     | `journal_entry_lines`    | ✅     | + kolom`description`         |
| `audit_trail`       | `audit_trails`           | ✅     | Identik                        |

**Kolom tambahan (tidak di ERD, wajar karena kebutuhan teknis):**

- `journal_entries.status` — workflow draft/posted
- `journal_entries.is_closing` — penanda jurnal penutup
- `accounts.cash_flow_category` — klasifikasi arus kas

---

## ⚠️ 8. DEVIASI DARI DOKUMEN PERANCANGAN

### ✅ Deviasi Dibenarkan (Improvement)

| Item                  | Spesifikasi Asli            | Implementasi                  | Alasan                                    |
| --------------------- | --------------------------- | ----------------------------- | ----------------------------------------- |
| Database              | MySQL 8.x                   | SQLite (lokal)                | Kemudahan dev lokal; produksi tetap MySQL |
| Styling               | Bootstrap/Tailwind          | Tailwind v4 + Filament        | Filament bundled Tailwind                 |
| Role naming           | Admin, Staff Finance, Owner | owner, staff, finance         | Normalisasi nama kode                     |
| Dashboard             | "Grafik bulan berjalan"     | Grafik 6 bulan historis       | Lebih informatif                          |
| Kolom deskripsi baris | Tidak ada di ERD            | Ada di`journal_entry_lines` | Kebutuhan user                            |

### 🟡 Belum Diimplementasi (Masih Scope)

| Item                            | Target  |
| ------------------------------- | ------- |
| Export PDF aktif (F-12)         | R3      |
| Tabel`saldo_awal` eksplisit   | R2      |
| Verifikasi NRC/LPE/LAK vs Excel | Ongoing |
| Uji Closing Entry menyeluruh    | R2      |

### ❌ Dikonfirmasi Out of Scope

- Payroll / Penggajian
- Integrasi Perbankan (Open Banking)
- Multi-cabang / Multi-entitas
- Integrasi SleekFlow / Google Sheets
- Modul inventori bertingkat
- Aplikasi mobile native

---

## 📈 9. PROGRESS PER RILIS (Berdasarkan PRD §7 — Release Plan)

PRD mendefinisikan 3 rilis dengan cakupan fitur yang jelas:

### Rilis R1 — MVP: Fondasi Pencatatan Jurnal & Buku Besar

*Cakupan PRD: F-01, F-02, F-03, F-04, F-05, F-14*

| Kode | Fitur                         | Status     |
| ---- | ----------------------------- | ---------- |
| F-01 | Master Chart of Account (COA) | ✅ Selesai |
| F-02 | Manajemen Periode Akuntansi   | ✅ Selesai |
| F-03 | Input Jurnal Umum             | ✅ 90%     |
| F-04 | Posting Jurnal                | ✅ Selesai |
| F-05 | Buku Besar Otomatis           | ✅ Selesai |
| F-14 | Manajemen User & Role         | ✅ Selesai |

**Status R1: ✅ Hampir Selesai (95%)**

---

### Rilis R2 — Laporan Inti: Seluruh Siklus Akuntansi Otomatis

*Cakupan PRD: F-06, F-07, F-08, F-09, F-10, F-11*

| Kode | Fitur                                     | Status     |
| ---- | ----------------------------------------- | ---------- |
| F-06 | Neraca Lajur (Trial Balance)              | ✅ Selesai |
| F-07 | Laporan Laba Rugi                         | ✅ Selesai |
| F-08 | Laporan Perubahan Ekuitas                 | 🟡 70%     |
| F-09 | Laporan Neraca                            | 🟡 70%     |
| F-10 | Laporan Arus Kas                          | 🟡 60%     |
| F-11 | Jurnal Penutup & Neraca Setelah Penutupan | 🟡 70%     |

**Status R2: 🟡 Dalam Pengerjaan (65%)**

---

### Rilis R3 — Penyempurnaan: Export, Dashboard, Audit, Notifikasi

*Cakupan PRD: F-12, F-13, F-15, F-16*

| Kode | Fitur               | Status                                       |
| ---- | ------------------- | -------------------------------------------- |
| F-12 | Export PDF/Excel    | 🔴 10% — Blade view ada, endpoint belum     |
| F-13 | Dashboard Ringkasan | ✅ Selesai (melebihi spec: 6 bulan historis) |
| F-15 | Audit Trail         | ✅ 90%                                       |
| F-16 | Notifikasi Validasi | ✅ Selesai                                   |

**Status R3: 🟡 Sebagian Selesai (60%)**

---

## 📄 10. TRACKING BUSINESS REQUIREMENTS (BRD §6)

BRD mendefinisikan 9 Business Requirements (BR) sebagai kebutuhan bisnis inti yang wajib dipenuhi sistem:

| ID              | Kebutuhan Bisnis                                                         | Prioritas | Status Implementasi |

```
```

| Bukti           |                                                                          |        |              |                                                                    |
| --------------- | ------------------------------------------------------------------------ | ------ | ------------ | ------------------------------------------------------------------ |
| **BR-01** | Sistem mencatat jurnal umum dengan validasi debet = kredit               | Tinggi | ✅ Terpenuhi | `JournalEntryForm.php` — validasi real-time sebelum simpan      |
| **BR-02** | Sistem menghasilkan Buku Besar otomatis per akun dari jurnal posted      | Tinggi | ✅ Terpenuhi | `GeneralLedger.php` + `LedgerService.php`                      |
| **BR-03** | Sistem menghasilkan Neraca Lajur, LR, LPE, Neraca, Arus Kas otomatis     | Tinggi | 🟡 Sebagian  | LR & Neraca Lajur ✅; LPE, Neraca, Arus Kas perlu verifikasi final |
| **BR-04** | Sistem mendukung jurnal penutup dan neraca setelah penutupan             | Tinggi | 🟡 70%       | `ClosingEntryService.php` sudah ada, perlu uji menyeluruh        |
| **BR-05** | Sistem membedakan hak akses Admin, Staff Finance, dan Owner              | Tinggi | ✅ Terpenuhi | Spatie Permission + Filament Shield, 3 role aktif                  |
| **BR-06** | Sistem mencatat audit trail (siapa, kapan, perubahan apa)                | Sedang | ✅ 90%       | `AuditObserver.php` + `AuditTrailPage.php`                     |
| **BR-07** | Sistem dapat mengekspor laporan ke PDF dan Excel                         | Sedang | 🔴 10%       | Blade PDF view ada, endpoint belum aktif (Target: R3)              |
| **BR-08** | Sistem menyediakan dashboard ringkasan kondisi keuangan bagi Owner       | Sedang | ✅ Terpenuhi | 3 widget aktif: Kas, Grafik 6 Bulan, Jurnal Terbaru                |
| **BR-09** | Sistem dapat mengunci (menutup) periode agar data historis tidak berubah | Tinggi | ✅ Terpenuhi | `PeriodClosing.php` — periode closed tidak bisa diedit          |

---

## ⚙️ 11. STATUS NON-FUNCTIONAL REQUIREMENTS (SRS §4)

SRS mendefinisikan 8 kategori kebutuhan non-fungsional. Berikut status pemenuhannya:

| Kategori                  | Standar (SRS)                                                     | Status                | Keterangan Implementasi                                              |
| ------------------------- | ----------------------------------------------------------------- | --------------------- | -------------------------------------------------------------------- |
| **Kinerja**         | Generate laporan < 5 detik untuk 5.000 baris                      | 🟡 Belum diuji formal | Belum ada uji beban; perlu diverifikasi saat data historis masuk     |
| **Keamanan**        | Hash bcrypt, session timeout, RBAC, proteksi CSRF & SQL Injection | ✅ Terpenuhi          | Laravel bawaan: CSRF, Eloquent (SQL injection safe), bcrypt, session |
| **Keandalan**       | Validasi debet=kredit mencegah data tidak seimbang tersimpan      | ✅ Terpenuhi          | Double validasi: level form (Livewire) + level observer              |
| **Kegunaan**        | Form jurnal semudah tabel Excel                                   | ✅ Terpenuhi          | Form Livewire multi-baris dengan total live & indikator selisih      |
| **Maintainability** | Struktur MVC Laravel standar, migration untuk seluruh skema       | ✅ Terpenuhi          | 8 migrasi terstruktur, Service Classes terpisah per domain           |
| **Portabilitas**    | Bisa dijalankan di hosting shared/VPS (PHP + MySQL)               | ✅ Terpenuhi          | Tidak ada dependency khusus; SQLite lokal, siap MySQL produksi       |
| **Skalabilitas**    | Struktur DB mendukung penambahan cabang/entitas fase berikutnya   | ✅ Terpenuhi          | Schema dirancang single-entity tapi siap diperluas                   |
| **Ketersediaan**    | Target uptime 99% pada jam kerja                                  | 🟡 Belum diukur       | Bergantung pada infrastruktur hosting produksi                       |

---

## 🏆 12. TRACKING KRITERIA KEBERHASILAN (BRD §9)

BRD menetapkan 3 kriteria keberhasilan (*Definition of Done*) yang bersifat terukur:

### Kriteria 1: Seluruh siklus akuntansi berjalan otomatis & hasilnya sesuai Excel (uji paralel 1-2 periode)

| Sub-item                          | Status                                                            |
| --------------------------------- | ----------------------------------------------------------------- |
| Siklus Jurnal → Buku Besar       | ✅ Berjalan otomatis                                              |
| Siklus Buku Besar → Neraca Lajur | ✅ Berjalan otomatis                                              |
| Siklus → Laba Rugi               | ✅ Berjalan;**verifikasi vs Excel: belum dilakukan formal** |
| Siklus → Neraca & LPE            | ✅ Berjalan;**verifikasi vs Excel: belum dilakukan formal** |
| Siklus → Arus Kas                | 🟡 Berjalan parsial;**verifikasi vs Excel: belum**          |
| Siklus → Jurnal Penutup          | 🟡 Service ada;**uji paralel belum dilakukan**              |

> **Status Kriteria 1: 🟡 Dalam Proses** — Uji paralel dengan data Excel bulan Juni 2026 harus dilakukan oleh QA (Radit / Dena) sebelum R2 dapat dinyatakan selesai.

### Kriteria 2: Owner dapat mengakses laporan kapan saja tanpa bergantung pada staff finance

| Sub-item                                              | Status       |
| ----------------------------------------------------- | ------------ |
| Dashboard real-time untuk Owner                       | ✅ Terpenuhi |
| Semua laporan keuangan accessible oleh Owner langsung | ✅ Terpenuhi |
| Tidak perlu intervensi staff untuk generate laporan   | ✅ Terpenuhi |

> **Status Kriteria 2: ✅ Terpenuhi**

### Kriteria 3: Zero selisih (unbalance) antara total debet dan kredit di semua laporan

| Sub-item                                                   | Status             |
| ---------------------------------------------------------- | ------------------ |
| Validasi balance di form input                             | ✅ Terpenuhi       |
| Proteksi jurnal tidak balance tersimpan ke DB              | ✅ Terpenuhi       |
| Indikator visual warning jika Neraca tidak balance         | ✅ Terpenuhi       |
| Uji formal zero-unbalance dengan data riil 1 periode penuh | 🟡 Belum dilakukan |

> **Status Kriteria 3: 🟡 Hampir Terpenuhi** — Infrastruktur validasi sudah ada; uji formal dengan data lengkap menunggu Fase Verifikasi QA.

---

## 👤 13. TRACKING USER STORIES (PRD §5)

PRD mendefinisikan 5 user story utama sebagai representasi kebutuhan pengguna nyata:

| #     | User Story                                                                                                                                                                 | Terpenuhi? | Catatan                                                                         |
| ----- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- | ------------------------------------------------------------------------------- |
| US-01 | *Sebagai Staff Finance, saya ingin menginput jurnal umum dengan validasi otomatis debet=kredit, agar tidak terjadi kesalahan pencatatan.*                                | ✅ Ya      | Form Livewire real-time, tombol Simpan nonaktif jika tidak balance              |
| US-02 | *Sebagai Staff Finance, saya ingin melihat buku besar per akun secara otomatis, agar tidak perlu menghitung manual di Excel.*                                            | ✅ Ya      | `GeneralLedger.php` dengan filter akun, tanggal, running balance & pagination |
| US-03 | *Sebagai Admin, saya ingin menutup periode akuntansi, agar data historis tidak bisa diubah lagi setelah laporan difinalisasi.*                                           | ✅ Ya      | `PeriodClosing.php` + konfirmasi modal; periode closed terkunci permanen      |
| US-04 | *Sebagai Owner, saya ingin melihat dashboard ringkasan (laba rugi, kas, dan neraca) kapan saja, agar dapat mengambil keputusan cepat tanpa menunggu laporan dari staff.* | ✅ Ya      | Dashboard dengan 3 widget: Saldo Kas+Laba, Grafik 6 Bulan, Jurnal Terbaru       |
| US-05 | *Sebagai Admin, saya ingin melihat log audit trail, agar dapat menelusuri siapa yang mengubah transaksi tertentu.*                                                       | ✅ 90%     | `AuditTrailPage.php` aktif; log otomatis via `AuditObserver.php`            |

> **Status User Stories: ✅ 4 dari 5 selesai penuh; 1 (Audit Trail) 90%**

---

## ❓ 14. FAQ — PRESENTASI KE DIVISI FINANCE

### Q1: Apa bedanya sistem ini dibanding Excel lama?

**A:** Di Excel lama, setiap transaksi harus dicatat manual di sheet JURNAL, lalu **manual** direkap ke 11 sheet lainnya. Satu angka salah di JURNAL bisa merusak seluruh laporan.

Di Finlog, staff **hanya** mengisi satu form. Sistem otomatis menghasilkan Buku Besar, Neraca Lajur, dan semua laporan keuangan secara real-time. **Tidak ada copy-paste antar sheet.**

---

### Q2: Apakah sistem ini bisa salah hitung?

**A:** Sistem memiliki tiga lapis proteksi:

1. **Validasi form:** Tombol Simpan terkunci jika Debit ≠ Kredit.
2. **Audit trail:** Setiap perubahan data tercatat otomatis.
3. **Indikator visual:** Laporan Neraca menampilkan peringatan merah jika total Aset ≠ Kewajiban + Ekuitas.

---

### Q3: Siapa yang bisa melihat laporan keuangan sensitif?

**A:** Hanya **Owner** dan **Finance**. Role **Staff** hanya bisa membuka form Input Jurnal. Sistem otomatis menolak (error 403) jika staff mencoba mengakses laporan keuangan, bahkan jika mereka mengetik URL-nya langsung di browser.

---

### Q4: Apakah data historis dari Excel bisa dipindah?

**A:** Ya. Sistem menyiapkan mekanisme import yang bisa membaca transaksi dari file Excel dan memasukkannya ke database. Import data historis adalah bagian dari tahap pengembangan lanjutan.

---

### Q5: Apa yang terjadi jika staff salah input jurnal?

**A:** Jurnal yang sudah tersimpan tidak bisa diedit langsung (demi integritas audit). Prosedurnya adalah membuat **Jurnal Koreksi** — jurnal baru yang membalik entri salah dan membuat entri yang benar. Riwayat koreksi tercatat di Audit Trail.

---

### Q6: Apa itu "Tutup Periode"? Apakah data bisa hilang?

**A:** Tutup Periode adalah finalisasi akhir bulan. Sistem akan:

1. Otomatis membuat **Jurnal Penutup** — mereset saldo akun Pendapatan & Beban menjadi 0, keuntungan dipindah ke Modal.
2. **Mengunci periode** — tidak ada yang bisa mengubah data bulan yang sudah ditutup.

Data **tidak hilang**. Semua transaksi tetap ada dan bisa dilihat. Yang terkunci hanya kemampuan mengedit.

---

### Q7: Apakah laporan bisa dicetak / diekspor ke PDF?

**A:** Fitur export PDF sedang dikembangkan. Tampilan PDF untuk semua laporan sudah dipersiapkan. Tombol "Unduh PDF" akan aktif pada Rilis R3.

---

### Q8: Apa yang terjadi jika internet mati saat mengisi form?

**A:** Data yang sudah berhasil di-submit sebelum koneksi terputus sudah tersimpan aman di server. Jika koneksi terputus sebelum menekan Simpan, staff perlu mengisi ulang form. Data tidak tersimpan setengah-setengah.

---

*Dokumen diperbarui: 22 Juli 2026 | Developer: Rivael Saputra | QA / Reviewer: Radit / Dena*
