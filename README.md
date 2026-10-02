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

## 🛠️ Tech Stack

- **Bahasa Pemrograman:** PHP Native (Prosedural, Non-OOP, Non-Framework)
- **Database:** MySQL / MariaDB
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

Agar pengerjaan tidak bentrok dan sesuai standar PBO (OOP), tim wajib menyimpan file sesuai dengan struktur kerangka MVC berikut:

```text
SI-DRIPP/
├── .github/
│   └── workflows/
│       ├── auto-reviewer.yml
│       └── php-test.yml
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── img/
│   │   └── .gitkeep
│   └── js/
│       └── app.js
├── components/                 <-- (Folder baru pengganti 'includes')
│   ├── footer.php
│   ├── header.php
│   └── sidebar.php
├── config/                     <-- (Folder baru khusus konfigurasi)
│   └── database.php            <-- (Berasal dari includes/connection.php)
├── docs/
│   └── sidripp_database.sql    <-- (Bersih, file Proposal PBL PDF sudah dihapus)
├── modules/                    <-- (Folder utama baru untuk semua fitur)
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── process_login.php
│   ├── dashboard/              <-- (Folder baru disiapkan untuk halaman utama)
│   ├── inventory/
│   │   ├── defects.php
│   │   ├── stock_in.php
│   │   └── stock_opname.php
│   ├── products/
│   │   ├── create.php
│   │   ├── index.php
│   │   └── process_create.php
│   ├── receivables/            <-- (Folder baru disiapkan untuk modul TOP/Piutang)
│   ├── transactions/
│   │   ├── index.php
│   │   ├── print_invoice.php
│   │   └── process_checkout.php
│   └── users/
│       ├── create.php
│       ├── index.php
│       └── process_user.php
├── index.php                   <-- (Halaman routing utama/landing page)
├── README.md                   <-- (Isi direvisi murni teknis, tanpa PBL/OOP)
├── SOP.md                      <-- (Isi direvisi jadi SOP Git & PHP Native)
└── ToDo.md                     <-- (Isi direvisi fokus ke fitur yang belum selesai)

---
## 💡 Apa Itu PHP Native & Cara Menjalankannya?
**PHP Native** berarti kita membangun sistem ini dari nol menggunakan bahasa PHP murni, tanpa menggunakan kerangka kerja (*framework*) pihak ketiga seperti Laravel atau CodeIgniter. Semua logika bisnis (tambah, edit, hapus data) dan koneksi database kita tulis sendiri menggunakan *Object-Oriented Programming* (OOP) agar performanya ringan dan cepat.

### 🧠 Penjelasan PHP Native Prosedural
- Proyek ini dibangun menggunakan PHP Native Prosedural Terstruktur.
- Tanpa Framework: Kami tidak menggunakan Laravel, CodeIgniter, atau framework lainnya.
- Tanpa OOP (Object-Oriented Programming): Kami tidak menggunakan Class, Object, Inheritance, atau arsitektur MVC murni. Semua logika ditulis secara berurutan menggunakan blok function atau pemrosesan skrip langsung.
- Pemisahan Tampilan & Logika: Meskipun prosedural, kode tidak ditumpuk dalam satu file. File yang menampilkan antarmuka HTML dipisah dari file yang melakukan eksekusi ke database (biasanya diawali dengan process_).

**Langkah Menjalankan Aplikasi di Komputer Lokal:**
1. **Siapkan Server Lokal:** Pastikan kamu sudah menginstal **XAMPP** atau **Laragon**.
2. **Nyalakan Service:** Buka aplikasi XAMPP/Laragon, lalu klik **Start** pada modul **Apache** dan **MySQL**.
3. **Simpan Proyek:** Pindahkan folder `SI-DRIPP-main` ini ke dalam folder `C:\xampp\htdocs\` (jika pakai XAMPP).
4. **Siapkan Database:**
   - Buka browser dan ketik `http://localhost/phpmyadmin`.
   - Buat database baru bernama `sidripp_db`.
   - Import file `docs/sidripp_database.sql` ke dalam database tersebut.
5. **Jalankan Aplikasi:** Buka tab baru di browser dan ketik `http://localhost/SI-DRIPP-main`.
```
