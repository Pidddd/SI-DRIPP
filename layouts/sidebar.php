<?php
/**
 * LAYOUT SIDEBAR DINAMIS (PROTOTYPE)
 * File: layouts/sidebar.php
 * Fungsi: Menu navigasi yang di-filter berdasarkan $_SESSION['role'].
 * Dipanggil dari layouts/header.php (variabel $root, $role, $me, $active tersedia).
 * Item dengan 'built' => false ditandai "Soon" (halaman belum dibuat di prototype).
 */

$menu = [
    'Operasional' => [
        ['key' => 'dashboard',    'label' => 'Dashboard',        'icon' => 'home',      'href' => 'modules/dashboard/index.php',     'roles' => ['super_admin', 'admin'],                  'built' => true],
        ['key' => 'transactions', 'label' => 'Kasir (POS)',      'icon' => 'cart',      'href' => 'modules/transactions/index.php',  'roles' => ['super_admin', 'admin', 'kasir'],          'built' => true],
        ['key' => 'tx_history',   'label' => 'Riwayat Transaksi','icon' => 'clock',     'href' => 'modules/transactions/history.php','roles' => ['super_admin', 'admin', 'kasir'],         'built' => true],
        ['key' => 'receivables',  'label' => 'Manajemen Piutang','icon' => 'wallet',    'href' => 'modules/receivables/index.php',   'roles' => ['super_admin', 'admin'],                  'built' => true],
    ],
    'Master Data' => [
        ['key' => 'categories',   'label' => 'Kategori Produk',  'icon' => 'tag',       'href' => 'modules/categories/index.php',    'roles' => ['super_admin', 'admin'],                  'built' => true],
        ['key' => 'products',     'label' => 'Master Produk',    'icon' => 'box',       'href' => 'modules/products/index.php',      'roles' => ['super_admin', 'admin'],                  'built' => true],
    ],
    'Gudang & Logistik' => [
        ['key' => 'inventory',    'label' => 'Inventaris Stok',  'icon' => 'box',       'href' => 'modules/inventory/index.php',     'roles' => ['super_admin', 'admin', 'staf_gudang'],    'built' => true],
        ['key' => 'defects',      'label' => 'Barang Defect',    'icon' => 'alert',     'href' => 'modules/inventory/defect.php',    'roles' => ['super_admin', 'admin', 'staf_gudang'],    'built' => true],
        ['key' => 'stock_opname', 'label' => 'Stock Opname',     'icon' => 'clipboard', 'href' => 'modules/inventory/opname.php',    'roles' => ['super_admin', 'admin', 'staf_gudang'],    'built' => true],
    ],
    'Laporan & Keuangan' => [
        ['key' => 'reports',      'label' => 'Rekap Penjualan',  'icon' => 'report',    'href' => 'modules/reports/index.php',       'roles' => ['super_admin'],                           'built' => true],
    ],
    'Administrasi' => [
        ['key' => 'users',        'label' => 'Manajemen Akun',   'icon' => 'users',     'href' => 'modules/users/index.php',         'roles' => ['super_admin'],                           'built' => true],
    ],
];
?>
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
  <a class="brand" href="<?= $root . $me['home'] ?>">
    <span class="brand-mark">
      <img src="<?= BASE_URL ?>assets/img/Logo.jpeg" alt="MKP Store" class="brand-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
      <span class="brand-fallback-text" style="display:none; font-weight:800; font-size:11px; color:#fff; align-items:center; justify-content:center; width:100%; height:100%; text-align:center; padding:2px;">MKP Store</span>
    </span>
    <span class="brand-text">
      <strong>SI-DRIPP</strong>
      <span>MKP Store · Malang</span>
    </span>
  </a>

  <nav class="nav">
    <?php foreach ($menu as $group => $items):
        $visible = array_filter($items, function ($it) use ($role) { return in_array($role, $it['roles'], true); });
        if (!$visible) continue;
    ?>
      <div class="nav-group">
        <div class="nav-label"><?= e($group) ?></div>
        <?php foreach ($visible as $it): ?>
          <?php if ($it['built']): ?>
            <a class="nav-link <?= $active === $it['key'] ? 'active' : '' ?>" href="<?= BASE_URL . $it['href'] ?>" id="nav-<?= e($it['key']) ?>">
              <?= icon($it['icon']) ?><span><?= e($it['label']) ?></span>
            </a>
          <?php else: ?>
            <span class="nav-link is-soon" title="Belum tersedia di prototype" id="nav-<?= e($it['key']) ?>">
              <?= icon($it['icon']) ?><span><?= e($it['label']) ?></span><span class="tag-soon">Soon</span>
            </span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-foot">
    <div class="role-line">
      <span class="eyebrow">Role Aktif</span>
      <?= role_badge($role) ?>
    </div>
    <small><?= e($me['desc']) ?></small>
  </div>
</aside>