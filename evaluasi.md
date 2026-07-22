
# Evaluasi Arsitektur Modul ShoeFinance

Berdasarkan pengecekan pada *codebase* Anda, saat ini proyek menggunakan **Filament versi 3.x (tepatnya ^3.3)** berdasarkan file `composer.json`. Pada Filament v3, pembuatan Custom Page menggunakan Livewire 3 terintegrasi dengan sangat baik.

Dari pengecekan di folder `app/Filament/`, saat ini arsitektur sudah cukup berada di jalur yang benar. Anda memisahkan entitas master ke `Resources` (`AccountResource`, `FiscalPeriodResource`, `JournalEntryResource`) dan laporan-laporan berat ke `Pages` (`BalanceSheet`, `IncomeStatement`, `GeneralLedger`, dll).

Berikut klasifikasi lengkap dari ke-14 modul berdasarkan spesifikasi Anda:

| Modul                                | Rekomendasi                      | Alasan Singkat                                                                                                                                                                                                        | Status Saat Ini                                                                                                          |
| ------------------------------------ | -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **1. COA (Kode Akun)**         | **Resource**               | Murni CRUD master data dengan filter sederhana.                                                                                                                                                                       | Sudah ada (`AccountResource`).                                                                                         |
| **2. Divisi**                  | **Resource**               | Murni CRUD master data tanpa agregasi yang rumit.                                                                                                                                                                     | Belum ada. Perlu dibuat Resource baru.                                                                                   |
| **3. Transaksi (Form Harian)** | **Custom Page + Livewire** | Entri jurnal harian butuh validasi*real-time* (Debit = Kredit), penambahan baris dinamis (Repeater yang berat jika murni standar), dan *state* khusus yang kurang nyaman jika dipaksa di halaman Create Resource. | Sudah ada berupa Custom Page (`CreateJournalEntry`) dan komponen Livewire (`journal-entry-form`). Teruskan pola ini. |
| **4. Jurnal Umum**             | **Resource**               | Hanya butuh*List* data (Tabel). Bisa dibuat `ReadOnly` Resource (disable create/edit/delete) dengan kemampuan search/filter bawaan Filament.                                                                      | Sudah ada (`JournalEntryResource`). Sebaiknya dipastikan mode-nya ReadOnly/dibatasi.                                   |
| **5. Buku Besar**              | **Custom Page**            | Butuh kalkulasi*running balance* (saldo berjalan), agregasi per akun, yang formatnya bukan *grid/table* biasa.                                                                                                    | Sudah ada (`GeneralLedger`).                                                                                           |
| **6. Laba Rugi**               | **Custom Page**            | Layout hierarkis bersarang (Pendapatan -> HPP -> Laba Kotor -> Beban), bukan tabular mentah.                                                                                                                          | Sudah ada (`IncomeStatement`).                                                                                         |
| **7. Neraca**                  | **Custom Page**            | Layout khusus (Aktiva di kiri/atas, Pasiva di kanan/bawah), dengan validasi persamaan dasar akuntansi.                                                                                                                | Sudah ada (`BalanceSheet`).                                                                                            |
| **8. Arus Kas**                | **Custom Page**            | Pengelompokan khusus (Aktivitas Operasi, Investasi, Pendanaan). Agregasi non-standar.                                                                                                                                 | Sudah ada (`CashFlowStatement`).                                                                                       |
| **9. Perubahan Ekuitas**       | **Custom Page**            | Perhitungan saldo awal + laba - prive/dividen yang butuh query agregasi khusus.                                                                                                                                       | Sudah ada (`EquityStatement`).                                                                                         |
| **10. HPP per Layanan**        | **Custom Page**            | Filter kompleks (`kategori='hpp'` dan `divisi=Workshop`), mungkin butuh *pivot table* atau *chart* breakdown.                                                                                                 | Belum ada.                                                                                                               |
| **11. Aset & Penyusutan**      | **Kombinasi**              | **Resource:** Untuk CRUD daftar Aset. **Custom Action/Page:** Untuk men-*trigger* jurnal penyusutan massal bulanan.                                                                                     | Belum ada. Buat`AssetResource` untuk daftar aset.                                                                      |
| **12. Sewa & Alokasi**         | **Kombinasi**              | Sama seperti Aset. Pencatatan awal berupa**Resource**, namun alokasi amortisasinya sebaiknya via **Custom Action/Command**.                                                                               | Belum ada.                                                                                                               |
| **13. RAB Forecast**           | **Custom Page**            | Tabel perbandingan Target vs Aktual (varians), layout*spreadsheet-like* sangat tidak cocok dengan Resource standar.                                                                                                 | Belum ada.                                                                                                               |
| **14. Dashboard Utama**        | **Filament Dashboard**     | Gunakan`Dashboard` bawaan Filament (sebenarnya ini Custom Page juga), isikan dengan Custom Widgets (Stats Overview, Chart) dari Filament.                                                                           | Sudah ada (bawaan Filament), tinggal tambahkan custom widgets (saat ini folder`Widgets` masih kosong).                 |

### Catatan Tambahan (Filament v3):

Pendekatan Anda membuat `CreateJournalEntry` sebagai Page terpisah di Filament (sementara `JournalEntry` punya Resource sendiri) sudah sangat tepat untuk meng-*handle* UX input yang rumit.

Namun, jika *logic* input jurnal ini masih sangat terikat dengan entitas `JournalEntry`, di Filament v3 Anda juga bisa me-replace halaman `CreateRecord` milik `JournalEntryResource` dengan komponen Livewire kustom sepenuhnya. Keduanya valid, namun Custom Page mandiri kadang lebih bersih (tidak tercampur *routing* Resource).

Viewed BalanceSheet.php:1-327
Viewed AdminPanelProvider.php:1-85
Searched for "balance"
Viewed balance-sheet.blade.php:1-18

Berdasarkan pengecekan langsung ke file `BalanceSheet.php`, `balance-sheet.blade.php`, dan `AdminPanelProvider.php`, berikut adalah *assessment* jujur dan jawaban atas pertanyaan Anda:

### 1. Masih Pakai Komponen Filament atau Murni Blade?

**Jawabannya: Masih sangat terikat dengan komponen Filament.**

Kesulitan yang Anda alami persis berasal dari sini. Jika Anda lihat di `BalanceSheet.php`, hampir 200 baris kode digunakan hanya untuk membuat `public function reportInfolist(Infolist $infolist)`. Di dalamnya, Anda menggunakan komponen struktural bawaan Filament seperti `Card`, `Grid`, `Section`, `RepeatableEntry`, dan `TextEntry`.

Lalu, pada file `resources/views/filament/pages/balance-sheet.blade.php`, halamannya hanya melakukan render komponen tersebut:

```blade
{{ $this->reportInfolist }}
```

Ini yang membuat Anda merasa "susah di-custom". Anda mencoba memaksakan layout hierarkis dan kustom (khas laporan Neraca) ke dalam wadah `Infolist` yang sebenarnya didesain untuk menampilkan profil data standar.

### 2. Apakah Sudah Ada Custom Theme?

**Jawabannya: Belum ada.**

Di dalam `App\Providers\Filament\AdminPanelProvider.php`, Anda hanya menggunakan kostumisasi warna bawaan:

```php
->colors([
    'primary' => Color::Indigo,
    'danger' => Color::Rose,
    // ...
])
```

Proyek ini belum menjalankan `php artisan make:filament-theme` (terlihat dari tidak adanya method `->viteTheme('resources/css/filament/admin/theme.css')` pada provider). Artinya, proyek ini masih menumpang pada *compiled CSS* bawaan Filament.

### 3. Langkah Paling Efisien untuk Replikasi Visual Referensi?

**Jawabannya: Hapus Infolist dan Edit File Blade-nya.**

Untuk membuat laporan seperti *Balance Sheet* terlihat persis seperti referensi (misal: tabel dengan *indentation* khusus, garis bawah ganda untuk total, warna *header* khusus), Anda **tidak perlu** mengandalkan `Infolist`.

Cara paling efisien:

1. Hapus (atau abaikan) metode `reportInfolist()` di `BalanceSheet.php`.
2. Buka `resources/views/filament/pages/balance-sheet.blade.php` dan buat struktur HTML/Tailwind Anda sendiri murni menggunakan *array* `$reportData` yang sudah di-*generate* di PHP.

Contoh kasarnya di dalam Blade:

```blade
@if(!empty($reportData))
   <div class="bg-white shadow rounded-lg p-6 w-full text-sm text-gray-700">
       <h2 class="text-xl font-bold text-center mb-4">NERACA</h2>
       <!-- Buat tabel flex/grid murni pakai Tailwind sesuka Anda -->
       <div class="grid grid-cols-2 gap-8">
           <!-- Kolom Aset -->
           <div>
                <h3 class="font-bold border-b-2 border-black">ASET</h3>
                @foreach($reportData['current_assets'] as $asset)
                    <div class="flex justify-between">
                        <span>{{ $asset['name'] }}</span>
                        <span>{{ number_format($asset['balance']) }}</span>
                    </div>
                @endforeach
           </div>
           <!-- Kolom Kewajiban -->
           <!-- ... -->
       </div>
   </div>
@endif
```

Apakah butuh Setup Custom Theme baru? **Biasanya TIDAK**. Meskipun Anda belum setup custom theme, class utilitas dasar Tailwind (seperti `flex`, `grid`, `text-center`, `p-6`, `bg-white`) tetap bisa digunakan dan di-render dengan baik di dalam halaman Filament. Custom theme hanya diwajibkan jika referensi Anda menggunakan palet warna kustom khusus (seperti brand color di luar default Tailwind) atau plugin Tailwind tambahan.

### Kesimpulan (Assessment Jujur)

Kesulitan kustomisasi Anda **bukan** karena batasan struktural dari Filament, melainkan karena Anda belum sepenuhnya "melepaskan" Custom Page tersebut. Anda sudah benar membuat Custom Page terpisah, tapi Anda masih memasukkan "ruh" Resource (menggunakan `Infolist`) ke dalamnya.

Untuk halaman *report* (seperti Buku Besar, Laba Rugi, Neraca), langsung saja "lempar" datanya dari class PHP ke file Blade, dan susun layout-nya sesuka hati menggunakan HTML & Tailwind murni di Blade-nya.

Apakah Anda ingin saya memberikan contoh konversi `balance-sheet.blade.php` menjadi layout tabel Neraca yang lebih rapi menggunakan HTML/Tailwind murni, tanpa `Infolist`?
