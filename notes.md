# 📌 Gambaran Lengkap Struktur Folder SI-DRIPP & Pembagian PIC Revisi Dosen

- **`assets/`** (Frontend - Kumpulan file pendukung desain visual)
  - `css/style.css` (Frontend - Mengatur warna, tata letak, accordion batch, modal POS, & highlight merah overdue) **'_inas_'**, **'_timothy_'**
  - `js/app.js` (Frontend - Mengatur interaksi browser seperti pop-up/klik)
  - `img/` (Frontend - Tempat menyimpan logo dan gambar aplikasi)

- **`layouts/`** (Frontend - Kerangka UI global yang dipanggil di semua modul)
  - `header.php` (Frontend - Tag pembuka HTML, head tags & pemanggilan CSS)
  - `sidebar.php` (Frontend - Menu navigasi kiri berbasis Role RBAC)
  - `footer.php` (Frontend - Tag penutup HTML dan pemanggilan JS)
  - `mock_data.php` (Frontend - Dummy data statis sementara)

- **`config/`** (Backend - Mesin inti dan pengaturan aplikasi) **'_rafid_'**
  - `bootstrap.php` (Backend - Inisialisasi awal. Semua file UI cukup panggil file ini di baris 1)
  - `auth_guard.php` (Backend - Mengecek session login dan hak akses role)
  - `database.php` & `database.example.php` (Backend - Koneksi PostgreSQL Supabase via `pg_connect`)
  - `helper.php` (Backend - Kumpulan fungsi rumus bantuan prosedural)

- **`docs/`** (Dokumen referensi tim & skema database) **'_rafid_'**, **'_timothy_'**
  - `sidripp_database.sql` (Database - DDL 9 tabel 3NF, View, Trigger FEFO, Batch Expired, & Tabel `customers`) **'_rafid_'**
  - `PANDUAN_KODE_TIM.md` (Dokumentasi panduan arsitektur & persiapan tanya jawab sidang)
  - `WBS_Revisi_Dosen.md` (Tabel WBS Tracker terbaru pasca-revisi dosen + Prompt AI siap pakai per anggota)

- **`modules/`** (Ruang kerja utama fitur aplikasi — **1 File = 1 PIC Bebas Konflik**)
  - **`auth/`** (Modul Login & Lupa Password — **Revisi Poin 9**)
    - `login.php` (Frontend - Layar antarmuka form login + link Lupa Password) **'_inas_'**
    - `forgot_password.php` (Frontend - Layar form verifikasi & reset password baru) **'_inas_'**
    - `process/login.php` (Backend - Mengecek kecocokan password ke database & set session) **'_toriq_'**
    - `process/forgot_password.php` (Backend - Validasi identitas & update `password_hash` ke tabel `users`) **'_toriq_'**
    - `process/logout.php` (Backend - Menghapus sesi login) **'_toriq_'**

  - **`categories/`** (Modul Kategori Produk)
    - `index.php` (Frontend - Menampilkan layar tabel daftar kategori) **'_inas_'**
    - `create.php` (Frontend - Menampilkan layar form tambah/edit kategori) **'_inas_'**
    - `process/create.php` (Backend - Mengeksekusi simpan/ubah/hapus kategori ke database) **'_rafid_'**

  - **`dashboard/`** (Modul Halaman Utama)
    - `index.php` (Frontend - Menampilkan layar ringkasan statistik & peringatan stok kritis) **'_timothy_'**

  - **`inventory/`** (Modul Gudang Fisik — **Revisi Poin 2, 3, & 4**)
    - `index.php` (Frontend - Tabel stok fisik dengan Bulk Mass Update `[-][+]` + Save All & Accordion `^`/`v` Expired Date per Batch) **'_timothy_'**
    - `stock_in.php` (Frontend - Form pencatatan barang masuk khusus Batch Baru + Input Tanggal Kedaluwarsa) **'_timothy_'**
    - `defect.php` (Frontend - Tabel Bulk Input barang rusak/cacat `[-][+]` dengan 1 tombol Save All) **'_inas_'**
    - `opname.php` (Frontend - Tabel Bulk Audit perbandingan stok sistem vs fisik dengan 1 tombol Save All) **'_timothy_'**
    - `process/stock_in.php` (Backend - Eksekusi Bulk Update stok & simpan Batch Baru beserta `expired_date`) **'_toriq_'**
    - `process/defects.php` (Backend - Eksekusi Bulk Input potong stok barang defect secara atomik) **'_toriq_'**
    - `process/stock_opname.php` (Backend - Eksekusi Bulk Sinkronisasi selisih audit & auto-cut batch expired) **'_rafid_'**

  - **`products/`** (Modul Master Data Produk — **Revisi Poin 1**)
    - `index.php` (Frontend - Menampilkan layar tabel daftar barang/katalog) **'_inas_'**
    - `create.php` (Frontend - Form tambah/edit produk TANPA input jumlah stok) **'_inas_'**
    - `process/create.php` (Backend - Simpan produk baru dengan `stok_aktual = 0` & edit tanpa menyentuh stok) **'_rafid_'**

  - **`receivables/`** (Modul Manajemen Piutang TOP & Pelunasan — **Revisi Poin 8**)
    - `index.php` (Frontend - Tabel piutang dengan prioritas `ORDER BY` Overdue di paling atas & highlight merah menyala) **'_inas_'**
    - `process/update_status.php` (Backend - Eksekusi pelunasan tagihan) **'_toriq_'**

  - **`reports/`** (Modul Laporan)
    - `index.php` (Frontend - Menampilkan layar rekapitulasi dari `v_laporan_penjualan` & GROUP BY/HAVING) **'_timothy_'**
    - `process/export_pdf.php` (Backend - Eksekusi cetak PDF laporan dengan query CTE) **'_rafid_'**
    - `process/delete_report.php` (Backend - Eksekusi hapus rekap dengan otorisasi PIN) **'_rafid_'**

  - **`transactions/`** (Modul Kasir / POS — **Revisi Poin 6**)
    - `index.php` (Frontend - Layar utama kasir POS + Searchable Dropdown Mitra PT + Auto-fill Data PJ + Modal Tambah Mitra Baru) **'_timothy_'**
    - `history.php` (Frontend - Riwayat transaksi harian) **'_timothy_'**
    - `print_invoice.php` (Frontend - Desain struk nota resmi) **'_timothy_'**
    - `process/create_customer.php` (Backend - Simpan data pelanggan/Mitra PT baru dari Modal POS ke tabel `customers`) **'_toriq_'**
    - `process/checkout.php` (Backend - Eksekusi simpan nota dengan `customerid_customer`, validasi subquery stok, & trigger potong stok) **'_toriq_'**

  - **`users/`** (Modul Pengguna Aplikasi)
    - `index.php` (Frontend - Menampilkan layar tabel daftar akun karyawan) **'_inas_'**
    - `create.php` (Frontend - Menampilkan layar form tambah/edit akun) **'_inas_'**
    - `process/create.php` (Backend - Mengeksekusi simpan akun baru ke database) **'_rafid_'**

- **File di Luar (Root):**
  - `index.php` (Backend - Mengarahkan pengunjung web ke login atau dashboard sesuai session)

---

**🔑 KUNCI SINGKAT UNTUK TIM:**

1. Jika file berada di area **luar folder `process/`** (seperti `index.php`, `create.php`, atau `forgot_password.php`), itu adalah **FRONTEND**. Tugasnya mendesain tampilan HTML dan membaca data `SELECT`.
2. Jika file berada di **dalam folder `process/`**, itu adalah **BACKEND**. Tugasnya MURNI PHP Prosedural (`pg_query_params`) untuk menyimpan, mengubah, atau menghapus data ke database. **DILARANG KERAS ada desain tag HTML di dalam folder `process/`**.
