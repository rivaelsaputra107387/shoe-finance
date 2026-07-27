# 📚 Panduan Standar Chart of Accounts (COA) & Saldo Normal

Dalam akuntansi, **Chart of Accounts (COA)** atau Bagan Akun adalah daftar seluruh akun yang digunakan oleh perusahaan untuk mencatat transaksi keuangan. Setiap kelompok akun memiliki aturan baku mengenai penambahan, pengurangan, dan **Saldo Normal** (posisi bawaan saat saldo bertambah).

Aturan dasar yang berlaku secara universal (termasuk di sistem Finlog) adalah sebagai berikut:

---

## 🧮 1. Kelompok Akun Neraca (Balance Sheet)

Akun-akun ini bersifat permanen (saldonya dibawa ke periode berikutnya) dan membentuk persamaan dasar akuntansi: **Aset = Kewajiban + Ekuitas**.

| Kode Awalan | Kelompok Akun               | Jika Bertambah | Jika Berkurang |   Saldo Normal   | Contoh Akun                                                                                      |
| :---------: | :-------------------------- | :------------: | :------------: | :--------------: | :----------------------------------------------------------------------------------------------- |
| **1** | **Aset / Harta**      |     DEBIT     |     KREDIT     | **DEBIT** | Kas (1100), Piutang (1200), Persediaan (1300), Kendaraan (1400)                                  |
| **2** | **Kewajiban / Utang** |     KREDIT     |     DEBIT     | **KREDIT** | Utang Dagang (2100), Utang Bank (2200), Utang Gaji (2101)                                        |
| **3** | **Ekuitas / Modal**   |     KREDIT     |     DEBIT     | **KREDIT** | Modal Pemilik (3100), Laba Ditahan (3200), Prive (3101 -*pengecualian, bersaldo normal debit*) |

> [!IMPORTANT]
> **Pengecualian:** Akun "Kontra-Aset" seperti **Akumulasi Penyusutan (1401)** memiliki saldo normal **KREDIT** karena berfungsi mengurangi nilai aset. Begitu juga "Prive" (Penarikan Modal) memiliki saldo normal **DEBIT** karena mengurangi ekuitas.

---

## 📈 2. Kelompok Akun Laba Rugi (Income Statement)

Akun-akun ini bersifat sementara (saldonya ditutup/dinolkan di akhir periode akuntansi) untuk menghitung laba atau rugi bersih perusahaan.

|   Kode Awalan   | Kelompok Akun                         | Jika Bertambah | Jika Berkurang |   Saldo Normal   | Contoh Akun                                                |
| :-------------: | :------------------------------------ | :------------: | :------------: | :--------------: | :--------------------------------------------------------- |
|   **4**   | **Pendapatan Usaha**            |     KREDIT     |     DEBIT     | **KREDIT** | Pendapatan Jasa (4100), Penjualan Barang (4200)            |
|   **5**   | **Harga Pokok Penjualan (HPP)** |     DEBIT     |     KREDIT     | **DEBIT** | Pembelian (5100), Biaya Angkut Pembelian (5200)            |
|   **6**   | **Beban Operasional**           |     DEBIT     |     KREDIT     | **DEBIT** | Beban Gaji (6100), Beban Sewa (6200), Beban Listrik (6300) |
|   **7**   | **Pend. Lain-lain (Non-Op)**    |     KREDIT     |     DEBIT     | **KREDIT** | Pendapatan Bunga Bank (7100), Laba Jual Aset (7200)        |
| **8 / 9** | **Beban Lain-lain (Non-Op)**    |     DEBIT     |     KREDIT     | **DEBIT** | Beban Bunga (8100), Pajak Bank (8200), Rugi Kurs (8300)    |

> [!TIP]
> **Cara Mudah Menghafal (Akronim DEAD CLIC):**
>
> - **DEAD** (Bersaldo Normal **Debit**): **D**ebit, **E**xpenses (Beban 5,6,8), **A**ssets (Aset 1), **D**rawings (Prive).
> - **CLIC** (Bersaldo Normal **Kredit**): **C**redit, **L**iabilities (Kewajiban 2), **I**ncome (Pendapatan 4,7), **C**apital (Modal 3).

---

## 🛠️ Aturan Validasi di Sistem Finlog

Sesuai standar di atas, sistem jurnal dan laporan keuangan (Buku Besar, Neraca, Laba Rugi) akan otomatis melakukan kalkulasi:

- **Akun Saldo Normal Debit (1, 5, 6, 8, 9):** Sa
  ```
  ```

  ldo Akhir = Total Debit - Total Kredit.
- **Akun Saldo Normal Kredit (2, 3, 4, 7):** Saldo Akhir = Total Kredit - Total Debit.

Jika ada akun yang memiliki saldo akhir bernilai *minus* (negatif), itu artinya ada yang salah dalam pencatatan jurnal (misalnya, mengeluarkan kas lebih banyak daripada saldo yang ada).
