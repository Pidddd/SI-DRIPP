<?php

/**
 * LAYOUT HEADER
 * File: layouts/header.php
 * Fungsi : Membuka dokumen HTML, memuat sidebar, dan merender topbar
 *          (profil user aktif, penanda role, switch role untuk Super Admin).
 *
 * Aturan RBAC & Switch Role:
 *   1. Super Admin memiliki hak akses penuh ke seluruh halaman & fitur semua role
 *      tanpa mengganti identitas akun aslinya.
 *   2. Admin, Kasir, dan Staf Gudang hanya dapat mengakses fitur di ranah role
 *      mereka masing-masing (tombol role lain terkunci/disabled).
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/mock_data.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$root       = isset($root) ? $root : '../../';
if (!defined('BASE_URL')) {
  define('BASE_URL', $root);
}
$page_title = isset($page_title) ? $page_title : 'SI-DRIPP';
$page_sub   = isset($page_sub) ? $page_sub : '';
$active     = isset($active) ? $active : '';
$allowed    = isset($allowed) ? $allowed : [];

$roles = role_catalog();

/* --- Sinkronisasi & Pemulihan Akun Asli dari Database --- */
if (isset($conn) && $conn) {
  // Jika session username kosong (misal akibat bug switch role lama atau sesi awal),
  // pulihkan otomatis ke akun Super Admin dari tabel users
  if (empty($_SESSION['username'])) {
    $res_sa = pg_query($conn, "SELECT id_user, nama, username, role FROM users ORDER BY CASE WHEN LOWER(role) IN ('super_admin', 'super admin', 'owner') THEN 0 ELSE 1 END, id_user ASC LIMIT 1");
    if ($res_sa && pg_num_rows($res_sa) > 0) {
      $row_sa = pg_fetch_assoc($res_sa);
      $_SESSION['id_user']  = (int)$row_sa['id_user'];
      $_SESSION['nama']     = $row_sa['nama'];
      $_SESSION['username'] = $row_sa['username'];
      $_SESSION['role']     = normalisasi_role($row_sa['role']);
    }
  } else {
    // Jika user sudah login riil, pastikan id_user & role selalu merujuk ke data asli akunnya di DB
    $cek_uid = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
    $res_uid = pg_query_params($conn, "SELECT id_user, nama, username, role FROM users WHERE id_user = $1 LIMIT 1", [$cek_uid]);
    if ($res_uid && pg_num_rows($res_uid) > 0) {
      $row_u = pg_fetch_assoc($res_uid);
      $_SESSION['id_user']  = (int)$row_u['id_user'];
      $_SESSION['nama']     = $row_u['nama'];
      $_SESSION['username'] = $row_u['username'];
      $_SESSION['role']     = normalisasi_role($row_u['role']);
    }
  }
}

/* --- Default role bila belum ada session --- */
if (isset($_SESSION['role'])) {
  $_SESSION['role'] = normalisasi_role($_SESSION['role']);
}
if (!isset($_SESSION['role']) || !isset($roles[$_SESSION['role']])) {
  $_SESSION['role']     = 'super_admin';
  $_SESSION['id_user']  = $roles['super_admin']['id'];
  $_SESSION['nama']     = $roles['super_admin']['nama'];
  $_SESSION['username'] = $roles['super_admin']['username'];
}

$role          = $_SESSION['role'];
$is_superadmin = ($role === 'super_admin');

/* --- Switch Role Navigasi (?as=<role>) ---
 * Hanya Super Admin yang diizinkan berpindah ruang kerja lintas role,
 * dan AKUN ASLI Super Admin TIDAK IKUT TERGANTI.
 */
if (isset($_GET['as']) && isset($roles[$_GET['as']])) {
  $target_role = $_GET['as'];
  if ($is_superadmin) {
    $_SESSION['view_role'] = $target_role;
    header('Location: ' . $root . $roles[$target_role]['home']);
    exit();
  } else {
    // Role selain Super Admin terkunci di ranahnya sendiri
    $_SESSION['view_role'] = $role;
    $q = $_GET;
    unset($q['as']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . ($q ? '?' . http_build_query($q) : ''));
    exit();
  }
}

/* --- Role tampilan aktif pada bar Switch Role --- */
$view_role = ($is_superadmin && isset($_SESSION['view_role']) && isset($roles[$_SESSION['view_role']]))
  ? $_SESSION['view_role']
  : $role;

/* --- Profil akun asli yang sedang login (TIDAK berubah saat switch role) --- */
$me = $roles[$role];
if (isset($_SESSION['nama']) && !empty($_SESSION['nama'])) {
  $me['nama'] = $_SESSION['nama'];
}
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
  $me['username'] = $_SESSION['username'];
}

/* --- Super Admin memiliki akses penuh ke seluruh halaman (tidak pernah 403) --- */
$is_forbidden = !$is_superadmin && !empty($allowed) && !in_array($role, $allowed, true);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> · SI-DRIPP MKP Store</title>
  <meta name="description" content="SI-DRIPP — Sistem Informasi Pendataan Stok dan Transaksi MKP Store, supplier HORECA sirup DRIPP di Malang.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $root ?>assets/css/style.css">
  <script>
    (function() {
      try {
        var t = localStorage.getItem('sidripp_theme');
        if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
      } catch (e) {}
    })();
  </script>
</head>

<body>
  <div class="app">

    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="sidebar-backdrop" data-nav-close></div>

    <div class="main">
      <header class="topbar">
        <div class="topbar-left">
          <button type="button" class="menu-toggle" data-nav-toggle id="btn-menu" aria-label="Buka menu"><?= icon('menu') ?></button>
          <div class="topbar-title">
            <h1><?= e($page_title) ?></h1>
            <?php if ($page_sub !== ''): ?><p><?= e($page_sub) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="topbar-right">
          <span class="date-chip"><?= icon('clock', 16) ?><?= e(tgl_id()) ?></span>
          <?= role_badge($view_role) ?>
          <button type="button" class="theme-toggle" id="btn-theme-toggle" title="Ganti Mode Terang / Gelap" aria-label="Ganti Mode Terang / Gelap">
            <span class="ico-sun"><?= icon('sun', 18) ?></span>
            <span class="ico-moon"><?= icon('moon', 18) ?></span>
          </button>

          <details class="profile" id="profile-menu">
            <summary>
              <span class="profile-btn">
                <span class="avatar"><?= e(initials($me['nama'])) ?></span>
                <span class="profile-info">
                  <strong><?= e($me['nama']) ?></strong>
                  <span><?= e($me['label']) ?></span>
                </span>
                <?= icon('chevron', 16) ?>
              </span>
            </summary>
            <div class="profile-menu">
              <div class="pm-head">
                <span class="avatar lg"><?= e(initials($me['nama'])) ?></span>
                <div>
                  <strong><?= e($me['nama']) ?></strong>
                  <div class="muted text-xs">@<?= e($me['username']) ?></div>
                </div>
              </div>
              <div class="pm-label">
                <?= icon('shield', 13) ?>
                <?= $is_superadmin ? 'Akses Lintas Role (Super Admin)' : 'Ranah Akses Role Anda' ?>
              </div>
              <?php foreach ($roles as $key => $r): ?>
                <?php if ($is_superadmin || $key === $role): ?>
                  <a class="pm-role <?= $key === $view_role ? 'active' : '' ?>" href="<?= e(role_url($key)) ?>">
                    <span><?= e($r['label']) ?></span>
                    <?php if ($key === $view_role): ?><?= icon('check', 16) ?><?php endif; ?>
                  </a>
                <?php else: ?>
                  <span class="pm-role is-locked" title="Terkunci — Hanya dapat diakses sesuai role masing-masing">
                    <span><?= e($r['label']) ?></span>
                    <?= icon('lock', 14) ?>
                  </span>
                <?php endif; ?>
              <?php endforeach; ?>
              <div class="pm-sep"></div>
              <a class="pm-link" href="<?= BASE_URL ?>modules/auth/process/logout.php"><?= icon('logout', 18) ?> Keluar</a>
            </div>
          </details>
        </div>
      </header>

      <div class="content">
        <?php
        /* --- Proteksi akses: tampilkan 403 Forbidden bila role non-Super Admin mengakses luar ranahnya --- */
        if ($is_forbidden):
          $need = array_map(function ($r) use ($roles) {
            return $roles[$r]['label'];
          }, $allowed);
        ?>
          <section class="forbidden rise" aria-labelledby="forbidden-title">
            <div class="card forbidden-card">
              <div class="forbidden-icon"><?= icon('lock', 38) ?></div>
              <div class="forbidden-code">403 · FORBIDDEN</div>
              <h2 id="forbidden-title">Akses Ditolak</h2>
              <p>Halaman <strong><?= e($page_title) ?></strong> hanya dapat diakses oleh <strong><?= e(implode(', ', $need)) ?></strong>.</p>
              <div class="forbidden-who">Anda masuk sebagai <?= role_badge($role) ?></div>
              <div class="forbidden-actions">
                <a class="btn btn-primary" href="<?= $root . $me['home'] ?>"><?= icon('home', 18) ?> Ke Halaman Utama Saya</a>
              </div>
            </div>
          </section>
        <?php
          require __DIR__ . '/footer.php';
          exit();
        endif;
        ?>
