# Laporan Bug (Bug Report)

Sesuai permintaan Anda, saya tidak mengeksekusi (*run*) tesnya dan saya telah **mengembalikan (revert) kode yang sebelumnya saya perbaiki** agar *bug*-nya muncul kembali.

Berikut adalah daftar *bug* yang berhasil saya deteksi selama menulis kode *testing* (berdasarkan *code review* dan tes awal):

## 1. Bug Backend: Status Mutasi Bank Tidak Ter-Update Saat "Submit" Jurnal Satuan
- **Lokasi File:** `app/Http/Controllers/JournalEntryController.php`
- **Method:** `submit()`
- **Deskripsi:** 
  Pada fungsi `bulkSubmit` (aksi massal), ketika jurnal diubah statusnya menjadi `unapproved`, sistem dengan benar mencari data `BankMutation` yang terkait lalu mengubah status mutasinya menjadi `unapproved` juga. 
  Namun, pada fungsi `submit` tunggal (aksi satu-per-satu dari halaman detail/draft), **sistem lupa** melakukan pembaruan ke tabel `BankMutation`. Akibatnya, jurnalnya berubah menjadi `unapproved`, tetapi mutasi bank-nya tetap menyangkut di status `drafted`.
- **Dampak:** Inkosistensi status antara Transaksi (Mutasi) dan Jurnalnya.
- **Solusi yang sempat saya buat (dan sudah di-revert):**
  Menambahkan baris kode berikut di dalam method `submit()`:
  ```php
  \App\Models\BankMutation::where('journal_entry_id', $journalEntry->id)->update(['status' => 'unapproved']);
  ```

---

## 2. Bug Frontend: Komponen AppLayout Gagal Render (window.matchMedia)
- **Lokasi File:** `resources/js/Layouts/AppLayout.jsx` (Baris 34)
- **Deskripsi:** 
  Saat diuji, komponen *layout* utama melempar *error* `TypeError: window.matchMedia is not a function`. Hal ini terjadi karena kode mengecek preferensi tema (*dark mode*) dari sistem operasi/browser tanpa perlindungan (*fallback*), sehingga saat fungsi `matchMedia` tidak tersedia (seperti pada beberapa *environment* lawas atau saat *testing* via Node.js), komponen akan mati dan halaman menjadi kosong (Blank Screen).
- **Dampak:** Kerentanan pada sistem operasi/browser lama yang menyebabkan seluruh aplikasi gagal dimuat.
- **Saran Solusi:** 
  Tambahkan pengecekan eksistensi fungsi sebelum memanggilnya:
  ```javascript
  (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
  ```

## 3. Bug Frontend: Prop Mismatch di Komponen EditJournal
- **Lokasi File:** `resources/js/Pages/Transactions/EditJournal.jsx`
- **Deskripsi:**
  Komponen ini sangat bergantung pada struktur *prop* `entry.fiscal_period_id`, namun terkadang *backend* mengirimkannya dengan *key* nama yang berbeda (misalnya `journal` alih-alih `entry` dalam beberapa konteks). Hal ini mengakibatkan `TypeError: Cannot read properties of undefined (reading 'fiscal_period_id')`.
- **Dampak:** Aplikasi mengalami *crash* saat form edit coba dibuka, jika *prop* tidak dikirimkan secara sempurna dari *Controller*.
- **Saran Solusi:** 
  Tambahkan *fallback/default value* pada *destructuring* atau *prop-checking* sebelum form di-*render*.
