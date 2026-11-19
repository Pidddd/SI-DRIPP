<?php
/**
 * MODUL INVENTARIS — STOK GUDANG HORECA (ACCORDION 2 TINGKAT)
 * File: modules/inventory/index.php
 * Fungsi: Monitoring ketersediaan stok fisik gudang berbasis Hirarki 2 Tingkat:
 *         Level 1 (Tabel Utama) -> Kategori Produk HORECA
 *         Level 2 (Sub-Row Kategori) -> Master Produk dalam Kategori
 *         Level 3 (Sub-Row Produk) -> Rincian Batch Kedatangan FEFO & Stepper Penyesuaian
 * Aturan: Murni PHP Native Prosedural (pg_query_params/pg_query), Zero Inline CSS.
 */

$root       = '../../';
$page_title = 'Inventaris Stok Gudang';
$page_sub   = 'Monitoring ketersediaan fisik sirup DRIPP, Multibev, & Powder · Gudang MKP Store';
$active     = 'inventory';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// 1. Eksekusi sinkronisasi otomatis batch kedaluwarsa ke sistem gudang
$botol_auto_expired = 0;
$res_sync = @pg_query($conn, "SELECT proses_batch_kedaluwarsa()");
if ($res_sync && pg_num_rows($res_sync) > 0) {
    $botol_auto_expired = (int)pg_fetch_result($res_sync, 0, 0);
}

// 2. Ambil daftar seluruh Kategori dari tabel category
$categories = [];
$res_cat = pg_query($conn, "SELECT id_category, nama_category FROM category ORDER BY nama_category ASC");
if ($res_cat) {
    while ($row_c = pg_fetch_assoc($res_cat)) {
        $cid = (int)$row_c['id_category'];
        $categories[$cid] = [
            'id'           => $cid,
            'nama'         => $row_c['nama_category'],
            'total_varian' => 0,
            'total_stok'   => 0,
            'products'     => []
        ];
    }
}

// 3. Ambil daftar Produk beserta stok aktual dan relasikan ke kategori
$query_prod = "SELECT p.id_product, p.nama_barang, p.stok_aktual, p.categoryid_category, c.nama_category
               FROM products p
               LEFT JOIN category c ON p.categoryid_category = c.id_category
               ORDER BY p.nama_barang ASC";
$res_prod = pg_query($conn, $query_prod);
$total_semua_produk = 0;
$total_semua_stok   = 0;

if ($res_prod) {
    while ($p = pg_fetch_assoc($res_prod)) {
        $cid = !empty($p['categoryid_category']) ? (int)$p['categoryid_category'] : 0;
        if (!isset($categories[$cid])) {
            $categories[$cid] = [
                'id'           => $cid,
                'nama'         => $p['nama_category'] ?? 'Tanpa Kategori',
                'total_varian' => 0,
                'total_stok'   => 0,
                'products'     => []
            ];
        }
        $categories[$cid]['products'][] = $p;
        $categories[$cid]['total_varian'] += 1;
        $categories[$cid]['total_stok']   += (int)$p['stok_aktual'];

        $total_semua_produk++;
        $total_semua_stok += (int)$p['stok_aktual'];
    }
}

// 4. Ambil data rincian batch individual dari tabel restocks (sisa_stok_batch > 0)
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

<!-- Form Pembungkus Seluruh Penyesuaian Stok Gudang (Nested Accordion 2 Tingkat) -->
<form action="<?= BASE_URL ?>modules/inventory/process/stock_in.php" method="POST">
  <input type="hidden" name="mode" value="bulk_update">

  <!-- Tabel Utama: Level 1 (Kategori Produk HORECA) -->
  <div class="card flush rise card-inventory-wrap">
    <div class="card-head pad pb-4">
      <div>
        <h2>Inventaris Stok Gudang (Kategori &amp; Batch FEFO)</h2>
        <p>Buka kategori untuk mengelola produk, lalu buka produk untuk memantau tanggal kedaluwarsa batch dan menyesuaikan stok.</p>
      </div>
      <span class="badge neutral"><?= count($categories) ?> Kategori · <?= $total_semua_produk ?> Varian (<?= $total_semua_stok ?> Botol)</span>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Nama Kategori Produk</th>
            <th>Jumlah Varian</th>
            <th class="col-stok-total">Total Sisa Stok</th>
            <th class="col-status-prod">Status Kategori</th>
            <th class="col-batch-action">Aksi Produk</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($categories) > 0): ?>
            <?php $no_cat = 1; foreach ($categories as $cid => $cat):
                $cat_stok   = (int)$cat['total_stok'];
                $cat_varian = (int)$cat['total_varian'];
                $cat_karton = floor($cat_stok / 12);
                $cat_kritis = $cat_stok < ($cat_varian * 12) || $cat_stok < 24;
            ?>
              <!-- BARIS LEVEL 1: KATEGORI PRODUK -->
              <tr id="tr-cat-<?= $cid ?>">
                <td class="mono muted"><?= $no_cat++ ?></td>
                <td>
                  <strong><?= e($cat['nama']) ?></strong>
                  <div class="muted text-xs">ID Kategori: #<?= $cid ?></div>
                </td>
                <td>
                  <span class="badge info"><?= $cat_varian ?> Varian Produk</span>
                </td>
                <td class="mono">
                  <strong class="<?= $cat_kritis ? 'text-danger' : '' ?>"><?= $cat_stok ?> Botol</strong>
                  <div class="muted text-xs">(<?= $cat_karton >= 1 ? $cat_karton . ' Karton' : '&lt; 1 Karton' ?>)</div>
                </td>
                <td>
                  <?php if ($cat_stok <= 0): ?>
                    <span class="badge danger"><i class="dot"></i>Habis</span>
                  <?php elseif ($cat_kritis): ?>
                    <span class="badge danger"><i class="dot"></i>Kritis</span>
                  <?php else: ?>
                    <span class="badge success"><i class="dot"></i>Aman</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <button type="button" class="btn-accordion" onclick="toggleAccordion('sub-cat-<?= $cid ?>', this)" title="Buka/tutup daftar produk dalam kategori ini">
                    <span class="acc-icon">&#9662;</span>
                    <span>Lihat Produk (<?= $cat_varian ?>)</span>
                  </button>
                </td>
              </tr>

              <!-- SUB-ROW LEVEL 2: DAFTAR MASTER PRODUK DALAM KATEGORI -->
              <tr id="sub-cat-<?= $cid ?>" class="accordion-subrow is-hidden">
                <td colspan="6" class="accordion-content">
                  <div class="category-detail-box">
                    <div class="category-head">
                      <div class="category-head-title">
                        <?= icon('clipboard', 16) ?>
                        <span>Daftar Master Produk — Kategori <?= e($cat['nama']) ?></span>
                      </div>
                      <span class="badge neutral"><?= $cat_varian ?> Varian Terdaftar di Kategori Ini</span>
                    </div>

                    <?php if ($cat_varian > 0): ?>
                      <div class="table-nested-wrap">
                        <table class="table-nested-products">
                          <thead>
                            <tr>
                              <th class="col-no">No</th>
                              <th>Nama Produk &amp; SKU</th>
                              <th class="col-stok-total">Sisa Stok Produk</th>
                              <th class="col-status-prod">Status</th>
                              <th class="col-batch-action">Rincian Batch FEFO</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php $no_p = 1; foreach ($cat['products'] as $prod):
                                $p_id          = $prod['id_product'];
                                $stok_p        = (int)$prod['stok_aktual'];
                                $karton_p      = floor($stok_p / 12);
                                $kritis_p      = $stok_p < 24;
                                $prod_batches  = $batches_by_product[$p_id] ?? [];
                                $count_b       = count($prod_batches);
                            ?>
                              <!-- BARIS LEVEL 2: MASTER PRODUK -->
                              <tr>
                                <td class="mono muted"><?= $no_p++ ?></td>
                                <td>
                                  <strong><?= e($prod['nama_barang']) ?></strong>
                                  <div class="muted text-xs">SKU: <?= e($p_id) ?></div>
                                </td>
                                <td class="mono">
                                  <strong class="<?= $kritis_p ? 'text-danger' : '' ?>"><?= $stok_p ?> Botol</strong>
                                  <div class="muted text-xs">(<?= $karton_p >= 1 ? $karton_p . ' Karton' : '&lt; 1 Karton' ?>)</div>
                                </td>
                                <td>
                                  <?php if ($stok_p <= 0): ?>
                                    <span class="badge danger"><i class="dot"></i>Habis</span>
                                  <?php elseif ($kritis_p): ?>
                                    <span class="badge danger"><i class="dot"></i>Kritis</span>
                                  <?php else: ?>
                                    <span class="badge success"><i class="dot"></i>Aman</span>
                                  <?php endif; ?>
                                </td>
                                <td class="text-center">
                                  <button type="button" class="btn-accordion-sub" onclick="toggleAccordion('sub-prod-<?= e($p_id) ?>', this)" title="Buka/tutup rincian batch kedaluwarsa produk ini">
                                    <span class="acc-icon">&#9662;</span>
                                    <span>Detail Batch (<?= $count_b ?>)</span>
                                  </button>
                                </td>
                              </tr>

                              <!-- SUB-ROW LEVEL 3: RINCIAN BATCH FEFO & STEPPER PENYESUAIAN -->
                              <tr id="sub-prod-<?= e($p_id) ?>" class="accordion-nested-subrow is-hidden">
                                <td colspan="5" class="nested-content">
                                  <div class="nested-batch-box">
                                    <?php
                                      $batch_terdekat_hari = null;
                                      $ada_batch_expired   = false;
                                      $ada_batch_segera    = false;
                                      if ($count_b > 0) {
                                          foreach ($prod_batches as $b_check) {
                                              if (!empty($b_check['expired_date'])) {
                                                  $c_exp_ts = strtotime($b_check['expired_date']);
                                                  $c_diff   = (int)floor(($c_exp_ts - $today_ts) / 86400);
                                                  if ($c_diff < 0) {
                                                      $ada_batch_expired = true;
                                                  } elseif ($c_diff <= 30) {
                                                      $ada_batch_segera = true;
                                                  }
                                                  if ($batch_terdekat_hari === null || $c_diff < $batch_terdekat_hari) {
                                                      $batch_terdekat_hari = $c_diff;
                                                  }
                                              }
                                          }
                                      }
                                    ?>
                                    <div class="batch-head">
                                      <div class="batch-head-title">
                                        <?= icon('clipboard', 14) ?>
                                        <span>Rincian Batch Kedatangan (FEFO) — <?= e($prod['nama_barang']) ?></span>
                                      </div>
                                      <div class="batch-head-tags">
                                        <?php if ($count_b > 0): ?>
                                          <?php if ($ada_batch_expired): ?>
                                            <span class="badge danger"><i class="dot"></i>Terdapat Batch Kedaluwarsa!</span>
                                          <?php elseif ($ada_batch_segera): ?>
                                            <span class="badge warn"><i class="dot"></i>Terdekat Expired: <?= $batch_terdekat_hari ?> Hari</span>
                                          <?php else: ?>
                                            <span class="badge success"><i class="dot"></i>Masa Simpan Aman</span>
                                          <?php endif; ?>
                                          <span class="badge info"><i class="dot"></i>Total Stok: <?= $stok_p ?> Botol</span>
                                          <a href="<?= BASE_URL ?>modules/inventory/stock_in.php?category_id=<?= (int)$cid ?>&product_id=<?= e($p_id) ?>" class="btn-sub-add" title="Tambah batch kedatangan baru untuk varian ini">
                                            <?= icon('plus', 13) ?> Batch Baru
                                          </a>
                                        <?php else: ?>
                                          <span class="badge danger"><i class="dot"></i>Stok Kosong (0 Botol)</span>
                                          <span class="badge warn"><i class="dot"></i>Wajib Input Batch &amp; Expired</span>
                                        <?php endif; ?>
                                      </div>
                                    </div>

                                    <?php if ($count_b > 0): ?>
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
                                            <?php $no_b = 1; foreach ($prod_batches as $b_item):
                                                $b_id    = (int)$b_item['id_restock'];
                                                $exp_str = $b_item['expired_date'];
                                                $sisa_b  = (int)$b_item['sisa_stok_batch'];
                                                $tgl_msk = !empty($b_item['waktu_masuk']) ? date('d M Y', strtotime($b_item['waktu_masuk'])) : '-';

                                                if (!empty($exp_str)) {
                                                    $exp_ts    = strtotime($exp_str);
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
                                                <td class="mono muted"><?= $no_b++ ?></td>
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
                                      <div class="batch-empty-box">
                                        <div class="batch-empty-status">
                                          <span class="badge danger"><i class="dot"></i>Stok Habis / Belum Ada Batch</span>
                                          <span class="badge warn"><i class="dot"></i>Status Kedaluwarsa: Belum Terdata (Wajib FEFO)</span>
                                        </div>
                                        <div class="batch-empty-body">
                                          <h4 class="batch-empty-headline">Tidak ada rincian batch kedatangan aktif untuk varian ini.</h4>
                                          <p class="batch-empty-desc">
                                            Sesuai Standar Operasional Gudang <strong>FEFO (First Expired, First Out)</strong> MKP Store, setiap penambahan stok barang <strong>wajib tercatat resmi bersama Tanggal Kedaluwarsa dan Kode Batch pabrik</strong>. Penyesuaian stok manual/langsung ditiadakan agar tidak ada barang yang beredar tanpa tanggal kedaluwarsa.
                                          </p>
                                        </div>
                                        <div class="batch-empty-actions">
                                          <a href="<?= BASE_URL ?>modules/inventory/stock_in.php?category_id=<?= (int)$cid ?>&product_id=<?= e($p_id) ?>" class="btn btn-primary btn-sm">
                                            <?= icon('plus', 16) ?> Catat Batch Kedatangan Baru
                                          </a>
                                        </div>
                                      </div>
                                    <?php endif; ?>
                                  </div>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <div class="batch-empty">Belum ada master produk yang terdaftar di kategori ini.</div>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="table-empty">Belum ada kategori produk terdaftar di gudang.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (count($categories) > 0): ?>
      <div class="card-foot-bar">
        <span class="muted text-xs">Petunjuk: Buka kategori, pilih produk, dan sesuaikan kuantitas pada masing-masing batch. Nilai 0 diabaikan, nilai negatif mengurangi stok batch, dan nilai positif menambah stok batch.</span>
        <button type="submit" class="btn btn-primary" data-confirm="Terapkan seluruh penyesuaian stok batch ke dalam sistem gudang?">
          <?= icon('check', 18) ?> Save All Perubahan Stok
        </button>
      </div>
    <?php endif; ?>
  </div>
</form>

<script>
function toggleAccordion(id, btn) {
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
