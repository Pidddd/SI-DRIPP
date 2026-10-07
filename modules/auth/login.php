<?php
/**
 * UI MODUL AUTH - HALAMAN LOGIN
 * File: modules/auth/login.php
 * Fungsi: Menampilkan antarmuka form login.
 * Aksi Form: Mengarah ke process/login.php (Method POST)
 */

// Panggil bootstrap untuk mengecek session
require_once '../../config/bootstrap.php';

// Jika diarahkan untuk logout via query param
if (isset($_GET['logout'])) {
    header("Location: process/logout.php");
    exit();
}

// Jika user sudah login, arahkan ke halaman utama sesuai role
if (isset($_SESSION['id_user']) || isset($_SESSION['ID_User'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'kasir') {
        header("Location: ../transactions/index.php");
    } elseif (isset($_SESSION['role']) && ($_SESSION['role'] === 'staf_gudang' || $_SESSION['role'] === 'gudang')) {
        header("Location: ../inventory/index.php");
    } else {
        header("Location: ../dashboard/index.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login SI-DRIPP</title>
  <!-- Perbaikan Path CSS -->
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  
    <div class="login-card">

      <div class="brand">
        <span class="title">SI-DRIPP</span>
    </div>
    <p class="subtitle">Sistem Inventaris & Penjualan Grosir HORECA</p>

    <!-- Tangkap Pesan Error & Notifikasi Sukses dari Backend -->
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger" style="color: #dc2626; background: #fee2e2; border: 1px solid #fca5a5; padding: 10px 14px; border-radius: 8px; margin-bottom: 15px; font-size: 13.5px;">
            <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['pesan']) || isset($_GET['success'])): ?>
        <div class="alert alert-success" style="color: #044650; background: #ebf7f8; border: 1px solid #68cdda; padding: 10px 14px; border-radius: 8px; margin-bottom: 15px; font-size: 13.5px;">
            <?= str_replace('_', ' ', htmlspecialchars($_GET['pesan'] ?? $_GET['success'])) ?>
        </div>
    <?php endif; ?>

    <!-- Perbaikan action menuju folder process/ -->
    <form action="process/login.php" method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Masukkan username anda..." required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="********" required>
      </div>

      <!-- Dropdown Role dihapus dari sini karena Backend yang akan mengeceknya di Database -->

      <button type="submit" class="btn-submit">Masuk ke Sistem</button>
    </form>

      <div class="card-footer-links">
        <a href="../../index.php">&larr; Halaman Utama</a>
        <!-- Link Daftar Akun dihapus sesuai aturan proposal (Sistem Tertutup) -->
      </div>

      <p class="copyright">&copy; 2026 SI-DRIPP Project Based Learning</p>
    </div>
</body>
</html>