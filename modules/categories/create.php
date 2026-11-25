<?php
/**
 * UI MODUL KATEGORI - FORM TAMBAH & EDIT KATEGORI (BENTO GRID)
 * File: modules/categories/create.php
 * Fungsi: Menampilkan form tambah atau edit kategori produk dalam layout Bento Grid modern.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

// Memastikan hak akses hanya untuk Super Admin dan Admin
cek_hak_akses(['super_admin', 'admin']);

// Cek apakah sedang dalam mode Edit (?edit=id_category)
$is_edit       = false;
$edit_id       = 0;
$nama_category = '';

if (isset($_GET['edit']) && trim($_GET['edit']) !== '') {
    $edit_id = (int) $_GET['edit'];
    $res_edit = pg_query_params($conn, 'SELECT * FROM category WHERE id_category = $1 LIMIT 1', array($edit_id));
    if ($res_edit && pg_num_rows($res_edit) > 0) {
        $row_edit      = pg_fetch_assoc($res_edit);
        $is_edit       = true;
        $nama_category = $row_edit['nama_category'];
    }
}

// Ambil seluruh daftar kategori aktif & total produk terhubung untuk panel Bento samping
$res_categories = pg_query($conn, 'SELECT c.id_category, c.nama_category, COUNT(p.id_product) AS total_produk 
                                   FROM category c 
                                   LEFT JOIN products p ON c.id_category = p.categoryid_category 
                                   GROUP BY c.id_category, c.nama_category 
                                   ORDER BY c.nama_category ASC');
$total_kategori = $res_categories ? pg_num_rows($res_categories) : 0;

$page_title = $is_edit ? 'Edit Kategori Produk' : 'Tambah Kategori Produk';
$page_sub   = $is_edit ? 'Perbarui nama pengelompokan katalog produk MKP Store' : 'Tambahkan kategori baru untuk pengelompokan katalog produk';
$active     = 'categories';
$allowed    = ['super_admin', 'admin'];

require_once '../../layouts/header.php';
?>

<!-- Bar Aksi Atas -->
<div class="page-actions page-actions-narrow rise">
    <a href="index.php" class="btn btn-ghost">&larr; Kembali ke Daftar Kategori</a>
    <span class="badge info"><i class="dot"></i>Kategori Katalog MKP Store</span>
</div>

<!-- Alert Notifikasi Error -->
<?php if (isset($_GET['error'])): ?>
    <div class="callout danger callout-narrow rise">
        <?= icon('alert', 18) ?>
        <div><?= e(str_replace('_', ' ', $_GET['error'])) ?></div>
    </div>
<?php endif; ?>

<!-- Form Terpusat Kategori Produk (Single Card max-width: 640px) -->
<div class="card card-form-narrow rise">
    <div class="card-head">
        <div>
            <h2><?= $is_edit ? 'Form Edit Kategori Produk' : 'Form Tambah Kategori Produk' ?></h2>
            <p><?= $is_edit ? 'Perbarui nama kategori lalu klik Simpan Perubahan.' : 'Tambahkan kategori baru untuk pengelompokan katalog produk dan kasir.' ?></p>
        </div>
        <span class="badge neutral"><?= $is_edit ? 'KAT-' . str_pad($edit_id, 3, '0', STR_PAD_LEFT) : 'Kategori Baru' ?></span>
    </div>

    <form action="process/create.php" method="POST">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id_category" value="<?= e($edit_id) ?>">
            <div class="field">
                <label>ID Kategori</label>
                <input type="text" class="input mono" value="KAT-<?= str_pad($edit_id, 3, '0', STR_PAD_LEFT) ?>" readonly disabled>
            </div>
        <?php endif; ?>

        <div class="field">
            <label for="nama_category">Nama Kategori</label>
            <input type="text" id="nama_category" name="nama_category" class="input" value="<?= e($nama_category) ?>" placeholder="Contoh: DRIPP Syrup, Multibev Powder, Ramoe Beverage" maxlength="100" required autofocus>
            <small class="muted">Tampil pada kartu katalog, pencarian kasir POS, dan filter stok gudang.</small>
        </div>

        <div class="callout amber">
            <?= icon('tag', 18) ?>
            <div>
                <strong>SOP Penamaan Kategori MKP Store:</strong>
                Gunakan nama spesifik lini brand agar konsisten (misal: <em>DRIPP Syrup</em>, <em>Multibev Powder</em>, atau <em>Ramoe Beverage</em>).
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Simpan Perubahan' : 'Simpan Kategori' ?></button>
            <a href="index.php" class="btn btn-ghost">Batal</a>
        </div>
    </form>

    <!-- Daftar Ringkas Kategori Terdaftar (Badge/Pill Kompak) -->
    <div class="cat-existing-box">
        <div class="cat-existing-head">
            <h3 class="cat-existing-title">Kategori Terdaftar Saat Ini (<?= (int)$total_kategori ?>)</h3>
            <a href="index.php" class="text-xs muted">Kelola semua &rarr;</a>
        </div>
        <?php if (!$res_categories || pg_num_rows($res_categories) === 0): ?>
            <p class="text-xs muted">Belum ada kategori terdaftar dalam sistem.</p>
        <?php else: ?>
            <div class="cat-pill-list">
                <?php
                pg_result_seek($res_categories, 0);
                while ($cat = pg_fetch_assoc($res_categories)):
                ?>
                    <span class="cat-pill">
                        <span><?= e($cat['nama_category']) ?></span>
                        <span class="cat-pill-count mono"><?= (int)$cat['total_produk'] ?></span>
                    </span>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once '../../layouts/footer.php'; 
?>
