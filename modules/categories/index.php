/**
 * UI MODUL KATEGORI - SI-DRIPP ADMIN
 * File: modules/categories/index.php
 * Fungsi: Menampilkan layar tabel daftar kategori produk.
 */

<?php
require_once '../../config/bootstrap.php';

// kalo blm login, balik ke halaman login
if (!isset($_SESSION['ID_User'])) {
    header("Location: " . BASE_URL . "modules/auth/login.php?error=Silahkan_Login_Terlebih_Dahulu");
    exit();
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Kategori Produk</title>
</head>
<body>
  <div style="display: flex;">
    <?php include '../../components/sidebar.php'; ?>
    <main style="margin-left: 20px;">
      <h3>Daftar Kategori Produk</h3>
      <p>
        <a href="create.php"><button>+Tambah Kategori Produk</button></a>
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
            <td></td>
            <td></td>
            <td></td>
            <td>
              <a href="edit.php?id=1"><button>Edit</button></a>
              <a href="process/delete.php?=1"><button>Hapus</button></a>
            </td>
          </tr>
        </tbody>
      </table>
    </main>
  </div>

  <hr>
  <footer>
    <p>&copy; 2026 SI-DRIPP Project Based Learning</p>
  </footer>
</body>
</html>