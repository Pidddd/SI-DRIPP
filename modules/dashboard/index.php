<?php
/**
 * UI MODUL DASHBOARD - SI-DRIPP
 * File: modules/dashboard/index.php
 * Fungsi: Halaman tujuan setelah login. Menampilkan Hero Banner Artisan HORECA,
 *         ringkasan statistik (Total Produk, Total Kategori, Total Transaksi),
 *         navigasi cepat operasional, dan katalog unggulan dari PostgreSQL.
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

// Ambil 5 produk dengan stok terendah / unggulan untuk ditampilkan pada Bento Grid bawah
$res_highlight = pg_query($conn, "SELECT p.id_product, p.nama_barang, p.harga_jual, p.stok_aktual, c.nama_category 
                                  FROM products p 
                                  LEFT JOIN category c ON p.categoryid_category = c.id_category 
                                  ORDER BY p.stok_aktual ASC, p.nama_barang ASC 
                                  LIMIT 5");

$stat_cards = [
    ['label' => 'Total Produk',     'value' => $total_produk,    'icon' => 'box',  'hint' => 'item terdaftar di katalog'],
    ['label' => 'Total Kategori',   'value' => $total_kategori,  'icon' => 'tag',  'hint' => 'kategori produk aktif'],
    ['label' => 'Total Transaksi',  'value' => $total_transaksi, 'icon' => 'cart', 'hint' => 'nota penjualan tercatat'],
];

// Halaman ini hanya untuk Super Admin & Admin. Role lain mendapat layar 403 dari header.php
$root       = '../../';
$page_title = 'Dashboard';
$page_sub   = 'Ringkasan operasional & distribusi HORECA · MKP Store Malang';
$active     = 'dashboard';
$allowed    = ['super_admin', 'admin'];

require_once '../../layouts/header.php';
?>

<?php if (isset($_GET['error'])): ?>
  <div class="callout danger">
    <?= e(str_replace('_', ' ', $_GET['error'])) ?>
  </div>
<?php endif; ?>

<div class="bento">
  <!-- 1. Artisan Coffeehouse Hero Banner -->
  <div class="card hero-banner span-12 rise">
    <div class="hero-content">
      <span class="hero-eyebrow">
        <i class="dot"></i> Distributor Resmi HORECA Malang &middot; DRIPP &amp; Multibev
      </span>
      <h2><?= e(salam()) ?>, <?= e($me['nama']) ?>!</h2>
      <p>
        Pantau ketersediaan stok sirup gourmet, bubuk minuman barista, serta aktivitas transaksi harian CV. Mitra Kembar Pradipta dalam satu panel terpadu.
      </p>
    </div>
    <div class="hero-actions">
      <a href="<?= BASE_URL ?>modules/transactions/index.php" class="btn btn-caramel">
        <?= icon('cart', 18) ?> Buka Kasir (POS)
      </a>
      <a href="<?= BASE_URL ?>modules/products/index.php" class="btn btn-glass">
        <?= icon('box', 18) ?> Kelola Katalog Produk
      </a>
    </div>
  </div>

  <!-- 2. Tiga Kartu Statistik Utama -->
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

  <!-- 3. Bento Kiri: Pantauan Katalog & Stok Produk -->
  <div class="card span-7 rise">
    <div class="card-head">
      <div>
        <h2>Pantauan Stok &amp; Katalog Produk</h2>
        <p>5 item dengan prioritas pemantauan stok gudang saat ini</p>
      </div>
      <a href="<?= BASE_URL ?>modules/inventory/index.php" class="btn btn-soft btn-sm">Lihat Gudang &rarr;</a>
    </div>

    <div class="list">
      <?php if ($res_highlight && pg_num_rows($res_highlight) > 0): ?>
        <?php while ($item = pg_fetch_assoc($res_highlight)):
            $stok_item = (int)$item['stok_aktual'];
            $badge_cls = $stok_item <= 0 ? 'danger' : ($stok_item < 24 ? 'warn' : 'success');
        ?>
          <div class="list-item">
            <span class="thumb">
              <?= bottle_svg('#D97706', '#78350F', substr($item['nama_barang'], 0, 10), '760 ml', 'syrup', 'DRIPP', $item['id_product']) ?>
            </span>
            <div class="grow">
              <strong><?= e($item['nama_barang']) ?></strong>
              <small><?= e($item['nama_category'] ?? 'Sirup DRIPP') ?> &middot; SKU: <?= e($item['id_product']) ?> &middot; <?= rupiah($item['harga_jual']) ?></small>
            </div>
            <span class="badge <?= $badge_cls ?> mono">
              <i class="dot"></i><?= $stok_item ?> unit
            </span>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="table-empty">Belum ada produk terdaftar di katalog.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 4. Bento Kanan: Pintasan Cepat Operasional -->
  <div class="card span-5 rise">
    <div class="card-head">
      <div>
        <h2>Akses Cepat Operasional</h2>
        <p>Pintasan modul utama sesuai alur kerja MKP Store</p>
      </div>
    </div>

    <div class="quick-grid">
      <a href="<?= BASE_URL ?>modules/transactions/index.php" class="quick-tile">
        <span class="qt-icon"><?= icon('cart', 19) ?></span>
        <div>
          <strong>Kasir (POS)</strong>
          <small>Input nota B2B/B2C</small>
        </div>
      </a>

      <a href="<?= BASE_URL ?>modules/receivables/index.php" class="quick-tile">
        <span class="qt-icon"><?= icon('wallet', 19) ?></span>
        <div>
          <strong>Piutang Mitra</strong>
          <small>Tagihan &amp; pengingat WA</small>
        </div>
      </a>

      <a href="<?= BASE_URL ?>modules/inventory/index.php" class="quick-tile">
        <span class="qt-icon"><?= icon('truck', 19) ?></span>
        <div>
          <strong>Inventaris Stok</strong>
          <small>Barang masuk &amp; keluar</small>
        </div>
      </a>

      <a href="<?= BASE_URL ?>modules/opname/index.php" class="quick-tile">
        <span class="qt-icon"><?= icon('clipboard', 19) ?></span>
        <div>
          <strong>Stock Opname</strong>
          <small>Audit fisik gudang</small>
        </div>
      </a>

      <a href="<?= BASE_URL ?>modules/categories/index.php" class="quick-tile">
        <span class="qt-icon"><?= icon('tag', 19) ?></span>
        <div>
          <strong>Kategori Produk</strong>
          <small>Klasifikasi varian</small>
        </div>
      </a>

      <a href="<?= BASE_URL ?>modules/reports/index.php" class="quick-tile">
        <span class="qt-icon"><?= icon('report', 19) ?></span>
        <div>
          <strong>Rekap Penjualan</strong>
          <small>Laporan &amp; cetak PDF</small>
        </div>
      </a>
    </div>
  </div>
</div>

<?php
require_once '../../layouts/footer.php';
?>
