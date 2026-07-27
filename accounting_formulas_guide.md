# Panduan Logika & Rumus Sistem Informasi Akuntansi (Finlog)

Dokumen ini disusun khusus sebagai "contekan" (*cheat sheet*) untuk mempermudah Anda selaku *Developer* dalam memahami logika bisnis akuntansi yang beroperasi di balik kode (PHP/Laravel) sistem **Finlog** Anda.

---

## 1. Persamaan Dasar Akuntansi (Hukum Mutlak)

Semua sistem akuntansi modern menganut prinsip **Double-Entry** (Tata Buku Berpasangan). Artinya, setiap kali ada uang masuk atau keluar, **minimal harus ada 2 akun yang dicatat**, dan nilai **Total Debit harus selalu sama dengan Total Kredit**.

Hukum absolut di sistem ini adalah:

> **ASET = KEWAJIBAN + EKUITAS**
> *(Harta perusahaan = Uang Pinjaman + Uang Modal Sendiri)*

## 2. Aturan Debit, Kredit, & Saldo Normal

Dalam pemrograman, kita tidak memandang Debit sebagai "Minus" dan Kredit sebagai "Plus" (seperti di mutasi bank biasa). Debit dan Kredit hanyalah **Posisi Kiri dan Kanan**.

Setiap jenis akun memiliki **Saldo Normal** (tempat di mana nilainya "Bertambah"):

|                                      Kepala Akun                                      | Kategori Akun                               |   Saldo Normal   | Jika Bertambah |        Jika Berkurang        |
| :------------------------------------------------------------------------------------: | ------------------------------------------- | :--------------: | :------------: | :--------------------------: |
|                                  **8897891xxx**                                  | **Aset / Harta** (Kas, Bank, Piutang) | **DEBIT** |  Masuk Debit  |         Masuk Kredit         |
|                                     **2xxx**                                     | **Kewajiban / Hutang**                | **KREDIT** |  Masuk Kredit  |         Masuk Debit         |
|                                     **3xxx**                                     | **Ekuitas / Modal**                   | **KREDIT** |  Masuk Kredit  |         Masuk Debit         |
|                                     **4xxx**                                     | **Pendapatan / Penjualan**            | **KREDIT** |  Masuk Kredit  | Masuk Debit y7 y  b bga6 n' |
| **5, 6, 7  ,                                               ** | **HPP & Beban (Pengeluaran)**         | **DEBIT** |  Masuk Debit  |         Masuk Kredit         |

> [!TIP]
> **Cara Cepat Menghafal:** Hanya **ASET (1)** dan **BEBAN (5,6,7)** yang saldo normalnya **DEBIT**. Sisanya pasti KREDIT.

---

```
```

## 3. Rumus di Balik Fitur-Fitur Finlog

Berikut adalah cara sistem Anda menghitu

```
```

ng angka-angka di laporan:

### A. Buku Besar (General Ledger) & Saldo Akhir Akun

Fungsi Buku Besar adalah melihat rincian keluar-masuk uang pada *satu* akun tertentu. Rumus saldo berjalannya (*Running Balance*) tergantung Saldo Normal akun tersebut:

* **Untuk Akun Aset & Beban (Normal Debit):**
  `Saldo Akhir = Saldo Awal + Total Transaksi Debit - Total Transaksi Kredit`
* **Untuk Akun Hutang, Modal, Pendapatan (Normal Kredit):
  ```
  ```

  **
  `Saldo Akhir = Saldo Awal + Total Transaksi Kredit - Total Transaksi Debit`

*(Itulah kenapa di model `Account.php` Anda, ada fungsi `getBalanceForPeriod()` yang memiliki `if/else` untuk mengecek Normal Balance).*

### B. Neraca Saldo (Trial Balance)

Neraca Saldo adalah daftar seluruh akun (dari kelompok 1 sampai akhir) beserta saldo akhirnya pada periode tertentu. Tujuannya adalah memastikan bahwa pencatatan sudah *balance*.

Rumusnya:

1. Sistem mengambil saldo akhir dari masing-masing akun (seperti perhitungan Buku Besar di atas).
2. Sistem meletakkan angka saldo tersebut ke kolom yang sesuai dengan Saldo Normalnya (apakah di Debit atau Kredit).
3. **Total Kolom Debit = Total Kolom Kredit**.
   Jika tidak sama, berarti ada ketidakseimbangan pada saat proses penjurnalan (misal input Rp10.000 di debit tapi Rp1.000 di kredit).

### C. Laporan Laba Rugi (Income Statement)

Laporan ini digunakan untuk melihat apakah *Shoe Workshop* Anda sedang untung atau rugi. Rumusnya sangat sederhana, murni mengambil data dari kepala 4, 5, dan 6:

> **Laba Bersih = Total Pendapatan (4xxx) - [Total HPP (5xxx) + Total Beban (6xxx)]**

* Jika hasil Laba Bersih positif (+), berarti **Untung (Surplus)**.
* Jika hasil Laba Bersih negatif (-), berarti **Rugi (Defisit)**.

### C. Laporan Perubahan Ekuitas (Equity Statement)

Laporan ini menunjukkan bagaimana Modal (Ekuitas) si Bos bertambah atau berkurang selama bulan ini.

> **Ekuitas Akhir = Modal Awal + Laba Bersih (Dari Laporan B) - Prive (Penarikan Pribadi Bos)**

### D. Neraca (Balance Sheet)

Ini adalah "Rapor Final" bisnis Anda. Jika kodingan Anda benar, maka total sisi Kiri (Aktiva) harus pas (balance) dengan sisi Kanan (Pasiva).

* **Total Aktiva** = Total semua akun Aset (1xxx)
* **Total Pasiva** = Total Kewajiban (2xxx) + Total Ekuitas Akhir (Diambil dari Laporan C)

> [!IMPORTANT]
> **Kenapa Neraca sering "Tidak Balance" saat dicoding?**
> Biasanya *Developer* lupa memasukkan angka **Laba Bersih** periode berjalan ke dalam penambahan Modal (Ekuitas) saat merender Neraca. Laba/Rugi wajib disuntikkan sementara ke Ekuitas agar hukum `Aset = Kewajiban + Ekuitas` tetap terjaga.

---

## 4. Proses Jurnal Penutup (Closing Entry)

Banyak *Developer* kebingungan dengan fitur "Tutup Buku". Intinya begini:
Laba Rugi adalah laporan bulanan/tahunan. Artinya, di bulan baru (Periode Baru), angka Pendapatan dan Beban harus mulai dari Rp 0 lagi.

Proses **Closing Entry** secara otomatis membuat Jurnal yang "menghapus" saldo tersebut.

* Misal Total Pendapatan bulan ini ada Kredit Rp 10 Juta.
* Jurnal penutup akan membuat transaksi: **Debit Pendapatan Rp 10 Juta**. (Sehingga saldo akhirnya jadi 0).
* Uang Rp 10 Juta tersebut akan "dilempar" secara permanen masuk ke akun **Laba Ditahan / Modal (3xxx)**.

Sehingga di periode berikutnya, riwayat uang tidak hilang, melainkan sudah berubah wujud menjadi "Modal", dan metrik Pendapatan siap diisi mulai dari 0 lagi.
