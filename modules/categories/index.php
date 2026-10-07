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

// Memanggil Header dan Sidebar standar aplikasi
require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';

?>

<main class="main-content" style="margin-left: 20px;">
    <h3>Daftar Kategori Produk</h3>
    <p>
        <a href="create.php"><button class="btn btn-primary">+ Tambah Kategori Produk</button></a>
    </p>

    <table border="1" width="100%" cellspacing="0" cellpadding="8">
        <thead>
            <tr>
                <th>No</th>
                <th>ID Kategori</th>
                <th>Nama Kategori</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>KAT-001</td>
                <td>DRIPP Syrup</td>
                <td>
                    <a href="edit.php?id=1"><button>Edit</button></a>
                    <a href="process/delete.php?id=1"><button>Hapus</button></a>
                </td>
            </tr>
        </tbody>
    </table>
</main>

<?php 
// Memanggil Footer standar aplikasi
require_once '../../layouts/footer.php'; 
?>