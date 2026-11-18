<?php
/**
 * UI MODUL INVENTORY - BARANG DEFECT (ACCORDION 2 TINGKAT & BULK INPUT PER BATCH)
 * File: modules/inventory/defect.php
 * Hirarki:
 *   Level 1 (Tabel Utama) -> Kategori Produk HORECA
 *   Level 2 (Detail Kategori) -> Master Produk dalam Kategori
 *   Level 3 (Detail Produk) -> Detail Batch Aktif dari tabel restocks (Stepper [-][qty][+] & Keterangan)
 * Paradigma: PHP Native Prosedural Murni (DILARANG OOP/Class/PDO), Zero Inline CSS.
 */

$root       = '../../';
$page_title = 'Laporan Barang Defect';
$page_sub   = 'Pencatatan massal dan riwayat barang rusak, pecah, atau kedaluwarsa di gudang per batch · MKP Store';
$active     = 'defects';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// 1. Ambil daftar Kategori dari tabel category
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

// 2. Ambil daftar Produk beserta stok aktual dan relasikan ke kategori
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

// 3. Ambil data rincian batch aktif dari tabel restocks (sisa_stok_batch > 0)
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

// 4. Ambil riwayat laporan barang defect menggunakan Multi-table JOIN (defects, products, users)
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
    <strong>SOP Penanganan Barang Rusak / Defect Berbasis Batch (FEFO):</strong><br>
    Pencatatan barang cacat (pecah, bocor, segel rusak, atau kedaluwarsa) dilakukan secara spesifik pada <strong>Detail Batch aktif</strong> dari produk terkait. Sistem akan <strong>otomatis memotong sisa stok batch pada tabel restocks dan stok fisik aktual produk</strong>. Masukkan kuantitas dan keterangan rusak pada batch yang bersangkutan, lalu klik tombol <strong>"Save All Laporan Defect"</strong>.
  </div>
</div>

<!-- 1. Form Pembungkus Seluruh Laporan Defect (Accordion 2 Tingkat) -->
<form action="<?= BASE_URL ?>modules/inventory/process/defects.php" method="POST">
  <div class="card flush rise card-inventory-wrap">
    <div class="card-head pad pb-4">
      <div>
        <h2>Tabel Input Massal Barang Defect per Batch (Save All)</h2>
        <p>Buka kategori dan produk untuk mencatat kerusakan fisik pada batch yang bersangkutan.</p>
      </div>
      <div class="form-actions">
        <span class="badge warn"><i class="dot"></i>Potong Stok Batch &amp; Produk Otomatis</span>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan seluruh laporan barang defect yang telah diinput ke dalam sistem?')">
          <?= icon('check', 18) ?> Save All Laporan Defect
        </button>
      </div>
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
                              <th class="col-batch-action">Rincian Batch Defect</th>
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
                                  <button type="button" class="btn-accordion-sub" onclick="toggleAccordion('sub-prod-<?= e($p_id) ?>', this)" title="Buka/tutup rincian batch produk ini">
                                    <span class="acc-icon">&#9662;</span>
                                    <span>Detail Batch (<?= $count_b ?>)</span>
                                  </button>
                                </td>
                              </tr>

                              <!-- SUB-ROW LEVEL 3: DETAIL BATCH AKTIF DENGAN INPUT DEFECT -->
                              <tr id="sub-prod-<?= e($p_id) ?>" class="accordion-nested-subrow is-hidden">
                                <td colspan="5" class="nested-content">
                                  <div class="nested-batch-box">
                                    <div class="batch-head">
                                      <div class="batch-head-title">
                                        <?= icon('alert', 14) ?>
                                        <span>Rincian Batch Aktif &amp; Input Defect — <?= e($prod['nama_barang']) ?></span>
                                      </div>
                                      <div class="batch-head-tags">
                                        <span class="badge info"><i class="dot"></i>Total Stok: <?= $stok_p ?> Botol</span>
                                        <span class="badge neutral"><i class="dot"></i><?= $count_b ?> Batch Aktif</span>
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
                                              <th class="text-center">Jumlah Rusak</th>
                                              <th>Keterangan / Kronologi Kerusakan</th>
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
                                                    <button type="button" class="btn-step" onclick="stepDownDefectBatch('<?= $b_id ?>')">&minus;</button>
                                                    <input type="number" id="qty_b_<?= $b_id ?>" name="kuantitas_batch[<?= $b_id ?>]" class="input-step mono" value="0" min="0" max="<?= $sisa_b ?>">
                                                    <button type="button" class="btn-step" onclick="stepUpDefectBatch('<?= $b_id ?>', <?= $sisa_b ?>)">&plus;</button>
                                                  </div>
                                                </td>
                                                <td>
                                                  <input type="text" name="keterangan_batch[<?= $b_id ?>]" class="input" placeholder="Contoh: Pecah saat bongkar muat / bocor / kedaluwarsa" maxlength="255">
                                                </td>
                                              </tr>
                                            <?php endforeach; ?>
                                          </tbody>
                                        </table>
                                      </div>
                                    <?php else: ?>
                                      <div class="batch-empty-box">
                                        <div class="batch-empty-status">
                                          <span class="badge danger"><i class="dot"></i>Belum ada batch aktif</span>
                                        </div>
                                        <div class="batch-empty-body">
                                          <h4 class="batch-empty-headline">Belum ada batch aktif untuk varian produk ini.</h4>
                                          <p class="batch-empty-desc">
                                            Sesuai SOP Gudang FEFO, pelaporan defect dilakukan pada batch yang terdaftar. Produk ini saat ini belum memiliki batch aktif dengan sisa stok &gt; 0 di tabel restocks.
                                          </p>
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
        <span class="muted text-xs">
          <?= icon('alert', 14) ?> Buka kategori dan produk terkait, lalu masukkan kuantitas rusak pada batch. Hanya baris batch dengan jumlah rusak &gt; 0 yang akan dicatat dan memotong sisa stok batch serta stok aktual produk.
        </span>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan seluruh laporan barang defect yang telah diinput ke dalam sistem?')">
          <?= icon('check', 18) ?> Save All Laporan Defect
        </button>
      </div>
    <?php endif; ?>
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
function toggleAccordion(id, btn) {
  var row = document.getElementById(id);
  if (!row) return;
  row.classList.toggle('is-hidden');
  if (btn) {
    btn.classList.toggle('active');
  }
}

function stepDownDefectBatch(id) {
  var el = document.getElementById('qty_b_' + id);
  if (el) {
    var val = parseInt(el.value, 10) || 0;
    if (val > 0) {
      el.value = val - 1;
    }
  }
}

function stepUpDefectBatch(id, max) {
  var el = document.getElementById('qty_b_' + id);
  if (el) {
    var val = parseInt(el.value, 10) || 0;
    if (val < max) {
      el.value = val + 1;
    }
  }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
