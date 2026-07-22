Shoe Workshop — Sistem Laporan Keuangan 

**SHOE WORKSHOP** Sistem Informasi Laporan Keuangan 

# **ENTITY RELATIONSHIP DIAGRAM (ERD)** 

_Sistem Informasi Laporan Keuangan_ 

|**Nama Proyek**|Sistem Laporan Keuangan Shoe Workshop|
|---|---|
|**Versi Dokumen**|1.0|
|**Tanggal**|14 Juli 2026|
|**Disusun oleh**|System Analyst|
|**Database**|MySQL 8.x|



1 

Shoe Workshop — Sistem Laporan Keuangan 

## Daftar Isi 

2 



<!-- Start of picture text -->
jurnal_umum<br>users el PK id PK, INT<br>PK periode_akuntansi FK periode_id FK: periode_akuntansi<br>PK id rolesPK, INT. ———»N | FKnameidrole_id VARCHAR(100)Pk,FK: INTroles |—_—--+N  | tanggal_muleinama_periodePK id VARCHAR(50)PK,DATEINT. >N tanggalno_bukti VARCHAR(30)DATE<br>nama_role VARCHAR(50) a email VARCHAR(100) q tanggal_selesai DATE ba keteranganan TEXT 1<br>password VARCHAR(255) status ENUM(open,closed) jenis ENUM(umum,penutup)<br>. .<br>is_active status ENUM(draft,posted)<br>BOOLEAN FK closed_by -FK: users FK created_by FK: users<br>1 “ FK posted_by FK: users<br>audit_trail<br>PK id PK, INT<br>FK user_id FK: users .<br>n_ | tabel VARCHAR(50) saldo_awal jurnal_detail<br>aksi VARCHAR(20) PK id PK, INT PK id PK, INT<br>data_lama JSON “| FK periode_id FK: periode_akuntensi FK jurnal_id FK: jurnal_umum<br>data_baru JSON FK coa_id FK: coa FK coa_id — FK: coa<br>created_at TIMESTAMP saldo DECIMAL(18,2) debet DECIMAL(18,2)<br>N posisi ENUM (debet, kredit) kredit DECIMAL(18,2)<br>=.| keterangan VARCHAR(255)<br>coa<br>PK id PK, INT<br>FK perent_id FK: coa self-ref<br>kode_akun VARCHAR(LO)<br>1 name_akun VARCHAR(1L00)<br>tipe_akun VARCHAR(50) ‘<br>saldo_normal ENUM(debet, kredit)<br>pos_laporan ENUM(neraca,laba_rugi)<br>kategori_arus_kas ENUM(operasi,investasi,pendanaan)<br>is_active BOOLEAN<br><!-- End of picture text -->

Shoe Workshop — Sistem Laporan Keuangan 

## <u>3. Deskripsi Entitas</u> 

|**Entitas**|**Deskripsi**|
|---|---|
|roles|Daftar peran pengguna: Admin, Staff Finance, Owner/Manajemen|
|users|Akun pengguna sistem beserta peran yang dimiliki|
|periode_akuntansi|Rentang waktu pelaporan (mis. Juni 2026), berstatus open/closed|
|coa|Chart of Account/daftar akun, termasuk tipe, saldo normal, dan posisi<br>pada laporan|
|saldo_awal|Saldo awal setiap akun pada setiap periode akuntansi|
|jurnal_umum|Header transaksi jurnal (tanggal, no bukti, status, jenis)|
|jurnal_detail|Baris debet/kredit dari sebuah jurnal, terhubung ke akun COA|
|audit_trail|Log setiap perubahan data penting (siapa, apa, kapan)|



## 4. Kamus Data (Data Dictionary) 

### <u>4.1 Tabel roles</u> 

|**Kolom**|**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT UNSIGNED, PK|Identitas unik|
|nama_role|VARCHAR(50), UNIQUE|Admin / Staff Finance / Owner-Manajemen|



### <u>4.2 Tabel users</u> 

|**Kolom**|**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT UNSIGNED, PK|Identitas unik|
|role_id|BIGINT UNSIGNED, FK -><br>roles.id|Peran pengguna|
|name|VARCHAR(100)|Nama pengguna|
|email|VARCHAR(100), UNIQUE|Email login|
|password|VARCHAR(255)|Password ter-hash|
|is_active|BOOLEAN|Status aktif/nonaktif|



### <u>4.3 Tabel periode_akuntansi</u> 

|<br>**Kolom**|<br>**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT<br>UNSIGNED, PK|Identitas unik|
|nama_periode|VARCHAR(50)|Contoh: Juni 2026|
|tanggal_mulai /<br>tanggal_selesai|DATE|Rentang tanggal periode|
|status|ENUM(open,<br>closed)|Status buka/tutup periode|
|closed_by|BIGINT<br>UNSIGNED, FK -><br>users.id|User yang menutup periode|



### <u>4.4 Tabel coa</u> 

|**Kolom**|**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT UNSIGNED, PK|Identitas unik|



4 

Shoe Workshop — Sistem Laporan Keuangan 

|**Kolom**|**Tipe**|**Keterangan**|
|---|---|---|
|parent_id|BIGINT UNSIGNED, FK -><br>coa.id|Akun induk (opsional, untuk sub-akun)|
|kode_akun|VARCHAR(10), UNIQUE|Contoh: 1101, 4110|
|nama_akun|VARCHAR(100)|Contoh: Kas, Pendapatan Jasa|
|tipe_akun|VARCHAR(50)|Kategori akun|
|saldo_normal|ENUM(debet, kredit)|Posisi saldo normal akun|
|pos_laporan|ENUM(neraca, laba_rugi)|Akun masuk laporan Neraca atau Laba Rugi|
|kategori_arus_kas|ENUM(operasi, investasi,<br>pendanaan)|Klasifikasi untuk Laporan Arus Kas (nullable)|



### <u>4.5 Tabel saldo_awal</u> 

|**Kolom**|**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT UNSIGNED, PK|Identitas unik|
|periode_id|BIGINT UNSIGNED, FK -><br>periode_akuntansi.id|Periode terkait|
|coa_id|BIGINT UNSIGNED, FK -><br>coa.id|Akun terkait|
|saldo|DECIMAL(18,2)|Nominal saldo awal|
|posisi|ENUM(debet, kredit)|Posisi saldo awal|



### <u>4.6 Tabel jurnal_umum</u> 

|<br>**Kolom**|<br>**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT UNSIGNED, PK|Identitas unik|
|periode_id|BIGINT UNSIGNED, FK -><br>periode_akuntansi.id|Periode transaksi|
|tanggal|DATE|Tanggal transaksi|
|no_bukti|VARCHAR(30), UNIQUE|Nomor referensi bukti|
|jenis|ENUM(umum, penutup)|Jenis jurnal|
|status|ENUM(draft, posted)|Status jurnal|
|created_by / posted_by|BIGINT UNSIGNED, FK -><br>users.id|Pembuat dan pemroses posting|



### <u>4.7 Tabel jurnal_detail</u> 

|**Kolom**|**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT UNSIGNED, PK|Identitas unik|
|jurnal_id|BIGINT UNSIGNED, FK -><br>jurnal_umum.id|Header jurnal terkait|
|coa_id|BIGINT UNSIGNED, FK -><br>coa.id|Akun yang didebet/dikredit|
|debet / kredit|DECIMAL(18,2)|Nominal debet atau kredit (salah satu harus 0)|



4.8 Tabel audit_trail 

5 

Shoe Workshop — Sistem Laporan Keuangan 

|**Kolom**|**Tipe**|**Keterangan**|
|---|---|---|
|id|BIGINT UNSIGNED, PK|Identitas unik|
|user_id|BIGINT UNSIGNED, FK -><br>users.id|Pengguna yang melakukan aksi|
|tabel / record_id|VARCHAR(50) / BIGINT|Tabel dan baris data yang diubah|
|aksi|VARCHAR(20)|create/update/delete/post/close_period|
|data_lama / data_baru|JSON|Snapshot data sebelum dan sesudah perubahan|



## 5. Penjelasan Relasi Antar Entitas 

- roles 1—N users: satu peran dapat dimiliki banyak pengguna. 

- users 1—N periode_akuntansi (sebagai penutup periode) dan 1—N jurnal_umum (sebagai pembuat/pemroses). 

- coa 1—N coa: relasi rekursif untuk mendukung sub-akun (parent-child). 

- periode_akuntansi 1—N saldo_awal dan 1—N jurnal_umum: setiap periode memiliki saldo awal dan transaksinya sendiri. 

- coa 1—N saldo_awal dan 1—N jurnal_detail: setiap akun dapat memiliki banyak saldo awal (beda periode) dan banyak baris transaksi. 

- jurnal_umum 1—N jurnal_detail: satu jurnal terdiri dari minimal dua baris (debet dan kredit). 

- users 1—N audit_trail: setiap aksi pengguna yang relevan tercatat sebagai satu baris log. 

6 

