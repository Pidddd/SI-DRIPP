<?php
/**
 * MODUL TRANSAKSI — KASIR / POINT OF SALE (POS)
 * File: modules/transactions/index.php
 * Fungsi: Antarmuka Kasir (POS) dinamis yang terhubung ke tabel products Supabase dan mengirim form ke checkout.php.
 */

$root       = '../../';
$page_title = 'Kasir (Point of Sale)';
$page_sub   = 'Transaksi penjualan B2B/B2C HORECA · MKP Store Malang';
$active     = 'transactions';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil katalog produk dari Supabase beserta kategori
$q_katalog   = "SELECT p.id_product, p.nama_barang, p.harga_jual, p.stok_aktual, c.nama_category
                FROM products p
                LEFT JOIN category c ON p.categoryid_category = c.id_category
                ORDER BY p.nama_barang ASC";
$res_katalog = pg_query($conn, $q_katalog);
$katalog_arr = [];
if ($res_katalog) {
    while ($item = pg_fetch_assoc($res_katalog)) {
        $katalog_arr[] = $item;
    }
}
?>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'transaksi_sukses'): ?>
  <div class="callout success rise">
    <?= icon('check', 18) ?>
    <div>
      <strong>Transaksi Berhasil Disimpan!</strong> Data transaksi dan pemotongan stok otomatis telah tercatat di Supabase.
      <?php if (!empty($_GET['id'])): ?>
        <a href="<?= BASE_URL ?>modules/transactions/print_invoice.php?id=<?= urlencode($_GET['id']) ?>" class="btn btn-soft btn-sm ml-2">
          <?= icon('report', 14) ?> Cetak Struk #<?= e($_GET['id']) ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div>Transaksi gagal diproses: <?= e($_GET['error']) ?></div>
  </div>
<?php endif; ?>

<section class="pos rise" aria-label="Area Kasir Point of Sale">

  <!-- ==================== KOLOM KIRI: KATALOG PRODUK SUPABASE ==================== -->
  <div class="card">
    <div class="card-head">
      <div>
        <h2>Katalog Produk MKP Store</h2>
        <p>Pilih produk dari database untuk dimasukkan ke keranjang transaksi kasir</p>
      </div>
      <span class="badge info"><i class="dot"></i><?= count($katalog_arr) ?> Produk Aktif</span>
    </div>

    <div class="pos-tools">
      <div class="input-icon">
        <?= icon('search', 18) ?>
        <input type="text" id="pos-search" class="input" placeholder="Cari nama produk atau kategori..." oninput="filterKatalogPos()" aria-label="Cari produk">
      </div>
    </div>

    <div class="product-grid" id="pos-product-grid">
      <?php if (count($katalog_arr) > 0): ?>
        <?php foreach ($katalog_arr as $idx => $p):
            $stok      = (int)$p['stok_aktual'];
            $habis     = $stok <= 0;
            $kritis    = $stok > 0 && $stok < 24;
            $kat_label = $p['nama_category'] ?? 'Sirup DRIPP';
            $palet     = [
                ['#F0B35A', '#B8651B'],
                ['#F9E8B6', '#D4A843'],
                ['#84D49D', '#278C48'],
                ['#A5B4FC', '#4338CA'],
                ['#F87171', '#991B1B'],
                ['#68CDDA', '#088395']
            ];
            $warna = $palet[$idx % count($palet)];
        ?>
          <article class="product-card <?= $habis ? 'out' : '' ?>" data-search="<?= e(strtolower($p['nama_barang'] . ' ' . $kat_label . ' ' . $p['id_product'])) ?>">
            <div class="product-photo">
              <span class="badge cat-tag"><?= e($kat_label) ?></span>
              <span class="badge <?= $habis ? 'danger' : ($kritis ? 'warn' : 'success') ?>">
                <i class="dot"></i>Stok: <?= $stok ?>
              </span>
              <?= bottle_svg($warna[0], $warna[1], substr($p['nama_barang'], 0, 10), '760 ml', 'syrup', 'DRIPP') ?>
            </div>
            <div class="product-body">
              <h3><?= e($p['nama_barang']) ?></h3>
              <span class="meta">SKU: <?= e($p['id_product']) ?></span>
              <div class="product-foot">
                <div class="price">
                  <?= rupiah($p['harga_jual']) ?>
                  <small>/ unit</small>
                </div>
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  <?= $habis ? 'disabled' : '' ?>
                  onclick="tambahKeKeranjang('<?= e(addslashes($p['id_product'])) ?>', '<?= e(addslashes($p['nama_barang'])) ?>', <?= (int)$p['harga_jual'] ?>, <?= $stok ?>)">
                  <?= icon('plus', 15) ?> Tambah
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="table-empty">Belum ada produk di database. Silakan tambahkan di menu Master Produk.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ==================== KOLOM KANAN: KERANJANG & FORM CHECKOUT ==================== -->
  <aside class="card flush cart" aria-label="Keranjang Belanja Kasir">
    <form action="<?= BASE_URL ?>modules/transactions/process/checkout.php" method="POST" id="form-checkout" onsubmit="return validasiCheckout()">
      <input type="hidden" name="userid_user" value="<?= (int)($_SESSION['id_user'] ?? 1) ?>">

      <div class="cart-head">
        <div>
          <h2>Keranjang Belanja</h2>
          <div class="order-no">Kasir Aktif: <strong><?= e($me['nama']) ?></strong></div>
        </div>
        <span class="badge info" id="cart-badge-count">0 Item (0 Unit)</span>
      </div>

      <div class="cart-customer">
        <div class="field field-compact">
          <label for="nama_pelanggan">Nama Pelanggan / Mitra HORECA</label>
          <input type="text" id="nama_pelanggan" name="nama_pelanggan" class="input" placeholder="Contoh: Kopi Studio 24 - Suhat" required>
        </div>
        <div class="field field-tight">
          <label for="kontak_pelanggan">No. WhatsApp / Kontak</label>
          <input type="text" id="kontak_pelanggan" name="kontak_pelanggan" class="input mono" placeholder="081234567890" maxlength="15" required>
        </div>
      </div>

      <!-- Tabel Item Keranjang Dinamis -->
      <div class="table-wrap border-top-line">
        <table class="table">
          <thead>
            <tr>
              <th>Item Produk</th>
              <th class="text-center">Qty</th>
              <th class="text-right">Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="cart-tbody">
            <tr id="cart-empty-row">
              <td colspan="4" class="table-empty">Keranjang masih kosong. Klik tombol <strong>+ Tambah</strong> pada katalog.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Container Hidden Inputs untuk Array POST -->
      <div id="cart-hidden-inputs"></div>

      <!-- Ringkasan Pembayaran -->
      <div class="cart-summary">
        <div class="field field-compact">
          <label for="input_diskon">Potongan Diskon Transaksi (Rp)</label>
          <input type="number" id="input_diskon" name="diskon" class="input mono" value="0" min="0" step="500" oninput="renderKeranjang()">
        </div>

        <div class="field field-compact">
          <label for="status_pembayaran">Metode &amp; Status Pembayaran</label>
          <select id="status_pembayaran" name="status_pembayaran" class="select" onchange="toggleJatuhTempo()">
            <option value="1" selected>Lunas — Tunai / QRIS / Transfer</option>
            <option value="2">Piutang Mitra — Tempo (Belum Lunas)</option>
          </select>
        </div>

        <div class="field field-compact is-hidden" id="box-jatuh-tempo">
          <label for="jatuh_tempo">Tanggal Jatuh Tempo Piutang</label>
          <input type="date" id="jatuh_tempo" name="jatuh_tempo" class="input" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
        </div>

        <div class="sum-row">
          <span>Subtotal Barang</span>
          <b class="mono" id="label-subtotal">Rp 0</b>
        </div>
        <div class="sum-row disc">
          <span>Potongan Diskon</span>
          <b class="mono" id="label-diskon">- Rp 0</b>
        </div>

        <div class="sum-total">
          <span>Total Tagihan</span>
          <strong class="mono" id="label-grandtotal">Rp 0</strong>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block" id="btn-submit-checkout">
          <?= icon('check', 20) ?> Proses Pembayaran (Checkout)
        </button>
      </div>
    </form>
  </aside>

</section>

<script>
let keranjang = [];

function formatRupiah(angka) {
  return 'Rp ' + Math.max(0, Math.round(angka)).toLocaleString('id-ID');
}

function tambahKeKeranjang(id, nama, harga, stokMaks) {
  const idx = keranjang.findIndex(item => item.id === id);
  if (idx > -1) {
    if (keranjang[idx].qty + 1 > stokMaks) {
      alert('Stok produk ' + nama + ' hanya tersisa ' + stokMaks + ' unit.');
      return;
    }
    keranjang[idx].qty += 1;
  } else {
    keranjang.push({ id: id, nama: nama, harga: harga, qty: 1, stokMaks: stokMaks });
  }
  renderKeranjang();
}

function ubahQty(id, delta) {
  const idx = keranjang.findIndex(item => item.id === id);
  if (idx === -1) return;
  const qtyBaru = keranjang[idx].qty + delta;
  if (qtyBaru <= 0) {
    keranjang.splice(idx, 1);
  } else if (qtyBaru > keranjang[idx].stokMaks) {
    alert('Tidak dapat melebihi stok tersedia (' + keranjang[idx].stokMaks + ').');
    return;
  } else {
    keranjang[idx].qty = qtyBaru;
  }
  renderKeranjang();
}

function renderKeranjang() {
  const tbody = document.getElementById('cart-tbody');
  const hiddenWrap = document.getElementById('cart-hidden-inputs');
  const badge = document.getElementById('cart-badge-count');
  const diskonInput = document.getElementById('input_diskon');
  const diskon = Math.max(0, parseInt(diskonInput.value || '0', 10));

  tbody.innerHTML = '';
  hiddenWrap.innerHTML = '';

  if (keranjang.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="table-empty">Keranjang masih kosong. Klik tombol <strong>+ Tambah</strong> pada katalog.</td></tr>';
    badge.textContent = '0 Item (0 Unit)';
    document.getElementById('label-subtotal').textContent = 'Rp 0';
    document.getElementById('label-diskon').textContent = '- Rp 0';
    document.getElementById('label-grandtotal').textContent = 'Rp 0';
    return;
  }

  let totalUnit = 0;
  let subtotal = 0;

  keranjang.forEach(item => {
    const subItem = item.qty * item.harga;
    totalUnit += item.qty;
    subtotal += subItem;

    const tr = document.createElement('tr');
    tr.innerHTML =
      '<td><strong>' + item.nama + '</strong><div class="muted text-xxs">' + formatRupiah(item.harga) + ' / unit</div></td>' +
      '<td class="text-center mono">' +
        '<button type="button" class="btn btn-ghost btn-sm" onclick="ubahQty(\'' + item.id + '\', -1)">-</button> ' +
        '<strong>' + item.qty + '</strong> ' +
        '<button type="button" class="btn btn-ghost btn-sm" onclick="ubahQty(\'' + item.id + '\', 1)">+</button>' +
      '</td>' +
      '<td class="text-right mono"><strong>' + formatRupiah(subItem) + '</strong></td>' +
      '<td class="text-right"><button type="button" class="btn btn-danger-soft btn-sm" onclick="ubahQty(\'' + item.id + '\', -9999)">&times;</button></td>';
    tbody.appendChild(tr);

    hiddenWrap.innerHTML +=
      '<input type="hidden" name="product_id[]" value="' + item.id + '">' +
      '<input type="hidden" name="kuantitas[]" value="' + item.qty + '">' +
      '<input type="hidden" name="harga[]" value="' + item.harga + '">';
  });

  const grandTotal = Math.max(0, subtotal - diskon);
  badge.textContent = keranjang.length + ' Item (' + totalUnit + ' Unit)';
  document.getElementById('label-subtotal').textContent = formatRupiah(subtotal);
  document.getElementById('label-diskon').textContent = '- ' + formatRupiah(diskon);
  document.getElementById('label-grandtotal').textContent = formatRupiah(grandTotal);
}

function toggleJatuhTempo() {
  const status = document.getElementById('status_pembayaran').value;
  const box = document.getElementById('box-jatuh-tempo');
  if (status === '2') {
    box.classList.remove('is-hidden');
  } else {
    box.classList.add('is-hidden');
  }
}

function filterKatalogPos() {
  const q = (document.getElementById('pos-search').value || '').toLowerCase().trim();
  document.querySelectorAll('#pos-product-grid .product-card').forEach(card => {
    const teks = card.getAttribute('data-search') || '';
    if (!q || teks.includes(q)) {
      card.classList.remove('is-hidden');
    } else {
      card.classList.add('is-hidden');
    }
  });
}

function validasiCheckout() {
  if (keranjang.length === 0) {
    alert('Pilih minimal 1 produk ke dalam keranjang sebelum memproses pembayaran!');
    return false;
  }
  return confirm('Proses transaksi penjualan sekarang?');
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
