<?php
/**
 * MODUL INVENTARIS — STOCK OPNAME GUDANG
 * File: modules/inventory/opname.php
 * Fungsi: Antarmuka Audit Perbandingan Stok Sistem vs Stok Fisik Gudang beserta penyesuaian ke Supabase.
 */

$root       = '../../';
$page_title = 'Stock Opname Gudang';
$page_sub   = 'Audit kesesuaian stok sistem terhadap perhitungan fisik rak gudang · MKP Store';
$active     = 'stock_opname';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil daftar produk beserta stok sistem saat ini
$q_products = "SELECT p.id_product, p.nama_barang, p.stok_aktual, c.nama_category
               FROM products p
               LEFT JOIN category c ON p.categoryid_category = c.id_category
               ORDER BY p.nama_barang ASC";
$res_products = pg_query($conn, $q_products);

// Ambil riwayat stock opname terakhir
$q_history = "SELECT so.id_opname, so.waktu_opname, so.stok_sistem, so.stok_fisik, so.selisih, so.status_validasi,
                     p.id_product, p.nama_barang, u.nama AS petugas
              FROM stock_opname so
              JOIN products p ON so.productsid_product = p.id_product
              LEFT JOIN users u ON so.userid_user = u.id_user
              ORDER BY so.waktu_opname DESC, so.id_opname DESC
              LIMIT 15";
$res_history = pg_query($conn, $q_history);
?>

<?php if (isset($_SESSION['success'])): ?>
  <div class="callout success rise">
    <?= icon('check', 18) ?>
    <div><?= e($_SESSION['success']) ?></div>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div><?= e($_SESSION['error']) ?></div>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="callout amber rise">
  <?= icon('clipboard', 20) ?>
  <div>
    <strong>Audit Stock Opname Berkala:</strong> Masukkan hasil hitung <em>Stok Fisik</em> aktual di rak gudang pada masing-masing baris produk, lalu klik <strong>Sesuaikan</strong> untuk menyelaraskan stok sistem secara otomatis.
  </div>
</div>

<!-- Tabel Perbandingan & Penyesuaian Stok Sistem vs Stok Fisik -->
<div class="card flush mb-5 rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Tabel Audit &amp; Penyesuaian Stok (Sistem vs Fisik)</h2>
      <p>Pemeriksaan fisik gudang HORECA oleh Staf Logistik pada <?= e(tgl_id()) ?></p>
    </div>
    <span class="badge info"><i class="dot"></i>Sinkronisasi Langsung ke Produk</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th>Stok Sistem</th>
          <th>Input Stok Fisik</th>
          <th>Catatan Validasi</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res_products && pg_num_rows($res_products) > 0): ?>
          <?php $no = 1; while ($p = pg_fetch_assoc($res_products)):
              $form_id = 'form-opname-' . $no;
          ?>
            <tr>
              <td class="mono muted"><?= $no ?></td>
              <td>
                <strong><?= e($p['nama_barang']) ?></strong>
                <div class="muted text-xs">SKU: <?= e($p['id_product']) ?></div>
              </td>
              <td><span class="badge info"><?= e($p['nama_category'] ?? 'Umum') ?></span></td>
              <td class="mono"><strong><?= (int)$p['stok_aktual'] ?> Botol</strong></td>
              <td>
                <form id="<?= e($form_id) ?>" action="<?= BASE_URL ?>modules/inventory/process/stock_opname.php" method="POST">
                  <input type="hidden" name="id_product" value="<?= e($p['id_product']) ?>">
                  <input type="number" name="stok_fisik" class="input mono" min="0" value="<?= (int)$p['stok_aktual'] ?>" required>
                </form>
              </td>
              <td>
                <input type="text" name="status_validasi" form="<?= e($form_id) ?>" class="input" value="Audit Rutin Gudang" placeholder="Catatan opname" required>
              </td>
              <td class="text-right">
                <button type="submit" form="<?= e($form_id) ?>" class="btn btn-primary btn-sm" onclick="return confirm('Terapkan penyesuaian stok fisik ke dalam sistem?')">
                  <?= icon('clipboard', 14) ?> Sesuaikan
                </button>
              </td>
            </tr>
          <?php $no++; endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="table-empty">Belum ada produk terdaftar untuk diaudit.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Riwayat Audit Stock Opname -->
<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Riwayat Penyesuaian Stock Opname Terakhir</h2>
      <p>Baris berwarna merah menandakan ditemukannya selisih antara stok sistem dan stok fisik</p>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Waktu Opname</th>
          <th>Produk</th>
          <th>Stok Sistem</th>
          <th>Stok Fisik</th>
          <th>Selisih</th>
          <th>Keterangan / Petugas</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res_history && pg_num_rows($res_history) > 0): ?>
          <?php $no_h = 1; while ($h = pg_fetch_assoc($res_history)):
              $selisih = (int)$h['selisih'];
          ?>
            <tr class="<?= $selisih !== 0 ? 'tr-danger' : '' ?>">
              <td class="mono"><?= $no_h++ ?></td>
              <td class="nowrap"><?= e(date('d M Y · H:i', strtotime($h['waktu_opname']))) ?></td>
              <td>
                <strong><?= e($h['nama_barang']) ?></strong>
                <div class="text-xs">SKU: <?= e($h['id_product']) ?></div>
              </td>
              <td class="mono"><?= (int)$h['stok_sistem'] ?></td>
              <td class="mono"><strong><?= (int)$h['stok_fisik'] ?></strong></td>
              <td class="mono"><strong><?= $selisih > 0 ? '+' . $selisih : $selisih ?></strong></td>
              <td><?= e($h['status_validasi']) ?> (<?= e($h['petugas'] ?? 'Petugas') ?>)</td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="table-empty">Belum ada riwayat penyesuaian stock opname.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
