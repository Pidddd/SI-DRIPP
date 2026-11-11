<?php
/**
 * MODUL INVENTARIS — STOK GUDANG HORECA
 * File: modules/inventory/index.php
 * Fungsi: Monitoring ketersediaan stok aktual gudang langsung dari Supabase PostgreSQL.
 * Revisi Dosen Poin 2 & 3:
 *   - Auto-sinkronisasi batch kedaluwarsa (SELECT proses_batch_kedaluwarsa()).
 *   - Tabel utama bersih & rapi: Menampilkan Total Stok per Produk.
 *   - Penyesuaian stok granular ditempatkan di dalam Rincian Batch (Accordion FEFO).
 *   - 1 Tombol utama "Save All Perubahan Stok" untuk menyimpan seluruh perubahan sekaligus.
 */

$root       = '../../';
$page_title = 'Inventaris Stok Gudang';
$page_sub   = 'Monitoring ketersediaan fisik sirup DRIPP, Multibev, & Powder · Gudang MKP Store';
$active     = 'inventory';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// 1. Eksekusi sinkronisasi otomatis batch kedaluwarsa ke sistem gudang (Revisi Poin 3)
$botol_auto_expired = 0;
$res_sync = @pg_query($conn, "SELECT proses_batch_kedaluwarsa()");
if ($res_sync && pg_num_rows($res_sync) > 0) {
    $botol_auto_expired = (int)pg_fetch_result($res_sync, 0, 0);
}

// 2. Ambil daftar stok produk beserta nama kategorinya dari database
$query_stok = "SELECT p.id_product, p.nama_barang, p.stok_aktual, c.nama_category 
               FROM products p 
               LEFT JOIN category c ON p.categoryid_category = c.id_category 
               ORDER BY p.stok_aktual ASC, p.nama_barang ASC";
$res_stok   = pg_query($conn, $query_stok);
$total_item = $res_stok ? pg_num_rows($res_stok) : 0;

// 3. Ambil data rincian batch individual dari tabel restocks (hanya batch yang sisa_stok_batch > 0)
$query_batches = "SELECT id_restock, productsid_product, kode_batch, expired_date, waktu_masuk, sisa_stok_batch, status_batch
                  FROM restocks
                  WHERE sisa_stok_batch > 0
                  ORDER BY expired_date ASC NULLS LAST, waktu_masuk ASC, id_restock ASC";
$res_batches = pg_query($conn, $query_batches);
$batches_by_product = [];
if ($res_batches) {
    while ($b = pg_fetch_assoc($res_batches)) {
        $pid = $b['productsid_product'];
        if (!isset($batches_by_product[$pid])) {
            $batches_by_product[$pid] = [];
        }
        $batches_by_product[$pid][] = $b;
    }
}
$today_ts = strtotime(date('Y-m-d'));
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

<?php if ($botol_auto_expired > 0): ?>
  <div class="callout amber rise">
    <?= icon('alert', 18) ?>
    <div>Sistem mendeteksi <strong><?= (int)$botol_auto_expired ?> botol</strong> kedaluwarsa hari ini dan otomatis memotong stok serta memindahkannya ke catatan defect.</div>
  </div>
<?php endif; ?>

<!-- Bar Aksi Atas -->
<div class="page-actions rise">
  <div>
    <span class="badge info"><i class="dot"></i>Standar Karton MKP: 1 Karton = 12 Botol</span>
    <span class="badge danger ml-1"><i class="dot"></i>Batas Stok Kritis: &lt; 24 Botol</span>
  </div>
  <div class="grow"></div>
  <a href="<?= BASE_URL ?>modules/inventory/stock_in.php" class="btn btn-primary">
    <?= icon('plus', 18) ?> Catat Barang Masuk (Batch Baru)
  </a>
</div>

<!-- Form Pembungkus Seluruh Penyesuaian Stok Gudang (Granular per Batch) -->
<form action="<?= BASE_URL ?>modules/inventory/process/stock_in.php" method="POST">
  <input type="hidden" name="mode" value="bulk_update">

  <!-- Tabel Utama Daftar Stok Gudang HORECA -->
  <div class="card flush rise card-inventory-wrap">
    <div class="card-head pad pb-4">
      <div>
        <h2>Daftar Stok Gudang HORECA &amp; Manajemen Batch FEFO</h2>
        <p>Tabel utama menyajikan total stok per varian. Klik tombol <strong>Kelola Batch</strong> untuk melihat tanggal kedaluwarsa dan menyesuaikan stok per batch kedatangan.</p>
      </div>
      <div class="form-actions">
        <span class="badge neutral"><?= (int)$total_item ?> Varian Terdaftar</span>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Terapkan seluruh penyesuaian stok batch ke dalam sistem gudang?')">
          <?= icon('check', 18) ?> Save All Perubahan Stok
        </button>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th class="col-nama-prod">Nama Produk &amp; SKU</th>
            <th>Kategori</th>
            <th class="col-stok-total">Total Sisa Stok</th>
            <th class="col-status-prod">Status</th>
            <th class="col-batch-action">Rincian Batch FEFO</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($res_stok && $total_item > 0): ?>
            <?php $no = 1; while ($row = pg_fetch_assoc($res_stok)):
                $stok   = (int)$row['stok_aktual'];
                $karton = floor($stok / 12);
                $kritis = $stok < 24;
                $p_id   = $row['id_product'];
                $prod_batches = $batches_by_product[$p_id] ?? [];
                $count_batches = count($prod_batches);
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
                  <div class="muted text-xs">(<?= $karton >= 1 ? $karton . ' Karton' : '&lt; 1 Karton' ?>)</div>
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
                  <button type="button" class="btn-accordion" onclick="toggleBatch('batch_<?= e($p_id) ?>', this)" title="Klik untuk membuka / menutup rincian dan penyesuaian stok per batch">
                    <span class="acc-icon">&#9662;</span>
                    <span><?= $count_batches > 0 ? $count_batches . ' Batch Aktif' : 'Detail Batch (0)' ?></span>
                  </button>
                </td>
              </tr>

              <!-- Sub-Row Accordion Detail & Penyesuaian Granular per Batch -->
              <tr id="batch_<?= e($p_id) ?>" class="accordion-subrow is-hidden">
                <td colspan="6" class="accordion-content">
                  <div class="batch-detail-box">
                    <div class="batch-head">
                      <div class="batch-head-title">
                        <?= icon('clipboard', 16) ?>
                        <span>Rincian Batch Kedatangan &amp; Penyesuaian Stok (FEFO) — <?= e($row['nama_barang']) ?></span>
                      </div>
                      <span class="badge info"><i class="dot"></i>Total Stok Terdaftar: <?= $stok ?> Botol</span>
                    </div>

                    <?php if ($count_batches > 0): ?>
                      <div class="batch-table-wrap">
                        <table class="table-batch">
                          <thead>
                            <tr>
                              <th class="col-no">No</th>
                              <th>Kode Batch</th>
                              <th>Tanggal Masuk</th>
                              <th>Tanggal Kedaluwarsa</th>
                              <th>Sisa Stok Batch</th>
                              <th>Indikator Kedaluwarsa</th>
                              <th class="text-center">Penyesuaian Batch (+ / -)</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php $b_no = 1; foreach ($prod_batches as $b_item):
                              $b_id    = (int)$b_item['id_restock'];
                              $exp_str = $b_item['expired_date'];
                              $sisa_b  = (int)$b_item['sisa_stok_batch'];
                              $tgl_msk = !empty($b_item['waktu_masuk']) ? date('d M Y', strtotime($b_item['waktu_masuk'])) : '-';

                              if (!empty($exp_str)) {
                                  $exp_ts = strtotime($exp_str);
                                  $diff_days = (int)floor(($exp_ts - $today_ts) / 86400);
                                  $exp_formatted = date('d M Y', $exp_ts);

                                  if ($diff_days < 0) {
                                      $badge_class = 'danger';
                                      $status_text = 'Kedaluwarsa (Lewat ' . abs($diff_days) . ' hari)';
                                  } elseif ($diff_days <= 30) {
                                      $badge_class = 'warn';
                                      $status_text = 'Segera Expired (' . $diff_days . ' hari lagi)';
                                  } else {
                                      $badge_class = 'success';
                                      $status_text = 'Aman (' . $diff_days . ' hari lagi)';
                                  }
                              } else {
                                  $exp_formatted = 'Tanpa Expired';
                                  $badge_class   = 'neutral';
                                  $status_text   = 'Non-Expired';
                              }
                            ?>
                              <tr>
                                <td class="mono muted"><?= $b_no++ ?></td>
                                <td><strong class="mono"><?= e($b_item['kode_batch'] ?? 'BATCH-UMUM') ?></strong></td>
                                <td><?= e($tgl_msk) ?></td>
                                <td class="mono"><strong><?= e($exp_formatted) ?></strong></td>
                                <td class="mono"><strong><?= $sisa_b ?> Botol</strong></td>
                                <td>
                                  <span class="badge <?= $badge_class ?>">
                                    <i class="dot"></i><?= e($status_text) ?>
                                  </span>
                                </td>
                                <td class="text-center">
                                  <div class="qty-stepper">
                                    <button type="button" class="btn-step" onclick="stepBatch('delta_b_<?= $b_id ?>', -1, <?= $sisa_b ?>)" title="Kurangi stok batch">&minus;</button>
                                    <input type="number" id="delta_b_<?= $b_id ?>" name="delta_batch[<?= $b_id ?>]" class="input-stepper mono" value="0" step="1">
                                    <button type="button" class="btn-step" onclick="stepBatch('delta_b_<?= $b_id ?>', 1, <?= $sisa_b ?>)" title="Tambah stok batch">&plus;</button>
                                  </div>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <div class="batch-empty">
                        <p>Belum ada batch kedatangan terdaftar untuk varian ini.</p>
                        <div class="mt-2">
                          <span class="muted text-xs">Penyesuaian Stok Cepat:</span>
                          <div class="qty-stepper ml-1">
                            <button type="button" class="btn-step" onclick="stepBatch('delta_p_<?= e($p_id) ?>', -1, <?= $stok ?>)">&minus;</button>
                            <input type="number" id="delta_p_<?= e($p_id) ?>" name="delta_stok[<?= e($p_id) ?>]" class="input-stepper mono" value="0" step="1">
                            <button type="button" class="btn-step" onclick="stepBatch('delta_p_<?= e($p_id) ?>', 1, <?= $stok ?>)">&plus;</button>
                          </div>
                          <span class="muted text-xs ml-2">atau catat batch resmi melalui tombol <strong>Catat Barang Masuk</strong> di atas.</span>
                        </div>
                      </div>
                    <?php endif; ?>
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
        <span class="muted text-xs">Petunjuk: Penyesuaian stok dilakukan secara aman per batch kedatangan (FEFO). Nilai 0 diabaikan, nilai negatif mengurangi stok batch, dan nilai positif menambah stok batch.</span>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Terapkan seluruh penyesuaian stok batch ke dalam sistem gudang?')">
          <?= icon('check', 18) ?> Save All Perubahan Stok
        </button>
      </div>
    <?php endif; ?>
  </div>
</form>

<script>
function toggleBatch(id, btn) {
  var row = document.getElementById(id);
  if (!row) return;
  row.classList.toggle('is-hidden');
  if (btn) {
    btn.classList.toggle('active');
  }
}

function stepBatch(id, step, sisaBatchSekarang) {
  var el = document.getElementById(id);
  if (!el) return;
  var currentVal = parseInt(el.value, 10) || 0;
  var nextVal = currentVal + step;
  if ((sisaBatchSekarang + nextVal) < 0) {
    alert('Pengurangan tidak boleh membuat sisa fisik batch bernilai negatif!');
    return;
  }
  el.value = nextVal;
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
