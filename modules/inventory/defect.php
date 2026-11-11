<?php
/**
 * UI MODUL INVENTORY - BARANG DEFECT (BULK INPUT)
 * File: modules/inventory/defect.php
 * Fungsi: Form pelaporan massal (Save All) dan riwayat barang rusak/cacat yang otomatis memotong stok aktual.
 */

$root       = '../../';
$page_title = 'Laporan Barang Defect';
$page_sub   = 'Pencatatan massal dan riwayat barang rusak, pecah, atau kedaluwarsa di gudang · MKP Store';
$active     = 'defects';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil seluruh daftar produk beserta nama kategorinya untuk Tabel Bulk Input
$q_products = "SELECT p.id_product, p.nama_barang, p.stok_aktual, c.nama_category 
               FROM products p 
               LEFT JOIN category c ON p.categoryid_category = c.id_category 
               ORDER BY p.nama_barang ASC";
$res_products = pg_query($conn, $q_products);
$total_products = $res_products ? pg_num_rows($res_products) : 0;

// Ambil riwayat laporan barang defect menggunakan Multi-table JOIN (defects, products, users)
$q_defects = "SELECT d.id_defect, d.waktu_lapor, d.kuantitas, d.keterangan_rusak,
                     p.id_product, p.nama_barang, u.nama AS pelapor
              FROM defects d
              JOIN products p ON d.productsid_product = p.id_product
              LEFT JOIN users u ON d.userid_user = u.id_user
              ORDER BY d.waktu_lapor DESC, d.id_defect DESC";
$res_defects = pg_query($conn, $q_defects);
$total_defect_rows = $res_defects ? pg_num_rows($res_defects) : 0;
?>

<!-- Flash Messages -->
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

<!-- SOP Callout Info -->
<div class="callout amber rise">
  <?= icon('alert', 20) ?>
  <div>
    <strong>SOP Penanganan Barang Rusak / Defect:</strong><br>
    Pencatatan barang cacat (pecah, bocor, segel rusak, atau kedaluwarsa) akan <strong>langsung memotong stok fisik aktual</strong> di gudang secara otomatis. Masukkan jumlah botol yang rusak pada baris produk terkait, lalu tekan tombol <strong>"Save All Laporan Defect"</strong>.
  </div>
</div>

<!-- 1. Form Tabel Bulk Input Barang Defect -->
<form action="<?= BASE_URL ?>modules/inventory/process/defects.php" method="POST">
  <div class="card flush mb-5 rise">
    <div class="card-head pad pb-4">
      <div>
        <h2>Tabel Input Massal Barang Defect (Save All)</h2>
        <p>Total <?= (int)$total_products ?> produk aktif terdaftar di katalog gudang MKP Store</p>
      </div>
      <span class="badge warn"><i class="dot"></i>Potong Stok Otomatis</span>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Stok Gudang</th>
            <th>Jumlah Rusak</th>
            <th>Keterangan / Kronologi Kerusakan</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($res_products && $total_products > 0): ?>
            <?php $no = 1; while ($p = pg_fetch_assoc($res_products)): 
                $pid = e($p['id_product']);
                $stok = (int)$p['stok_aktual'];
                $is_empty = ($stok <= 0);
            ?>
              <tr>
                <td class="mono muted"><?= $no++ ?></td>
                <td>
                  <strong><?= e($p['nama_barang']) ?></strong>
                  <div class="muted text-xs">SKU: <?= $pid ?></div>
                </td>
                <td><span class="badge info"><?= e($p['nama_category'] ?? 'Umum') ?></span></td>
                <td class="mono">
                  <?php if ($is_empty): ?>
                    <span class="badge danger"><i class="dot"></i>Habis (0)</span>
                  <?php else: ?>
                    <strong><?= $stok ?> Unit</strong>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="qty-stepper">
                    <button type="button" class="btn-step" onclick="stepDownDefect('<?= $pid ?>')" <?= $is_empty ? 'disabled' : '' ?>>−</button>
                    <input type="number" id="qty_<?= $pid ?>" name="kuantitas[<?= $pid ?>]" class="input-step mono" value="0" min="0" max="<?= $stok ?>" <?= $is_empty ? 'disabled' : '' ?>>
                    <button type="button" class="btn-step" onclick="stepUpDefect('<?= $pid ?>', <?= $stok ?>)" <?= $is_empty ? 'disabled' : '' ?>>+</button>
                  </div>
                </td>
                <td>
                  <input type="text" name="keterangan_rusak[<?= $pid ?>]" class="input" placeholder="Contoh: Pecah saat pengiriman / bocor / kadaluwarsa" maxlength="255" <?= $is_empty ? 'disabled' : '' ?>>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="table-empty">Belum ada data produk di katalog.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="card-foot-bar">
      <span class="muted text-xs">
        <?= icon('alert', 14) ?> Hanya baris dengan jumlah rusak &gt; 0 yang akan dicatat dan memotong stok produk.
      </span>
      <button type="submit" class="btn btn-primary">
        <?= icon('check', 18) ?> Save All Laporan Defect
      </button>
    </div>
  </div>
</form>

<!-- 2. Tabel Riwayat Laporan Barang Defect -->
<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Daftar Riwayat Barang Defect / Rusak</h2>
      <p>Arsip laporan kerusakan fisik dari staf gudang yang telah memotong stok aktual</p>
    </div>
    <span class="badge danger"><i class="dot"></i><?= (int)$total_defect_rows ?> Laporan Tercatat</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Tanggal Lapor</th>
          <th>Produk</th>
          <th>Jumlah</th>
          <th>Keterangan Kerusakan</th>
          <th>Pelapor</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res_defects && $total_defect_rows > 0): ?>
          <?php $no = 1; while ($d = pg_fetch_assoc($res_defects)): ?>
            <tr>
              <td class="mono muted"><?= $no++ ?></td>
              <td class="nowrap"><?= e(date('d M Y · H:i', strtotime($d['waktu_lapor']))) ?></td>
              <td>
                <strong><?= e($d['nama_barang']) ?></strong>
                <div class="muted text-xs">SKU: <?= e($d['id_product']) ?></div>
              </td>
              <td class="mono"><strong class="text-danger">-<?= (int)$d['kuantitas'] ?> Unit</strong></td>
              <td><?= e($d['keterangan_rusak']) ?></td>
              <td><span class="badge neutral"><?= e($d['pelapor'] ?? 'Petugas Gudang') ?></span></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="table-empty">Belum ada catatan laporan barang defect di database.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function stepDownDefect(id) {
    var el = document.getElementById('qty_' + id);
    if (el && !el.disabled) {
        var val = parseInt(el.value, 10) || 0;
        if (val > 0) {
            el.value = val - 1;
        }
    }
}

function stepUpDefect(id, max) {
    var el = document.getElementById('qty_' + id);
    if (el && !el.disabled) {
        var val = parseInt(el.value, 10) || 0;
        if (val < max) {
            el.value = val + 1;
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
