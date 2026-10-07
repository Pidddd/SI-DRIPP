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
                    <tr>
                        <td class="mono">1</td>
                        <td><span class="badge info">KAT-001</span></td>
                        <td><strong>DRIPP Syrup</strong></td>
                        <td>
                            <div class="row-actions">
                                <a href="edit.php?id=1"><button type="button" class="btn btn-ghost btn-sm">Edit</button></a>
                                <a href="process/delete.php?id=1"><button type="button" class="btn btn-danger-soft btn-sm">Hapus</button></a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php 
// Memanggil Footer standar aplikasi
require_once '../../layouts/footer.php'; 
?>