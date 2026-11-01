# 📌 Daftar Tugas Tim Pasca-Konsultasi & Revisi Dosen (SI-DRIPP)

Dokumen ini memetakan pembagian tugas **9 Poin Revisi Dosen Penguji** secara spesifik per anggota tim (Kelompok 404). Pembagian file telah dirancang **1 File = 1 PIC** agar seluruh anggota tim dapat bekerja secara paralel **tanpa mengalami bentrok kode (*merge conflict*) di Git**.

---

## ⚠️ Aturan Mutlak Arsitektur Proyek (Wajib Dipatuhi Semua Anggota)
1. **Paradigma:** PHP Native Prosedural Modular murni (**DILARANG KERAS** memakai OOP, `class`, `interface`, atau framework MVC).
2. **Database Driver:** PostgreSQL Native (`pg_connect`, `pg_query`, `pg_query_params`). **DILARANG** menggunakan PDO (`$pdo`) atau `mysqli`.
3. **Keamanan SQL Injection:** Seluruh query yang menerima input dari user (`$_POST` / `$_GET`) **WAJIB** menggunakan `pg_query_params($conn, $sql, $params)`.
4. **Pemisahan Layer (Separation of Concerns):**
   - File di luar folder `process/` hanya untuk tampilan UI/HTML.
   - File di dalam folder `process/` murni logika PHP + SQL + Redirect (`header("Location: ...")`), **dilarang ada tag HTML**.
   - **Zero Inline CSS:** Dilarang menulis atribut `style="..."` atau tag `<style>` di file PHP. Semua class baru ditambahkan ke `assets/css/style.css`.

---

## 👨‍💻 1. Tugas Ahmad Rafid Riqkullah
**Peran:** Project Manager, Database Administrator (DDL/Supabase), & Backend Master/Gudang Core

### 🛠️ File yang Dikerjakan (Bebas Konflik):
1. **`docs/sidripp_database.sql` (Revisi Poin 3, 6, 9 — Skema Database & Trigger)**
   - **Poin 3 (Batch Expired Date):** Tambahkan kolom `kode_batch VARCHAR(40)`, `expired_date DATE`, `sisa_stok_batch INTEGER NOT NULL DEFAULT 0 CHECK (sisa_stok_batch >= 0)`, dan `status_batch VARCHAR(20) DEFAULT 'aktif'` pada tabel `restocks`.
   - **Poin 3 (Trigger FEFO & Auto-Expired):** Perbarui fungsi trigger `kurangi_stok_otomatis()` agar memotong `sisa_stok_batch` berdasarkan tanggal kedaluwarsa terdekat (*First Expired, First Out*), serta buat fungsi SQL `proses_batch_kedaluwarsa()` untuk memotong stok otomatis dan mencatat ke tabel `defects` jika `expired_date < CURRENT_DATE`.
   - **Poin 6 (Master Pelanggan/Mitra PT):** Buat tabel baru `customers` (`id_customer SERIAL PRIMARY KEY`, `nama_pt VARCHAR(100) UNIQUE NOT NULL`, `nama_pj VARCHAR(100) NOT NULL`, `nik_pj VARCHAR(20) NOT NULL`, `no_telp VARCHAR(20) NOT NULL`, `alamat_toko TEXT NOT NULL`, `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`) dan tambahkan kolom `customerid_customer INTEGER REFERENCES customers(id_customer) ON DELETE SET NULL` pada tabel `transactions`.
   - **Poin 9 (Lupa Password):** Tambahkan kolom `no_telp VARCHAR(20)` / `kode_pemulihan VARCHAR(100)` pada tabel `users` untuk verifikasi reset password.
2. **`modules/products/process/create.php` (Revisi Poin 1 — Backend Master Produk)**
   - Saat mode `create` (`INSERT`), paksa nilai `stok_aktual = 0` secara otomatis tanpa membaca `$_POST['stok']`.
   - Saat mode `edit` (`UPDATE`), hapus `stok_aktual` dari klausa `SET` agar stok produk murni hanya bisa diubah melalui modul Gudang.
3. **`modules/inventory/process/stock_opname.php` (Revisi Poin 2 & 3 — Backend Bulk Update & Opname)**
   - Ubah pemrosesan dari 1 produk tunggal menjadi *looping* array `$_POST['stok_fisik'][$id_product]` dengan `BEGIN`, `COMMIT`, dan `ROLLBACK` untuk menyimpan banyak perubahan stok lintas kategori sekaligus dalam 1 klik tombol **"Save All"**.

---

## ⚙️ 2. Tugas Muhammad Toriq Januarsyah
**Peran:** Back-End Developer (Auth, Transaksi ACID, Logika Proses Gudang) & QA

### 🛠️ File yang Dikerjakan (Bebas Konflik):
1. **`modules/auth/process/forgot_password.php` *(File Baru)* (Revisi Poin 9 — Backend Lupa Password)**
   - Tangkap input `username`, verifikasi identitas (`jenis_kelamin` / `nama` / `kode_pemulihan`), dan `password_baru` dari form `forgot_password.php`.
   - Validasi kecocokan konfirmasi password, enkripsi dengan `password_hash($password_baru, PASSWORD_DEFAULT)`, lalu jalankan `UPDATE users SET password = $1 WHERE id_user = $2` menggunakan `pg_query_params`.
2. **`modules/inventory/process/stock_in.php` (Revisi Poin 2 & 3 — Backend Barang Masuk & Batch Kedaluwarsa)**
   - Dukung 2 mode pengiriman dari form gudang:
     - **Mode Bulk Mass Update (`mode = bulk_update`):** Looping array `$_POST['delta_stok'][$id_product]` dari tabel `inventory/index.php` dalam 1 transaksi `BEGIN ... COMMIT` untuk menambah/mengurangi stok banyak produk sekaligus.
     - **Mode Batch Baru (`mode = new_batch`):** Simpan data kedatangan batch baru dari `stock_in.php` ke tabel `restocks` lengkap dengan `kode_batch`, `expired_date`, `kuantitas`, dan `sisa_stok_batch`, sekaligus menambah `stok_aktual` di tabel `products`.
3. **`modules/inventory/process/defects.php` (Revisi Poin 4 — Backend Bulk Input Barang Defect)**
   - Refactor dari single-item menjadi *looping* array `$_POST['kuantitas'][$id_product]` dan `$_POST['keterangan_rusak'][$id_product]`.
   - Gunakan transaksi `BEGIN ... COMMIT / ROLLBACK` dan `SELECT ... FOR UPDATE`: hanya proses baris produk yang nilai kuantitas rusaknya `> 0`, insert ke tabel `defects`, dan potong `stok_aktual` di `products` (serta `sisa_stok_batch` terkait).
4. **`modules/transactions/process/create_customer.php` *(File Baru)* & `modules/transactions/process/checkout.php` (Revisi Poin 6 — Backend Mitra PT POS)**
   - Buat `create_customer.php` untuk menyimpan data Mitra PT baru dari modal POS (`nama_pt`, `nama_pj`, `nik_pj`, `no_telp`, `alamat_toko`) ke tabel `customers` via `pg_query_params`.
   - Perbarui `checkout.php` agar menangkap `customerid_customer` dari dropdown POS dan menyimpannya ke tabel `transactions`.

---

## 🎨 3. Tugas Inas Asami El Murtadho
**Peran:** UI/UX & Front-End Developer (Master Produk, UI Defect, UI Piutang, UI Auth)

### 🛠️ File yang Dikerjakan (Bebas Konflik):
1. **`modules/products/create.php` (Revisi Poin 1 — UI Master Produk Tanpa Input Stok)**
   - Hapus blok `<div class="field">` untuk `<input type="number" id="stok" name="stok">` dari form Tambah/Edit Produk.
   - Ganti dengan *callout/badge* informasi bahwa penambahan stok awal maupun restock hanya dilakukan melalui menu **Gudang & Logistik**.
2. **`modules/inventory/defect.php` (Revisi Poin 4 — UI Bulk Input Barang Defect)**
   - Ubah form dropdown tunggal menjadi **Tabel Bulk Input** yang menampilkan daftar produk aktif beserta stok saat ini.
   - Sediakan kontrol tombol `[-]` `[input angka qty rusak]` `[+]` dan kolom input *Keterangan Kerusakan* di setiap baris, dengan **1 tombol utama "Save All Laporan Defect"** di bagian atas/bawah tabel.
3. **`modules/receivables/index.php` & `assets/css/style.css` (Revisi Poin 8 — Prioritas & Highlight Merah Menyala Piutang Overdue)**
   - Perbarui query `ORDER BY` di `modules/receivables/index.php` agar transaksi yang *Overdue* (melewati tanggal jatuh tempo dan belum lunas) selalu dikunci di urutan paling atas (`CASE WHEN ... < CURRENT_DATE THEN 0 ...`).
   - Tambahkan class `tr-overdue-glow` pada elemen `<tr>` untuk baris yang *overdue* dan definisikan styling warna merah menyala (latar merah terang lembut, *border-left* merah tegas, dan teks/badge merah kontras) di `assets/css/style.css`.
4. **`modules/auth/login.php` & `modules/auth/forgot_password.php` *(File Baru)* (Revisi Poin 9 — UI Lupa Password)**
   - Tambahkan link **"Lupa Password?"** di halaman `modules/auth/login.php`.
   - Buat halaman `modules/auth/forgot_password.php` dengan desain identik *login-shell* untuk form verifikasi akun dan input password baru yang mengarah ke `process/forgot_password.php`.

---

## 🖥️ 4. Tugas I Gusti Agung Timothy P.A.P.
**Peran:** Front-End Developer & Technical Writer (UI Inventaris Bulk & Accordion Expired, UI POS Mitra PT, Dokumentasi Mitra)

### 🛠️ File yang Dikerjakan (Bebas Konflik):
1. **`modules/inventory/index.php`, `modules/inventory/stock_in.php`, & `modules/inventory/opname.php` (Revisi Poin 2 & 3 — UI Bulk Mass Update & Accordion Kedaluwarsa)**
   - **`modules/inventory/index.php`:**
     - Bungkus tabel stok dengan 1 form Bulk Update yang memiliki tombol `[-]` `[input angka]` `[+]` pada tiap baris produk lintas kategori dan **1 tombol "Save All Perubahan Stok"**.
     - Tambahkan tombol **Accordion (`v` / `^`)** di setiap baris produk. Saat diklik, tampilkan baris detail (*expandable sub-row*) yang mengelompokkan botol berdasarkan **Tanggal Kedaluwarsa (`expired_date`)** per batch beserta indikator status (*Aman / Segera Expired / Expired*).
   - **`modules/inventory/stock_in.php`:**
     - Khususkan halaman ini untuk form **Catat Barang Masuk (Batch Baru)** dengan menambahkan input **Kode Batch** (`kode_batch`) dan **Tanggal Kedaluwarsa** (`expired_date`).
   - **`modules/inventory/opname.php`:**
     - Ubah form per baris menjadi **1 form tabel utuh** dengan tombol `[-]` `[input stok fisik]` `[+]` dan **1 tombol "Save All Stock Opname"**.
2. **`modules/transactions/index.php` (Revisi Poin 6 — Searchable Dropdown & Modal Mitra PT di Kasir POS)**
   - Ganti input ketik manual `nama_pelanggan` di keranjang belanja POS dengan **Searchable Dropdown / Autocomplete** yang mengambil data dari tabel `customers`.
   - Tambahkan tampilan detail otomatis (*auto-fill readonly*): **Nama PT, Nama Penanggung Jawab (PJ), NIK/Identitas PJ, No. Telp/WA, dan Alamat Toko** yang langsung terisi otomatis via JavaScript saat kasir memilih nama PT.
   - Buat tombol **"+ Tambah Pelanggan/Mitra Baru"** di area keranjang yang memunculkan **Pop-up Modal** untuk mendaftarkan PT baru ke `process/create_customer.php`.
3. **Dokumentasi Akhir & User Manual Mitra (WBS `5.2.1` — Revisi Poin 5 & Poin 7)**
   - **Poin 5 (Kebijakan Kapasitas Database Mitra):** Tulis bab khusus di *User Manual* & Laporan Serah Terima Mitra MKP Store bahwa seluruh data transaksi/stok historis **dilarang dihapus** demi audit akuntansi, disertai panduan rekomendasi upgrade kapasitas database cloud (Supabase Pro / *Unlimited Storage*).
   - **Poin 7 (Backlog Barcode Member):** Dokumentasikan rancangan fitur *Barcode Member Pelanggan* pada bab *Saran Pengembangan Lanjut (Future Improvement / Product Backlog)*.

---

## 📦 Daftar Backlog / Pengembangan Tahap Lanjut (Tidak Dikerjakan di Sprint Saat Ini)
- **Poin 7 — Fitur Scan Barcode Member Pelanggan di POS:** Setiap mitra PT memiliki kartu/kode barcode khusus yang dapat di-scan menggunakan *barcode scanner* di halaman Kasir POS untuk memilih pelanggan secara otomatis. *(Disimpan di Product Backlog sesuai arahan dosen).*
