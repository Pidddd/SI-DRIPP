# struktur folder yang jauh lebih simpel dan langsung *to the point* (Gambaran)

* **`assets/`** (Frontend - Kumpulan file desain)
* `css/style.css` (Frontend - Mengatur warna dan tata letak)
* `js/app.js` (Frontend - Mengatur interaksi seperti pop-up atau klik)

* **`components/`** (Frontend - Potongan desain yang dipakai berulang)
* `header.php` (Frontend - Bagian atas halaman)
* `sidebar.php` (Frontend - Menu navigasi kiri)
* `footer.php` (Frontend - Bagian paling bawah halaman)

* **`config/`** (Backend - Pengaturan inti aplikasi)
* `database.php` (Backend - Kunci penghubung PHP ke MySQL)
* `helper.php` (Backend - Kumpulan fungsi rumus bantuan)

* **`docs/`**
* `sidripp_database.sql` (Database - Cetak biru tabel yang di-import ke phpMyAdmin)

* **`modules/`** (Ruang kerja utama fitur aplikasi)

* **`auth/`**
* `login.php` (Frontend - Layar antarmuka form login)
* `process_login.php` (Backend - Mencocokkan password ke database)
* `logout.php` (Backend - Menghapus sesi login)

* **`products/`**
* `index.php` (Frontend - Menampilkan layar tabel daftar barang)
* `create.php` (Frontend - Menampilkan layar form tambah barang)
* `process_create.php` (Backend - Mengeksekusi aksi simpan barang ke database)

* **`inventory/`**
* `stock_in.php` (Frontend - Menampilkan layar pencatatan stok masuk)
* `defects.php` (Frontend - Menampilkan layar pencatatan barang rusak)
* `stock_opname.php` (Frontend - Menampilkan layar pengecekan stok fisik)

* **`transactions/`**
* `index.php` (Frontend - Menampilkan layar utama meja kasir)
* `print_invoice.php` (Frontend - Menampilkan desain struk nota)
* `process_checkout.php` (Backend - Mengeksekusi hitung diskon & memotong stok)

* **File di Luar (Root):**
* `index.php` (Backend - Berfungsi sebagai polisi lalu lintas yang mengarahkan user ke login atau dashboard)
* `.gitignore` & folder `.github/` (Konfigurasi - Aturan untuk GitHub)
* `README.md`, `SOP.md`, `ToDo.md`, `AI-GUIDE.md` (Dokumentasi - Panduan teks untuk dibaca manusia dan AI)

**Kunci Singkat untuk Tim:**
* Jika file bernama **`index.php` (di dalam modul), `create.php`, atau `edit.php**`, itu adalah **Frontend** (Tugasnya hanya mendesain tampilan dan form HTML).
* Jika file berawalan **`process_`**, itu adalah **Backend** (Tugasnya murni PHP untuk nyimpan, ubah, atau hapus data dari database, dilarang ada desain HTML di sini).