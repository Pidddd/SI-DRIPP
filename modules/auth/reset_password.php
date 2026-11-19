<?php
/**
 * UI MODUL AUTH - TAHAP 2: VERIFIKASI OTP & SIMPAN KATA SANDI BARU
 * File: modules/auth/reset_password.php
 * Fungsi: Menampilkan form input Kode OTP (6 digit), Kata Sandi Baru, dan Konfirmasi Kata Sandi Baru.
 * Aksi Form: Mengarah ke process/reset_password.php (Method POST)
 */

require_once '../../config/bootstrap.php';

// Jika user sudah login, arahkan langsung ke dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: " . BASE_URL . "modules/dashboard/index.php");
    exit();
}

// Pastikan session OTP aktif; jika tidak ada atau sudah kedaluwarsa, arahkan kembali ke halaman Lupa Kata Sandi
if (!isset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expires'])) {
    header("Location: forgot_password.php?error=" . urlencode("Sesi verifikasi OTP tidak ditemukan. Silakan masukkan email Anda untuk meminta kode OTP baru."));
    exit();
}

if (time() > (int)$_SESSION['reset_expires']) {
    unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
    header("Location: forgot_password.php?error=" . urlencode("Kode OTP telah kedaluwarsa (melebihi 10 menit). Silakan minta kode OTP baru."));
    exit();
}

$target_email = (string)$_SESSION['reset_email'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi OTP &amp; Reset Kata Sandi · SI-DRIPP MKP Store</title>
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
        <h2>Verifikasi Kode OTP</h2>
        <p>Masukkan kode OTP 6 digit yang telah dikirim ke kotak masuk email Anda, lalu buat kata sandi baru yang kuat untuk mengamankan akun.</p>
      </div>

      <div class="login-art-copy">
        &copy; 2026 CV Mitra Kembar Pradipta · SI-DRIPP
      </div>
    </div>

    <div class="login-form">
      <h1>Atur Ulang Kata Sandi</h1>
      <p>Kode OTP dikirim ke <strong class="mono"><?= htmlspecialchars($target_email, ENT_QUOTES, 'UTF-8') ?></strong> (berlaku 10 menit).</p>

      <!-- Tangkap Pesan Error / Sukses dari Backend (Jika ada) -->
      <?php if (isset($_GET['error'])): ?>
        <?php
          $err_raw = (string)$_GET['error'];
          $err_txt = (strpos($err_raw, ' ') === false) ? str_replace('_', ' ', $err_raw) : $err_raw;
        ?>
        <div class="callout danger">
          <div><strong>Verifikasi Gagal:</strong> <?= htmlspecialchars($err_txt, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['pesan'])): ?>
        <?php
          $msg_raw = (string)$_GET['pesan'];
          $msg_txt = (strpos($msg_raw, ' ') === false) ? str_replace('_', ' ', $msg_raw) : $msg_raw;
        ?>
        <div class="callout success">
          <div><?= htmlspecialchars($msg_txt, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      <?php endif; ?>

      <form action="process/reset_password.php" method="POST">
        <div class="field">
          <label for="otp">Kode OTP (6 Digit)</label>
          <input type="text" id="otp" name="otp" class="input mono" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Masukkan 6 digit kode OTP" required autofocus>
          <small class="muted">Periksa folder Inbox atau Spam jika email belum terlihat.</small>
        </div>

        <div class="field">
          <label for="password_baru">Kata Sandi Baru</label>
          <input type="password" id="password_baru" name="password_baru" class="input" placeholder="Minimal 6 karakter..." minlength="6" required>
        </div>

        <div class="field">
          <label for="konfirmasi_password">Konfirmasi Kata Sandi Baru</label>
          <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="input" placeholder="Ulangi kata sandi baru..." minlength="6" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-2">Verifikasi OTP &amp; Simpan Kata Sandi</button>
      </form>

      <div class="login-foot">
        <a href="forgot_password.php" class="text-primary fw-semibold">Kirim Ulang Kode OTP</a>
        <div class="mt-2">
          <a href="login.php" class="muted">&larr; Kembali ke Halaman Login</a>
        </div>
      </div>
    </div>

  </div>

</body>

</html>
