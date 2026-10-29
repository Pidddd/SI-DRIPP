<?php
/**
 * UI MODUL INVENTORY - BARANG DEFECT
 * File: modules/inventory/defect.php
 * Fungsi: Form pelaporan dan tabel riwayat barang rusak/cacat yang otomatis memotong stok aktual.
 */

$root       = '../../';
$page_title = 'Laporan Barang Defect';
$page_sub   = 'Pencatatan dan riwayat barang rusak, pecah, atau kedaluwarsa di gudang · MKP Store';
$active     = 'defects';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil daftar produk yang stoknya > 0 untuk pilihan dropdown form defect
$q_products = "SELECT id_product, nama_barang, stok_aktual FROM products ORDER BY nama_barang ASC";
$res_products = pg_query($conn, $q_products);

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

<!-- Form Input Laporan Barang Defect -->
<div class="card mb-5 rise">
  <div class="card-head">
    <div>
      <h2>Catat Laporan Barang Defect / Rusak Baru</h2>
      <p>Pencatatan barang rusak akan otomatis mengurangi stok aktual produk di gudang secara atomik</p>
    </div>
    <span class="badge warn"><i class="dot"></i>Potong Stok Otomatis</span>
  </div>

  <form action="<?= BASE_URL ?>modules/inventory/process/defects.php" method="POST">
    <div class="form-row">
      <div class="field">
        <label for="id_product">Pilih Produk yang Rusak</label>
        <select name="id_product" id="id_product" class="select" required>
          <option value="">-- Pilih Produk --</option>
          <?php if ($res_products): ?>
            <?php while ($p = pg_fetch_assoc($res_products)): ?>
              <option value="<?= e($p['id_product']) ?>">
                <?= e($p['nama_barang']) ?> (Stok tersedia: <?= (int)$p['stok_aktual'] ?>)
              </option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="field">
        <label for="kuantitas">Jumlah Rusak (Botol / Satuan)</label>
        <input type="number" name="kuantitas" id="kuantitas" class="input mono" min="1" placeholder="Contoh: 2" required>
      </div>
    </div>

    <div class="field">
      <label for="keterangan_rusak">Keterangan / Kronologi Kerusakan</label>
      <input type="text" name="keterangan_rusak" id="keterangan_rusak" class="input" maxlength="255" placeholder="Contoh: Botol retak pada bagian leher saat bongkar muat ekspedisi" required>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <?= icon('plus', 18) ?> Simpan Laporan Defect &amp; Potong Stok
      </button>
    </div>
  </form>
</div>

<!-- Tabel Riwayat Laporan Barang Defect -->
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

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
