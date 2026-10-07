<?php
/**
 * UI MODUL DASHBOARD - SI-DRIPP
 * File: modules/dashboard/index.php
 * Fungsi: Halaman tujuan setelah login. Menampilkan ringkasan statistik
 *         (Total Produk, Total Kategori, Total Transaksi) dari PostgreSQL.
 */

require_once '../../config/bootstrap.php';
require_once '../../config/auth_guard.php';

/**
 * Menjalankan query COUNT(*) dan mengembalikan angkanya.
 * Mengembalikan null jika query gagal, supaya halaman tetap tampil (tidak blank).
 */
function hitung_total($conn, $sql) {
    $result = pg_query($conn, $sql);
    if (!$result) {
        return null;
    }
    return (int) pg_fetch_result($result, 0, 0);
}

function tampil_angka($n) {
    return $n === null ? '—' : number_format($n, 0, ',', '.');
}

// Nama tabel kategori di skema adalah "category" (tunggal), lihat docs/sidripp_database.sql
$total_produk    = hitung_total($conn, 'SELECT COUNT(*) FROM products');
$total_kategori  = hitung_total($conn, 'SELECT COUNT(*) FROM category');
$total_transaksi = hitung_total($conn, 'SELECT COUNT(*) FROM transactions');

$stat_cards = [
    ['label' => 'Total Produk',     'value' => $total_produk,    'icon' => 'box',  'hint' => 'item terdaftar di katalog'],
    ['label' => 'Total Kategori',   'value' => $total_kategori,  'icon' => 'tag',  'hint' => 'kategori produk aktif'],
    ['label' => 'Total Transaksi',  'value' => $total_transaksi, 'icon' => 'cart', 'hint' => 'nota penjualan tercatat'],
];

// Halaman ini hanya untuk Super Admin & Admin. Role lain mendapat layar 403 dari header.php
$root       = '../../';
$page_title = 'Dashboard';
$page_sub   = 'Ringkasan data MKP Store';
$active     = 'dashboard';
$allowed    = ['super_admin', 'admin'];

require_once '../../layouts/header.php';
?>

<?php if (isset($_GET['error'])): ?>
  <div class="callout" style="background: var(--danger-bg); color: var(--danger); border: 1px solid #FECACA;">
    <?= e(str_replace('_', ' ', $_GET['error'])) ?>
  </div>
<?php endif; ?>

<div class="bento">
  <?php foreach ($stat_cards as $card): ?>
    <div class="card stat span-4 rise">
      <div class="stat-top">
        <span class="stat-label"><?= e($card['label']) ?></span>
        <span class="stat-icon"><?= icon($card['icon']) ?></span>
      </div>
      <div class="stat-value"><?= tampil_angka($card['value']) ?></div>
      <div class="stat-foot">
        <?php if ($card['value'] === null): ?>
          <span class="badge danger"><i class="dot"></i>Query gagal</span>
          <span>cek koneksi / tabel database</span>
        <?php else: ?>
          <span class="badge success"><i class="dot"></i>Live</span>
          <span><?= e($card['hint']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php
require_once '../../layouts/footer.php';
?>