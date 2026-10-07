<?php
/**
 * UI MODUL KATEGORI - SI-DRIPP ADMIN
 * File: modules/categories/create.php
 * Fungsi: Menampilkan layar form tambah kategori.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

// Memanggil fungsi dari auth_guard.php untuk mengamankan halaman
cek_hak_akses(['super_admin', 'admin']);

// Memanggil Header dan Sidebar standar aplikasi
require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';

?>

<main class="main-content" style="margin-left: 20px;">
  <h3>Tambah Kategori Produk</h3>
    <p>
      <a href="index.php"><button class="btn btn-secondary">&larr; Kembali ke Daftar Kategori</button></a>
    </p>

    <table border="0" cellpadding="8">
      <tr>
        <td><label for="nama_kategori"><strong>Nama Kategori</strong></label></td>
        <td>
          <input type="text" id="nama_kategori" name="nama_kategori" required>
        </td>
      </tr>
      <tr>
        <td><label for="deskripsi"><strong>Deskripsi (Opsional)</strong></label></td>
        <td>
          <textarea id="deskripsi" name="deskripsi"></textarea>
        </td>
      </tr>
      <tr>
        <td colspan="3" style="padding-top: 10px;">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="reset" class="btn btn-warning">Reset</button>
        </td>
      </tr>
    </table>
</main>

<?php 
// Memanggil Footer standar aplikasi
require_once '../../layouts/footer.php'; 
?>