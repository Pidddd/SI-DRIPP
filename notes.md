# 📌 Gambaran Lengkap Struktur Folder SI-DRIPP

- **`assets/`** (Frontend - Kumpulan file pendukung desain visual)
  - `css/style.css` (Frontend - Mengatur warna dan tata letak)
  - `js/app.js` (Frontend - Mengatur interaksi browser seperti pop-up/klik)
  - `img/` (Frontend - Tempat menyimpan logo dan gambar aplikasi)

- **`components/`** (Frontend - Potongan desain UI yang dipakai berulang) <font color="red">Timoty</font>
  - `header.php` (Frontend - Tag pembuka HTML, pemanggil CSS, dan Navbar atas)
  - `sidebar.php` (Frontend - Menu navigasi kiri, dinamis sesuai role)
  - `footer.php` (Frontend - Tag penutup HTML dan pemanggil JS)

- **`config/`** (Backend - Mesin inti dan pengaturan aplikasi) <font color="navy">Rafid</font>
  - `bootstrap.php` (Backend - Mesin utama yang menjalankan session, memanggil database & guard. Semua file UI cukup panggil file ini di baris 1)
  - `auth_guard.php` (Backend - Polisi penjaga yang mengecek session login dan hak akses role)
  - `database.example.php` (Backend - Template koneksi DB untuk di-push ke GitHub, password dikosongkan)
  - `helper.php` (Backend - Kumpulan fungsi rumus bantuan prosedural, misal: format_rupiah)
  - _(File `database.php` yang berisi password asli disembunyikan oleh .gitignore)_

- **`docs/`** (Dokumen referensi tim) <font color="navy">Rafid</font>
  - `sidripp_database.sql` (Database - Cetak biru tabel untuk di-import ke phpMyAdmin)
  - `ERD_Revisi.jpg` (Referensi gambar relasi antar tabel)
  - `Proposal_PBL.pdf` (Referensi aturan bisnis aplikasi)

- **`modules/`** (Ruang kerja utama fitur aplikasi)
  - **`auth/`** (Modul Login) <font color="green">Iqlima</font>, <font color="brown">Toriq</font>
    - `login.php` (Frontend - Layar antarmuka form login)
    - `process/login.php` (Backend - Mengecek kecocokan password ke database & set session)
    - `process/logout.php` (Backend - Menghapus sesi login)

  - **`categories/`** (Modul Kategori Produk) <font color="green">Iqlima</font>, <font color="navy">Rafid</font>
    - `index.php` (Frontend - Menampilkan layar tabel daftar kategori)
    - `create.php` (Frontend - Menampilkan layar form tambah kategori)
    - `process/create.php` (Backend - Mengeksekusi simpan kategori baru ke database)

  - **`dashboard/`** (Modul Halaman Utama) <font color="red">Timoty</font>
    - `index.php` (Frontend - Menampilkan layar ringkasan statistik setelah login)

  - **`inventory/`** (Modul Gudang Fisik) <font color="red">Timoty</font>, <font color="brown">Toriq</font>
    - `stock_in.php` (Frontend - Menampilkan layar form pencatatan stok masuk vendor)
    - `defects.php` (Frontend - Menampilkan layar form laporan barang rusak/cacat)
    - `stock_opname.php` (Frontend - Menampilkan layar form pengecekan selisih stok)
    - `process/stock_in.php` (Backend - Eksekusi simpan histori restock & tambah stok aktual)
    - `process/defects.php` (Backend - Eksekusi simpan defect & kurangi stok aktual)
    - `process/stock_opname.php` (Backend - Eksekusi hitung selisih & simpan validasi)

  - **`products/`** (Modul Data Produk) <font color="green">Iqlima</font>, <font color="navy">Rafid</font>
    - `index.php` (Frontend - Menampilkan layar tabel daftar barang/katalog)
    - `create.php` (Frontend - Menampilkan layar form tambah barang)
    - `process/create.php` (Backend - Mengeksekusi simpan barang baru ke database)

  - **`receivables/`** (Modul Piutang / Hutang Pelanggan) <font color="red">Timoty</font>, <font color="brown">Toriq</font>
    - `index.php` (Frontend - Menampilkan layar tabel transaksi yang belum lunas/TOP)
    - `process/update_status.php` (Backend - Eksekusi mengubah status menjadi lunas)

  - **`reports/`** (Modul Laporan) <font color="green">Iqlima</font>, <font color="navy">Rafid</font>
    - `index.php` (Frontend - Menampilkan layar filter tanggal laporan)
    - `process/export_pdf.php` (Backend - Mengeksekusi query data rekap dan cetak PDF)

  - **`transactions/`** (Modul Kasir / POS) <font color="red">Timoty</font>, <font color="brown">Toriq</font>
    - `index.php` (Frontend - Menampilkan layar utama meja kasir)
    - `print_invoice.php` (Frontend - Menampilkan desain struk nota)
    - `process/checkout.php` (Backend - Mengeksekusi simpan nota, hitung diskon MOQ, & potong stok)

  - **`users/`** (Modul Pengguna Aplikasi) <font color="green">Iqlima</font>, <font color="navy">Rafid</font>
    - `index.php` (Frontend - Menampilkan layar tabel daftar akun karyawan)
    - `create.php` (Frontend - Menampilkan layar form tambah akun)
    - `process/create.php` (Backend - Mengeksekusi simpan akun baru ke database)

- **File di Luar (Root):**
  - `index.php` (Backend - Berfungsi sebagai polisi lalu lintas yang mengarahkan pengunjung web ke login atau dashboard)
  - `.gitignore` & folder `.github/` (Konfigurasi - Aturan kerahasiaan & workflow untuk GitHub)
  - `README.md`, `SOP.md`, `ToDo.md`, `AI-GUIDE.md` (Dokumentasi - Panduan teks untuk dibaca manusia dan AI)

---

**🔑 KUNCI SINGKAT UNTUK TIM:**

1. Jika file berada di area **luar folder `process/`** (seperti `index.php`, `create.php`, atau `edit.php`), itu adalah **FRONTEND**. Tugasnya HANYA mendesain tampilan HTML. Wajib tambahkan `require_once '../../config/bootstrap.php';` di baris 1.
2. Jika file berada di **dalam folder `process/`**, itu adalah **BACKEND**. Tugasnya MURNI PHP untuk menyimpan, mengubah, atau menghapus data ke database. **DILARANG KERAS ada desain tag HTML di dalam folder process**.
