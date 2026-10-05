# Panduan Tugas dan Alur Kerja Sistem (To-Do List)

## A. Alur Kerja Sistem (System Flow) SI-DRIPP

Untuk memahami bagaimana aplikasi ini berjalan, pahami siklus data berikut:

1. **Master Data (`modules/users` & `modules/products`):** Sistem dimulai dari Admin yang mendaftarkan akun pegawai dan memasukkan data katalog produk. Stok awal produk diatur ke 0.
2. **Barang Masuk (`modules/inventory/stock_in.php`):** Karyawan gudang mencatat suplai barang yang datang dari pabrik/vendor. Sistem akan menambah `Stok_Aktual` di tabel `products`.
3. **Kasir / POS (`modules/transactions`):** Karyawan kasir melayani pembeli. Saat tombol _Checkout_ ditekan:
   - Data pelanggan dan total harga masuk ke tabel `Transactions`.
   - Rincian belanja masuk ke tabel `Transaction_Details`.
   - `Stok_Aktual` di tabel `products` berkurang otomatis sesuai jumlah yang dibeli.
4. **Piutang / TOP (`modules/receivables`):** Jika transaksi kasir tadi ditandai "Belum Lunas", data akan muncul di halaman Receivables. Modul ini digunakan untuk memantau jatuh tempo dan memproses pelunasan hutang pelanggan.
5. **Penyesuaian Gudang (`modules/inventory`):** Jika ada barang yang rusak/hilang, gudang menggunakan form `defects.php` untuk memotong stok. Untuk penyesuaian besar-besaran, gunakan `stock_opname.php`.

## B. Panduan Tugas Per File (Pembagian Kerja Tim)

Setiap anggota tim akan ditugaskan untuk menghidupkan fungsi-fungsi di bawah ini:

### 1. Folder `modules/products/` & `modules/users/` (Modul Master Data)

- **Status Saat Ini:** Read (Index) dan Create sudah selesai.
- **Tugas Selanjutnya:**
  - `edit.php`: Buat antarmuka yang menangkap `$_GET['id']`, lalu ambil data lama dari database untuk ditampilkan ke dalam nilai `input` form.
  - `process_edit.php`: Buat logika validasi POST dan eksekusi query `UPDATE` ke database.
  - `delete.php`: Buat script yang menangkap `$_GET['id']`, eksekusi query `DELETE`, lalu `header('Location: index.php')`.

### 2. Folder `modules/inventory/` (Modul Gudang)

- **Status Saat Ini:** Hanya ada file UI form, belum bisa memproses data.
- **Tugas Selanjutnya:**
  - `process_inventory.php`: Buat satu file utama yang menangani _submit_ dari 3 form berbeda.
  - Jika kiriman berasal dari form `stock_in.php` ➔ Eksekusi query `UPDATE products SET Stok_Aktual = Stok_Aktual + [Kuantitas]`.
  - Jika kiriman berasal dari form `defects.php` ➔ Eksekusi query `UPDATE products SET Stok_Aktual = Stok_Aktual - [Kuantitas]`, dan simpan catatan/keterangan rusaknya.
  - Jika dari `stock_opname.php` ➔ Sesuaikan stok dan catat selisihnya.

### 3. Folder `modules/transactions/` (Modul Kasir)

- **Status Saat Ini:** Hanya UI kasir kosong, logika inti belum ada.
- **Tugas Selanjutnya:**
  - `process_checkout.php`: Tulis logika penyimpanan multi-tabel. **Wajib menggunakan Transaction Control (`pg_query($conn, "BEGIN")`)**.
    1. Lakukan `INSERT` ke tabel `Transactions`.
    2. Dapatkan ID transaksi yang baru dibuat (`RETURNING id_transaksi`).
    3. Lakukan `foreach` pada data keranjang. Di dalam _looping_, jalankan `INSERT` ke `Transaction_Details` dan `UPDATE` potong stok ke tabel `products`.
    4. Jika sukses semua, lakukan `pg_query($conn, "COMMIT")`. Jika gagal, `pg_query($conn, "ROLLBACK")`.
  - `print_invoice.php`: Buat layout struk pembayaran khusus printer kasir dan picu `window.print()` pada JavaScript.

### 4. Folder `modules/receivables/` (Modul Piutang)

- **Status Saat Ini:** Belum ada file sama sekali.
- **Tugas Selanjutnya:**
  - `index.php`: Buat antarmuka tabel yang menampilkan hasil query `SELECT` dari tabel `Transactions` dengan filter `WHERE Status_Pembayaran = 'Belum Lunas'`.
  - `process_payment.php`: Buat logika query `UPDATE` untuk mengubah `Status_Pembayaran` menjadi 'Lunas' ketika pelanggan menyelesaikan tagihannya.

### 5. Folder `modules/dashboard/` (Modul Laporan)

- **Status Saat Ini:** Belum ada file.
- **Tugas Selanjutnya:**
  - `index.php`: Buat tampilan ringkasan (widget) menggunakan fungsi `COUNT()` dan `SUM()` PostgreSQL untuk menampilkan: Total Transaksi Hari Ini, Total Piutang Berjalan, dan Daftar Barang yang Stoknya Menipis.
