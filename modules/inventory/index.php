<?php
/**
 * MODUL INVENTARIS — STOK GUDANG HORECA
 * File: modules/inventory/index.php
 * Fungsi: Monitoring ketersediaan stok aktual gudang langsung dari Supabase PostgreSQL.
 * Revisi Dosen Poin 2:
 *   - Tabel stok dibungkus 1 form bulk update ke process/stock_in.php (mode=bulk_update).
 *   - Kolom penyesuaian stok massal [-] [input delta_stok] [+] di setiap baris produk.
 *   - 1 Tombol utama "Save All Perubahan Stok".
 */

$root       = '../../';
$page_title = 'Inventaris Stok Gudang';
$page_sub   = 'Monitoring ketersediaan fisik sirup DRIPP, Multibev, & Powder · Gudang MKP Store';
$active     = 'inventory';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil daftar stok produk beserta nama kategorinya dari database
$query_stok = "SELECT p.id_product, p.nama_barang, p.stok_aktual, c.nama_category 
               FROM products p 
               LEFT JOIN category c ON p.categoryid_category = c.id_category 
               ORDER BY p.stok_aktual ASC, p.nama_barang ASC";
$res_stok   = pg_query($conn, $query_stok);
$total_item = $res_stok ? pg_num_rows($res_stok) : 0;
?>

<!-- Notifikasi Flash Message -->
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

<!-- Bar Aksi Atas -->
<div class="page-actions rise">
  <div>
    <span class="badge info"><i class="dot"></i>Standar Karton MKP: 1 Karton = 12 Botol</span>
    <span class="badge danger ml-1"><i class="dot"></i>Batas Stok Kritis: &lt; 24 Botol</span>
  </div>
  <div class="grow"></div>
  <a href="<?= BASE_URL ?>modules/inventory/stock_in.php" class="btn btn-primary">
    <?= icon('plus', 18) ?> Catat Barang Masuk
  </a>
</div>

<!-- Form Bulk Update Stok Gudang HORECA -->
<form action="<?= BASE_URL ?>modules/inventory/process/stock_in.php" method="POST">
  <input type="hidden" name="mode" value="bulk_update">

  <!-- Tabel Daftar Stok Gudang HORECA -->
  <div class="card flush rise">
    <div class="card-head pad pb-4">
      <div>
        <h2>Daftar Stok Gudang HORECA</h2>
        <p>Gunakan kontrol [-] dan [+] atau ketik langsung angka penyesuaian stok, lalu klik Save All Perubahan Stok</p>
      </div>
      <div class="form-actions">
        <span class="badge neutral"><?= (int)$total_item ?> Varian Terdaftar</span>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Terapkan seluruh perubahan stok massal ini?')">
          <?= icon('check', 18) ?> Save All Perubahan Stok
        </button>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Sisa Stok</th>
            <th>Status</th>
            <th class="text-center">Penyesuaian Stok Massal (+ / -)</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($res_stok && $total_item > 0): ?>
            <?php $no = 1; while ($row = pg_fetch_assoc($res_stok)):
                $stok   = (int)$row['stok_aktual'];
                $karton = floor($stok / 12);
                $kritis = $stok < 24;
                $p_id   = $row['id_product'];
            ?>
              <tr>
                <td class="mono muted"><?= $no++ ?></td>
                <td>
                  <strong><?= e($row['nama_barang']) ?></strong>
                  <div class="muted text-xs">SKU: <?= e($p_id) ?></div>
                </td>
                <td><span class="badge info"><?= e($row['nama_category'] ?? 'Tanpa Kategori') ?></span></td>
                <td class="mono">
                  <strong class="<?= $kritis ? 'text-danger' : '' ?>"><?= $stok ?> Botol</strong>
                  <span class="muted text-xs">(<?= $karton >= 1 ? $karton . ' Karton' : '&lt; 1 Karton' ?>)</span>
                </td>
                <td>
                  <?php if ($stok <= 0): ?>
                    <span class="badge danger"><i class="dot"></i>Habis</span>
                  <?php elseif ($kritis): ?>
                    <span class="badge danger"><i class="dot"></i>Kritis</span>
                  <?php else: ?>
                    <span class="badge success"><i class="dot"></i>Aman</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="qty-stepper">
                    <button type="button" class="btn-step" onclick="stepDelta('delta_<?= e($p_id) ?>', -1, <?= $stok ?>)" title="Kurangi stok">&minus;</button>
                    <input type="number" id="delta_<?= e($p_id) ?>" name="delta_stok[<?= e($p_id) ?>]" class="input-stepper mono" value="0" step="1">
                    <button type="button" class="btn-step" onclick="stepDelta('delta_<?= e($p_id) ?>', 1, <?= $stok ?>)" title="Tambah stok">&plus;</button>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="table-empty">Belum ada produk yang terdaftar di gudang.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($res_stok && $total_item > 0): ?>
      <div class="card-foot-bar">
        <span class="muted text-xs">Nilai 0 berarti tidak diubah. Nilai positif menambah stok, dan nilai negatif mengurangi stok aktual.</span>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Terapkan seluruh perubahan stok massal ini?')">
          <?= icon('check', 18) ?> Save All Perubahan Stok
        </button>
      </div>
    <?php endif; ?>
  </div>
</form>

<script>
function stepDelta(id, step, stokSekarang) {
  var el = document.getElementById(id);
  if (!el) return;
  var val = parseInt(el.value, 10) || 0;
  var nextVal = val + step;
  if ((stokSekarang + nextVal) < 0) {
    alert('Pengurangan stok tidak boleh membuat stok akhir bernilai negatif!');
    return;
  }
  el.value = nextVal;
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
