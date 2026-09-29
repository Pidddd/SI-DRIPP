# 🛒 SI-DRIPP (Sistem Informasi Pendataan Stok dan Transaksi)
> **CV Mitra Kembar Pradipta (MKP Store)**

Sistem Informasi Pendataan Stok dan Point of Sales (POS) tersentralisasi untuk CV Mitra Kembar Pradipta (MKP Store). Proyek ini dikembangkan untuk mendigitalkan alur kerja operasional, mencegah selisih stok, dan mengotomatiskan diskon HORECA.

Aplikasi berbasis web menggunakan **PHP Native** untuk mengelola pendataan barang, transaksi kasir (POS), dan mutasi stok gudang.

## 💡 Apa Itu PHP Native & Cara Menjalankannya?
**PHP Native** berarti kita membangun sistem ini dari nol menggunakan bahasa PHP murni, tanpa menggunakan kerangka kerja (*framework*) pihak ketiga seperti Laravel atau CodeIgniter. Semua logika bisnis (tambah, edit, hapus data) dan koneksi database kita tulis sendiri menggunakan *Object-Oriented Programming* (OOP) agar performanya ringan dan cepat.

**Langkah Menjalankan Aplikasi di Komputer Lokal:**
1. **Siapkan Server Lokal:** Pastikan kamu sudah menginstal **XAMPP** atau **Laragon**.
2. **Nyalakan Service:** Buka aplikasi XAMPP/Laragon, lalu klik **Start** pada modul **Apache** dan **MySQL**.
3. **Simpan Proyek:** Pindahkan folder `SI-DRIPP-main` ini ke dalam folder `C:\xampp\htdocs\` (jika pakai XAMPP).
4. **Siapkan Database:**
   - Buka browser dan ketik `http://localhost/phpmyadmin`.
   - Buat database baru bernama `sidripp_db`.
   - Import file `docs/sidripp_database.sql` ke dalam database tersebut.
5. **Jalankan Aplikasi:** Buka tab baru di browser dan ketik `http://localhost/SI-DRIPP-main`.

---

## 🚀 Fitur Utama (Target Sprint Iterasi 1 - 2 Minggu Ini)
Sesuai dengan jadwal Milestone PBL, fokus pengerjaan tim selama 2 minggu ke depan adalah meletakkan fondasi sistem dan modul dasar:
* Implementasi dan sinkronisasi tabel basis data (DDL) ke MySQL/PostgreSQL.
* Slicing antarmuka (Front-End) untuk Halaman Login dan Dashboard.
* Integrasi Modul Autentikasi Super Admin (Login, Logout, dan Proteksi Sesi).
* Modul CRUD Master Data Admin (Tambah, Edit, Hapus data Produk dan Kategori).

---

## ✅ Checklist Pengerjaan Keseluruhan Fitur

**👑 Hak Akses: Super Admin**
- [ ] Manajemen Pengguna: Pendaftaran akun staf baru, pembaruan data staf, dan penetapan role akses mutlak.

**💼 Hak Akses: Admin (Manajer / Owner)**
- [ ] Dashboard Analitik: Menampilkan total omzet dan peringatan "Sisa Stok Menipis" untuk stok di bawah 2 karton.
- [ ] Kelola Master Data Produk: CRUD nama barang, kategori, dan vendor.
- [ ] Manajemen Piutang (TOP): Penyortiran jatuh tempo teratas dan integrasi tombol pesan WhatsApp ke pelanggan.
- [ ] Rekapitulasi Penjualan Global: Filter rentang waktu dan ekspor data ke format PDF/Spreadsheet.
- [ ] Keamanan Data: Proteksi fitur hapus data riwayat operasional menggunakan verifikasi PIN.

**💵 Hak Akses: Petugas Kasir**
- [ ] Katalog POS Interaktif: Kalkulasi otomatis diskon MOQ untuk minimal pembelian 2 karton.
- [ ] Formulir Checkout: Opsi status pembayaran Lunas atau Piutang (TOP) beserta input batas tempo (hari) manual.
- [ ] Transaksi Real-time: Pencetakan otomatis invoice ke format PDF dan pemotongan stok global secara otomatis.
- [ ] Riwayat Transaksi Kasir: Melihat detail pesanan harian dan kontak pembeli.

**📦 Hak Akses: Staf Gudang**
- [ ] Modul Barang Masuk: Menambah stok dari pabrik/vendor (status default lunas).
- [ ] Modul Barang Defect: Memisahkan stok cacat/rusak dengan kolom keterangan alasan spesifik.
- [ ] Modul Stock Opname Interaktif: Sinkronisasi data sistem vs fisik dengan input selisih (+1/-1) dan indikator validasi warna.

---

## 🛠️ Tech Stack
*   **Front-End:** HTML5, CSS3, JavaScript, Bootstrap/Tailwind CSS
*   **Back-End:** PHP (OOP & MVC Architecture)
*   **Database:** MySQL / PostgreSQL
*   **Design/Prototyping:** Figma

## 👥 Tim Pengembang (Kelompok 4 - 404: Error Found)
1.  **Ahmad Rafid Riqkullah** - Project Manager & Database
2.  **I Gusti Agung Timothy P.A.P.** - QA & Back-End
3.  **Inas Asami El Murtadho** - Front-End & UI/UX
4.  **Muhammad Toriq Januarsyah** - QA & Database

## 📂 Struktur Folder Proyek
Agar pengerjaan tidak bentrok dan sesuai standar PBO (OOP), tim wajib menyimpan file sesuai dengan struktur kerangka MVC berikut:

```text
SI-DRIPP/
├── classes/                  # Tempat menyimpan Class OOP (Model)
│   ├── Database.php          # Class enkapsulasi koneksi database
│   ├── User.php              # Class Induk manajemen pengguna & hak akses
│   ├── Product.php           # Class cetak biru master data produk
│   ├── Transaction.php       # Class untuk logika transaksi & kalkulasi diskon
│   └── Inventory.php         # Class khusus manajemen stok, defect, & opname
├── index.php                 # Halaman utama / Dashboard (mengarahkan sesuai Role)
├── README.md                 # Halaman sampul penjelasan repositori
├── SOP.md                    # Standar Operasional Prosedur Git Workflow Tim
├── ToDo.md                   # File checklist pekerjaan tim
├── includes/                 # Folder komponen reuasable (Header, Footer, Sidebar)
│   ├── header.php            # Bagian atas HTML + tag <head> + Navbar
│   ├── footer.php            # Bagian bawah HTML + tag </body>
│   └── sidebar.php           # Menu samping dinamis (berubah tergantung Role)
├── assets/                   # Folder untuk file statis (Front-End)
│   ├── css/style.css         # Styling kustom (pelengkap Bootstrap/Tailwind)
│   ├── js/app.js             # Script untuk interaksi UI
│   └── img/                  # Folder logo MKP Store / gambar statis (berisi .gitkeep)
├── auth/                     # Modul Autentikasi Sistem
│   ├── login.php             # Halaman form login
│   ├── process_login.php     # Validasi username, password, & set $_SESSION Role
│   └── logout.php            # Proses penghapusan sesi pengguna
├── users/                    # Modul Manajemen Pengguna (Akses Mutlak: Super Admin)
│   ├── index.php             # Tabel daftar seluruh akun pegawai MKP Store
│   ├── create.php            # Form tambah akun pegawai baru & penetapan Role
│   └── process_user.php      # Validasi dan simpan/edit/cabut akses akun di database
├── products/                 # Modul Master Data Produk (Akses: Admin)
│   ├── index.php             # List katalog barang
│   ├── create.php            # Form tambah produk/kategori/vendor baru
│   └── process_create.php    # Validasi server + simpan data ke database
├── transactions/             # Modul Kasir / POS (Akses: Kasir)
│   ├── index.php             # Katalog interaktif & Keranjang HORECA
│   ├── process_checkout.php  # Kalkulasi diskon MOQ, potong stok global, input TOP
│   └── print_invoice.php     # Halaman format Invoice siap checkout/cetak (PDF)
├── inventory/                # Modul Manajemen Inventaris (Akses: Staf Gudang)
│   ├── stock_in.php          # Halaman input restock barang dari pabrik/vendor
│   ├── defects.php           # Halaman pemindahan stok cacat/rusak
│   └── stock_opname.php      # Modul interaktif penyesuaian stok sistem vs fisik
└── docs/                     # Folder Dokumentasi Proyek
    ├── PBL_Proposal.pdf      # Tempat menyimpan proposal yang sudah di-acc
    └── sidripp_database.sql  # Backup file tabel dan relasi database mentah