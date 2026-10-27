<?php

/**
 * UI MODUL AUTH - HALAMAN LOGIN
 * File: modules/auth/login.php
 * Fungsi: Menampilkan antarmuka form login.
 * Aksi Form: Mengarah ke process/login.php (Method POST)
 */

// Panggil bootstrap untuk mengecek session
require_once '../../config/bootstrap.php';

// Jika user sudah login, jangan biarkan masuk ke halaman login lagi
if (isset($_SESSION['id_user'])) {
  header("Location: " . BASE_URL . "modules/dashboard/index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login · SI-DRIPP MKP Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="login-body">

  <div class="login-shell">

    <div class="login-art">
      <div class="brand">
        <span class="brand-mark">
          <img src="../../assets/img/Logo.jpeg" alt="MKP Store" class="brand-logo" onerror="this.classList.add('is-hidden'); this.nextElementSibling.style.display='flex';">
          <span class="brand-fallback-box">MKP</span>
        </span>
        <span class="brand-text">
          <strong class="text-white">SI-DRIPP</strong>
          <span>MKP Store · Malang</span>
        </span>
      </div>

      <div>
        <h2>Sistem Inventaris &amp; POS Grosir HORECA</h2>
        <p>Pengelolaan stok gudang real-time, kasir Point of Sales, dan pemantauan piutang (TOP) terpusat untuk CV Mitra Kembar Pradipta.</p>
      </div>

      <div class="login-art-copy">
        &copy; 2026 CV Mitra Kembar Pradipta · SI-DRIPP
      </div>
    </div>

    <div class="login-form">
      <h1>Masuk ke Sistem</h1>
      <p>Masukkan kredensial akun pegawai Anda untuk melanjutkan.</p>

      <!-- Tangkap Pesan Error / Info dari Backend (Jika ada) -->
      <?php if (isset($_GET['error'])): ?>
        <div class="callout danger">
          <strong>Gagal Masuk:</strong> <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['pesan'])): ?>
        <div class="callout success">
          <?= str_replace('_', ' ', htmlspecialchars($_GET['pesan'])) ?>
        </div>
      <?php endif; ?>

      <form action="process/login.php" method="POST">
        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="input" placeholder="Masukkan username anda..." required>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="input" placeholder="********" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-2">Masuk ke Sistem</button>
      </form>

      <div class="login-foot">
        <a href="../../index.php" class="text-primary fw-semibold">&larr; Halaman Utama</a>
        <div class="mt-2">&copy; 2026 SI-DRIPP Project Based Learning</div>
      </div>
    </div>

  </div>

</body>

</html>