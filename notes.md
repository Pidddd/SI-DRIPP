# 📌 Gambaran Lengkap Struktur Folder SI-DRIPP

- **`assets/`** (Frontend - Kumpulan file pendukung desain visual)
  - `css/style.css` (Frontend - Mengatur warna dan tata letak)
  - `js/app.js` (Frontend - Mengatur interaksi browser seperti pop-up/klik)
  - `images/` (Frontend - Tempat menyimpan logo dan gambar aplikasi)

- **`layouts/`** (Frontend - Kerangka UI global yang dipanggil di semua modul)
  - `header.php` (Frontend - Tag pembuka HTML, head tags & pemanggilan CSS)
  - `sidebar.php` (Frontend - Menu navigasi kiri)
  - `footer.php` (Frontend - Tag penutup HTML dan pemanggilan JS)
  - `mock_data.php` (Frontend - Dummy data statis sementara)

- **`config/`** (Backend - Mesin inti dan pengaturan aplikasi) **'_rafid_'**
  - `bootstrap.php` (Backend - Inisialisasi awal. Semua file UI cukup panggil file ini di baris 1)
  - `auth_guard.php` (Backend - Mengecek session login dan hak akses role)
  - `database.example.php` (Backend - Template koneksi DB untuk di-push ke GitHub)
  - `helper.php` (Backend - Kumpulan fungsi rumus bantuan prosedural)
  - _(File `database.php` yang berisi password asli disembunyikan oleh .gitignore)_

- **`docs/`** (Dokumen referensi tim) **'_rafid_'**
  - `sidripp_database.sql` (Database - Cetak biru tabel untuk di-import ke pgAdmin)

- **`modules/`** (Ruang kerja utama fitur aplikasi)
  - **`auth/`** (Modul Login) **'_inas_'**, **'_toriq_'**
    - `login.php` (Frontend - Layar antarmuka form login)
    - `process/login.php` (Backend - Mengecek kecocokan password ke database & set session)
    - `process/logout.php` (Backend - Menghapus sesi login)

  - **`categories/`** (Modul Kategori Produk) **'_inas_'**, **'_rafid_'**
    - `index.php` (Frontend - Menampilkan layar tabel daftar kategori)
    - `create.php` (Frontend - Menampilkan layar form tambah kategori)
    - `process/create.php` (Backend - Mengeksekusi simpan kategori baru ke database)

  - **`dashboard/`** (Modul Halaman Utama) **'_timothy_'**
    - `index.php` (Frontend - Menampilkan layar ringkasan statistik)

  - **`inventory/`** (Modul Gudang Fisik) **'_timothy_'**, **'_toriq_'**
    - `index.php` (Frontend - Menampilkan tabel stok fisik)
    - `stock_in.php` (Frontend - Menampilkan form pencatatan stok masuk)
    - `defect.php` (Frontend - Menampilkan form laporan barang rusak/cacat)
    - `opname.php` (Frontend - Menampilkan form pengecekan selisih stok)
    - `process/stock_in.php` (Backend - Eksekusi simpan restock)
    - `process/defects.php` (Backend - Eksekusi simpan defect)
    - `process/stock_opname.php` (Backend - Eksekusi hitung selisih audit)

  - **`products/`** (Modul Data Produk) **'_inas_'**, **'_rafid_'**
    - `index.php` (Frontend - Menampilkan layar tabel daftar barang/katalog)
    - `create.php` (Frontend - Menampilkan layar form tambah barang)
    - `process/create.php` (Backend - Mengeksekusi simpan barang baru)

  - **`receivables/`** (Modul Manajemen Piutang TOP & Pelunasan) **'_Timothy_'**, **'_Toriq_'**
    - `index.php` (Frontend - Menampilkan tabel piutang & integrasi WhatsApp)
    - `process/update_status.php` (Backend - Eksekusi pelunasan tagihan)

  - **`reports/`** (Modul Laporan) **'_inas_'**, **'_rafid_'**
    - `index.php` (Frontend - Menampilkan layar rekapitulasi & modal PIN)
    - `process/export_pdf.php` (Backend - Eksekusi cetak PDF)
    - `process/delete_report.php` (Backend - Eksekusi hapus rekap)

  - **`transactions/`** (Modul Kasir / POS) **'_timothy_'**, **'_toriq_'**
    - `index.php` (Frontend - Menampilkan layar utama meja kasir)
    - `history.php` (Frontend - Riwayat transaksi harian)
    - `print_invoice.php` (Frontend - Desain struk nota)
    - `process/checkout.php` (Backend - Eksekusi simpan nota, diskon, & potong stok)

  - **`users/`** (Modul Pengguna Aplikasi) **'_inas_'**, **'_rafid_'**
    - `index.php` (Frontend - Menampilkan layar tabel daftar akun karyawan)
    - `create.php` (Frontend - Menampilkan layar form tambah akun)
    - `process/create.php` (Backend - Mengeksekusi simpan akun baru ke database)

- **File di Luar (Root):**
  - `index.php` (Backend - Berfungsi sebagai polisi lalu lintas yang mengarahkan pengunjung web ke login atau dashboard)

---

**🔑 KUNCI SINGKAT UNTUK TIM:**

1. Jika file berada di area **luar folder `process/`** (seperti `index.php`, `create.php`, atau `edit.php`), itu adalah **FRONTEND**. Tugasnya HANYA mendesain tampilan HTML. Wajib tambahkan `require_once '../../config/bootstrap.php';` di baris 1.
2. Jika file berada di **dalam folder `process/`**, itu adalah **BACKEND**. Tugasnya MURNI PHP untuk menyimpan, mengubah, atau menghapus data ke database. **DILARANG KERAS ada desain tag HTML di dalam folder process**.