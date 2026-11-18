<?php
/**
 * UI MODUL INVENTARIS — PENERIMAAN BARANG MASUK (RESTOCK BATCH BARU)
 * File: modules/inventory/stock_in.php
 * Fungsi: Form input penambahan stok barang masuk dari vendor/pabrik ke gudang.
 * Revisi Dosen Poin 2 & 3:
 *   - Penambahan input kode_batch (contoh: BATCH-202611-01)
 *   - Penambahan input date expired_date untuk pencatatan kedatangan batch baru.
 */

$root       = '../../';
$page_title = 'Input Restock Barang Masuk (Batch Baru)';
$page_sub   = 'Catat penambahan stok fisik dan batch kedatangan dari vendor ke database gudang · MKP Store';
$active     = 'inventory';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil daftar kategori produk dari Supabase
$query_categories = "SELECT id_category, nama_category FROM category ORDER BY nama_category ASC";
$res_categories   = pg_query($conn, $query_categories);

// Ambil daftar produk dari Supabase untuk pilihan dropdown
$query_products = "SELECT p.id_product, p.nama_barang, p.stok_aktual, p.categoryid_category, c.nama_category 
                   FROM products p 
                   LEFT JOIN category c ON p.categoryid_category = c.id_category 
                   ORDER BY p.nama_barang ASC";
$res_products = pg_query($conn, $query_products);

// Ambil riwayat nama vendor/pabrik resmi dari tabel restocks (filter catatan penyesuaian internal)
$query_vendors = "SELECT DISTINCT TRIM(nama_vendor) AS vendor_name 
                  FROM restocks 
                  WHERE nama_vendor IS NOT NULL 
                    AND TRIM(nama_vendor) != ''
                    AND LOWER(nama_vendor) NOT LIKE '%penyesuaian%'
                    AND LOWER(nama_vendor) NOT LIKE '%opname%'
                    AND LOWER(nama_vendor) NOT LIKE '%audit%'
                  ORDER BY vendor_name ASC";
$res_vendors   = pg_query($conn, $query_vendors);
$vendor_list   = [];
if ($res_vendors) {
    while ($v = pg_fetch_assoc($res_vendors)) {
        if (!empty($v['vendor_name'])) {
            $vendor_list[] = $v['vendor_name'];
        }
    }
}

// Auto-generate Kode Batch unik otomatis: BATCH-YYYYMM-XX
$prefix_batch = 'BATCH-' . date('Ym') . '-';
$q_last_batch = "SELECT kode_batch FROM restocks WHERE kode_batch LIKE $1 ORDER BY id_restock DESC LIMIT 1";
$res_last_b   = pg_query_params($conn, $q_last_batch, [$prefix_batch . '%']);
$nomor_urut   = 1;
if ($res_last_b && pg_num_rows($res_last_b) > 0) {
    $row_lb = pg_fetch_assoc($res_last_b);
    $parts  = explode('-', (string)$row_lb['kode_batch']);
    $last_num = isset($parts[2]) ? (int)$parts[2] : 0;
    $nomor_urut = $last_num + 1;
}
$auto_kode_batch = $prefix_batch . str_pad($nomor_urut, 2, '0', STR_PAD_LEFT);

// Tangkap parameter URL untuk auto-select jika dialihkan dari rincian batch
$pre_cat_id  = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$pre_prod_id = isset($_GET['product_id']) ? trim($_GET['product_id']) : '';
?>

<div class="page-actions page-actions-form rise">
  <a href="<?= BASE_URL ?>modules/inventory/index.php" class="btn btn-ghost">
    &larr; Kembali ke Inventaris Stok
  </a>
</div>

<?php if (isset($_SESSION['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div><?= e($_SESSION['error']) ?></div>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="card card-form rise">
  <div class="card-head">
    <div>
      <h2>Form Penerimaan Barang Masuk (Batch Baru)</h2>
      <p>Pilih produk, catat kode batch pabrik, tanggal kedaluwarsa, serta kuantitas kedatangan barang</p>
    </div>
    <span class="badge info"><i class="dot"></i>Batch &amp; FEFO</span>
  </div>

  <form action="<?= BASE_URL ?>modules/inventory/process/stock_in.php" method="POST">
    <div class="field">
      <label for="id_category">Kategori Produk</label>
      <select id="id_category" class="select" required>
        <option value="">-- Pilih Kategori Produk Terlebih Dahulu --</option>
        <?php if ($res_categories): ?>
          <?php while ($cat = pg_fetch_assoc($res_categories)): ?>
            <option value="<?= e($cat['id_category']) ?>">
              <?= e($cat['nama_category']) ?>
            </option>
          <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="field">
      <label for="id_product">Pilih Produk Sirup / Bubuk</label>
      <select name="id_product" id="id_product" class="select" required disabled>
        <option value="">-- Pilih Kategori Terlebih Dahulu --</option>
        <?php if ($res_products): ?>
          <?php while ($prod = pg_fetch_assoc($res_products)): ?>
            <option value="<?= e($prod['id_product']) ?>" data-category="<?= e($prod['categoryid_category'] ?? '') ?>">
              <?= e($prod['nama_barang']) ?> (Stok saat ini: <?= (int)$prod['stok_aktual'] ?>)
            </option>
          <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="field">
      <div class="field-label-row">
        <label>Pabrik / Vendor Penyuplai</label>
        <div class="vendor-switch-track" id="vendor_switch_track" data-mode="list" role="tablist" aria-label="Mode input vendor">
          <span class="vendor-switch-thumb" aria-hidden="true"></span>
          <button type="button" class="vendor-switch-opt is-active" id="btn_mode_list" onclick="toggleVendorMode('list')">
            Vendor Terdaftar
          </button>
          <button type="button" class="vendor-switch-opt" id="btn_mode_new" onclick="toggleVendorMode('new')">
            Vendor Baru
          </button>
        </div>
      </div>

      <div class="vendor-box-card">
        <!-- Mode 1: Item List Dropdown Vendor Terdaftar -->
        <div id="vendor_list_wrapper" class="vendor-input-group">
          <select id="vendor_select_input" class="select" onchange="onVendorSelectChange(this.value)">
            <option value="">-- Pilih Pabrik / Vendor Penyuplai Terdaftar --</option>
            <?php foreach ($vendor_list as $v_item): ?>
              <option value="<?= e($v_item) ?>">
                <?= e($v_item) ?>
              </option>
            <?php endforeach; ?>
            <option value="__NEW__">+ Ketik Nama Vendor Baru Lainnya...</option>
          </select>
        </div>

        <!-- Mode 2: Input Manual Vendor Baru -->
        <div id="vendor_new_wrapper" class="vendor-input-group is-hidden">
          <input type="text" id="vendor_manual_input" class="input" placeholder="Masukkan nama PT / pabrik / vendor baru (Contoh: PT. Denali Prima, Cikarang)" oninput="onVendorManualInput(this.value)">
        </div>

        <!-- Hidden input nama_vendor yang disubmit ke proses backend -->
        <input type="hidden" name="nama_vendor" id="nama_vendor" value="" required>
      </div>
      <span class="field-hint" id="vendor_hint">Pilih dari daftar vendor terdaftar, atau geser switch ke 'Vendor Baru' untuk mengetik manual.</span>
    </div>

    <div class="form-row">
      <div class="field">
        <div class="field-label-row">
          <label for="kode_batch">Kode Batch Pabrik</label>
          <span class="badge info"><i class="dot"></i>Otomatis</span>
        </div>
        <input type="text" name="kode_batch" id="kode_batch" class="input mono input-readonly" value="<?= e($auto_kode_batch) ?>" data-base-batch="<?= e($auto_kode_batch) ?>" readonly required>
        <span class="field-hint">Dibuat otomatis oleh sistem sesuai vendor &amp; tanggal masuk.</span>
      </div>

      <div class="field">
        <div class="field-label-row">
          <label for="expired_date">Tanggal Kedaluwarsa</label>
          <span class="badge warn"><i class="dot"></i>Wajib FEFO</span>
        </div>
        <input type="date" name="expired_date" id="expired_date" class="input" min="<?= date('Y-m-d') ?>" required>
        <span class="field-hint">Sesuai tanggal kedaluwarsa pada kemasan botol fisik.</span>
      </div>
    </div>

    <div class="field">
      <label for="kuantitas">Jumlah Masuk (Botol / Satuan)</label>
      <input type="number" name="kuantitas" id="kuantitas" class="input mono" min="1" placeholder="Masukkan jumlah kuantitas botol" required>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <?= icon('check', 18) ?> Simpan Data Restock Batch
      </button>
      <a href="<?= BASE_URL ?>modules/inventory/index.php" class="btn btn-ghost">
        Batal
      </a>
    </div>
  </form>
</div>

<script>
function updateKodeBatchByVendor(vendorName) {
  var batchInput = document.getElementById('kode_batch');
  if (!batchInput) return;
  var baseBatch = batchInput.getAttribute('data-base-batch') || 'BATCH-<?= date('Ym') ?>-01';
  var suffix = baseBatch.replace(/^BATCH-/, '');
  var clean = String(vendorName || '')
    .toUpperCase()
    .replace(/\b(PT|CV|UD|FA|PD|TBK)\b/g, ' ')
    .replace(/[^A-Z0-9\s]/g, ' ')
    .trim();
  if (!clean) {
    batchInput.value = baseBatch;
    return;
  }
  var firstWord = clean.split(/\s+/)[0] || '';
  var code = firstWord.slice(0, 5);
  batchInput.value = code ? ('BATCH-' + code + '-' + suffix) : baseBatch;
}

// Toggle antara mode Pilih Vendor Terdaftar dan Input Vendor Baru (Sliding Switch)
function toggleVendorMode(mode) {
  var listWrap    = document.getElementById('vendor_list_wrapper');
  var newWrap     = document.getElementById('vendor_new_wrapper');
  var selInput    = document.getElementById('vendor_select_input');
  var manInput    = document.getElementById('vendor_manual_input');
  var hiddenVal   = document.getElementById('nama_vendor');
  var hintEl      = document.getElementById('vendor_hint');
  var switchTrack = document.getElementById('vendor_switch_track');
  var btnList     = document.getElementById('btn_mode_list');
  var btnNew      = document.getElementById('btn_mode_new');

  if (!listWrap || !newWrap) return;

  if (mode === 'new') {
    if (switchTrack) switchTrack.setAttribute('data-mode', 'new');
    if (btnList) btnList.classList.remove('is-active');
    if (btnNew) btnNew.classList.add('is-active');
    listWrap.classList.add('is-hidden');
    newWrap.classList.remove('is-hidden');
    if (manInput) {
      var valMan = manInput.value.trim();
      if (hiddenVal) hiddenVal.value = valMan;
      updateKodeBatchByVendor(valMan);
      manInput.focus();
    }
    if (hintEl) hintEl.textContent = 'Ketik nama pabrik / vendor baru. Sistem akan otomatis mencatatnya ke database.';
  } else {
    if (switchTrack) switchTrack.setAttribute('data-mode', 'list');
    if (btnNew) btnNew.classList.remove('is-active');
    if (btnList) btnList.classList.add('is-active');
    newWrap.classList.add('is-hidden');
    listWrap.classList.remove('is-hidden');
    if (selInput) {
      if (selInput.value === '__NEW__') {
        selInput.value = '';
      }
      if (hiddenVal) hiddenVal.value = selInput.value;
      updateKodeBatchByVendor(selInput.value);
      selInput.focus();
    }
    if (hintEl) hintEl.textContent = 'Pilih salah satu vendor resmi yang sudah pernah terdaftar di sistem.';
  }
}

function onVendorSelectChange(val) {
  var hiddenVal = document.getElementById('nama_vendor');
  if (val === '__NEW__') {
    toggleVendorMode('new');
    return;
  }
  if (hiddenVal) hiddenVal.value = val;
  updateKodeBatchByVendor(val);
}

function onVendorManualInput(val) {
  var hiddenVal = document.getElementById('nama_vendor');
  var trimmed = val.trim();
  if (hiddenVal) hiddenVal.value = trimmed;
  updateKodeBatchByVendor(trimmed);
}

document.addEventListener('DOMContentLoaded', function() {
  var categorySelect = document.getElementById('id_category');
  var productSelect  = document.getElementById('id_product');

  if (categorySelect && productSelect) {
    // Simpan data master opsi produk dari HTML ke dalam memory array
    var masterProducts = [];
    var options = productSelect.options;
    for (var i = 0; i < options.length; i++) {
      var opt = options[i];
      if (opt.value !== '') {
        masterProducts.push({
          value: opt.value,
          text: opt.textContent.trim(),
          categoryId: opt.getAttribute('data-category') || ''
        });
      }
    }

    // Event listener saat kategori produk dipilih/diubah
    categorySelect.addEventListener('change', function() {
      var selectedCatId = this.value;

      // Bersihkan opsi produk sebelumnya
      productSelect.innerHTML = '';

      if (!selectedCatId) {
        // Jika kategori dikosongkan/belum dipilih, disable dropdown produk
        productSelect.disabled = true;
        var emptyOpt = document.createElement('option');
        emptyOpt.value = '';
        emptyOpt.textContent = '-- Pilih Kategori Terlebih Dahulu --';
        productSelect.appendChild(emptyOpt);
        return;
      }

      // Filter daftar produk sesuai ID kategori yang dipilih
      var filtered = masterProducts.filter(function(p) {
        return String(p.categoryId) === String(selectedCatId);
      });

      var placeholder = document.createElement('option');
      placeholder.value = '';

      if (filtered.length === 0) {
        placeholder.textContent = '-- Belum ada produk di kategori ini --';
        productSelect.appendChild(placeholder);
        productSelect.disabled = true;
      } else {
        placeholder.textContent = '-- Pilih Produk (' + filtered.length + ' varian) --';
        productSelect.appendChild(placeholder);
        productSelect.disabled = false;

        filtered.forEach(function(item) {
          var opt = document.createElement('option');
          opt.value = item.value;
          opt.textContent = item.text;
          productSelect.appendChild(opt);
        });
      }
    });

    // Pre-select kategori & produk jika dialihkan dari link tambah batch di halaman inventaris
    var preCatId  = <?= json_encode($pre_cat_id) ?>;
    var preProdId = <?= json_encode($pre_prod_id) ?>;

    if (preCatId && preCatId > 0) {
      categorySelect.value = preCatId;
      categorySelect.dispatchEvent(new Event('change'));
      if (preProdId) {
        productSelect.value = preProdId;
      }
    }
  }

  // Jika daftar vendor belum ada, arahkan langsung ke mode input baru
  <?php if (empty($vendor_list)): ?>
    toggleVendorMode('new');
  <?php else: ?>
    // Inisialisasi nilai nama_vendor jika vendor default sudah dipilih
    var selInputInit = document.getElementById('vendor_select_input');
    if (selInputInit && selInputInit.value && selInputInit.value !== '__NEW__') {
      var hVal = document.getElementById('nama_vendor');
      if (hVal) hVal.value = selInputInit.value;
    }
  <?php endif; ?>

  // Validasi form sebelum submit
  var formRestock = document.querySelector('form');
  if (formRestock) {
    formRestock.addEventListener('submit', function(e) {
      var vendorVal = document.getElementById('nama_vendor').value.trim();
      if (!vendorVal) {
        e.preventDefault();
        alert('Silakan pilih salah satu vendor terdaftar atau ketik nama vendor baru terlebih dahulu!');
        var switchTrack = document.getElementById('vendor_switch_track');
        if (switchTrack && switchTrack.getAttribute('data-mode') === 'new') {
          document.getElementById('vendor_manual_input').focus();
        } else {
          document.getElementById('vendor_select_input').focus();
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
