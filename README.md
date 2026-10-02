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
│   ├── pull_request_template.md     (Template checklist saat tim bikin PR)
│   └── workflows/
│       ├── auto-reviewer.yml        (Robot pengecek kode GitHub)
│       └── php-test.yml             (Robot pengetes kode GitHub)
│
├── assets/                          (Folder khusus Frontend / Tampilan)
│   ├── css/
│   │   └── style.css                (File desain warna dan tata letak)
│   ├── img/
│   │   └── .gitkeep                 (Tempat nyimpan logo/gambar)
│   └── js/
│       └── app.js                   (File javascript untuk interaksi UI)
│
├── components/                      (Folder potongan UI yang dipakai berulang)
│   ├── footer.php                   (Tag penutup HTML & pemanggil JS)
│   ├── header.php                   (Tag pembuka HTML, Navbar atas, pemanggil CSS)
│   └── sidebar.php                  (Menu navigasi kiri, dinamis sesuai role)
│
├── config/                          (Folder Inti / Mesin Backend)
│   ├── auth_guard.php               (Polisi Penjaga: Cek session & cegah akses ilegal)
│   ├── bootstrap.php                (File Utama: Jalankan session, panggil guard & db)
│   ├── database.example.php         (Template koneksi DB yang di-push ke GitHub - PASSWORD KOSONG)
│   └── helper.php                   (Kumpulan fungsi bantuan: format_rupiah, sanitasi)
│
├── docs/                            (Dokumen referensi tim)
│   └── sidripp_database.sql         (File SQL untuk di-import ke phpMyAdmin)
│
├── modules/                         (Ruang Kerja Utama Fitur Aplikasi)
│   │
│   ├── auth/                        (Modul Login)
│   │   ├── login.php                (UI: Halaman form login)
│   │   └── process/                 (Backend: Folder khusus pemrosesan)
│   │       ├── login.php            (Cek password ke DB, set session)
│   │       └── logout.php           (Hancurkan session, tendang ke luar)
│   │
│   ├── categories/                  (Modul Master Kategori)
│   │   ├── create.php               (UI: Form tambah kategori)
│   │   ├── index.php                (UI: Tabel daftar kategori)
│   │   └── process/
│   │       └── create.php           (Backend: INSERT kategori ke DB)
│   │
│   ├── dashboard/                   (Modul Halaman Utama setelah Login)
│   │   └── index.php                (UI: Menampilkan ringkasan/widget statistik)
│   │
│   ├── inventory/                   (Modul Gudang)
│   │   ├── defects.php              (UI: Form laporan barang rusak)
│   │   ├── stock_in.php             (UI: Form barang masuk dari vendor)
│   │   ├── stock_opname.php         (UI: Form pengecekan stok fisik vs sistem)
│   │   └── process/
│   │       ├── defects.php          (Backend: Simpan data defect & kurangi stok aktual)
│   │       ├── stock_in.php         (Backend: Tambah stok aktual produk)
│   │       └── stock_opname.php     (Backend: Hitung selisih & simpan histori)
│   │
│   ├── products/                    (Modul Master Produk)
│   │   ├── create.php               (UI: Form tambah produk baru)
│   │   ├── index.php                (UI: Tabel katalog produk)
│   │   └── process/
│   │       └── create.php           (Backend: INSERT produk baru ke DB)
│   │
│   ├── receivables/                 (Modul Piutang / Belum Lunas)
│   │   ├── index.php                (UI: Tabel daftar transaksi yang belum lunas)
│   │   └── process/
│   │       └── update_status.php    (Backend: Ubah status dari Belum Lunas jadi Lunas)
│   │
│   ├── reports/                     (Modul Laporan / Cetak PDF)
│   │   ├── index.php                (UI: Tampilan filter tanggal laporan)
│   │   └── process/
│   │       └── export_pdf.php       (Backend: Query raksasa & generate PDF)
│   │
│   ├── transactions/                (Modul Kasir / POS)
│   │   ├── index.php                (UI: Layar kasir tempat milih barang & input qty)
│   │   ├── print_invoice.php        (UI: Tampilan struk nota untuk di-print)
│   │   └── process/
│   │       └── checkout.php         (Backend: INSERT transaksi, hitung diskon MOQ, potong stok)
│   │
│   └── users/                       (Modul Manajemen Pengguna & Role)
│       ├── create.php               (UI: Form tambah akun)
│       ├── index.php                (UI: Tabel daftar user (Admin, Kasir, dll))
│       └── process/
│           └── create.php           (Backend: INSERT user baru ke DB)
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

1. **Siapkan Server Lokal:** Pastikan kamu sudah menginstal **XAMPP** atau **Laragon**.
2. **Nyalakan Service:** Buka aplikasi XAMPP/Laragon, lalu klik **Start** pada modul **Apache** dan **MySQL**.
3. **Simpan Proyek:** Pindahkan folder `SI-DRIPP-main` ini ke dalam folder `C:\xampp\htdocs\` (jika pakai XAMPP).
4. **Siapkan Database:**
   - Buka browser dan ketik `http://localhost/phpmyadmin`.
   - Buat database baru bernama `sidripp_db`.
   - Import file `docs/sidripp_database.sql` ke dalam database tersebut.
5. **Jalankan Aplikasi:** Buka tab baru di browser dan ketik `http://localhost/SI-DRIPP-main`.
---