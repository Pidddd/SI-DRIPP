# 📝 Panduan Tugas Per File (Pembagian Kerja Tim)

File ini menjelaskan fungsi setiap file PHP yang ada di struktur direktori kita, agar tim Back-End (Timothy & Toriq) dan Front-End (Inas) tahu persis di mana harus menulis kodenya.

## 1. Folder `classes/` (Pusat Logika Back-End / OOP)
Di sini kita akan menulis *query* database. Dilarang menulis perintah SQL di luar folder ini .
- `Database.php`: Berisi konfigurasi PDO (Host, User, Password, DB Name) dan fungsi untuk koneksi ke database.
- `User.php`: Berisi fungsi untuk cek login, hashing password, dan CRUD data pegawai (Super Admin).
- `Product.php`: Berisi fungsi CRUD barang, pengecekan stok minimum (<2 karton), dan pengurangan stok otomatis.
- `Transaction.php`: Berisi fungsi untuk menyimpan nota penjualan, kalkulasi diskon MOQ, dan manajemen TOP (utang).
- `Inventory.php`: Berisi fungsi untuk mencatat mutasi barang masuk, barang defect, dan pencatatan stock opname.

## 2. Folder `includes/` (Komponen UI Front-End)
Inas akan memotong (slicing) elemen desain yang berulang ke sini agar tidak perlu diketik berkali-kali.
- `header.php`: Berisi tag `<head>`, pemanggilan file CSS Bootstrap/Tailwind, dan navigasi atas (Navbar).
- `sidebar.php`: Berisi menu navigasi samping yang dinamis (menu akan disembunyikan/ditampilkan berdasarkan pengecekan `$_SESSION['role']`).
- `footer.php`: Berisi tag penutup `</body>` dan pemanggilan *script* JavaScript.

## 3. Folder `auth/` (Sistem Login)
- `login.php`: Tampilan form login (Input Username & Password).
- `process_login.php`: File logika murni (tanpa HTML) untuk mengecek kecocokan password ke `classes/User.php` lalu menyimpan sesi `$_SESSION['user_id']` dan `$_SESSION['role']`.
- `logout.php`: File untuk menghancurkan sesi (`session_destroy()`) dan melempar *user* kembali ke `login.php`.

## 4. Folder Modul Fitur (`users/`, `products/`, `inventory/`, `transactions/`)
Ini adalah halaman antarmuka utama. Setiap folder memiliki struktur logika yang sama:
- `index.php`: Halaman utama modul yang menampilkan tabel data (View). Contoh: `products/index.php` menampilkan tabel daftar barang.
- `create.php` / `defects.php` / dll: Halaman yang berisi formulir (Form HTML) untuk menginput data baru.
- `process_*.php` (misal `process_create.php`): File tak terlihat (Back-End) yang menerima data `$_POST` dari formulir, lalu mengirimkannya ke class terkait di folder `classes/`, dan terakhir melakukan *redirect* kembali ke `index.php`.
- `print_invoice.php` (di folder transactions): Khusus untuk menarik data nota dan me-render-nya ke bentuk layout cetak/PDF.

## 5. File Root
- `index.php`: Ini adalah halaman Dashboard Utama. File ini akan mengecek siapa yang sedang login, lalu menampilkan widget grafik omzet (untuk Admin) atau sekadar ucapan selamat datang (untuk Kasir/Gudang).