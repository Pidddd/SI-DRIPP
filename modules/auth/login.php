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
          <img src="../../assets/img/Logo.jpeg" alt="MKP Store" class="brand-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
          <span class="brand-fallback-text" style="display:none; align-items:center; justify-content:center; width:100%; height:100%;">MKP</span>
        </span>
        <span class="brand-text">
          <strong style="color:#fff;">SI-DRIPP</strong>
          <span>MKP Store · Malang</span>
        </span>
      </div>

      <div>
        <h2>Sistem Inventaris &amp; POS Grosir HORECA</h2>
        <p>Pengelolaan stok gudang real-time, kasir Point of Sales, dan pemantauan piutang (TOP) terpusat untuk CV Mitra Kembar Pradipta.</p>
      </div>

      <div style="font-size: 12px; color: rgba(255, 255, 255, .65); position: relative; z-index: 1;">
        &copy; 2026 CV Mitra Kembar Pradipta · SI-DRIPP
      </div>
    </div>

    <div class="login-form">
      <h1>Masuk ke Sistem</h1>
      <p>Masukkan kredensial akun pegawai Anda untuk melanjutkan.</p>

      <!-- Tangkap Pesan Error / Info dari Backend (Jika ada) -->
      <?php if (isset($_GET['error'])): ?>
        <div class="callout" style="background: var(--danger-bg); color: var(--danger); border: 1px solid #FECACA; margin-bottom: 18px;">
          <strong>Gagal Masuk:</strong> <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['pesan'])): ?>
        <div class="callout" style="background: var(--success-bg); color: var(--success); border: 1px solid #A7F3D0; margin-bottom: 18px;">
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

        <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 8px;">Masuk ke Sistem</button>
      </form>

      <div class="login-foot">
        <a href="../../index.php" style="color: var(--primary); font-weight: 600;">&larr; Halaman Utama</a>
        <div style="margin-top: 8px;">&copy; 2026 SI-DRIPP Project Based Learning</div>
      </div>
    </div>

  </div>

</body>

</html>