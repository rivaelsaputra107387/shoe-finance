<div align="center">
  
  # Finlog — Sistem Keuangan Internal
  **Platform Tata Buku & Laporan Keuangan Berbasis Double-Entry**
</div>

---

## 📌 Tentang Proyek
**Finlog** adalah sistem informasi akuntansi dan pencatatan keuangan internal (*General Ledger*) yang dibangun khusus untuk mengelola pembukuan, transaksi ganda (*double-entry*), serta mencetak laporan keuangan (Buku Besar, Laba Rugi, dsb.) secara presisi.

Sistem ini dikembangkan menggunakan tumpukan teknologi modern:
- **Framework Utama:** [Laravel 11](https://laravel.com)
- **Admin Panel & UI:** [Filament v3](https://filamentphp.com) + [Livewire v3](https://livewire.laravel.com)
- **Styling:** [Tailwind CSS v4](https://tailwindcss.com/)

---

## 🚀 Panduan Instalasi (Untuk Tim Developer)

Ikuti langkah-langkah di bawah ini untuk meng-*clone* dan menjalankan aplikasi Finlog di komputer lokal (localhost) Anda.

### 1. Kebutuhan Sistem (Prerequisites)
Pastikan Anda sudah menginstal aplikasi berikut di komputer Anda:
- **PHP** (Minimal versi 8.2 atau terbaru)
- **Composer** (Untuk *package manager* PHP)
- **Node.js** & **NPM** (Minimal v18+)
- **MySQL / SQLite** (Sistem ini secara *default* sudah siap jalan dengan SQLite local, namun Anda bisa mengubahnya ke MySQL di file `.env`).

### 2. Clone Repositori
Silakan *clone branch* utama dari GitHub dan masuk ke dalam folder proyek:
```bash
git clone https://github.com/rivaelsaputra107387/shoe-finance.git
cd shoe-finance
```

### 3. Install Dependencies
Instal semua modul PHP dan *library* pendukung *front-end*:
```bash
# Instal modul PHP
composer install

# Instal modul JavaScript (Tailwind, dll)
npm install
```

### 4. Konfigurasi Environment (.env)
*Copy* file `.env.example` bawaan menjadi file `.env` yang sesungguhnya:
```bash
cp .env.example .env
```
Lalu *generate* kunci aplikasi:
```bash
php artisan key:generate
```

### 5. Setup Database & Seeding Data
Sistem ini dilengkapi dengan data *dummy* otomatis (termasuk CoA/Akun, Periode Fiskal, dan Transaksi Jurnal Demo) agar Anda bisa langsung melihat wujud aplikasinya. Jalankan perintah ini:
```bash
php artisan migrate:fresh --seed
```
*(Catatan: Jika ditanya apakah ingin membuat file database sqlite, ketik `yes`)*

### 6. Jalankan Server Lokal
Anda harus menjalankan dua terminal secara bersamaan agar fungsi aplikasi (PHP) dan *styling* (CSS/Vite) dapat berjalan sempurna.

**Terminal 1 (Backend):**
```bash
php artisan serve
```

**Terminal 2 (Frontend/Vite):**
```bash
npm run dev
```

---

## 🔑 Akses Login Aplikasi

Setelah server berjalan, buka browser Anda dan akses alamat:
👉 **[http://localhost:8000/admin](http://localhost:8000/admin)**

Gunakan akun berikut untuk mencoba aplikasinya:
*   **Email:** `owner@shoeworkshop.com` ATAU `staff@shoeworkshop.com`
*   **Password:** `password`

---

## 📂 Struktur Utama Pekerjaan (Untuk Navigasi Tim)
Jika Anda anggota tim yang ingin ikut campur mengembangkan *codebase*, berikut titik-titik krusial yang perlu Anda ketahui:
*   **Logika Akuntansi Utama:** Ada di folder `app/Services/` (LedgerService, IncomeStatementService).
*   **UI Dashboard & Form:** Diatur lewat komponen `app/Filament/Widgets` dan `app/Livewire/`.
*   **View Kustom:** Berada di `resources/views/filament/` dan `resources/views/livewire/`.
*   **Skema Database:** Terpusat di `database/migrations/` dan `database/seeders/`.

> **Selamat Mengkoding! Silakan buat branch baru jika ingin menambahkan fitur.**
