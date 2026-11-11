<?php
/**
 * UI MODUL MASTER PRODUK - FORM TAMBAH & EDIT PRODUK
 * File: modules/products/create.php
 * Fungsi: Menampilkan form pendaftaran produk baru atau edit produk beserta upload foto produk.
 * Aturan SOP: Stok awal produk baru otomatis 0 (penambahan stok murni via modul Gudang/Inventaris).
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
$page_sub   = $is_edit ? 'Perbarui informasi harga, kategori, atau foto produk' : 'Daftarkan item baru ke dalam katalog produk MKP Store';
$active     = 'products';
$allowed    = ['super_admin', 'admin'];

require_once '../../layouts/header.php';
?>

<!-- Bar Aksi Atas -->
<div class="page-actions rise">
    <a href="index.php" class="btn btn-ghost">&larr; Kembali ke Daftar Produk</a>
    <span class="badge info"><i class="dot"></i>Katalog Produk MKP Store</span>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="callout danger rise">
        <?= icon('alert', 18) ?>
        <div><?= e(str_replace('_', ' ', $_GET['error'])) ?></div>
    </div>
<?php endif; ?>

<div class="card card-form rise">
    <div class="card-head">
        <div>
            <h2><?= $is_edit ? 'Form Edit Produk' : 'Form Tambah Produk' ?></h2>
            <p>Lengkapi informasi produk di bawah ini. Pastikan kategori sudah terdaftar.</p>
        </div>
        <span class="badge neutral"><?= e($prod['id_product']) ?></span>
    </div>

    <form action="process/create.php" method="POST" enctype="multipart/form-data">
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
            <label for="foto_produk">Foto Produk (Opsional &mdash; JPG, PNG, WebP)</label>
            <?php
            $foto_saat_ini = function_exists('cari_foto_produk')
                ? cari_foto_produk($prod['id_product'], $prod['nama_barang'])
                : '';
            ?>
            <?php if ($is_edit && $foto_saat_ini !== ''): ?>
                <div class="photo-preview-box mb-3">
                    <span class="thumb">
                        <img src="../../<?= htmlspecialchars($foto_saat_ini) ?>" alt="<?= htmlspecialchars($prod['nama_barang']) ?>" class="bottle product-real-img">
                    </span>
                    <div>
                        <strong class="text-xs">Foto Produk Aktif Saat Ini</strong>
                        <small class="muted">Pilih file gambar baru jika ingin mengganti foto produk ini.</small>
                    </div>
                </div>
            <?php endif; ?>

            <label for="foto_produk" class="upload-dropzone">
                <input type="file" id="foto_produk" name="foto_produk" class="upload-input-hidden" accept=".jpg,.jpeg,.png,.webp" onchange="previewUploadFoto(this)">
                <div class="upload-btn-chip">
                    <?= icon('download', 16) ?>
                    <span id="label-pilih-foto">Pilih Berkas Foto</span>
                </div>
                <div class="upload-text-info">
                    <span id="nama-file-foto" class="upload-filename">Belum ada file dipilih</span>
                    <small class="muted">Format: JPG, PNG, WebP (Maks. 2MB)</small>
                </div>
            </label>
            <small class="muted">Jika dikosongkan, sistem otomatis menampilkan ilustrasi botol/pouch DRIPP sebagai fallback.</small>
        </div>

        <script>
        function previewUploadFoto(input) {
            var labelName = document.getElementById('nama-file-foto');
            var chipLabel = document.getElementById('label-pilih-foto');
            if (input.files && input.files[0]) {
                labelName.textContent = input.files[0].name + ' (' + Math.round(input.files[0].size / 1024) + ' KB)';
                labelName.classList.add('has-file');
                chipLabel.textContent = 'Ganti Foto';
            } else {
                labelName.textContent = 'Belum ada file dipilih';
                labelName.classList.remove('has-file');
                chipLabel.textContent = 'Pilih Berkas Foto';
            }
        }
        </script>

        <div class="callout amber">
            <?= icon('tag', 18) ?>
            <div>
                <strong>Kebijakan Manajemen Stok (SOP Gudang):</strong><br>
                <?php if ($is_edit): ?>
                    Stok aktual saat ini tercatat sebanyak <strong><?= (int) $prod['stok_aktual'] ?> unit</strong>. Penambahan atau pengurangan stok fisik wajib dilakukan melalui menu <strong>Inventaris Stok / Stock Opname</strong> oleh Staf Gudang.
                <?php else: ?>
                    Produk baru otomatis didaftarkan dengan <strong>Stok Awal = 0 unit</strong>. Penambahan stok barang masuk dilakukan melalui menu <strong>Inventaris Stok</strong>.
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Simpan Perubahan' : 'Simpan Produk' ?></button>
            <button type="reset" class="btn btn-ghost">Reset</button>
        </div>
    </form>
</div>

<?php
require_once '../../layouts/footer.php';
?>
