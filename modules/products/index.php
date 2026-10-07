<?php
/**
 * UI MODUL MASTER PRODUK - SI-DRIPP ADMIN
 * File: modules/products/index.php
 * Fungsi: Menampilkan layar tabel katalog produk beserta kategori, harga, stok, dan aksi Edit/Hapus.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

// Hanya Super Admin dan Admin yang dapat mengelola Master Produk
cek_hak_akses(['super_admin', 'admin']);

// Query katalog produk beserta nama kategorinya (FK sesuai skema DB: categoryid_category)
$query  = "SELECT p.*, c.nama_category 
           FROM products p 
           LEFT JOIN category c ON p.categoryid_category = c.id_category 
           ORDER BY p.id_product DESC";
$result = pg_query($conn, $query);

$page_title = 'Master Produk';
$page_sub   = 'Kelola katalog produk sirup, bahan minuman, dan stok aktual MKP Store';
$active     = 'products';

require_once '../../layouts/header.php';
?>

<main class="main-content">
    <div class="page-actions">
        <div class="grow">
            <h2>Daftar Katalog Produk</h2>
        </div>
        <a href="create.php" class="btn btn-primary">+ Tambah Produk Baru</a>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <div class="card" style="border-color: var(--success); background: var(--success-bg); color: var(--success); margin-bottom: 16px; padding: 12px 18px;">
            <?= str_replace('_', ' ', htmlspecialchars($_GET['pesan'])) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="card" style="border-color: var(--danger); background: var(--danger-bg); color: var(--danger); margin-bottom: 16px; padding: 12px 18px;">
            <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
        </div>
    <?php endif; ?>

    <div class="card flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-right">Harga Beli</th>
                        <th class="text-right">Harga Jual</th>
                        <th class="text-right">Stok</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$result || pg_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" class="muted" style="text-align: center; padding: 32px;">
                                Belum ada produk
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php while ($row = pg_fetch_assoc($result)): ?>
                            <?php
                            $stok = (int) $row['stok_aktual'];
                            $stok_badge = $stok > 10 ? 'success' : ($stok > 0 ? 'warn' : 'danger');
                            ?>
                            <tr>
                                <td class="mono"><?= $no++ ?></td>
                                <td>
                                    <div class="cell-user">
                                        <div>
                                            <strong><?= htmlspecialchars($row['nama_barang']) ?></strong>
                                            <span class="mono">Kode: <?= htmlspecialchars($row['id_product']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['nama_category'])): ?>
                                        <span class="badge info"><?= htmlspecialchars($row['nama_category']) ?></span>
                                    <?php else: ?>
                                        <span class="badge">Tanpa Kategori</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right mono"><?= rupiah($row['harga_beli']) ?></td>
                                <td class="text-right mono"><strong><?= rupiah($row['harga_jual']) ?></strong></td>
                                <td class="text-right">
                                    <span class="badge <?= $stok_badge ?> mono">
                                        <i class="dot"></i><?= $stok ?> btl
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="create.php?edit=<?= urlencode($row['id_product']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                                        <a href="process/create.php?hapus_id=<?= urlencode($row['id_product']) ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Yakin ingin menghapus produk <?= htmlspecialchars(addslashes($row['nama_barang'])) ?>?');">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
require_once '../../layouts/footer.php';
?>