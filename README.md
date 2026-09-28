# SI-DRIPP (Sistem Informasi Pendataan Stok dan Transaksi) 📦

Sistem Informasi Pendataan Stok dan Point of Sales (POS) tersentralisasi untuk CV Mitra Kembar Pradipta (MKP Store). Proyek ini dikembangkan untuk mendigitalkan alur kerja operasional, mencegah selisih stok, dan mengotomatiskan diskon HORECA.

## 🚀 Fitur Utama
*   **Multi-Role Access:** Super Admin, Admin (Manajer), Kasir, dan Staf Gudang.
*   **POS Kasir:** Pencetakan otomatis *invoice* PDF & kalkulasi diskon Minimum Order Quantity (MOQ).
*   **Real-time Inventory:** Pemotongan stok global secara otomatis saat transaksi terjadi.
*   **Manajemen Piutang:** Pelacakan Term of Payment (TOP) untuk klien HORECA.
*   **Stock Opname Interaktif:** Modul sinkronisasi data fisik vs sistem.

## 🛠️ Tech Stack
*   **Front-End:** HTML5, CSS3, JavaScript, Bootstrap/Tailwind CSS
*   **Back-End:** PHP (MVC Architecture)
*   **Database:** MySQL / PostgreSQL
*   **Design/Prototyping:** Figma

## 👥 Tim Pengembang (Kelompok 4 - 404: Error Found)
1.  **Ahmad Rafid Riqkullah** - Project Manager & Database
2.  **I Gusti Agung Timothy P.A.P.** - QA & Back-End
3.  **Inas Asami El Murtadho** - Front-End & UI/UX
4.  **Muhammad Toriq Januarsyah** - QA & Database

## 📂 Struktur Folder Proyek
Agar pengerjaan tidak bentrok, tim wajib menyimpan file sesuai dengan struktur kerangka MVC (Model-View-Controller) berikut:

```text
SI-DRIPP/
├── classes/                  # 🌟 FOLDER BARU: Tempat menyimpan Class OOP
│   ├── Database.php          # Class untuk koneksi ke database
│   ├── User.php              # Class Induk untuk pengguna
│   ├── Product.php           # Class untuk cetak biru barang
│   └── Transaction.php       # Class untuk proses POS kasir
├── index.php                 # Halaman utama / Dashboard (mengarahkan sesuai Role)
├── README.md                 # Halaman sampul penjelasan repositori
├── TODO.md                   # File checklist pekerjaan tim
├── includes/                 # Folder komponen yang dipakai berulang kali
│   ├── connection.php        # File untuk koneksi ke database MySQL/PostgreSQL
│   ├── header.php            # Bagian atas HTML + tag <head> + Navbar
│   ├── footer.php            # Bagian bawah HTML + tag </body>
│   └── sidebar.php           # Menu samping dinamis (berubah tergantung Role)
├── assets/                   # Folder untuk file statis (Front-End)
│   ├── css/style.css         # Styling kustom (pelengkap Bootstrap/Tailwind)
│   ├── js/app.js             # Script untuk interaksi UI
│   └── img/                  # Folder untuk logo MKP Store / gambar statis
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
│   └── print_invoice.php     # Halaman format Invoice siap cetak (PDF)
├── inventory/                # Modul Manajemen Inventaris (Akses: Staf Gudang)
│   ├── stock_in.php          # Halaman input restock barang dari pabrik/vendor
│   ├── defects.php           # Halaman pemindahan stok cacat/rusak
│   └── stock_opname.php      # Modul interaktif penyesuaian stok sistem vs fisik
└── docs/                     # Folder Dokumentasi Proyek
    ├── PBL_Proposal.pdf      # Tempat menyimpan proposal yang sudah di-acc
    └── sidripp_database.sql  # Backup file tabel dan relasi database mentah
```