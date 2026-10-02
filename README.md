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
```text
SI-DRIPP/
├── .github/
│   ├── pull_request_template.md  (Baru: Template checklist saat tim membuat PR)
│   └── workflows/                (Tetap: Auto-reviewer & testing GitHub Actions)
├── assets/
│   ├── css/style.css             (Tetap: Styling khusus SI-DRIPP)
│   ├── img/                      (Tetap: Aset gambar logo/banner)
│   └── js/app.js                 (Tetap: Interaktivitas frontend DOM)
├── components/
│   ├── header.php                (Tetap: Tag <head>, pemanggil CSS, Navbar atas)
│   ├── sidebar.php               (Tetap: Menu navigasi kiri, dinamis sesuai role)
│   └── footer.php                (Tetap: Tag penutup </body>, pemanggil JS)
├── config/
│   ├── database.php              (Tetap: Koneksi database prosedural - mysqli/PDO)
│   └── helper.php                (Baru: File berisi fungsi bantuan murni (format_rupiah, anti_injection) - tanpa class)
├── docs/
│   ├── sidripp_database.sql      (Tetap: Skema database yang 100% sinkron ERD revisi)
│   ├── Proposal_PBL.pdf          (Tetap: Referensi aturan bisnis)
│   └── ERD_Revisi.jpg            (Baru: Masukkan file gambar ERD ke repo sebagai acuan visual)
├── modules/                      (Wadah utama logika aplikasi berdasarkan entitas)
│   ├── auth/                     (Tetap: login, logout, process_login)
│   ├── dashboard/                (Baru: index.php - Halaman pertama setelah login)
│   ├── categories/               (Baru: index.php, create.php, process_*.php - Master kategori)
│   ├── products/                 (Tetap: Master data barang)
│   ├── inventory/                (Tetap: stock_in, defects, stock_opname - Manajemen fisik)
│   ├── transactions/             (Tetap: POS Kasir, perhitungan diskon MOQ, invoice)
│   ├── receivables/              (Baru: Daftar piutang/TOP dan update status lunas)
│   ├── reports/                  (Baru: Cetak/Ekspor PDF rekap stok & transaksi)
│   └── users/                    (Tetap: Kelola akun & role oleh Super Admin)
├── .gitignore                    (Baru: Melindungi OS files, config lokal, & vendor library)
├── AI-GUIDE.md                   (Tetap - Ditulis Ulang: Konteks untuk prompt AI)
├── index.php                     (Tetap - Diubah Fungsi: Hanya sebagai Global Router/Redirect)
├── README.md                     (Tetap - Ditulis Ulang: Profil & setup SI-DRIPP)
├── SOP.md                        (Tetap - Ditulis Ulang: Aturan kolaborasi tim)
└── ToDo.md                       (Tetap - Ditulis Ulang: Kanban/Checklist task)

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

1. **Siapkan Server Lokal:** Pastikan kamu sudah menginstal **XAMPP** atau **Laragon**.
2. **Nyalakan Service:** Buka aplikasi XAMPP/Laragon, lalu klik **Start** pada modul **Apache** dan **MySQL**.
3. **Simpan Proyek:** Pindahkan folder `SI-DRIPP-main` ini ke dalam folder `C:\xampp\htdocs\` (jika pakai XAMPP).
4. **Siapkan Database:**
   - Buka browser dan ketik `http://localhost/phpmyadmin`.
   - Buat database baru bernama `sidripp_db`.
   - Import file `docs/sidripp_database.sql` ke dalam database tersebut.
5. **Jalankan Aplikasi:** Buka tab baru di browser dan ketik `http://localhost/SI-DRIPP-main`.
