<?php
/**
 * MODUL INVENTARIS — STOCK OPNAME GUDANG (Accordion 1 Tingkat per Kategori)
 * File: modules/inventory/opname.php
 * Fungsi: Audit perbandingan Stok Sistem vs Stok Fisik gudang MKP Store secara massal.
 * Arsitektur: PHP Native Prosedural Murni (pg_query / pg_query_params). Zero Inline CSS.
 * Revisi Dosen Poin 2:
 *   - Accordion 1 Tingkat: Level 1 = Kategori, Level 2 = Master Produk per Kategori.
 *   - 1 form pembungkus seluruh tabel + 1 tombol Save All Stock Opname.
 *   - Stepper [-] [stok_fisik[id_product]] [+] dan input Catatan Validasi per produk.
 */

$root       = '../../';
$page_title = 'Stock Opname Gudang';
$page_sub   = 'Audit kesesuaian stok sistem terhadap perhitungan fisik rak gudang · MKP Store';
$active     = 'stock_opname';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// 1. Ambil semua kategori (akan diisi produk di bawah)
$q_categories = "SELECT id_category, nama_category FROM category ORDER BY nama_category ASC";
$res_cat_raw  = pg_query($conn, $q_categories);
$categories   = [];
if ($res_cat_raw) {
    while ($c = pg_fetch_assoc($res_cat_raw)) {
        $categories[$c['id_category']] = [
            'id'           => (int)$c['id_category'],
            'nama'         => $c['nama_category'],
            'total_varian' => 0,
            'total_stok'   => 0,
            'products'     => [],
        ];
    }
}

// 2. Ambil semua produk beserta stok sistem dan relasikan ke kategori
$q_products = "SELECT p.id_product, p.nama_barang, p.stok_aktual, p.categoryid_category, c.nama_category
               FROM products p
               LEFT JOIN category c ON p.categoryid_category = c.id_category
               ORDER BY p.nama_barang ASC";
$res_products = pg_query($conn, $q_products);
$total_all_produk = 0;

if ($res_products) {
    while ($p = pg_fetch_assoc($res_products)) {
        $cid = !empty($p['categoryid_category']) ? (int)$p['categoryid_category'] : 0;
        // Tangani produk tanpa kategori terdaftar
        if (!isset($categories[$cid])) {
            $categories[$cid] = [
                'id'           => $cid,
                'nama'         => $p['nama_category'] ?? 'Tanpa Kategori',
                'total_varian' => 0,
                'total_stok'   => 0,
                'products'     => [],
            ];
        }
        $categories[$cid]['products'][]     = $p;
        $categories[$cid]['total_varian']  += 1;
        $categories[$cid]['total_stok']    += (int)$p['stok_aktual'];
        $total_all_produk++;
    }
}

// 3. Ambil riwayat stock opname terakhir (15 entri)
$q_history = "SELECT so.id_opname, so.waktu_opname, so.stok_sistem, so.stok_fisik, so.selisih, so.status_validasi,
                     p.id_product, p.nama_barang, u.nama AS petugas
              FROM stock_opname so
              JOIN products p ON so.productsid_product = p.id_product
              LEFT JOIN users u ON so.userid_user = u.id_user
              ORDER BY so.waktu_opname DESC, so.id_opname DESC
              LIMIT 15";
$res_history = pg_query($conn, $q_history);
?>

<div class="inv-page-wrap">
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
    <strong>Panduan Audit Stock Opname:</strong> Buka setiap kategori untuk melihat daftar produk. Masukkan hasil hitung <em>Stok Fisik</em> aktual di rak gudang menggunakan tombol <strong>[-] / [+]</strong> atau ketik langsung, lalu tekan <strong>Save All Stock Opname</strong> untuk menyelaraskan seluruh stok sistem.
  </div>
</div>

<!-- 1 Form Pembungkus Seluruh Tabel Stock Opname (Revisi Dosen Poin 2) -->
<form action="<?= BASE_URL ?>modules/inventory/process/stock_opname.php" method="POST">
  <div class="card flush rise card-inventory-wrap inv-section-gap">
    <div class="card-head pad pb-4">
      <div>
        <h2>Audit Stock Opname &mdash; Per Kategori Produk</h2>
        <p>Pemeriksaan fisik gudang HORECA oleh Staf Logistik pada <?= e(tgl_id()) ?></p>
      </div>
      <div class="form-actions">
        <span class="badge neutral"><i class="dot"></i><?= count($categories) ?> Kategori &middot; <?= $total_all_produk ?> Varian</span>
        <span class="badge info"><i class="dot"></i>Bulk Audit</span>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Nama Kategori</th>
            <th>Jumlah Varian</th>
            <th>Total Stok Sistem</th>
            <th class="text-center">Audit Produk</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($categories)): ?>
            <?php $no_cat = 1; foreach ($categories as $cid => $cat):
                $cat_varian = $cat['total_varian'];
                $cat_stok   = $cat['total_stok'];
                $kritis_cat = $cat_stok < 24;
            ?>
              <!-- BARIS LEVEL 1: KATEGORI PRODUK -->
              <tr>
                <td class="mono muted"><?= $no_cat++ ?></td>
                <td>
                  <strong><?= e($cat['nama']) ?></strong>
                  <div class="muted text-xs">ID Kategori: #<?= $cid ?></div>
                </td>
                <td>
                  <span class="badge neutral"><?= $cat_varian ?> Varian Produk</span>
                </td>
                <td class="mono">
                  <strong class="<?= $kritis_cat ? 'text-danger' : '' ?>">
                    <?= $cat_stok ?> Botol
                  </strong>
                  <div class="muted text-xs">(<?= floor($cat_stok / 12) >= 1 ? floor($cat_stok / 12) . ' Karton' : '< 1 Karton' ?>)</div>
                </td>
                <td class="text-center">
                  <?php if ($cat_varian > 0): ?>
                    <button type="button" class="btn-accordion" onclick="toggleOpnameAccordion('opname-cat-<?= (int)$cid ?>', this)" title="Buka/tutup daftar produk untuk audit">
                      <span class="acc-icon">&#9662;</span>
                      <span>Audit Produk (<?= $cat_varian ?>)</span>
                    </button>
                  <?php else: ?>
                    <span class="muted text-xs">Kosong</span>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- SUB-ROW: DAFTAR MASTER PRODUK UNTUK DIAUDIT -->
              <tr id="opname-cat-<?= (int)$cid ?>" class="accordion-subrow is-hidden">
                <td colspan="5" class="accordion-content">
                  <div class="category-detail-box">
                    <div class="category-head">
                      <div class="category-head-title">
                        <?= icon('clipboard', 16) ?>
                        <span>Daftar Produk &mdash; Kategori <?= e($cat['nama']) ?></span>
                      </div>
                      <div class="form-actions">
                        <span class="badge neutral"><?= $cat_varian ?> Varian Terdaftar</span>
                        <span class="badge info"><i class="dot"></i>Total Stok Sistem: <?= $cat_stok ?> Botol</span>
                      </div>
                    </div>

                    <div class="table-nested-wrap">
                      <table class="table-nested-products">
                        <thead>
                          <tr>
                            <th class="col-no">No</th>
                            <th>Nama Produk &amp; SKU</th>
                            <th>Stok Sistem</th>
                            <th class="text-center">Input Stok Fisik</th>
                            <th>Catatan Validasi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no_p = 1; foreach ($cat['products'] as $prod):
                              $p_id     = $prod['id_product'];
                              $stok_sys = (int)$prod['stok_aktual'];
                              $kritis_p = $stok_sys < 24;
                          ?>
                            <tr>
                              <td class="mono muted"><?= $no_p++ ?></td>
                              <td>
                                <strong><?= e($prod['nama_barang']) ?></strong>
                                <div class="muted text-xs">SKU: <?= e($p_id) ?></div>
                              </td>
                              <td class="mono">
                                <strong class="<?= $kritis_p ? 'text-danger' : '' ?>">
                                  <?= $stok_sys ?> Botol
                                </strong>
                                <?php if ($stok_sys <= 0): ?>
                                  <div class="muted text-xs"><span class="badge danger badge-xs"><i class="dot"></i>Habis</span></div>
                                <?php elseif ($kritis_p): ?>
                                  <div class="muted text-xs"><span class="badge danger badge-xs"><i class="dot"></i>Kritis</span></div>
                                <?php endif; ?>
                              </td>
                              <td class="text-center">
                                <div class="qty-stepper">
                                  <button type="button" class="btn-step" onclick="stepOpname('fisik_<?= e($p_id) ?>', -1)" title="Kurangi stok fisik">&minus;</button>
                                  <input type="number" id="fisik_<?= e($p_id) ?>" name="stok_fisik[<?= e($p_id) ?>]" class="input-stepper mono" min="0" value="<?= $stok_sys ?>" required>
                                  <button type="button" class="btn-step" onclick="stepOpname('fisik_<?= e($p_id) ?>', 1)" title="Tambah stok fisik">&plus;</button>
                                </div>
                              </td>
                              <td>
                                <input type="text" name="status_validasi[<?= e($p_id) ?>]" class="input table-input-compact" value="Audit Rutin Gudang" placeholder="Catatan opname">
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="table-empty">Belum ada kategori produk terdaftar untuk diaudit.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($categories) && $total_all_produk > 0): ?>
      <div class="card-foot-bar">
        <span class="muted text-xs">Produk dengan stok fisik yang sama persis dengan stok sistem otomatis diabaikan agar riwayat audit tetap bersih.</span>
        <button type="submit" class="btn btn-primary" data-confirm="Terapkan seluruh penyesuaian stock opname fisik ini ke dalam sistem?">
          <?= icon('check', 18) ?> Save All Stock Opname
        </button>
      </div>
    <?php endif; ?>
  </div>
</form>

<script>
function toggleOpnameAccordion(id, btn) {
  var row = document.getElementById(id);
  if (!row) return;
  row.classList.toggle('is-hidden');
  if (btn) btn.classList.toggle('active');
}

function stepOpname(id, step) {
  var el = document.getElementById(id);
  if (!el) return;
  var currentVal = parseInt(el.value, 10);
  if (isNaN(currentVal)) currentVal = 0;
  var nextVal = currentVal + step;
  if (nextVal < 0) nextVal = 0;
  el.value = nextVal;
}
</script>

<!-- Riwayat Audit Stock Opname (Dipertahankan) -->
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
                <div class="text-xs muted">SKU: <?= e($h['id_product']) ?></div>
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
</div><!-- /.inv-page-wrap -->

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
