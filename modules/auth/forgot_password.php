<?php

/**
 * UI MODUL AUTH - HALAMAN LUPA PASSWORD (PEMULIHAN KATA SANDI)
 * File: modules/auth/forgot_password.php
 * Fungsi: Menampilkan form pemulihan kata sandi akun pegawai.
 * Aksi Form: Mengarah ke process/forgot_password.php (Method POST)
 */

// Panggil bootstrap untuk mengecek session
require_once '../../config/bootstrap.php';

// Jika user sudah login, arahkan langsung ke dashboard
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
  <title>Lupa Password · SI-DRIPP MKP Store</title>
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
          <img src="../../assets/img/Logo.jpeg" alt="MKP Store" class="brand-logo" onerror="this.classList.add('is-hidden'); this.nextElementSibling.classList.add('is-active');">
          <span class="brand-fallback-box">MKP</span>
        </span>
        <span class="brand-text">
          <strong class="text-white">SI-DRIPP</strong>
          <span>MKP Store · Malang</span>
        </span>
      </div>

      <div>
        <h2>Pemulihan Kata Sandi Akun</h2>
        <p>Masukkan alamat email yang terdaftar pada akun SI-DRIPP Anda untuk menerima instruksi atau kode OTP pengaturan ulang kata sandi.</p>
      </div>

      <div class="login-art-copy">
        &copy; 2026 CV Mitra Kembar Pradipta · SI-DRIPP
      </div>
    </div>

    <div class="login-form">
      <h1>Lupa Kata Sandi?</h1>
      <p>Masukkan email terdaftar Anda untuk meminta verifikasi pemulihan kata sandi.</p>

      <!-- Tangkap Pesan Error / Sukses dari Backend (Jika ada) -->
      <?php if (isset($_GET['error'])): ?>
        <div class="callout danger">
          <strong>Gagal Memulihkan:</strong> <?= str_replace('_', ' ', htmlspecialchars($_GET['error'])) ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['pesan'])): ?>
        <div class="callout success">
          <?= str_replace('_', ' ', htmlspecialchars($_GET['pesan'])) ?>
        </div>
      <?php endif; ?>

      <form action="process/forgot_password.php" method="POST">
        <div class="field">
          <label for="email">Email Terdaftar</label>
          <input type="email" id="email" name="email" class="input" maxlength="100" placeholder="Contoh: nama@mkpstore.id" required autofocus>
          <small class="muted">Pastikan alamat email sesuai dengan data akun yang didaftarkan oleh Super Admin.</small>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-2">Kirim Instruksi Pemulihan</button>
      </form>

      <div class="login-foot">
        <a href="login.php" class="text-primary fw-semibold">&larr; Kembali ke Halaman Login</a>
        <div class="mt-2">&copy; 2026 SI-DRIPP Project Based Learning</div>
      </div>
    </div>

  </div>

</body>

</html>
