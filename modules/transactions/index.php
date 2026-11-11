<?php
/**
 * MODUL TRANSAKSI — KASIR / POINT OF SALE (POS)
 * File: modules/transactions/index.php
 * Fungsi: Antarmuka Kasir (POS) dinamis yang terhubung ke tabel products & customers Supabase.
 * Revisi Dosen Poin 6:
 *   - Query data pelanggan / mitra PT dari tabel customers.
 *   - Searchable Dropdown / Select Mitra PT dengan tombol "+ Mitra Baru".
 *   - Auto-fill detail data PT (Nama PT, Nama PJ, NIK PJ, Telp/WA, Alamat Toko) via JS.
 *   - Pop-up Modal pendaftaran Mitra PT baru ke process/create_customer.php.
 */

$root       = '../../';
$page_title = 'Kasir (Point of Sale)';
$page_sub   = 'Transaksi penjualan B2B/B2C HORECA · MKP Store Malang';
$active     = 'transactions';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';

// 1. Ambil katalog produk dari Supabase beserta kategori
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

// 2. Ambil seluruh data Mitra PT dari tabel customers (Revisi Poin 6)
$q_customers   = "SELECT id_customer, nama_pt, nama_pj, nik_pj, no_telp, alamat_toko
                  FROM customers
                  ORDER BY nama_pt ASC";
$res_customers = pg_query($conn, $q_customers);
$customers_arr = [];
if ($res_customers) {
    while ($cust = pg_fetch_assoc($res_customers)) {
        $customers_arr[] = $cust;
    }
}
?>

<!-- Notifikasi Transaksi & Pendaftaran Mitra Baru -->
<?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'transaksi_sukses'): ?>
  <div class="callout success rise">
    <?= icon('check', 18) ?>
    <div>
      <strong>Transaksi Berhasil Disimpan!</strong> Pemotongan stok otomatis telah tercatat di Supabase.
      <?php if (!empty($_GET['id'])): ?>
        <a href="<?= BASE_URL ?>modules/transactions/print_invoice.php?id=<?= urlencode($_GET['id']) ?>" class="btn btn-soft btn-sm ml-2">
          <?= icon('report', 14) ?> Cetak Struk #<?= e($_GET['id']) ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'mitra_sukses'): ?>
  <div class="callout success rise">
    <?= icon('check', 18) ?>
    <div>
      <strong>Mitra Baru Berhasil Didaftarkan!</strong> Data mitra PT telah tersimpan di Supabase dan langsung dipilih pada keranjang transaksi.
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div>Gagal memproses: <?= e($_GET['error']) ?></div>
  </div>
<?php endif; ?>

<section class="pos rise" aria-label="Area Kasir Point of Sale">

  <!-- ==================== KOLOM KIRI: KATALOG PRODUK SUPABASE ==================== -->
  <div class="card">
    <div class="card-head">
      <div>
        <h2>Katalog Produk MKP Store</h2>
        <p>Pilih produk sirup / bubuk dari database untuk dimasukkan ke keranjang kasir</p>
      </div>
      <span class="badge info"><i class="dot"></i><?= count($katalog_arr) ?> Produk Aktif</span>
    </div>

    <div class="pos-tools">
      <div class="input-icon">
        <?= icon('search', 18) ?>
        <input type="text" id="pos-search" class="input" placeholder="Cari nama produk, SKU, atau kategori..." oninput="filterKatalogPos()" aria-label="Cari produk">
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

  <!-- ==================== KOLOM KANAN: KERANJANG & CHECKOUT MITRA ==================== -->
  <aside class="card flush cart" aria-label="Keranjang Belanja Kasir">
    <form action="<?= BASE_URL ?>modules/transactions/process/checkout.php" method="POST" id="form-checkout" onsubmit="return validasiCheckout()">
      <input type="hidden" name="userid_user" value="<?= (int)($_SESSION['id_user'] ?? 1) ?>">
      
      <!-- Input Hidden Snapshot Pelanggan untuk Kompatibilitas Database -->
      <input type="hidden" id="nama_pelanggan" name="nama_pelanggan" value="Pelanggan Walk-In">
      <input type="hidden" id="kontak_pelanggan" name="kontak_pelanggan" value="-">

      <div class="cart-head">
        <div>
          <h2>Keranjang Belanja</h2>
          <div class="order-no">Kasir Aktif: <strong><?= e($me['nama']) ?></strong></div>
        </div>
        <span class="badge info" id="cart-badge-count">0 Item (0 Unit)</span>
      </div>

      <!-- Area Pemilihan Pelanggan / Mitra PT (Revisi Poin 6) -->
      <div class="cart-customer">
        <div class="cart-customer-header">
          <label for="select_customer" class="label">Mitra PT / Pelanggan HORECA</label>
          <button type="button" class="btn-add-mitra" onclick="bukaModalMitra()" title="Daftarkan mitra usaha baru">
            <?= icon('plus', 13) ?> + Mitra Baru
          </button>
        </div>

        <div class="field field-compact">
          <select id="select_customer" name="customerid_customer" class="select" onchange="pilihMitra(this.value)">
            <option value="">-- Pelanggan Walk-In / Non-Mitra PT --</option>
            <?php foreach ($customers_arr as $c): ?>
              <option value="<?= (int)$c['id_customer'] ?>"
                      data-nama-pt="<?= e($c['nama_pt']) ?>"
                      data-pj="<?= e($c['nama_pj']) ?>"
                      data-nik="<?= e($c['nik_pj']) ?>"
                      data-telp="<?= e($c['no_telp']) ?>"
                      data-alamat="<?= e($c['alamat_toko']) ?>">
                <?= e($c['nama_pt']) ?> (PJ: <?= e($c['nama_pj']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Box Auto-Fill Informasi Mitra PT Terpilih -->
        <div id="box-detail-mitra" class="customer-meta-box is-hidden">
          <div class="customer-meta-row">
            <span class="muted text-xxs">Nama PT / Kafe:</span>
            <strong id="disp-nama-pt" class="text-xs"></strong>
          </div>
          <div class="customer-meta-row">
            <span class="muted text-xxs">Penanggung Jawab (PJ):</span>
            <span id="disp-nama-pj" class="text-xs fw-semibold"></span>
          </div>
          <div class="customer-meta-row">
            <span class="muted text-xxs">NIK PJ:</span>
            <span id="disp-nik-pj" class="text-xs mono"></span>
          </div>
          <div class="customer-meta-row">
            <span class="muted text-xxs">No. Telp / WA:</span>
            <span id="disp-no-telp" class="text-xs mono"></span>
          </div>
          <div class="customer-meta-row">
            <span class="muted text-xxs">Alamat Kedai:</span>
            <span id="disp-alamat-toko" class="text-xs"></span>
          </div>
        </div>

        <!-- Fallback Input Manual jika Pelanggan Walk-In (Non-Mitra PT) -->
        <div id="box-manual-customer">
          <div class="field field-compact">
            <label for="input_manual_nama">Nama Pelanggan Walk-In</label>
            <input type="text" id="input_manual_nama" class="input" placeholder="Contoh: Bpk. Dani (Kafe Soehat)" oninput="syncManualCustomer()">
          </div>
          <div class="field field-tight">
            <label for="input_manual_kontak">No. WhatsApp / Kontak</label>
            <input type="text" id="input_manual_kontak" class="input mono" placeholder="081234567890" maxlength="15" oninput="syncManualCustomer()">
          </div>
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

<!-- ==================== POP-UP MODAL TAMBAH MITRA PT BARU (Revisi Poin 6) ==================== -->
<div id="modal-tambah-mitra" class="modal-backdrop hidden" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-head">
      <div>
        <h2>Tambah Pelanggan / Mitra PT Baru</h2>
        <p>Daftarkan profil bisnis mitra HORECA resmi ke sistem MKP Store</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalMitra()">&times;</button>
    </div>

    <form action="<?= BASE_URL ?>modules/transactions/process/create_customer.php" method="POST">
      <div class="modal-body-pad">
        <div class="field">
          <label for="modal_nama_pt">Nama PT / Nama Usaha / Kafe *</label>
          <input type="text" id="modal_nama_pt" name="nama_pt" class="input" placeholder="Contoh: PT. Kopi Bersama / Kopi Studio 24" required>
        </div>

        <div class="form-row">
          <div class="field">
            <label for="modal_nama_pj">Penanggung Jawab (PJ) *</label>
            <input type="text" id="modal_nama_pj" name="nama_pj" class="input" placeholder="Nama lengkap PJ" required>
          </div>
          <div class="field">
            <label for="modal_nik_pj">NIK / Identitas PJ *</label>
            <input type="text" id="modal_nik_pj" name="nik_pj" class="input mono" placeholder="16 Digit NIK KTP" maxlength="20" required>
          </div>
        </div>

        <div class="field">
          <label for="modal_no_telp">No. Telepon / WhatsApp Toko *</label>
          <input type="text" id="modal_no_telp" name="no_telp" class="input mono" placeholder="Contoh: 081234567890" maxlength="20" required>
        </div>

        <div class="field">
          <label for="modal_alamat_toko">Alamat Toko / Kedai HORECA *</label>
          <textarea id="modal_alamat_toko" name="alamat_toko" class="textarea" placeholder="Alamat lengkap outlet atau kedai mitra" required></textarea>
        </div>
      </div>

      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="tutupModalMitra()">Batal</button>
        <button type="submit" class="btn btn-primary">
          <?= icon('check', 16) ?> Simpan Data Mitra
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let keranjang = [];

function formatRupiah(angka) {
  return 'Rp ' + Math.max(0, Math.round(angka)).toLocaleString('id-ID');
}

function bukaModalMitra() {
  const modal = document.getElementById('modal-tambah-mitra');
  if (modal) modal.classList.remove('hidden');
}

function tutupModalMitra() {
  const modal = document.getElementById('modal-tambah-mitra');
  if (modal) modal.classList.add('hidden');
}

function pilihMitra(idCustomer) {
  const select = document.getElementById('select_customer');
  const boxDetail = document.getElementById('box-detail-mitra');
  const boxManual = document.getElementById('box-manual-customer');
  const hNama = document.getElementById('nama_pelanggan');
  const hKontak = document.getElementById('kontak_pelanggan');

  if (!idCustomer || idCustomer === '') {
    boxDetail.classList.add('is-hidden');
    boxManual.classList.remove('is-hidden');
    syncManualCustomer();
    return;
  }

  const selectedOpt = select.options[select.selectedIndex];
  if (!selectedOpt) return;

  const namaPt = selectedOpt.getAttribute('data-nama-pt') || '';
  const pj = selectedOpt.getAttribute('data-pj') || '';
  const nik = selectedOpt.getAttribute('data-nik') || '';
  const telp = selectedOpt.getAttribute('data-telp') || '';
  const alamat = selectedOpt.getAttribute('data-alamat') || '';

  // Isi tampilan auto-fill readonly
  document.getElementById('disp-nama-pt').textContent = namaPt;
  document.getElementById('disp-nama-pj').textContent = pj;
  document.getElementById('disp-nik-pj').textContent = nik;
  document.getElementById('disp-no-telp').textContent = telp;
  document.getElementById('disp-alamat-toko').textContent = alamat;

  // Sinkronkan ke input hidden untuk disimpan di transaksi
  hNama.value = namaPt;
  hKontak.value = telp;

  boxDetail.classList.remove('is-hidden');
  boxManual.classList.add('is-hidden');
}

function syncManualCustomer() {
  const namaManual = document.getElementById('input_manual_nama').value.trim();
  const kontakManual = document.getElementById('input_manual_kontak').value.trim();
  document.getElementById('nama_pelanggan').value = namaManual || 'Pelanggan Walk-In';
  document.getElementById('kontak_pelanggan').value = kontakManual || '-';
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

// Inisialisasi otomatis jika ada parameter mitra baru dari URL
window.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const selectedCust = urlParams.get('selected_customer');
  if (selectedCust) {
    const sel = document.getElementById('select_customer');
    if (sel) {
      sel.value = selectedCust;
      pilihMitra(selectedCust);
    }
  }
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
