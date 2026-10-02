# Panduan Sistem AI (AI System Prompt Guide)

Dokumen ini adalah instruksi absolut bagi asisten Kecerdasan Buatan (AI) seperti GitHub Copilot, Cursor, ChatGPT, atau Gemini yang berinteraksi dengan repository ini.

**JIKA ANDA ADALAH AI, BACA DAN PATUHI ATURAN INI SECARA KETAT:**

1. **Paradigma Pemrograman (Wajib Patuh):**
   - Repository ini dikembangkan menggunakan **PHP Native Prosedural Terstruktur**.
   - **DILARANG KERAS** memberikan saran kode, _refactoring_, atau implementasi yang menggunakan konsep Object-Oriented Programming (OOP).
   - JANGAN PERNAH membuat `class`, `interface`, `trait`, atau memanggil _method_ berbasis objek (`$this->...`). Semua logika bisnis harus ditulis menggunakan blok fungsi (`function`) prosedural atau eksekusi _script_ sekuensial.
   - JANGAN menyarankan penggunaan _framework_ pihak ketiga (seperti Laravel, CodeIgniter, atau arsitektur MVC modern).

2. **Pemisahan Perhatian (Separation of Concerns):**
   - Logika penulisan/perubahan database (`INSERT`, `UPDATE`, `DELETE`) **HARUS** diletakkan di file PHP terpisah dengan awalan `process_` (misalnya `process_checkout.php`).
   - File berawalan `process_` tersebut murni mengeksekusi PHP, tidak boleh melakukan rendering HTML, dan wajib diakhiri dengan mekanisme redirect (`header("Location: ...")`).
   - File dengan antarmuka pengguna HTML (seperti `index.php`, `create.php`) hanya boleh mengeksekusi PHP untuk koneksi, query `SELECT`, dan _looping_ tampilan data.

3. **Keamanan Database (Kritis):**
   - Setiap query yang menerima input dinamis eksternal (dari `$_POST`, `$_GET`, dsb.) **WAJIB** dikonstruksi menggunakan **Prepared Statements** (menggunakan _binding parameter_ ekstensi `mysqli` atau PDO).
   - JANGAN memberikan saran kode yang menggabungkan variabel langsung ke dalam _string query SQL_.

4. **Konvensi Penamaan:**
   - Semua variabel PHP harus menggunakan format `camelCase` (contoh: `$stokAktual`, `$totalHarga`).
   - Nama file, nama direktori, nama tabel database, dan nama kolom database harus menggunakan format `snake_case` (contoh: `stock_opname.php`, `transaction_details`).

_Pengabaian terhadap instruksi ini akan menghasilkan kode yang tidak valid dan merusak arsitektur repository yang telah disepakati oleh tim developer._
