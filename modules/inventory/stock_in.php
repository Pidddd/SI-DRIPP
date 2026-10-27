<?php
/**
 * UI MODUL INVENTARIS — PENERIMAAN BARANG MASUK (RESTOCK)
 * File: modules/inventory/stock_in.php
 * Fungsi: Form input penambahan stok barang masuk dari vendor/pabrik ke gudang.
 */

$root       = '../../';
$page_title = 'Input Restock Barang Masuk';
$page_sub   = 'Catat penambahan stok fisik dari vendor langsung ke database gudang · MKP Store';
$active     = 'inventory';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil daftar produk dari Supabase untuk pilihan dropdown
$query_products = "SELECT p.id_product, p.nama_barang, p.stok_aktual, c.nama_category 
                   FROM products p 
                   LEFT JOIN category c ON p.categoryid_category = c.id_category 
                   ORDER BY p.nama_barang ASC";
$res_products = pg_query($conn, $query_products);
?>

<div class="page-actions rise">
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
      <h2>Form Penerimaan Barang Masuk (Restock)</h2>
      <p>Pilih produk dan masukkan jumlah kedatangan barang dari vendor penyuplai</p>
    </div>
    <span class="badge info"><i class="dot"></i>Transaksi Gudang</span>
  </div>

  <form action="<?= BASE_URL ?>modules/inventory/process/stock_in.php" method="POST">
    <div class="field">
      <label for="id_product">Pilih Produk Sirup / Bubuk</label>
      <select name="id_product" id="id_product" class="select" required>
        <option value="">-- Pilih Produk --</option>
        <?php if ($res_products): ?>
          <?php while ($prod = pg_fetch_assoc($res_products)): ?>
            <option value="<?= e($prod['id_product']) ?>">
              <?= e($prod['nama_barang']) ?> (Stok saat ini: <?= (int)$prod['stok_aktual'] ?>) — [<?= e($prod['nama_category'] ?? 'Umum') ?>]
            </option>
          <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="field">
      <label for="nama_vendor">Nama Pabrik / Vendor Penyuplai</label>
      <input type="text" name="nama_vendor" id="nama_vendor" class="input" placeholder="Contoh: PT. Denali Prima, Pabrik Cikarang" required>
    </div>

    <div class="field">
      <label for="kuantitas">Jumlah Masuk (Botol / Satuan)</label>
      <input type="number" name="kuantitas" id="kuantitas" class="input mono" min="1" placeholder="Masukkan jumlah kuantitas" required>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <?= icon('check', 18) ?> Simpan Data Restock
      </button>
      <a href="<?= BASE_URL ?>modules/inventory/index.php" class="btn btn-ghost">
        Batal
      </a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
