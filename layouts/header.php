<?php
/**
 * LAYOUT HEADER (PROTOTYPE)
 * File: layouts/header.php
 * Fungsi : Membuka dokumen HTML, memuat sidebar, dan merender topbar
 *          (profil user aktif, penanda role, switch role simulasi).
 *
 * Variabel yang diset oleh halaman sebelum require:
 *   $root       (string) path relatif ke root project, mis. '../../'
 *   $page_title (string) judul halaman (menjadi <h1>)
 *   $page_sub   (string) subjudul opsional
 *   $active     (string) key menu sidebar yang aktif
 *   $allowed    (array)  daftar role yang boleh mengakses (kosong = semua)
 *
 * Role disimulasikan lewat $_SESSION['role'] (ganti via ?as=<role>).
 */

require_once __DIR__ . '/mock_data.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root       = isset($root) ? $root : '../../';
$page_title = isset($page_title) ? $page_title : 'SI-DRIPP';
$page_sub   = isset($page_sub) ? $page_sub : '';
$active     = isset($active) ? $active : '';
$allowed    = isset($allowed) ? $allowed : [];

$roles = role_catalog();

/* --- Switch role simulasi: ?as=kasir  -> set session lalu redirect bersih --- */
if (isset($_GET['as']) && isset($roles[$_GET['as']])) {
    $_SESSION['role']    = $_GET['as'];
    $_SESSION['id_user'] = $roles[$_GET['as']]['id'];
    $q = $_GET;
    unset($q['as']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . ($q ? '?' . http_build_query($q) : ''));
    exit();
}

/* --- Default role bila belum ada session --- */
if (!isset($_SESSION['role']) || !isset($roles[$_SESSION['role']])) {
    $_SESSION['role']    = 'super_admin';
    $_SESSION['id_user'] = $roles['super_admin']['id'];
}

$role = $_SESSION['role'];
$me   = $roles[$role];

$is_forbidden = !empty($allowed) && !in_array($role, $allowed, true);
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
        <?= role_badge($role) ?>

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
                <div class="muted" style="font-size:12px">@<?= e($me['username']) ?></div>
              </div>
            </div>
            <div class="pm-label"><?= icon('flask', 13) ?> Switch Role (Simulasi)</div>
            <?php foreach ($roles as $key => $r): ?>
              <a class="pm-role <?= $key === $role ? 'active' : '' ?>" href="<?= e(role_url($key)) ?>">
                <span><?= e($r['label']) ?></span>
                <?php if ($key === $role): ?><?= icon('check', 16) ?><?php endif; ?>
              </a>
            <?php endforeach; ?>
            <div class="pm-sep"></div>
            <a class="pm-link" href="<?= $root ?>modules/auth/login.php?logout=1"><?= icon('logout', 18) ?> Keluar</a>
          </div>
        </details>
      </div>
    </header>

    <div class="content">
<?php
/* --- Proteksi akses: tampilkan 403 Forbidden mockup bila role tidak diizinkan --- */
if ($is_forbidden):
    $need = array_map(function ($r) use ($roles) { return $roles[$r]['label']; }, $allowed);
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
            <a class="btn btn-ghost" href="<?= e(role_url($allowed[0])) ?>"><?= icon('shield', 18) ?> Simulasikan <?= e($roles[$allowed[0]]['label']) ?></a>
          </div>
        </div>
      </section>
<?php
    require __DIR__ . '/footer.php';
    exit();
endif;
?>
