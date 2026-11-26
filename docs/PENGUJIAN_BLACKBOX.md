# 📋 DOKUMEN PENGUJIAN BLACK-BOX END-TO-END (QA TESTING)
## SI-DRIPP — SISTEM INFORMASI PENDATAAN STOK & TRANSAKSI HORECA
**Mitra Studi:** CV. Mitra Kembar Pradipta (MKP Store Malang)  
**Lead Quality Assurance (QA):** Muhammad Toriq Januarsyah (NIM 254107020075 / D-IV TI-2B)  
**Fokus Siklus Pengujian:** Tahap 4 — Validasi Fitur Revisi Mitra MKP Store (20–25 November 2026)  
**Branch Sumber Pengujian:** `dev`  
**Basis Teknologi:** PHP Native Prosedural v8.2 + PostgreSQL Supabase (pg_query_params)  

---

## 1. RINGKASAN EKSEKUTIF PENGUJIAN

Pengujian perangkat lunak ini dilaksanakan oleh tim Quality Assurance (QA) menggunakan metode **Black-Box Testing (Equivalence Partitioning & Boundary Value Analysis)** untuk memverifikasi fungsionalitas menyeluruh (*end-to-end*) pasca-demo dan masukan langsung dari pemilik toko (Ibu Emy Maria Ulfa) serta kasir/gudang (Ibu Mia Setyaningpala) pada siklus Revisi Mitra Tahap 4.

### Ruang Lingkup Verifikasi Revisi Mitra:
1. **Sinkronisasi Format Waktu:** Standarisasi waktu Indonesia Barat (WIB / `Asia/Jakarta`) berformat bersih `d M Y · H:i WIB` tanpa mikrodetik di layar POS, Riwayat Transaksi, Nota Kasir A5, dan Laporan Penjualan.
2. **Katalog Kasir POS Modern:** Panel kiri mandiri dengan 7 Produk *Best Seller* (30 hari terakhir), Accordion Kategori A–Z dinamis, pencarian instan + filter kategori (otomatis menyembunyikan Best Seller saat filter aktif), serta *dual independent scroll*.
3. **Validasi Diskon 2 Mode:** 
   - **Mode Otomatis (Default):** Jika akumulasi unit dalam keranjang $\ge 24$ botol (minimal 2 karton), diskon otomatis dihitung **2% resmi** (`round(subtotal * 0.02)`) lengkap dengan indikator `(2%)` dan nominal Rupiah di POS serta Nota A5. Jika $< 24$ botol, diskon = Rp 0.
   - **Mode Manual:** Input nominal Rupiah bebas yang divalidasi server-side (`0 <= diskon <= subtotal_kotor`) disertai kalkulasi ekuivalen persentase `%`.
4. **Validasi Pemisahan Finansial Ongkir:** Biaya kirim manual terbukti masuk ke Total Tagihan, Struk Nota A5, dan Manajemen Piutang, **TETAPI 100% TIDAK TERCAMPUR / TIDAK MASUK ke Omzet Penjualan dan Laba Bersih** pada modul Laporan (`modules/reports/` di antarmuka web, cetak PDF CTE, unduh Excel `.xlsx` multi-sheet, dan ekspor Google Sheets).
5. **Sumber Transaksi:** Pemisahan penanda saluran penjualan (`Umum` / `Shopee`) yang independen dari metode pembayaran (Tunai / Transfer / Tempo Piutang).
6. **Desain Cetak Nota A5 Landscape:** Format cetak standar distributor HORECA (Logo fisik MKP Store, alamat Jl. Moch Juki & Rampal, konversi Terbilang Rupiah Bahasa Indonesia, rekening BCA/BRI, catatan retur 1x24 jam, kolom tanda tangan kasir & penerima, serta latar putih hemat tinta otomatis baik dari Dark Mode maupun Light Mode).

---

## 2. METRIK DAN STATUS REKAPITULASI HASIL UJI

| Kategori Modul / Fitur Uji | Total Kasus Uji | Lulus (Pass) | Sebagian (Partial) | Gagal (Fail) | Persentase Kelulusan |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **A. Sinkronisasi Waktu WIB** | 2 | 2 | 0 | 0 | 100% |
| **B. Katalog Kasir POS & Navigasi** | 4 | 4 | 0 | 0 | 100% |
| **C. Diskon 2 Mode (Otomatis 2% & Manual)** | 4 | 4 | 0 | 0 | 100% |
| **D. Integritas Finansial Ongkir Non-Omzet** | 3 | 3 | 0 | 0 | 100% |
| **E. Sumber Transaksi (Umum / Shopee)** | 2 | 2 | 0 | 0 | 100% |
| **F. Format Cetak Nota A5 Landscape** | 3 | 3 | 0 | 0 | 100% |
| **TOTAL KESELURUHAN** | **18** | **18** | **0** | **0** | **100%** |

---

## 3. TABEL DETAIL SKENARIO UJI BLACK-BOX (TC-B01 S.D. TC-B18)

### Kategori A: Sinkronisasi Waktu WIB (Asia/Jakarta) Tanpa Mikrodetik

| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Data Uji (Test Data) | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-B01** | Format tanggal dan jam pada Nota Transaksi A5 | Buka transaksi baru atau riwayat cetak nota di `modules/transactions/print_invoice.php` | Transaksi tanggal berjalan di database Supabase | Tanggal dan waktu transaksi tercetak dalam format WIB bersih (contoh: `25 Nov 2026 · 14:30 WIB`) tanpa angka pecahan mikrodetik (seperti `.123456`) | Waktu tercetak rapi dengan format `d M Y · H:i WIB` tanpa pecahan mikrodetik | **Lulus (Pass)** |
| **TC-B02** | Konsistensi waktu WIB pada Riwayat Transaksi dan Laporan | Buka `modules/transactions/history.php` dan `modules/reports/index.php` | Data transaksi kasir | Kolom waktu menampilkan format waktu lokal WIB yang sinkron antara tabel transaksi dan header laporan | Waktu transaksi pada tabel riwayat dan laporan terkonversi seragam ke WIB | **Lulus (Pass)** |

---

### Kategori B: Katalog Kasir POS (Best Seller, Accordion, & Dual Scroll)

| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Data Uji (Test Data) | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-B03** | Penampilan 7 Produk Best Seller 30 Hari Terakhir | Buka halaman Kasir POS (`modules/transactions/index.php`) saat mode default (tanpa filter/pencarian) | Riwayat penjualan 30 hari terakhir dari tabel `transaction_details` | Menampilkan maksimal 7 kartu produk terlaris dengan badge khusus Best Seller di bagian atas grid produk | 7 varian produk dengan akumulasi penjualan terbanyak tampil di baris teratas grid | **Lulus (Pass)** |
| **TC-B04** | Accordion Kategori Katalog Produk A–Z | Klik header kategori pada katalog kasir POS untuk buka/tutup daftar produk | Kategori: DRIPP Syrup, Multibev, Ramoe, dll. | Konten kategori dapat dibuka/ditutup (accordion) secara halus, ikon panah berotasi, dan produk di dalamnya tersusun alfabetis | Accordion kategori responsif membuka dan menutup daftar varian produk | **Lulus (Pass)** |
| **TC-B05** | Filter Kategori dan Pencarian (Auto-Hide Best Seller) | Ketik kata kunci di kotak pencarian produk atau klik filter chip kategori | Kata kunci: "Caramel", Filter: "DRIPP Syrup" | Bagian kartu 7 Best Seller otomatis disembunyikan (*auto-hide*) agar kasir fokus pada hasil filter | Blok Best Seller otomatis sembunyi dan hanya menampilkan produk yang cocok | **Lulus (Pass)** |
| **TC-B06** | Scroll Independen 2 Panel (Katalog vs Keranjang) | Scroll panel katalog produk kiri ke bawah dan scroll panel keranjang belanja kanan | Data katalog > 15 produk, keranjang > 5 item | Panel katalog kiri dan panel keranjang kasir kanan dapat di-scroll terpisah tanpa menggeser layout halaman | Kedua panel memiliki container scroll independen yang rapi dan nyaman digunakan | **Lulus (Pass)** |

---

### Kategori C: Validasi Diskon 2 Mode (Ketentuan Final Mitra MKP Store)

| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Data Uji (Test Data) | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-B07** | Diskon Mode Otomatis dengan Kuantitas < 24 Botol | Masukkan produk ke keranjang dengan total kuantitas < 24 botol (misal 10 botol). Mode diskon: 'Otomatis' | Total qty = 10 botol, Subtotal = Rp 1.450.000 | Nilai potongan diskon = Rp 0, indikator diskon tidak aktif, total tagihan sama dengan subtotal | Diskon otomatis bernilai Rp 0 pada keranjang dan nota | **Lulus (Pass)** |
| **TC-B08** | Diskon Mode Otomatis dengan Kuantitas >= 24 Botol (Grosir 2%) | Masukkan produk ke keranjang dengan akumulasi gabungan >= 24 botol (misal 24 botol). Mode diskon: 'Otomatis' | Total qty = 24 botol, Subtotal = Rp 3.480.000 | Sistem otomatis menghitung diskon 2% (`round(3.480.000 * 0.02) = Rp 69.600`), badge indikator `(2%)` muncul di kasir dan nota | Diskon 2% terhitung presisi Rp 69.600 dan tercetak di nota kasir dengan label `(2%)` | **Lulus (Pass)** |
| **TC-B09** | Diskon Mode Manual dengan Input Nominal Rupiah Valid | Pilih radio button Diskon 'Manual', ketikkan nominal diskon tertentu di input kasir | Subtotal = Rp 1.000.000, Input Diskon = Rp 50.000 | Potongan diskon bernilai Rp 50.000, sistem menampilkan ekuivalen persentase `(5%)`, total tagihan berkurang Rp 50.000 | Diskon manual memotong subtotal dengan benar dan persentase ekuivalen terhitung | **Lulus (Pass)** |
| **TC-B10** | Validasi Server-Side Diskon Melebihi Subtotal di `checkout.php` | Kirim data transaksi dengan nominal diskon melebihi subtotal kotor melalui manipulasi request | Subtotal = Rp 500.000, Input Diskon = Rp 900.000 | Server di `checkout.php` membatasi diskon maksimal sebesar subtotal kotor (`min(subtotal, diskon)`), total tagihan tidak menjadi minus | Diskon dibatasi maksimal Rp 500.000 dan saldo tagihan terkunci aman di Rp 0 (+ ongkir) | **Lulus (Pass)** |

---

### Kategori D: Integritas Finansial Ongkir Non-Omzet

| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Data Uji (Test Data) | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-B11** | Penambahan Ongkir Manual ke Total Tagihan Kasir | Centang opsi 'Kirim Ekspedisi/Kurir', masukkan nominal ongkos kirim | Subtotal = Rp 1.000.000, Diskon = Rp 0, Ongkir = Rp 25.000 | Total tagihan kasir menjadi Rp 1.025.000, nilai ongkir tercatat di kolom `ongkir` tabel `transactions` | Total transaksi menjadi Rp 1.025.000 dan kolom `ongkir` terisi Rp 25.000 di basis data | **Lulus (Pass)** |
| **TC-B12** | Ongkir pada Piutang Jatuh Tempo dan Nota Cetak A5 | Pilih pembayaran 'Piutang Tempo', masukkan ongkir, lalu selesaikan transaksi | Ongkir = Rp 30.000, Subtotal = Rp 2.000.000 | Nilai piutang di `modules/receivables/` mencakup tagihan ongkir (Rp 2.030.000) dan baris ongkir tercetak jelas di Nota A5 | Nota A5 dan modul piutang menampilkan total tagihan utuh termasuk ongkir kurir | **Lulus (Pass)** |
| **TC-B13** | **Verifikasi Kritis:** Ongkir 0% Masuk ke Omzet/Laba di Modul Laporan | Buka `modules/reports/index.php`, cetak PDF laporan, unduh Excel `.xlsx`, dan ekspor Google Sheets | Transaksi dengan Subtotal Rp 1.000.000 + Ongkir Rp 50.000 | Omzet penjualan yang dihitung tetap murni **Rp 1.000.000**. Ongkir sama sekali tidak menambah omzet penjualan toko di tabel `transaction_details` | **Terverifikasi 100%:** Kueri laporan menghitung omzet murni dari `transaction_details`, ongkir tidak mencemari omzet/laba | **Lulus (Pass)** |

---

### Kategori E: Sumber Transaksi (Penanda Saluran Penjualan)

| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Data Uji (Test Data) | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-B14** | Transaksi Sumber 'Umum' (Offline / WhatsApp Mitra) | Pilih radio Sumber Transaksi: 'Umum' pada formulir kasir POS | `sumber_transaksi = 'Umum'` | Transaksi tersimpan dengan nilai `sumber_transaksi = 'Umum'`, badge 'Umum' muncul pada riwayat dan nota | Kolom `sumber_transaksi` bernilai 'Umum' pada database Supabase | **Lulus (Pass)** |
| **TC-B15** | Transaksi Saluran Marketplace 'Shopee' | Pilih radio Sumber Transaksi: 'Shopee' pada formulir kasir POS | `sumber_transaksi = 'Shopee'` | Transaksi tersimpan dengan nilai `sumber_transaksi = 'Shopee'`, nota A5 menampilkan penanda pesanan Shopee | Kolom `sumber_transaksi` bernilai 'Shopee' terpisah dari metode pembayaran | **Lulus (Pass)** |

---

### Kategori F: Format Cetak Nota A5 Landscape Standar Distributor

| ID Kasus Uji | Skenario Pengujian | Langkah Pengujian | Data Uji (Test Data) | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-B16** | Layout Fisik Nota A5 Landscape dan Elemen Identitas Toko | Buka `print_invoice.php?id=TRX-...` dan tekan Ctrl+P di browser | Kertas ukuran A5 (Landscape) | Format kertas landscape, logo fisik MKP Store tampil tajam, alamat Jl. Moch Juki & Rampal lengkap, data kasir & pelanggan rapi | Tata letak tercetak pas dalam 1 lembar A5 landscape tanpa terpotong baris | **Lulus (Pass)** |
| **TC-B17** | Konversi Kalimat Terbilang Rupiah Bahasa Indonesia | Periksa bagian bawah total tagihan pada nota invoice cetak | Total tagihan = Rp 1.575.000 | Menampilkan teks terbilang otomatis yang tepat: *"Satu Juta Lima Ratus Tujuh Puluh Lima Ribu Rupiah"* | Fungsi terbilang bahasa Indonesia menghasilkan ejaan nominal yang akurat | **Lulus (Pass)** |
| **TC-B18** | Mode Hemat Tinta Cetak (Latar Putih Bersih dari Dark Mode) | Aktifkan Dark Mode di aplikasi SI-DRIPP, lalu buka cetak nota (Ctrl+P) | Tema web: Dark Mode | Saat jendela cetak browser aktif, seluruh latar belakang gelap otomatis di-override menjadi putih bersih hemat tinta | `@media print` memaksa warna latar belakang menjadi putih polos dan teks hitam kontras | **Lulus (Pass)** |

---

## 4. TEMUAN DAN CATATAN PENGUJIAN QA (DEFECT TRACKING)

Selama siklus pengujian end-to-end Revisi Mitra Tahap 4, seluruh skenario uji berhasil dieksekusi dengan hasil **100% Lulus (Pass)**. Beberapa catatan teknis penting yang diverifikasi:

1. **Integritas Pemisahan Finansial Ongkir:**  
   Pemeriksaan mendalam pada skrip `modules/reports/report_data_helper.php` dan `modules/transactions/process/checkout.php` membuktikan bahwa `ongkir` hanya disimpan pada tabel `transactions`. Kueri agregasi laporan penjualan murni mengambil `SUM(td.subtotal_harga)` dari tabel `transaction_details`, sehingga data laba kotor dan omzet toko bebas dari distorsi biaya kurir/ekspedisi.
2. **Kesesuaian SOP Diskon Mitra:**  
   Diskon otomatis 2% terpicu secara ketat ketika total unit botol dalam keranjang mencapai $\ge 24$ botol (2 karton @ 12 botol). Untuk pembelian di bawah 2 karton, kasir tetap memiliki fleksibilitas memberikan potongan harga khusus melalui Diskon Mode Manual.
3. **Kepatuhan Arsitektur Kode:**  
   Seluruh skrip transaksi kasir mematuhi aturan baku: PHP Native Prosedural murni (`pg_query_params`), bebas tag inline styling, dan transaksi database terlindungi penuh dalam blok atomik `BEGIN-COMMIT-ROLLBACK`.

---

## 5. KESIMPULAN QUALITY ASSURANCE

Berdasarkan hasil pengujian Black-Box fungsional terhadap 18 kasus uji di branch `dev`, aplikasi **SI-DRIPP dinyatakan STABIL, AKURAT, DAN SIAP DIGUNAKAN (*PRODUCTION READY*)** untuk mendukung operasional harian mitra CV. Mitra Kembar Pradipta (MKP Store Malang).

*Malang, 25 November 2026*  
**Lead Quality Assurance (QA)**  
**Muhammad Toriq Januarsyah**  
NIM 254107020075
