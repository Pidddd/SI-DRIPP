/**
* UI MODUL AUTH - HALAMAN LOGIN
* File: modules/auth/login.php
* Fungsi: Menampilkan antarmuka form login.
* Aksi Form: Mengarah ke process_login.php (Method POST)
*/

<?php

// Panggil bootstrap untuk mengecek session
require_once '../../config/bootstrap.php';

// Jika user sudah login, jangan biarkan masuk ke halaman login lagi
if (isset($_SESSION['ID_User'])) {
  header("Location: " . BASE_URL . "modules/dashboard/index.php");
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

    <!-- Tangkap Pesan Error dari Backend (Jika ada) -->
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger" style="color: red; margin-bottom: 15px;">
        <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
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