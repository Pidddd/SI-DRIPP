<?php
/**
 * UI MODUL MASTER PRODUK - FORM TAMBAH & EDIT PRODUK
 * File: modules/products/create.php
 * Fungsi: Menampilkan form pendaftaran produk baru atau edit produk beserta input stok awal.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

// Hanya Super Admin dan Admin yang dapat menambah / mengubah produk
cek_hak_akses(['super_admin', 'admin']);

// Ambil daftar kategori untuk dropdown select
$res_categories = pg_query($conn, "SELECT * FROM category ORDER BY nama_category ASC");

// Cek apakah sedang dalam mode Edit (?edit=id_product)
$is_edit = false;
$prod = [
    'id_product'          => '',
    'nama_barang'         => '',
    'categoryid_category' => '',
    'harga_beli'          => '',
    'harga_jual'          => '',
    'stok_aktual'         => 0,
];

if (isset($_GET['edit']) && trim($_GET['edit']) !== '') {
    $edit_id  = trim($_GET['edit']);
    $res_edit = pg_query_params($conn, "SELECT * FROM products WHERE id_product = $1 LIMIT 1", array($edit_id));
    if ($res_edit && pg_num_rows($res_edit) > 0) {
        $prod    = pg_fetch_assoc($res_edit);
        $is_edit = true;
    }
}

if (!$is_edit) {
    $res_count          = pg_query($conn, "SELECT COUNT(*) FROM products");
    $next_num           = $res_count ? ((int) pg_fetch_result($res_count, 0, 0) + 1) : 1;
    $prod['id_product'] = 'PRD-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}

$page_title = $is_edit ? 'Edit Produk' : 'Tambah Produk Baru';
$page_sub   = $is_edit ? 'Perbarui informasi harga, kategori, atau stok produk' : 'Daftarkan item baru ke dalam katalog produk MKP Store';
$active     = 'products';

require_once '../../layouts/header.php';
?>

<main class="main-content">
    <div class="page-actions">
        <a href="index.php" class="btn btn-ghost">&larr; Kembali ke Daftar Produk</a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="card" style="border-color: var(--danger); background: var(--danger-bg); color: var(--danger); margin-bottom: 16px; padding: 12px 18px;">
            <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 680px;">
        <div class="card-head">
            <div>
                <h2><?= $is_edit ? 'Form Edit Produk' : 'Form Tambah Produk' ?></h2>
                <p>Lengkapi informasi produk di bawah ini. Pastikan kategori sudah terdaftar.</p>
            </div>
        </div>

        <form action="process/create.php" method="POST">
            <?php if ($is_edit): ?>
                <input type="hidden" name="mode" value="edit">
            <?php endif; ?>

            <div class="form-row">
                <div class="field">
                    <label for="id_product">Kode Produk (ID)</label>
                    <input type="text" id="id_product" name="id_product" class="input mono" value="<?= htmlspecialchars($prod['id_product']) ?>" placeholder="Contoh: PRD-001" maxlength="20" <?= $is_edit ? 'readonly' : 'required' ?>>
                </div>

                <div class="field">
                    <label for="categoryid_category">Kategori Produk</label>
                    <select id="categoryid_category" name="categoryid_category" class="select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php if ($res_categories): ?>
                            <?php while ($cat = pg_fetch_assoc($res_categories)): ?>
                                <option value="<?= htmlspecialchars($cat['id_category']) ?>" <?= ($prod['categoryid_category'] == $cat['id_category']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nama_category']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="nama_barang">Nama Produk</label>
                <input type="text" id="nama_barang" name="nama_barang" class="input" value="<?= htmlspecialchars($prod['nama_barang']) ?>" placeholder="Contoh: DRIPP Syrup Caramel 760 ml" maxlength="150" required>
            </div>

            <div class="form-row">
                <div class="field">
                    <label for="harga_beli">Harga Beli / Modal (Rp)</label>
                    <input type="number" id="harga_beli" name="harga_beli" class="input mono" value="<?= htmlspecialchars($prod['harga_beli']) ?>" min="0" step="500" placeholder="Contoh: 85000" required>
                </div>

                <div class="field">
                    <label for="harga_jual">Harga Jual (Rp)</label>
                    <input type="number" id="harga_jual" name="harga_jual" class="input mono" value="<?= htmlspecialchars($prod['harga_jual']) ?>" min="0" step="500" placeholder="Contoh: 105000" required>
                </div>
            </div>

            <div class="field">
                <label for="stok"><?= $is_edit ? 'Stok Aktual (Botol)' : 'Stok Awal (Botol)' ?></label>
                <input type="number" id="stok" name="stok" class="input mono" value="<?= (int) $prod['stok_aktual'] ?>" min="0" required>
                <small class="muted">Masukkan jumlah stok botol yang tersedia di gudang saat ini (minimal 0).</small>
            </div>

            <div style="display: flex; gap: 10px; padding-top: 8px;">
                <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Simpan Perubahan' : 'Simpan Produk' ?></button>
                <button type="reset" class="btn btn-ghost">Reset</button>
            </div>
        </form>
    </div>
</main>

<?php
require_once '../../layouts/footer.php';
?>