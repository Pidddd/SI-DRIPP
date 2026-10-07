<?php
/**
 * UI MODUL KATEGORI - SI-DRIPP ADMIN
 * File: modules/categories/index.php
 * Fungsi: Menampilkan layar tabel daftar kategori produk.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

// Memanggil fungsi dari auth_guard.php untuk mengamankan halaman
cek_hak_akses(['super_admin', 'admin']);

// Ambil seluruh kategori dari database (nama tabel di skema: category)
$result = pg_query($conn, 'SELECT * FROM category ORDER BY id_category ASC');

$page_title = 'Daftar Kategori Produk';
$page_sub   = 'Kelola pengelompokan katalog produk MKP Store';
$active     = 'categories';

// Memanggil Header standar aplikasi (sudah otomatis memuat sidebar.php)
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
                    <?php if (!$result): ?>
                        <tr>
                            <td colspan="4" class="muted" style="text-align:center; padding: 28px;">
                                Gagal mengambil data kategori dari database.
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
                                        <a href="edit.php?id=<?= urlencode($row['id_category']) ?>"><button type="button" class="btn btn-ghost btn-sm">Edit</button></a>
                                        <a href="process/delete.php?id=<?= urlencode($row['id_category']) ?>"><button type="button" class="btn btn-danger-soft btn-sm">Hapus</button></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($no === 1): ?>
                            <tr>
                                <td colspan="4" class="muted" style="text-align:center; padding: 28px;">
                                    Belum ada kategori. Klik "+ Tambah Kategori Produk" untuk menambahkan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php 
// Memanggil Footer standar aplikasi
require_once '../../layouts/footer.php'; 
?>