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

$page_title = 'Tambah Kategori Produk';
$page_sub   = 'Tambahkan kategori baru untuk pengelompokan katalog produk';
$active     = 'categories';

// Memanggil Header standar aplikasi (sudah otomatis memuat sidebar.php)
require_once '../../layouts/header.php';

?>

<main class="main-content">
  <div class="page-actions">
    <a href="index.php" class="btn btn-ghost">&larr; Kembali ke Daftar Kategori</a>
  </div>

  <?php if (isset($_GET['error'])): ?>
    <div class="card" style="border-color: var(--danger); background: var(--danger-bg); color: var(--danger); margin-bottom: 16px; padding: 12px 18px;">
      <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
    </div>
  <?php endif; ?>

  <div class="card" style="max-width: 640px;">
    <div class="card-head">
      <div>
        <h2>Form Tambah Kategori Produk</h2>
        <p>Masukkan nama kategori produk baru di bawah ini.</p>
      </div>
    </div>

    <form action="process/create.php" method="POST">
      <div class="field">
        <label for="nama_category">Nama Kategori</label>
        <input type="text" id="nama_category" name="nama_category" class="input" placeholder="Contoh: DRIPP Syrup" required>
      </div>

      <div class="field">
        <label for="deskripsi">Deskripsi (Opsional)</label>
        <textarea id="deskripsi" name="deskripsi" class="textarea" placeholder="Keterangan singkat kategori..."></textarea>
      </div>

      <div style="display: flex; gap: 10px; padding-top: 8px;">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <button type="reset" class="btn btn-ghost">Reset</button>
      </div>
    </form>
  </div>
</main>

<?php 
// Memanggil Footer standar aplikasi
require_once '../../layouts/footer.php'; 
?>