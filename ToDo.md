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

---
# 🔄 Alur Kerja Sistem (System Flow) SI-DRIPP

Dokumen ini menjelaskan bagaimana alur kerja antar-file dan folder di dalam sistem SI-DRIPP agar seluruh tim (Front-End & Back-End) memiliki pemahaman arsitektur yang sama saat menulis kode.

## 1. Konsep Arsitektur: Berbasis Modul (Module-Based)
Sistem kita **TIDAK** memisahkan folder berdasarkan jabatan (misal: `folder_admin/` atau `folder_kasir/`). Kita menggunakan pemisahan berdasarkan **Fitur/Modul Sistem** (seperti `products/`, `transactions/`, `inventory/`, `users/`). 

**Kenapa?** Agar penulisan kode tidak redundan. Jika Admin dan Kasir sama-sama butuh melihat katalog produk, mereka akan mengakses folder yang sama, namun dengan tombol akses (Edit/Hapus) yang disembunyikan sesuai otoritas jabatannya.

## 2. Alur Hak Akses (Role-Based Access)
Pusat kendali siapa yang bisa melihat apa, diatur melalui siklus *Login* dan *Sidebar*:
1. **Login:** Pengguna masuk melalui antarmuka `auth/login.php`.
2. **Validasi:** Data dilempar ke `auth/process_login.php` untuk dicocokkan dengan *database* melalui logika di `classes/User.php`.
3. **Penyimpanan Sesi:** Jika cocok, sistem mencatat status pengguna ke dalam fungsi bawaan PHP yaitu `$_SESSION` (contoh: `$_SESSION['role'] = 'Admin'`).
4. **Navigasi Dinamis:** Saat masuk ke Dashboard (`index.php`), komponen `includes/sidebar.php` akan membaca `$_SESSION['role']` tersebut. *Sidebar* akan melakukan filter logika (`if-else`) untuk menyembunyikan atau memunculkan tautan menu sesuai *role* pengguna saat itu.

## 3. Alur Tampilan Halaman (UI Assembly)
Untuk mencegah Front-End menulis ulang struktur dasar HTML (`<head>`, `<nav>`, `<footer>`) di setiap halaman, file antarmuka pada modul fitur (misal `products/index.php`) disusun layaknya *puzzle* dengan perintah `require_once`:

```php
<?php
require_once '../includes/header.php';  // Merender tag <head>, CSS, dan Header
require_once '../includes/sidebar.php'; // Merender navigasi menu samping
?>

<!-- KONTEN HALAMAN DITULIS DI SINI -->
<div class="main-content">
    <h2>Katalog Produk</h2>
    <!-- Tabel atau Form dimasukkan di sini -->
</div>

<?php
require_once '../includes/footer.php';  // Merender tag </body> dan script JS
?>