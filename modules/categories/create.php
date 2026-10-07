<?php
/**
 * UI MODUL KATEGORI - FORM TAMBAH & EDIT KATEGORI
 * File: modules/categories/create.php
 * Fungsi: Menampilkan layar form tambah atau edit kategori produk.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin']);

// Cek apakah sedang dalam mode Edit (?edit=id_category)
$is_edit       = false;
$edit_id       = '';
$nama_category = '';

if (isset($_GET['edit']) && $_GET['edit'] !== '') {
    $edit_id = (int) $_GET['edit'];
    $res_edit = pg_query_params($conn, 'SELECT * FROM category WHERE id_category = $1 LIMIT 1', array($edit_id));
    if ($res_edit && pg_num_rows($res_edit) > 0) {
        $row_edit      = pg_fetch_assoc($res_edit);
        $is_edit       = true;
        $nama_category = $row_edit['nama_category'];
    }
}

$page_title = $is_edit ? 'Edit Kategori Produk' : 'Tambah Kategori Produk';
$page_sub   = $is_edit ? 'Perbarui nama kategori produk yang dipilih' : 'Tambahkan kategori baru untuk pengelompokan katalog produk';
$active     = 'categories';

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
        <h2><?= $is_edit ? 'Form Edit Kategori Produk' : 'Form Tambah Kategori Produk' ?></h2>
        <p><?= $is_edit ? 'Ubah nama kategori di bawah ini lalu klik Simpan Perubahan.' : 'Masukkan nama kategori produk baru di bawah ini.' ?></p>
      </div>
    </div>

    <form action="process/create.php" method="POST">
      <?php if ($is_edit): ?>
        <input type="hidden" name="id_category" value="<?= htmlspecialchars($edit_id) ?>">
      <?php endif; ?>

      <div class="field">
        <label for="nama_category">Nama Kategori</label>
        <input type="text" id="nama_category" name="nama_category" class="input" value="<?= htmlspecialchars($nama_category) ?>" placeholder="Contoh: DRIPP Syrup" required>
      </div>

      <div class="field">
        <label for="deskripsi">Deskripsi (Opsional)</label>
        <textarea id="deskripsi" name="deskripsi" class="textarea" placeholder="Keterangan singkat kategori..."></textarea>
      </div>

      <div style="display: flex; gap: 10px; padding-top: 8px;">
        <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Simpan Perubahan' : 'Simpan' ?></button>
        <button type="reset" class="btn btn-ghost">Reset</button>
      </div>
    </form>
  </div>
</main>

<?php 
require_once '../../layouts/footer.php'; 
?>