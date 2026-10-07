<?php
/**
 * UI MODUL KATEGORI - SI-DRIPP ADMIN
 * File: modules/categories/index.php
 * Fungsi: Menampilkan layar tabel daftar kategori produk beserta aksi Edit & Hapus.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

// Memanggil fungsi dari auth_guard.php untuk mengamankan halaman
cek_hak_akses(['super_admin', 'admin']);

// Ambil seluruh kategori dari database
$result = pg_query($conn, 'SELECT * FROM category ORDER BY id_category ASC');

$page_title = 'Daftar Kategori Produk';
$page_sub   = 'Kelola pengelompokan katalog produk MKP Store';
$active     = 'categories';

require_once '../../layouts/header.php';
?>

<main class="main-content">
    <div class="page-actions">
        <div class="grow">
            <h2>Daftar Kategori Produk</h2>
        </div>
        <a href="create.php" class="btn btn-primary">+ Tambah Kategori Produk</a>
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
                        <th>ID Kategori</th>
                        <th>Nama Kategori</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$result || pg_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="4" class="muted" style="text-align:center; padding: 28px;">
                                Belum ada kategori. Klik "+ Tambah Kategori Produk" untuk menambahkan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php while ($row = pg_fetch_assoc($result)): ?>
                            <tr>
                                <td class="mono"><?= $no++ ?></td>
                                <td><span class="badge info">KAT-<?= str_pad($row['id_category'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                <td><strong><?= htmlspecialchars($row['nama_category']) ?></strong></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="create.php?edit=<?= urlencode($row['id_category']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                                        <a href="process/create.php?hapus_id=<?= urlencode($row['id_category']) ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Yakin ingin menghapus kategori <?= htmlspecialchars(addslashes($row['nama_category'])) ?>?');">Hapus</a>
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