<?php
/**
 * MODUL TRANSAKSI — KASIR / POINT OF SALE (POS)
 * File: modules/transactions/index.php
 * Fungsi: Antarmuka Kasir (POS) statis bergaya Bento Grid Toska MKP Store.
 */

$root       = '../../';
$page_title = 'Kasir (Point of Sale)';
$page_sub   = 'Transaksi penjualan B2B/B2C HORECA · MKP Store Malang';
$active     = 'transactions';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Layout 2 Kolom Grid: Kolom Kiri (Katalog Produk) & Kolom Kanan (Keranjang Struk) -->
<section class="pos rise" aria-label="Area Kasir Point of Sale">

  <!-- ==================== KOLOM KIRI: KATALOG PRODUK ==================== -->
  <div class="card">
    <div class="card-head">
      <div>
        <h2>Katalog Produk MKP Store</h2>
        <p>Pilih varian sirup DRIPP, Fruit Pulp, atau Powder untuk dimasukkan ke keranjang</p>
      </div>
      <span class="badge info"><i class="dot"></i>Ready Stock Gudang</span>
    </div>

    <!-- Bar Pencarian & Filter Kategori Statis -->
    <div class="pos-tools">
      <div class="input-icon">
        <?= icon('search', 18) ?>
        <input type="text" class="input" placeholder="Cari produk (mis. Caramel, Vanilla, Hazelnut)..." aria-label="Cari produk">
      </div>
      <div class="chips">
        <button type="button" class="chip active">Semua</button>
        <button type="button" class="chip">DRIPP Syrup</button>
        <button type="button" class="chip">Fruit Pulp</button>
        <button type="button" class="chip">Powder</button>
      </div>
    </div>

    <!-- Grid Kartu Produk Statis -->
    <div class="product-grid">

      <!-- Produk 1: DRIPP Caramel Syrup -->
      <article class="product-card">
        <div class="product-photo">
          <span class="badge cat-tag">DRIPP Syrup</span>
          <span class="badge success"><i class="dot"></i>Stok: 36 Btl</span>
          <?= bottle_svg('#F0B35A', '#B8651B', 'Caramel', '760 ml', 'syrup', 'DRIPP') ?>
        </div>
        <div class="product-body">
          <h3>DRIPP Flavour Syrup Caramel</h3>
          <span class="meta">SKU: DRP-CRM-760 · Botol 760ml</span>
          <div class="product-foot">
            <div class="price">
              Rp 111.000
              <small>/ botol</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="alert('Simulasi Demo: DRIPP Caramel ditambahkan ke keranjang!')">
              <?= icon('plus', 15) ?> Tambah
            </button>
          </div>
        </div>
      </article>

      <!-- Produk 2: DRIPP Vanilla Syrup -->
      <article class="product-card">
        <div class="product-photo">
          <span class="badge cat-tag">DRIPP Syrup</span>
          <span class="badge success"><i class="dot"></i>Stok: 48 Btl</span>
          <?= bottle_svg('#F9E8B6', '#D4A843', 'Vanilla', '760 ml', 'syrup', 'DRIPP') ?>
        </div>
        <div class="product-body">
          <h3>DRIPP Flavour Syrup Vanilla</h3>
          <span class="meta">SKU: DRP-VNL-760 · Botol 760ml</span>
          <div class="product-foot">
            <div class="price">
              Rp 111.000
              <small>/ botol</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="alert('Simulasi Demo: DRIPP Vanilla ditambahkan ke keranjang!')">
              <?= icon('plus', 15) ?> Tambah
            </button>
          </div>
        </div>
      </article>

      <!-- Produk 3: DRIPP Hazelnut Syrup -->
      <article class="product-card">
        <div class="product-photo">
          <span class="badge cat-tag">DRIPP Syrup</span>
          <span class="badge warn"><i class="dot"></i>Stok: 16 Btl</span>
          <?= bottle_svg('#B98458', '#7A4B2A', 'Hazelnut', '760 ml', 'syrup', 'DRIPP') ?>
        </div>
        <div class="product-body">
          <h3>DRIPP Flavour Syrup Hazelnut</h3>
          <span class="meta">SKU: DRP-HZN-760 · Botol 760ml</span>
          <div class="product-foot">
            <div class="price">
              Rp 111.000
              <small>/ botol</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="alert('Simulasi Demo: DRIPP Hazelnut ditambahkan ke keranjang!')">
              <?= icon('plus', 15) ?> Tambah
            </button>
          </div>
        </div>
      </article>

      <!-- Produk 4: DRIPP Pandan Syrup -->
      <article class="product-card">
        <div class="product-photo">
          <span class="badge cat-tag">DRIPP Syrup</span>
          <span class="badge danger"><i class="dot"></i>Stok: 12 Btl</span>
          <?= bottle_svg('#84D49D', '#278C48', 'Pandan', '760 ml', 'syrup', 'DRIPP') ?>
        </div>
        <div class="product-body">
          <h3>DRIPP Flavour Syrup Pandan</h3>
          <span class="meta">SKU: DRP-PDN-760 · Botol 760ml</span>
          <div class="product-foot">
            <div class="price">
              Rp 111.000
              <small>/ botol</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="alert('Simulasi Demo: DRIPP Pandan ditambahkan ke keranjang!')">
              <?= icon('plus', 15) ?> Tambah
            </button>
          </div>
        </div>
      </article>

      <!-- Produk 5: DRIPP Fruit Pulp Blueberry -->
      <article class="product-card">
        <div class="product-photo">
          <span class="badge cat-tag">Fruit Pulp</span>
          <span class="badge success"><i class="dot"></i>Stok: 24 Btl</span>
          <?= bottle_svg('#A5B4FC', '#4338CA', 'Blueberry', 'Pulp 760ml', 'pulp', 'DRIPP') ?>
        </div>
        <div class="product-body">
          <h3>DRIPP Fruit Pulp Blueberry</h3>
          <span class="meta">SKU: DRP-PLP-BLU · Botol 760ml</span>
          <div class="product-foot">
            <div class="price">
              Rp 166.500
              <small>/ botol</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="alert('Simulasi Demo: DRIPP Fruit Pulp Blueberry ditambahkan ke keranjang!')">
              <?= icon('plus', 15) ?> Tambah
            </button>
          </div>
        </div>
      </article>

      <!-- Produk 6: DRIPP Powder Red Velvet -->
      <article class="product-card">
        <div class="product-photo">
          <span class="badge cat-tag">Powder</span>
          <span class="badge success"><i class="dot"></i>Stok: 35 Pch</span>
          <?= bottle_svg('#F87171', '#991B1B', 'Red Velvet', '760 gr', 'powder', 'DRIPP') ?>
        </div>
        <div class="product-body">
          <h3>DRIPP Powder Red Velvet</h3>
          <span class="meta">SKU: DRP-PWD-RDV · Pouch 760g</span>
          <div class="product-foot">
            <div class="price">
              Rp 156.000
              <small>/ pouch</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="alert('Simulasi Demo: DRIPP Powder Red Velvet ditambahkan ke keranjang!')">
              <?= icon('plus', 15) ?> Tambah
            </button>
          </div>
        </div>
      </article>

    </div>
  </div>

  <!-- ==================== KOLOM KANAN: KERANJANG & STRUK BELANJA ==================== -->
  <aside class="card flush cart" aria-label="Keranjang Belanja Kasir">
    <div class="cart-head">
      <div>
        <h2>Keranjang Belanja</h2>
        <div class="order-no">No. Struk: <strong>#INV-20261008-001</strong></div>
      </div>
      <span class="badge info">3 Item (14 Btl)</span>
    </div>

    <div class="cart-customer">
      <div class="field" style="margin-bottom: 0;">
        <label for="nama_pelanggan">Pelanggan / Mitra HORECA</label>
        <input type="text" id="nama_pelanggan" class="input" value="Kopi Studio 24 - Soekarno Hatta" readonly>
      </div>
    </div>

    <!-- Tabel Struk Belanja Statis -->
    <div class="table-wrap" style="border-top: 1px solid var(--line);">
      <table class="table">
        <thead>
          <tr>
            <th>Item Produk</th>
            <th style="text-align: center;">Qty</th>
            <th class="text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <strong>DRIPP Caramel Syrup</strong>
              <div class="muted" style="font-size: 11.5px;">Rp 111.000 / btl</div>
            </td>
            <td style="text-align: center;" class="mono"><strong>6</strong></td>
            <td class="text-right mono"><strong>Rp 666.000</strong></td>
          </tr>
          <tr>
            <td>
              <strong>DRIPP Vanilla Syrup</strong>
              <div class="muted" style="font-size: 11.5px;">Rp 111.000 / btl</div>
            </td>
            <td style="text-align: center;" class="mono"><strong>6</strong></td>
            <td class="text-right mono"><strong>Rp 666.000</strong></td>
          </tr>
          <tr>
            <td>
              <strong>DRIPP Fruit Pulp Blueberry</strong>
              <div class="muted" style="font-size: 11.5px;">Rp 166.500 / btl</div>
            </td>
            <td style="text-align: center;" class="mono"><strong>2</strong></td>
            <td class="text-right mono"><strong>Rp 333.000</strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Ringkasan Pembayaran, Input Diskon & Tombol Checkout -->
    <div class="cart-summary">
      <div class="moq-hint ok">
        <?= icon('check', 16) ?>
        <span><strong>MOQ Grosir Terpenuhi:</strong> Pembelian &ge; 12 botol berhak klaim diskon mitra HORECA.</span>
      </div>

      <div class="field" style="margin-bottom: 12px;">
        <label for="input_diskon">Diskon Transaksi (Rp)</label>
        <input type="number" id="input_diskon" class="input mono" value="65000" min="0" step="1000">
      </div>

      <div class="field" style="margin-bottom: 14px;">
        <label for="metode_bayar">Metode & Status Pembayaran</label>
        <select id="metode_bayar" class="select">
          <option value="lunas_qris" selected>Lunas — QRIS / Transfer Bank</option>
          <option value="lunas_tunai">Lunas — Tunai (Cash)</option>
          <option value="piutang">Piutang Mitra (Tempo 14 Hari)</option>
        </select>
      </div>

      <div class="sum-row">
        <span>Subtotal (14 Unit)</span>
        <b class="mono">Rp 1.665.000</b>
      </div>
      <div class="sum-row disc">
        <span>Potongan Diskon Mitra</span>
        <b class="mono">- Rp 65.000</b>
      </div>

      <div class="sum-total">
        <span>Total Tagihan</span>
        <strong class="mono">Rp 1.600.000</strong>
      </div>

      <button type="button" class="btn btn-primary btn-lg btn-block" onclick="alert('Transaksi #INV-20261008-001 senilai Rp 1.600.000 berhasil diproses!')">
        <?= icon('check', 20) ?> Proses Pembayaran (Checkout)
      </button>
    </div>
  </aside>

</section>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>