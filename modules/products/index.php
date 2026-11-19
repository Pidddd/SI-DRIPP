<?php
/**
 * UI MODUL MASTER PRODUK - SI-DRIPP ADMIN (ACCORDION KATEGORI)
 * File: modules/products/index.php
 * Fungsi: Menampilkan daftar Kategori Produk (Accordion Level 1) beserta sub-tabel
 *         daftar Master Produk di setiap kategori (Accordion Level 2).
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

// Hanya Super Admin dan Admin yang dapat mengelola Master Produk
cek_hak_akses(['super_admin', 'admin']);

// 1. Query seluruh Kategori Produk (Level 1) beserta jumlah varian dan total stok
$q_categories = "SELECT c.id_category, c.nama_category, 
                        COUNT(p.id_product) AS total_varian,
                        COALESCE(SUM(p.stok_aktual), 0) AS total_stok
                 FROM category c
                 LEFT JOIN products p ON c.id_category = p.categoryid_category
                 GROUP BY c.id_category, c.nama_category
                 ORDER BY c.nama_category ASC";
$res_categories = pg_query($conn, $q_categories);
$total_kategori = $res_categories ? pg_num_rows($res_categories) : 0;

// 2. Query seluruh Master Produk untuk dipetakan ke dalam masing-masing Kategori
$q_products = "SELECT p.*, c.nama_category 
               FROM products p 
               LEFT JOIN category c ON p.categoryid_category = c.id_category 
               ORDER BY p.nama_barang ASC";
$res_products = pg_query($conn, $q_products);

$products_by_category   = [];
$uncategorized_products = [];
$total_semua_produk     = 0;
$total_semua_stok       = 0;

if ($res_products) {
    while ($p = pg_fetch_assoc($res_products)) {
        $cat_id = !empty($p['categoryid_category']) ? (int)$p['categoryid_category'] : 0;
        if ($cat_id > 0) {
            $products_by_category[$cat_id][] = $p;
        } else {
            $uncategorized_products[] = $p;
        }
        $total_semua_produk++;
        $total_semua_stok += (int)$p['stok_aktual'];
    }
}

$root       = '../../';
$page_title = 'Master Produk';
$page_sub   = 'Kelola katalog varian produk dan stok berdasarkan kategori · MKP Store Malang';
$active     = 'products';
$allowed    = ['super_admin', 'admin'];

require_once '../../layouts/header.php';
?>

<!-- Bar Aksi Halaman Atas -->
<div class="page-actions rise">
    <div class="grow">
        <span class="badge info"><i class="dot"></i><?= (int)$total_kategori ?> Kategori Terdaftar</span>
        <span class="badge neutral mono"><i class="dot"></i>Total <?= (int)$total_semua_produk ?> Produk (<?= number_format($total_semua_stok, 0, ',', '.') ?> Botol)</span>
    </div>
    <div class="row-actions">
        <a href="create.php" class="btn btn-primary">+ Tambah Produk Baru</a>
    </div>
</div>

<!-- Flash Message Notifikasi -->
<?php if (isset($_GET['pesan'])): ?>
    <div class="callout success rise">
        <?= icon('check', 18) ?>
        <div><?= str_replace('_', ' ', htmlspecialchars($_GET['pesan'])) ?></div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="callout danger rise">
        <?= icon('alert', 18) ?>
        <div><?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?></div>
    </div>
<?php endif; ?>

<!-- Tabel Utama: Accordion Level 1 (Daftar Kategori Produk) -->
<div class="card flush rise">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th>Nama Kategori Produk</th>
                    <th>Jumlah Varian Produk</th>
                    <th>Total Stok Kategori</th>
                    <th class="text-center col-batch-action">Detail Produk</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$res_categories || $total_kategori === 0): ?>
                    <tr>
                        <td colspan="5" class="table-empty">
                            Belum ada kategori yang terdaftar di database.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; while ($cat = pg_fetch_assoc($res_categories)): 
                        $c_id         = (int)$cat['id_category'];
                        $total_varian = (int)$cat['total_varian'];
                        $total_stok   = (int)$cat['total_stok'];
                        $prods_in_cat = isset($products_by_category[$c_id]) ? $products_by_category[$c_id] : [];
                    ?>
                        <!-- Baris Level 1: Kategori Produk -->
                        <tr>
                            <td class="mono muted"><?= $no++ ?></td>
                            <td>
                                <strong><?= e($cat['nama_category']) ?></strong>
                                <span class="badge neutral text-xs ml-1">KAT-<?= str_pad($c_id, 3, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <span class="badge info mono">
                                    <?= $total_varian ?> Varian
                                </span>
                            </td>
                            <td class="mono">
                                <strong><?= number_format($total_stok, 0, ',', '.') ?> Botol</strong>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-accordion" onclick="toggleCategory('cat_<?= $c_id ?>', this)" title="Klik untuk membuka / menutup rincian produk dalam kategori ini">
                                    <span class="acc-icon">&#9662;</span>
                                    <span><?= $total_varian > 0 ? $total_varian . ' Produk' : 'Detail Produk (0)' ?></span>
                                </button>
                            </td>
                        </tr>

                        <!-- Baris Level 2: Sub-tabel Accordion Rincian Master Produk -->
                        <tr id="cat_<?= $c_id ?>" class="accordion-subrow is-hidden">
                            <td colspan="5" class="accordion-content">
                                <div class="batch-detail-box">
                                    <div class="batch-head">
                                        <div class="batch-head-title">
                                            <?= icon('box', 16) ?>
                                            <span>Katalog Produk Kategori: <strong><?= e($cat['nama_category']) ?></strong></span>
                                        </div>
                                        <div class="row-actions">
                                            <span class="badge neutral mono"><i class="dot"></i>Total Stok: <?= number_format($total_stok, 0, ',', '.') ?> Botol</span>
                                            <a href="create.php?category_id=<?= urlencode($c_id) ?>" class="btn btn-primary btn-sm">
                                                <?= icon('plus', 14) ?> Tambah Produk di Kategori Ini
                                            </a>
                                        </div>
                                    </div>

                                    <?php if (!empty($prods_in_cat)): ?>
                                        <div class="batch-table-wrap">
                                            <table class="table-batch">
                                                <thead>
                                                    <tr>
                                                        <th class="col-no">No</th>
                                                        <th>Nama Produk &amp; SKU</th>
                                                        <th class="text-right">Harga Beli</th>
                                                        <th class="text-right">Harga Jual</th>
                                                        <th class="text-right">Stok Aktual</th>
                                                        <th class="text-right">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $p_no = 1; 
                                                    foreach ($prods_in_cat as $prod): 
                                                        $stok_val   = (int)$prod['stok_aktual'];
                                                        $stok_badge = $stok_val > 10 ? 'success' : ($stok_val > 0 ? 'warn' : 'danger');
                                                        $kat_lower  = strtolower((string)$cat['nama_category']);
                                                        $tipe_svg   = (strpos($kat_lower, 'powder') !== false) ? 'powder'
                                                                    : ((strpos($kat_lower, 'pulp') !== false) ? 'pulp' : 'syrup');
                                                    ?>
                                                        <tr>
                                                            <td class="mono muted"><?= $p_no++ ?></td>
                                                            <td>
                                                                <div class="cell-user">
                                                                    <span class="thumb">
                                                                        <?= bottle_svg('#D97706', '#78350F', substr($prod['nama_barang'], 0, 10), '760 ml', $tipe_svg, 'DRIPP', $prod['id_product']) ?>
                                                                    </span>
                                                                    <div>
                                                                        <strong><?= e($prod['nama_barang']) ?></strong>
                                                                        <div class="muted text-xs mono">SKU: <?= e($prod['id_product']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-right mono"><?= rupiah($prod['harga_beli']) ?></td>
                                                            <td class="text-right mono"><strong><?= rupiah($prod['harga_jual']) ?></strong></td>
                                                            <td class="text-right">
                                                                <span class="badge <?= $stok_badge ?> mono">
                                                                    <i class="dot"></i><?= $stok_val ?> btl
                                                                </span>
                                                            </td>
                                                            <td class="text-right">
                                                                <div class="row-actions">
                                                                    <a href="create.php?edit=<?= urlencode($prod['id_product']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                                                                    <a href="process/create.php?hapus_id=<?= urlencode($prod['id_product']) ?>" class="btn btn-danger-soft btn-sm" data-confirm="Yakin ingin menghapus produk <?= e($prod['nama_barang']) ?>?">Hapus</a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="batch-empty">
                                            Belum ada produk terdaftar pada kategori <strong><?= e($cat['nama_category']) ?></strong>.
                                            <div class="mt-2">
                                                <a href="create.php?category_id=<?= urlencode($c_id) ?>" class="btn btn-soft btn-sm">
                                                    <?= icon('plus', 14) ?> Daftarkan Produk Pertama
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <!-- Cadangan Khusus: Produk Tanpa Kategori (Jika Ada) -->
                    <?php if (!empty($uncategorized_products)): ?>
                        <tr>
                            <td class="mono muted"><?= $no++ ?></td>
                            <td>
                                <strong>Tanpa Kategori (Umum)</strong>
                                <span class="badge warn text-xs ml-1">Belum Terpetakan</span>
                            </td>
                            <td>
                                <span class="badge info mono">
                                    <?= count($uncategorized_products) ?> Varian
                                </span>
                            </td>
                            <td class="mono">
                                <strong>
                                    <?php 
                                    $stok_uncat = array_sum(array_column($uncategorized_products, 'stok_aktual'));
                                    echo number_format($stok_uncat, 0, ',', '.');
                                    ?> Botol
                                </strong>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-accordion" onclick="toggleCategory('cat_uncat', this)">
                                    <span class="acc-icon">&#9662;</span>
                                    <span><?= count($uncategorized_products) ?> Produk</span>
                                </button>
                            </td>
                        </tr>
                        <tr id="cat_uncat" class="accordion-subrow is-hidden">
                            <td colspan="5" class="accordion-content">
                                <div class="batch-detail-box">
                                    <div class="batch-head">
                                        <div class="batch-head-title">
                                            <?= icon('alert', 16) ?>
                                            <span>Produk Belum Memiliki Kategori</span>
                                        </div>
                                    </div>
                                    <div class="batch-table-wrap">
                                        <table class="table-batch">
                                            <thead>
                                                <tr>
                                                    <th class="col-no">No</th>
                                                    <th>Nama Produk &amp; SKU</th>
                                                    <th class="text-right">Harga Beli</th>
                                                    <th class="text-right">Harga Jual</th>
                                                    <th class="text-right">Stok Aktual</th>
                                                    <th class="text-right">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $u_no = 1; foreach ($uncategorized_products as $u_prod): ?>
                                                    <tr>
                                                        <td class="mono muted"><?= $u_no++ ?></td>
                                                        <td>
                                                            <strong><?= e($u_prod['nama_barang']) ?></strong>
                                                            <div class="muted text-xs mono">SKU: <?= e($u_prod['id_product']) ?></div>
                                                        </td>
                                                        <td class="text-right mono"><?= rupiah($u_prod['harga_beli']) ?></td>
                                                        <td class="text-right mono"><strong><?= rupiah($u_prod['harga_jual']) ?></strong></td>
                                                        <td class="text-right mono"><?= (int)$u_prod['stok_aktual'] ?> btl</td>
                                                        <td class="text-right">
                                                            <div class="row-actions">
                                                                <a href="create.php?edit=<?= urlencode($u_prod['id_product']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleCategory(id, btn) {
    var row = document.getElementById(id);
    if (!row) return;
    row.classList.toggle('is-hidden');
    if (btn) {
        btn.classList.toggle('active');
    }
}
</script>

<?php
require_once '../../layouts/footer.php';
?>
