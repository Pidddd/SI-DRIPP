# 🛒 SI-DRIPP (Sistem Informasi Pendataan Stok dan Transaksi)

> **CV Mitra Kembar Pradipta (MKP Store)**

SI-DRIPP adalah sistem informasi berbasis web yang dirancang khusus untuk mendigitalkan dan mengotomatisasi proses bisnis di CV Mitra Kembar Pradipta (MKP Store). Sistem ini menangani alur dari pengelolaan master data produk, pencatatan pergerakan stok gudang secara real-time, pemrosesan transaksi kasir (Point of Sales), hingga manajemen piutang (Term of Payment) pelanggan secara terpusat.

---

## ✨ Fitur Utama

- **Autentikasi Multi-Role:** Akses aman yang dibedakan berdasarkan peran (Super Admin, Kasir, Gudang).
- **Master Data Management:** Pengelolaan katalog produk dan data karyawan.
- **Inventory Tracking:** Pencatatan stok masuk (_restock_), barang cacat (_defect_), dan penyesuaian stok (_stock opname_).
- **Point of Sales (POS):** Antarmuka kasir untuk memproses transaksi penjualan, menghitung diskon otomatis, dan mencetak nota.
- **Manajemen TOP (Piutang):** Pelacakan tagihan pelanggan yang belum lunas beserta fitur pelunasannya.

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

## 👥 Role Pengguna

Sistem bersifat tertutup (tanpa fitur registrasi publik) dengan 4 role utama[cite: 2]:

1. `super_admin`
2. `admin`
3. `kasir`
4. `staf_gudang`

---

## 🛠️ Tech Stack

- **Bahasa Pemrograman:** PHP Native (Prosedural, Non-OOP, Non-Framework)
- **Database:** PostgreSQL
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Version Control:** Git & GitHub
- **Design/Prototyping:** Figma

## 👥 Tim Pengembang (Kelompok 4 - 404: Error Found)

| Nama                         |      NIM       | Role                       |
| :--------------------------- | :------------: | :------------------------- |
| Ahmad Rafid Riqkullah        | [254107020078] | Project Manager & Database |
| I Gusti Agung Timothy P.A.P. | [254107020157] | Front-End & Documentation  |
| Inas Asami El Murtadho       | [254107020165] | Front-End & UI/UX          |
| Muhammad Toriq Januarsyah    | [254107020075] | Back-End & QA              |

## 📂 Struktur Folder Proyek

```text
SI-DRIPP/
├── .github/
│   ├── pull_request_template.md     (Template checklist saat tim bikin PR)
│   └── workflows/
│       ├── auto-reviewer.yml        (Robot pengecek kode GitHub)
│       └── php-test.yml             (Robot pengetes kode GitHub)
│
├── assets/
│   ├── css/
│   │   └── style.css          <-- Custom styling (Tema Toska MKP & Bento Grid)
│   ├── images/
│   │   └── Logo.jpeg          <-- Logo perusahaan MKP Store
│   └── js/
│       └── app.js             <-- Logika interaktif frontend (modal, alert, dll)
├── config/
│   ├── auth_guard.php         <-- Middleware pengecekan session & pembatasan akses role
│   ├── bootstrap.php          <-- File inisialisasi awal (konstanta BASE_URL, mode dev, dll)
│   ├── database.php           <-- Koneksi ke database PostgreSQL
│   └── helper.php             <-- Fungsi bantuan (format Rupiah, tanggal, dll)
├── docs/
│   └── sidripp_database.sql   <-- Skema dan data dummy database PostgreSQL
├── layouts/
│   ├── footer.php             <-- Penutup HTML & pemanggilan JS global
│   ├── header.php             <-- Pembuka HTML, head tags & pemanggilan CSS
│   ├── mock_data.php          <-- [HANYA UNTUK PROTOTYPE] Dummy data statis
│   └── sidebar.php            <-- Navigasi menu utama
├── modules/
│   ├── auth/                  <-- Modul Autentikasi
│   │   ├── login.php
│   │   └── process/
│   │       ├── login.php
│   │       └── logout.php
│   ├── categories/            <-- Modul Kategori (Tugas Inas)
│   │   ├── index.php
│   │   ├── create.php
│   │   └── process/
│   │       └── create.php
│   ├── dashboard/             <-- Modul Dasbor Analitik
│   │   └── index.php
│   ├── inventory/             <-- Modul Gudang & Logistik
│   │   ├── index.php          <-- Halaman utama stok fisik
│   │   ├── defect.php         <-- Halaman pencatatan barang rusak/cacat
│   │   ├── opname.php         <-- Halaman audit fisik (stock opname)
│   │   ├── stock_in.php       <-- Halaman penerimaan barang dari vendor
│   │   └── process/           <-- Logika backend mutasi stok
│   │       ├── defects.php
│   │       ├── stock_in.php
│   │       └── stock_opname.php
│   ├── products/              <-- Modul Master Produk (Tugas Timothy)
│   │   ├── index.php
│   │   ├── create.php
│   │   └── process/
│   │       └── create.php
│   ├── receivables/           <-- Modul Manajemen Piutang HORECA (TOP) & Pelunasan
│   │   ├── index.php          <-- UI Manajemen Piutang TOP & WhatsApp
│   │   └── process/
│   │       └── update_status.php
│   ├── reports/               <-- Modul Rekap & Laporan
│   │   ├── index.php
│   │   └── process/
│   │       ├── delete_report.php
│   │       └── export_pdf.php
│   ├── transactions/          <-- Modul Kasir & Transaksi POS
│   │   ├── index.php          <-- Layar POS
│   │   ├── history.php        <-- Riwayat Transaksi
│   │   ├── print_invoice.php  <-- Cetak Struk/Faktur
│   │   └── process/
│   │       └── checkout.php   <-- Logika kalkulasi, diskon, & simpan transaksi
│   └── users/                 <-- Modul Manajemen Akses & Pegawai
│       ├── index.php
│       ├── create.php
│       └── process/
│           └── create.php
│
├── .gitignore                       (Aturan: Daftar file yang DILARANG masuk GitHub)
├── AI-GUIDE.md                      (Prompt wajib jika tim mau pakai ChatGPT/Gemini)
├── index.php                        (Router: Mengarahkan user yang baru buka web)
├── README.md                        (Profil proyek, cara install)
├── SOP.md                           (Aturan workflow Git & standar kode)
└── ToDo.md                          (Checklist pembagian tugas tim)

```

---

## 💡 Apa Itu PHP Native & Cara Menjalankannya?

**PHP Native** berarti kita membangun sistem ini dari nol menggunakan bahasa PHP murni, tanpa menggunakan kerangka kerja (_framework_) pihak ketiga seperti Laravel atau CodeIgniter. Semua logika bisnis (tambah, edit, hapus data) dan koneksi database kita tulis sendiri menggunakan _Object-Oriented Programming_ (OOP) agar performanya ringan dan cepat.

### 🧠 Penjelasan PHP Native Prosedural

- Proyek ini dibangun menggunakan PHP Native Prosedural Terstruktur.
- Tanpa Framework: Kami tidak menggunakan Laravel, CodeIgniter, atau framework lainnya.
- Tanpa OOP (Object-Oriented Programming): Kami tidak menggunakan Class, Object, Inheritance, atau arsitektur MVC murni. Semua logika ditulis secara berurutan menggunakan blok function atau pemrosesan skrip langsung.
- Pemisahan Tampilan & Logika: Meskipun prosedural, kode tidak ditumpuk dalam satu file. File yang menampilkan antarmuka HTML dipisah dari file yang melakukan eksekusi ke database (biasanya diawali dengan process\_).

**Langkah Menjalankan Aplikasi di Komputer Lokal:**

1. **Siapkan Server Lokal:** Pastikan kamu sudah menginstal **PostgreSQL** atau **Laragon**.
2. **Nyalakan Service:** Buka aplikasi PostgreSQL/Laragon, lalu klik **Start** pada modul **Apache** dan **PostgreSQL**.
3. **Simpan Proyek:** Pindahkan folder `SI-DRIPP-main` ini ke dalam folder `C:\xampp\htdocs\` (jika pakai XAMPP).
4. **Siapkan Database:**
   - buka koneksi melalui aplikasi DBeaver atau pgAdmin di komputer lokal.
   - Buat database baru bernama `sidripp_db`.
   - Import file `docs/sidripp_database.sql` ke dalam database tersebut.
5. **Jalankan Aplikasi:** Buka tab baru di browser dan ketik `http://localhost/SI-DRIPP-main`.

---
