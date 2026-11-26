<?php
/**
 * MODUL TRANSAKSI — KASIR / POINT OF SALE (POS)
 * File: modules/transactions/index.php
 * Fungsi: Antarmuka Kasir POS dinamis — Best Seller, Accordion Kategori, Diskon 2 Mode,
 *         Ongkir, Sumber Transaksi, Mitra PT autofill, Modal Mitra Baru.
 * Revisi Mitra MKP Store Tahap 3 (Timothy, 25 Nov 2026):
 *   - Best Seller 7 produk terlaris 30 hari terakhir (.pos-bestseller-wrap)
 *   - Accordion katalog per kategori (.pos-cat-acc), produk A-Z
 *   - Filter kategori + pencarian (.pos-toolbar) → sembunyikan bestseller saat aktif
 *   - Diskon 2 Mode: Auto (2% jika >= 24 unit campur) & Manual (.pos-discount-switch)
 *   - Ongkir checkbox + nominal (.pos-ongkir-box)
 *   - Sumber Transaksi: Umum / Shopee (.pos-source-switch) — TERPISAH dari status_pembayaran
 *   - .pos-catalog-pane (kiri) & .pos-cart-pane (kanan) scroll independen
 * Aturan Arsitektur: PHP Native Prosedural, pg_query_params, ZERO INLINE CSS.
 */

$root       = '../../';
$page_title = 'Kasir (Point of Sale)';
$page_sub   = 'Transaksi penjualan B2B/B2C HORECA · MKP Store Malang';
$active     = 'transactions';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';

// ============================================================
// 1. Best Seller: 7 produk terlaris 30 hari terakhir
// ============================================================
$q_bestseller = "SELECT p.id_product, p.nama_barang, p.harga_jual, p.stok_aktual,
                        c.id_category, c.nama_category,
                        SUM(td.kuantitas) AS total_terjual
                 FROM transaction_details td
                 JOIN transactions t ON td.transactionsid_transaksi = t.id_transaksi
                 JOIN products p ON td.productsid_product = p.id_product
                 LEFT JOIN category c ON p.categoryid_category = c.id_category
                 WHERE t.waktu_transaksi >= CURRENT_TIMESTAMP - INTERVAL '30 days'
                 GROUP BY p.id_product, p.nama_barang, p.harga_jual, p.stok_aktual,
                          c.id_category, c.nama_category
                 ORDER BY total_terjual DESC
                 LIMIT 7";
$res_bs     = pg_query($conn, $q_bestseller);
$bestseller = [];
if ($res_bs) {
    while ($bs = pg_fetch_assoc($res_bs)) {
        $bestseller[] = $bs;
    }
}

// ============================================================
// 2. Katalog produk dikelompokkan per kategori, produk A-Z
// ============================================================
$q_katalog   = "SELECT p.id_product, p.nama_barang, p.harga_jual, p.stok_aktual,
                       c.id_category, c.nama_category
                FROM products p
                LEFT JOIN category c ON p.categoryid_category = c.id_category
                ORDER BY c.nama_category ASC NULLS LAST, p.nama_barang ASC";
$res_katalog = pg_query($conn, $q_katalog);
$katalog_per_kat = [];
$total_produk    = 0;
if ($res_katalog) {
    while ($item = pg_fetch_assoc($res_katalog)) {
        $total_produk++;
        $kat_key  = !empty($item['id_category']) ? (int)$item['id_category'] : 0;
        $kat_nama = $item['nama_category'] ?? 'Lainnya';
        if (!isset($katalog_per_kat[$kat_key])) {
            $katalog_per_kat[$kat_key] = [
                'id'     => $kat_key,
                'nama'   => $kat_nama,
                'produk' => [],
            ];
        }
        $katalog_per_kat[$kat_key]['produk'][] = $item;
    }
}

// ============================================================
// 3. Data Mitra PT dari tabel customers
// ============================================================
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

// Palet warna kartu produk (cycling)
$palet = [
    ['#F0B35A', '#B8651B'],
    ['#F9E8B6', '#D4A843'],
    ['#84D49D', '#278C48'],
    ['#A5B4FC', '#4338CA'],
    ['#F87171', '#991B1B'],
    ['#68CDDA', '#088395'],
    ['#FDE68A', '#CA8A04'],
];
?>

<!-- Notifikasi Flash -->
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
      <strong>Mitra Baru Berhasil Didaftarkan!</strong> Data mitra PT tersimpan dan langsung dapat dipilih pada keranjang.
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div>Gagal memproses: <?= e($_GET['error']) ?></div>
  </div>
<?php endif; ?>

<!-- ==================== LAYOUT UTAMA POS: 2 PANE ==================== -->
<section class="pos rise" aria-label="Area Kasir Point of Sale">

  <!-- ========== PANEL KIRI: KATALOG (.pos-catalog-pane) ========== -->
  <div class="card pos-catalog-pane">
    <div class="card-head">
      <div>
        <h2>Katalog Produk MKP Store</h2>
        <p>Pilih produk sirup / bubuk dari database untuk dimasukkan ke keranjang kasir</p>
      </div>
      <span class="badge info"><i class="dot"></i><?= $total_produk ?> Produk Aktif</span>
    </div>

    <!-- Toolbar: Pencarian + Custom Searchable Dropdown Kategori (.pos-toolbar) -->
    <div class="pos-toolbar">
      <div class="input-icon pos-search-box">
        <?= icon('search', 18) ?>
        <input
          type="text"
          id="pos-search"
          class="input"
          placeholder="Cari nama produk atau SKU..."
          oninput="onPosFilter()"
          aria-label="Cari produk"
        >
      </div>

      <!-- Custom Searchable Dropdown (Combobox) Kategori -->
      <div class="pos-cat-combo" id="pos-cat-combo">
        <input type="hidden" id="pos-filter-kat" value="">
        <div class="pos-cat-combo-control">
          <span class="pos-cat-combo-ico" aria-hidden="true"><?= icon('tag', 16) ?></span>
          <input
            type="text"
            id="pos-cat-combo-input"
            class="pos-cat-combo-input"
            placeholder="Semua Kategori"
            autocomplete="off"
            role="combobox"
            aria-label="Filter kategori produk"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="pos-cat-combo-menu"
          >
          <button
            type="button"
            id="pos-cat-combo-clear"
            class="pos-cat-combo-clear is-hidden"
            title="Reset ke Semua Kategori"
            aria-label="Reset filter kategori"
          >&times;</button>
          <button
            type="button"
            id="pos-cat-combo-toggle"
            class="pos-cat-combo-toggle"
            tabindex="-1"
            aria-label="Buka daftar kategori"
          ><?= icon('chevron', 16) ?></button>
        </div>

        <ul class="pos-cat-combo-menu is-hidden" id="pos-cat-combo-menu" role="listbox" aria-label="Daftar Kategori">
          <li
            class="pos-cat-combo-opt is-selected"
            role="option"
            aria-selected="true"
            data-value=""
            data-label="Semua Kategori"
          >
            <span class="pos-cat-combo-opt-label">Semua Kategori</span>
            <span class="pos-cat-combo-opt-count"><?= $total_produk ?> produk</span>
          </li>
          <?php foreach ($katalog_per_kat as $kat): ?>
            <li
              class="pos-cat-combo-opt"
              role="option"
              aria-selected="false"
              data-value="<?= e($kat['id']) ?>"
              data-label="<?= e($kat['nama']) ?>"
            >
              <span class="pos-cat-combo-opt-label"><?= e($kat['nama']) ?></span>
              <span class="pos-cat-combo-opt-count"><?= count($kat['produk']) ?> produk</span>
            </li>
          <?php endforeach; ?>
          <li class="pos-cat-combo-empty is-hidden" id="pos-cat-combo-empty">Kategori tidak ditemukan.</li>
        </ul>
      </div>
    </div>

    <!-- Best Seller (.pos-bestseller-wrap) -->
    <?php if (count($bestseller) > 0): ?>
    <div class="pos-bestseller-wrap" id="pos-bestseller-wrap">
      <div class="pos-bestseller-head">
        <h3 class="pos-bestseller-title">
          <?= icon('trend', 16) ?>
          <span>Produk Terlaris 30 Hari Terakhir</span>
        </h3>
        <span class="badge warn"><?= count($bestseller) ?> Teratas</span>
      </div>
      <div class="pos-bestseller-items">
        <?php foreach ($bestseller as $bi => $bs):
            $stok_bs   = (int)$bs['stok_aktual'];
            $habis_bs  = $stok_bs <= 0;
            $kritis_bs = $stok_bs > 0 && $stok_bs < 24;
            $w = $palet[$bi % count($palet)];
        ?>
          <article class="product-card col-nama-prod<?= $habis_bs ? ' out' : '' ?>"
                   data-search="<?= e(strtolower($bs['nama_barang'] . ' ' . ($bs['nama_category'] ?? '') . ' ' . $bs['id_product'])) ?>"
                   data-kat-id="<?= e($bs['id_category'] ?? '') ?>">
            <div class="product-photo">
              <span class="badge cat-tag">#<?= $bi + 1 ?> Best Seller</span>
              <span class="badge <?= $habis_bs ? 'danger' : ($kritis_bs ? 'warn' : 'success') ?>">
                <i class="dot"></i>Stok: <?= $stok_bs ?>
              </span>
              <?= bottle_svg($w[0], $w[1], substr($bs['nama_barang'], 0, 10), '760 ml', 'syrup', 'DRIPP', $bs['id_product']) ?>
            </div>
            <div class="product-body">
              <h3><?= e($bs['nama_barang']) ?></h3>
              <span class="meta"><?= (int)$bs['total_terjual'] ?> Unit Terjual</span>
              <div class="product-foot">
                <div class="price">
                  <?= rupiah($bs['harga_jual']) ?>
                  <small>/ unit</small>
                </div>
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  <?= $habis_bs ? 'disabled' : '' ?>
                  onclick="tambahKeKeranjang('<?= e(addslashes($bs['id_product'])) ?>', '<?= e(addslashes($bs['nama_barang'])) ?>', <?= (int)$bs['harga_jual'] ?>, <?= $stok_bs ?>)"
                >
                  <?= icon('plus', 14) ?> Tambah
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Katalog Accordion Per Kategori (.pos-cat-acc) -->
    <div class="pos-cat-acc-wrap" id="pos-cat-acc-wrap">
      <?php if (count($katalog_per_kat) > 0): ?>
        <?php $kat_no = 0; foreach ($katalog_per_kat as $kat_id => $kat): $kat_no++; ?>
          <div class="pos-cat-acc<?= $kat_no === 1 ? ' is-open' : '' ?>" data-kat-id="<?= e($kat_id) ?>" id="pos-cat-<?= e($kat_id) ?>">
            <div
              class="pos-cat-acc-header"
              onclick="togglePosKat('<?= e($kat_id) ?>', this)"
              onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); togglePosKat('<?= e($kat_id) ?>', this); }"
              role="button"
              tabindex="0"
              aria-expanded="<?= $kat_no === 1 ? 'true' : 'false' ?>"
            >
              <div class="pos-cat-header-left">
                <span class="pos-cat-name"><?= e($kat['nama']) ?></span>
                <span class="badge neutral pos-cat-count"><?= count($kat['produk']) ?> produk</span>
              </div>
              <span class="acc-icon" aria-hidden="true"><?= icon('chevron', 16) ?></span>
            </div>

            <div class="pos-cat-acc-body<?= $kat_no === 1 ? '' : ' is-hidden' ?>" id="pos-cat-body-<?= e($kat_id) ?>">
              <div class="product-grid">
                <?php foreach ($kat['produk'] as $idx => $p):
                    $stok   = (int)$p['stok_aktual'];
                    $habis  = $stok <= 0;
                    $kritis = $stok > 0 && $stok < 24;
                    $w = $palet[$idx % count($palet)];
                ?>
                  <article
                    class="product-card<?= $habis ? ' out' : '' ?>"
                    data-search="<?= e(strtolower($p['nama_barang'] . ' ' . ($p['nama_category'] ?? '') . ' ' . $p['id_product'])) ?>"
                    data-kat-id="<?= e($kat_id) ?>"
                  >
                    <div class="product-photo">
                      <span class="badge cat-tag"><?= e($kat['nama']) ?></span>
                      <span class="badge <?= $habis ? 'danger' : ($kritis ? 'warn' : 'success') ?>">
                        <i class="dot"></i>Stok: <?= $stok ?>
                      </span>
                      <?= bottle_svg($w[0], $w[1], substr($p['nama_barang'], 0, 10), '760 ml', 'syrup', 'DRIPP', $p['id_product']) ?>
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
                          onclick="tambahKeKeranjang('<?= e(addslashes($p['id_product'])) ?>', '<?= e(addslashes($p['nama_barang'])) ?>', <?= (int)$p['harga_jual'] ?>, <?= $stok ?>)"
                        >
                          <?= icon('plus', 15) ?> Tambah
                        </button>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="table-empty">Belum ada produk di database. Silakan tambahkan di menu Master Produk.</div>
      <?php endif; ?>
    </div>

    <!-- Pesan tidak ada hasil filter -->
    <div class="pos-no-result is-hidden" id="pos-no-result">
      <?= icon('search', 24) ?>
      <p>Tidak ada produk yang cocok dengan pencarian.</p>
    </div>
  </div><!-- /.pos-catalog-pane -->

  <!-- ========== PANEL KANAN: KERANJANG & CHECKOUT (.pos-cart-pane) ========== -->
  <aside class="card flush cart pos-cart-pane" aria-label="Keranjang Belanja Kasir">
    <form
      action="<?= BASE_URL ?>modules/transactions/process/checkout.php"
      method="POST"
      id="form-checkout"
      data-confirm="Proses transaksi penjualan sekarang?"
      onsubmit="return validasiCheckout()"
    >
      <input type="hidden" name="userid_user" value="<?= (int)($_SESSION['id_user'] ?? 1) ?>">

      <!-- Snapshot pelanggan untuk kompatibilitas DB -->
      <input type="hidden" id="nama_pelanggan"   name="nama_pelanggan"   value="Pelanggan Walk-In">
      <input type="hidden" id="kontak_pelanggan" name="kontak_pelanggan" value="-">

      <!-- Diskon final yang dikirim ke backend (diisi JS) -->
      <input type="hidden" id="hidden_diskon" name="diskon" value="0">

      <!-- Status & nominal ongkir final yang dikirim ke backend (diisi JS) -->
      <input type="hidden" id="hidden_pakai_ongkir" name="pakai_ongkir" value="0">
      <input type="hidden" id="hidden_ongkir" name="ongkir" value="0">

      <!-- Sumber transaksi aktual (diisi JS via switch) -->
      <input type="hidden" id="hidden_sumber_transaksi" name="sumber_transaksi" value="Umum">

      <!-- ---- Cart Head ---- -->
      <div class="cart-head">
        <div>
          <h2>Keranjang Belanja</h2>
          <div class="order-no">Kasir Aktif: <strong><?= e($me['nama']) ?></strong></div>
        </div>
        <span class="badge info" id="cart-badge-count">0 Item (0 Unit)</span>
      </div>

      <!-- ---- Pemilihan Mitra PT (Revisi Poin 6) ---- -->
      <div class="cart-customer">
        <div class="cart-customer-header">
          <label for="select_customer" class="label">Mitra PT / Pelanggan HORECA</label>
          <button type="button" class="btn-add-mitra" onclick="bukaModalMitra()" title="Daftarkan mitra usaha baru">
            <?= icon('plus', 13) ?> Mitra Baru
          </button>
        </div>

        <div class="field field-compact">
          <select id="select_customer" name="customerid_customer" class="select" onchange="pilihMitra(this.value)">
            <option value="">-- Pelanggan Walk-In / Non-Mitra PT --</option>
            <?php foreach ($customers_arr as $c): ?>
              <option value="<?= (int)$c['id_customer'] ?>"
                      data-nama-pt="<?= e($c['nama_pt']) ?>"
                      data-pj="<?= e($c['nama_pj']) ?>"
                      data-nik="<?= e($c['nik_pj'] ?? '') ?>"
                      data-telp="<?= e($c['no_telp']) ?>"
                      data-alamat="<?= e($c['alamat_toko']) ?>">
                <?= e($c['nama_pt']) ?> (PJ: <?= e($c['nama_pj']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Auto-fill info mitra terpilih -->
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

        <!-- Fallback input manual untuk Walk-In -->
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

      <!-- ---- Tabel Item Keranjang ---- -->
      <div class="table-wrap border-top-line pos-cart-table-wrap" id="pos-cart-table-wrap">
        <table class="table pos-cart-table">
          <colgroup>
            <col class="pos-col-item">
            <col class="pos-col-qty">
            <col class="pos-col-sub">
            <col class="pos-col-del">
          </colgroup>
          <thead>
            <tr>
              <th>Item Produk</th>
              <th class="text-center">Qty</th>
              <th class="text-right">Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="cart-tbody">
            <tr>
              <td colspan="4" class="table-empty">Keranjang masih kosong. Klik tombol <strong>+ Tambah</strong> pada katalog.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Container hidden inputs POST -->
      <div id="cart-hidden-inputs"></div>

      <!-- ---- Ringkasan Pembayaran ---- -->
      <div class="cart-summary">

        <!-- SUMBER TRANSAKSI (.pos-source-switch) — TERPISAH dari status_pembayaran -->
        <div class="field field-compact">
          <label class="label">Sumber Transaksi</label>
          <div class="pos-source-switch">
            <button type="button" class="pos-source-btn active" id="src-btn-umum" onclick="setSumberTransaksi('Umum')">
              <?= icon('users', 14) ?> Umum
            </button>
            <button type="button" class="pos-source-btn" id="src-btn-shopee" onclick="setSumberTransaksi('Shopee')">
              <?= icon('tag', 14) ?> Shopee
            </button>
          </div>
        </div>

        <!-- DISKON 2 MODE (.pos-discount-switch) -->
        <div class="field field-compact">
          <label class="label">Sistem Diskon</label>
          <div class="pos-discount-switch" role="group" aria-label="Pilih mode sistem diskon">
            <button type="button" class="switch-btn active" id="tab-disc-auto" onclick="setDiskonMode('auto')">
              Diskon Otomatis
            </button>
            <button type="button" class="switch-btn" id="tab-disc-manual" onclick="setDiskonMode('manual')">
              Diskon Manual
            </button>
          </div>
          <input type="hidden" id="diskon_mode" name="diskon_mode" value="auto">

          <!-- Mode Auto: Info box -->
          <div class="moq-hint" id="disc-auto-info">
            <?= icon('alert', 14) ?>
            <span id="disc-auto-text">Diskon <strong>2%</strong> otomatis aktif jika keranjang &ge; <strong>24 botol</strong> (campur produk).</span>
          </div>

          <!-- Mode Manual: Input box -->
          <div class="is-hidden" id="disc-manual-box">
            <div class="field field-tight">
              <label for="input_diskon_manual">Potongan Diskon Manual (Rp)</label>
              <input
                type="text"
                inputmode="numeric"
                id="input_diskon_manual"
                class="input mono"
                value=""
                placeholder="Contoh: 15.000"
                autocomplete="off"
                oninput="formatInputRibuan(this)"
              >
              <span class="muted text-xs">Setara <strong id="disc-manual-pct-val">0%</strong> dari subtotal</span>
            </div>
          </div>
        </div>

        <!-- ONGKIR (.pos-ongkir-box) -->
        <div class="pos-ongkir-box">
          <div class="pos-ongkir-row">
            <label for="cb-ongkir">
              <input type="checkbox" id="cb-ongkir" onchange="toggleOngkir()">
              Tambahkan Ongkos Kirim (Rp)
            </label>
            <span class="badge neutral" id="badge-ongkir-status">Opsional</span>
          </div>
          <div id="ongkir-input-wrap" class="is-hidden">
            <input
              type="text"
              inputmode="numeric"
              id="input_ongkir"
              class="input mono"
              value=""
              placeholder="Contoh: 30.000"
              autocomplete="off"
              oninput="formatInputRibuan(this)"
            >
          </div>
        </div>

        <!-- Status Pembayaran -->
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

        <!-- Ringkasan Total -->
        <div class="sum-row">
          <span>Subtotal Barang</span>
          <b class="mono" id="label-subtotal">Rp 0</b>
        </div>
        <div class="sum-row disc">
          <span id="label-diskon-desc">Potongan Diskon</span>
          <b class="mono" id="label-diskon">- Rp 0</b>
        </div>
        <div class="sum-row is-hidden" id="sum-row-ongkir">
          <span>Ongkos Kirim</span>
          <b class="mono" id="label-ongkir">+ Rp 0</b>
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
  </aside><!-- /.pos-cart-pane -->

</section>

<!-- ==================== POP-UP MODAL TAMBAH MITRA PT BARU ==================== -->
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
            <label for="modal_nik_pj">NIK Penanggung Jawab (Opsional)</label>
            <input type="text" id="modal_nik_pj" name="nik_pj" class="input mono" placeholder="Opsional (16 digit NIK KTP)" maxlength="20">
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
/* ================================================================
   SI-DRIPP POS — Vanilla JS State Management Keranjang + Diskon 2 Mode
   Revisi Mitra Tahap 3 (Timothy, 25 Nov 2026)
   Zero external library — Pure Vanilla JS
   ================================================================ */

var keranjang = [];

// ----------------------------------------------------------------
// Utilitas Format Rupiah
// ----------------------------------------------------------------
function formatRupiah(angka) {
  return 'Rp ' + Math.max(0, Math.round(angka)).toLocaleString('id-ID');
}

// ----------------------------------------------------------------
// Modal Mitra PT
// ----------------------------------------------------------------
function bukaModalMitra() {
  var modal = document.getElementById('modal-tambah-mitra');
  if (modal) modal.classList.remove('hidden');
}
function tutupModalMitra() {
  var modal = document.getElementById('modal-tambah-mitra');
  if (modal) modal.classList.add('hidden');
}

// ----------------------------------------------------------------
// Pilih Mitra PT dari Dropdown
// ----------------------------------------------------------------
function pilihMitra(idCustomer) {
  var select    = document.getElementById('select_customer');
  var boxDetail = document.getElementById('box-detail-mitra');
  var boxManual = document.getElementById('box-manual-customer');
  var hNama     = document.getElementById('nama_pelanggan');
  var hKontak   = document.getElementById('kontak_pelanggan');

  if (!idCustomer || idCustomer === '') {
    boxDetail.classList.add('is-hidden');
    boxManual.classList.remove('is-hidden');
    syncManualCustomer();
    return;
  }

  var opt = select.options[select.selectedIndex];
  if (!opt) return;

  var namaPt = opt.getAttribute('data-nama-pt') || '';
  var pj     = opt.getAttribute('data-pj')      || '';
  var nik    = opt.getAttribute('data-nik')      || '';
  var telp   = opt.getAttribute('data-telp')     || '';
  var alamat = opt.getAttribute('data-alamat')   || '';

  document.getElementById('disp-nama-pt').textContent     = namaPt;
  document.getElementById('disp-nama-pj').textContent     = pj;
  document.getElementById('disp-nik-pj').textContent      = nik || '-';
  document.getElementById('disp-no-telp').textContent     = telp;
  document.getElementById('disp-alamat-toko').textContent = alamat;

  hNama.value   = namaPt;
  hKontak.value = telp;

  boxDetail.classList.remove('is-hidden');
  boxManual.classList.add('is-hidden');
}

function syncManualCustomer() {
  var namaManual   = document.getElementById('input_manual_nama').value.trim();
  var kontakManual = document.getElementById('input_manual_kontak').value.trim();
  document.getElementById('nama_pelanggan').value   = namaManual   || 'Pelanggan Walk-In';
  document.getElementById('kontak_pelanggan').value = kontakManual || '-';
}

// ----------------------------------------------------------------
// Keranjang — Tambah & Ubah Qty
// ----------------------------------------------------------------
function showStockLimitWarn(nama, stokMaks) {
  var msg = 'Jumlah melebihi stok tersedia (' + stokMaks + ' unit' + (nama ? ' untuk ' + nama : '') + ').';
  if (typeof window.sidrippToast === 'function') {
    window.sidrippToast(msg, 'warn');
  } else if (typeof window.showAlertModal === 'function') {
    window.showAlertModal(msg, { title: 'Batas Stok Tersedia', tone: 'warn' });
  }
}

function tambahKeKeranjang(id, nama, harga, stokMaks) {
  var stok = Math.max(0, parseInt(stokMaks, 10) || 0);
  if (stok <= 0) {
    showStockLimitWarn(nama, 0);
    return;
  }
  var idx = -1;
  for (var i = 0; i < keranjang.length; i++) {
    if (keranjang[i].id === id) { idx = i; break; }
  }
  if (idx > -1) {
    keranjang[idx].stok     = stok;
    keranjang[idx].stokMaks = stok;
    if (keranjang[idx].qty + 1 > stok) {
      showStockLimitWarn(nama, stok);
      return;
    }
    keranjang[idx].qty += 1;
  } else {
    keranjang.push({ id: id, nama: nama, harga: harga, qty: 1, stok: stok, stokMaks: stok });
  }
  renderKeranjang();
}

function escapeHtml(str) {
  return String(str == null ? '' : str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function cekBatasQty(inputEl, id) {
  if (!inputEl) return;
  var idx = -1;
  for (var i = 0; i < keranjang.length; i++) {
    if (keranjang[i].id === id) { idx = i; break; }
  }
  if (idx === -1) return;
  var stokMaks = keranjang[idx].stok;
  var val = parseInt(inputEl.value, 10);
  if (!isNaN(val) && val > stokMaks) {
    inputEl.value = stokMaks;
    keranjang[idx].qty = stokMaks;
    showStockLimitWarn(keranjang[idx].nama, stokMaks);
    renderKeranjang();
  }
}

function setQtyManual(id, rawVal) {
  var idx = -1;
  for (var i = 0; i < keranjang.length; i++) {
    if (keranjang[i].id === id) { idx = i; break; }
  }
  if (idx === -1) return;
  var stokMaks = keranjang[idx].stok;
  var qtyBaru = parseInt(rawVal, 10);
  if (isNaN(qtyBaru) || qtyBaru <= 0) {
    keranjang.splice(idx, 1);
  } else if (qtyBaru > stokMaks) {
    showStockLimitWarn(keranjang[idx].nama, stokMaks);
    keranjang[idx].qty = stokMaks;
  } else {
    keranjang[idx].qty = qtyBaru;
  }
  renderKeranjang();
}

function ubahQty(id, delta) {
  var idx = -1;
  for (var i = 0; i < keranjang.length; i++) {
    if (keranjang[i].id === id) { idx = i; break; }
  }
  if (idx === -1) return;
  var stokMaks = keranjang[idx].stok;
  var qtyBaru  = keranjang[idx].qty + delta;
  if (qtyBaru <= 0) {
    keranjang.splice(idx, 1);
  } else if (qtyBaru > stokMaks) {
    showStockLimitWarn(keranjang[idx].nama, stokMaks);
    keranjang[idx].qty = stokMaks;
  } else {
    keranjang[idx].qty = qtyBaru;
  }
  renderKeranjang();
}

// ----------------------------------------------------------------
// Sumber Transaksi (Umum vs Shopee)
// ----------------------------------------------------------------
function setSumberTransaksi(sumber) {
  document.getElementById('hidden_sumber_transaksi').value = sumber;
  var btnUmum   = document.getElementById('src-btn-umum');
  var btnShopee = document.getElementById('src-btn-shopee');
  if (sumber === 'Shopee') {
    btnShopee.classList.add('active');
    btnUmum.classList.remove('active');
  } else {
    btnUmum.classList.add('active');
    btnShopee.classList.remove('active');
  }
}

// ----------------------------------------------------------------
// Diskon 2 Mode
// ----------------------------------------------------------------
function setDiskonMode(mode) {
  document.getElementById('diskon_mode').value = mode;
  var tabAuto   = document.getElementById('tab-disc-auto');
  var tabManual = document.getElementById('tab-disc-manual');
  var infoAuto  = document.getElementById('disc-auto-info');
  var boxManual = document.getElementById('disc-manual-box');

  if (mode === 'auto') {
    tabAuto.classList.add('active');
    tabManual.classList.remove('active');
    infoAuto.classList.remove('is-hidden');
    boxManual.classList.add('is-hidden');
  } else {
    tabManual.classList.add('active');
    tabAuto.classList.remove('active');
    boxManual.classList.remove('is-hidden');
    infoAuto.classList.add('is-hidden');
  }
  renderKeranjang();
}

function parseRibuan(val) {
  var clean = String(val == null ? '' : val).replace(/[^0-9]/g, '');
  if (!clean) return 0;
  var num = parseInt(clean, 10);
  return isNaN(num) ? 0 : Math.max(0, num);
}

function formatInputRibuan(el) {
  if (!el) return;
  var digits = String(el.value || '').replace(/[^0-9]/g, '');
  if (digits === '') {
    el.value = '';
  } else {
    var num = parseInt(digits, 10);
    el.value = isNaN(num) ? '' : num.toLocaleString('id-ID');
  }
  renderKeranjang();
}

function hitungDiskon(subtotal, totalUnit) {
  var mode = document.getElementById('diskon_mode').value;
  if (mode === 'auto') {
    return totalUnit >= 24 ? Math.round(subtotal * 0.02) : 0;
  }
  var el = document.getElementById('input_diskon_manual');
  var val = parseRibuan(el ? el.value : '0');
  return Math.min(subtotal, Math.max(0, val));
}

// ----------------------------------------------------------------
// Ongkir
// ----------------------------------------------------------------
function toggleOngkir() {
  var cb    = document.getElementById('cb-ongkir');
  var wrap  = document.getElementById('ongkir-input-wrap');
  var rowOK = document.getElementById('sum-row-ongkir');
  var badge = document.getElementById('badge-ongkir-status');
  var hPakai = document.getElementById('hidden_pakai_ongkir');
  if (cb.checked) {
    wrap.classList.remove('is-hidden');
    if (rowOK) rowOK.classList.remove('is-hidden');
    if (hPakai) hPakai.value = '1';
    if (badge) {
      badge.textContent = 'Aktif';
      badge.className = 'badge info';
    }
    var elOn = document.getElementById('input_ongkir');
    if (elOn) setTimeout(function() { elOn.focus(); }, 30);
  } else {
    wrap.classList.add('is-hidden');
    if (rowOK) rowOK.classList.add('is-hidden');
    if (hPakai) hPakai.value = '0';
    if (badge) {
      badge.textContent = 'Opsional';
      badge.className = 'badge neutral';
    }
    var el = document.getElementById('input_ongkir');
    if (el) el.value = '';
  }
  renderKeranjang();
}

// ----------------------------------------------------------------
// Render Keranjang & Hitung Total
// ----------------------------------------------------------------
function renderKeranjang() {
  var tbody      = document.getElementById('cart-tbody');
  var hiddenWrap = document.getElementById('cart-hidden-inputs');
  var badge      = document.getElementById('cart-badge-count');

  tbody.innerHTML      = '';
  hiddenWrap.innerHTML = '';

  if (keranjang.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="table-empty">Keranjang masih kosong. Klik tombol <strong>+ Tambah</strong> pada katalog.</td></tr>';
    badge.textContent = '0 Item (0 Unit)';
    document.getElementById('label-subtotal').textContent   = 'Rp 0';
    document.getElementById('label-diskon').textContent     = '- Rp 0';
    document.getElementById('label-ongkir').textContent     = '+ Rp 0';
    document.getElementById('label-grandtotal').textContent = 'Rp 0';

    var hintEl = document.getElementById('disc-auto-info');
    var textEl = document.getElementById('disc-auto-text');
    if (hintEl && textEl) {
      hintEl.classList.remove('ok');
      textEl.innerHTML = 'Diskon <strong>2%</strong> otomatis aktif jika keranjang &ge; <strong>24 botol</strong> (campur produk).';
    }

    document.getElementById('hidden_diskon').value       = 0;
    document.getElementById('hidden_pakai_ongkir').value = document.getElementById('cb-ongkir').checked ? '1' : '0';
    document.getElementById('hidden_ongkir').value       = 0;
    return;
  }

  var totalUnit = 0;
  var subtotal  = 0;

  for (var i = 0; i < keranjang.length; i++) {
    var item    = keranjang[i];
    var subItem = item.qty * item.harga;
    totalUnit  += item.qty;
    subtotal   += subItem;

    var safeId     = escapeHtml(item.id);
    var safeNama   = escapeHtml(item.nama);
    var stokTotal  = Math.max(0, parseInt(item.stok != null ? item.stok : item.stokMaks, 10) || 0);
    var sisaStok   = Math.max(0, stokTotal - item.qty);
    var stockClass = sisaStok === 0 ? 'pos-stock-empty' : (sisaStok <= 5 ? 'pos-stock-warn' : 'pos-stock-ok');
    var isMaxQty   = item.qty >= stokTotal;
    var plusAttrs  = isMaxQty
      ? ' disabled title="Sudah mencapai batas maksimal stok (' + stokTotal + ')"'
      : ' title="Tambah jumlah"';

    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td class="pos-cart-col-name">' +
        '<strong>' + safeNama + '</strong>' +
        '<div class="muted text-xxs">' + formatRupiah(item.harga) + ' / unit</div>' +
        '<div class="pos-item-stock ' + stockClass + '">Stok: ' + stokTotal + ' (Sisa: ' + sisaStok + ')</div>' +
      '</td>' +
      '<td class="text-center mono pos-cart-col-qty">' +
        '<div class="pos-qty-stepper">' +
          '<button type="button" class="btn btn-ghost btn-sm" onclick="ubahQty(\'' + safeId + '\', -1)" title="Kurangi jumlah" aria-label="Kurangi jumlah">&minus;</button>' +
          '<input type="number" class="pos-qty-input mono" value="' + item.qty + '" min="1" max="' + stokTotal + '" oninput="cekBatasQty(this, \'' + safeId + '\')" onchange="setQtyManual(\'' + safeId + '\', this.value)" aria-label="Jumlah ' + safeNama + '">' +
          '<button type="button" class="btn btn-ghost btn-sm" onclick="ubahQty(\'' + safeId + '\', 1)" aria-label="Tambah jumlah"' + plusAttrs + '>&plus;</button>' +
        '</div>' +
      '</td>' +
      '<td class="text-right mono pos-cart-col-sub"><strong>' + formatRupiah(subItem) + '</strong></td>' +
      '<td class="text-right pos-cart-col-del"><button type="button" class="btn btn-danger-soft btn-sm" onclick="ubahQty(\'' + safeId + '\', -9999)" title="Hapus item" aria-label="Hapus item">&times;</button></td>';
    tbody.appendChild(tr);

    hiddenWrap.innerHTML +=
      '<input type="hidden" name="product_id[]" value="' + safeId + '">' +
      '<input type="hidden" name="kuantitas[]" value="' + item.qty + '">' +
      '<input type="hidden" name="harga[]" value="' + item.harga + '">';
  }

  // Hitung & update diskon
  var diskon = hitungDiskon(subtotal, totalUnit);

  var hintEl = document.getElementById('disc-auto-info');
  var textEl = document.getElementById('disc-auto-text');
  if (hintEl && textEl && document.getElementById('diskon_mode').value === 'auto') {
    if (totalUnit >= 24) {
      hintEl.classList.add('ok');
      textEl.innerHTML = '<strong>Selamat!</strong> Diskon 2% aktif &mdash; Hemat ' + formatRupiah(diskon) + ' (' + totalUnit + ' botol)';
    } else {
      hintEl.classList.remove('ok');
      var sisa = 24 - totalUnit;
      textEl.innerHTML = 'Tambah <strong>' + sisa + ' botol</strong> lagi untuk dapat diskon otomatis <strong>2%</strong> (' + totalUnit + '/24 botol).';
    }
  }

  var pctEl = document.getElementById('disc-manual-pct-val');
  if (pctEl) {
    pctEl.textContent = subtotal > 0 ? (diskon / subtotal * 100).toFixed(1) + '%' : '0%';
  }

  var descEl = document.getElementById('label-diskon-desc');
  if (descEl) {
    var mode = document.getElementById('diskon_mode').value;
    descEl.textContent = mode === 'auto'
      ? 'Diskon Otomatis (2%)' + (totalUnit >= 24 ? ' ✓' : '')
      : 'Diskon Manual';
  }

  // Ongkir
  var pakai_ongkir = document.getElementById('cb-ongkir').checked;
  var ongkir = 0;
  if (pakai_ongkir) {
    var ongkirEl = document.getElementById('input_ongkir');
    ongkir = parseRibuan(ongkirEl ? ongkirEl.value : '0');
  }

  var grandTotal = Math.max(0, subtotal - diskon + ongkir);

  badge.textContent = keranjang.length + ' Item (' + totalUnit + ' Unit)';
  document.getElementById('label-subtotal').textContent   = formatRupiah(subtotal);
  document.getElementById('label-diskon').textContent     = '- ' + formatRupiah(diskon);
  document.getElementById('label-ongkir').textContent     = '+ ' + formatRupiah(ongkir);
  document.getElementById('label-grandtotal').textContent = formatRupiah(grandTotal);

  document.getElementById('hidden_diskon').value       = diskon;
  document.getElementById('hidden_pakai_ongkir').value = pakai_ongkir ? '1' : '0';
  document.getElementById('hidden_ongkir').value       = ongkir;
}

// ----------------------------------------------------------------
// Status Pembayaran: Toggle Jatuh Tempo
// ----------------------------------------------------------------
function toggleJatuhTempo() {
  var status = document.getElementById('status_pembayaran').value;
  var box    = document.getElementById('box-jatuh-tempo');
  if (status === '2') {
    box.classList.remove('is-hidden');
  } else {
    box.classList.add('is-hidden');
  }
}

// ----------------------------------------------------------------
// Filter Katalog + Best Seller
// ----------------------------------------------------------------
function onPosFilter() {
  var q         = (document.getElementById('pos-search').value || '').toLowerCase().trim();
  var katFilter = document.getElementById('pos-filter-kat').value;
  var filterAktif = (q !== '' || katFilter !== '');

  var bsWrap = document.getElementById('pos-bestseller-wrap');
  if (bsWrap) {
    if (filterAktif) {
      bsWrap.classList.add('is-hidden');
    } else {
      bsWrap.classList.remove('is-hidden');
    }
  }

  var totalVisible = 0;
  var accItems     = document.querySelectorAll('.pos-cat-acc');

  for (var i = 0; i < accItems.length; i++) {
    var acc   = accItems[i];
    var katId = acc.getAttribute('data-kat-id');
    var katMatch = (katFilter === '' || katFilter === katId);

    var produk   = acc.querySelectorAll('.product-card');
    var visCount = 0;

    for (var j = 0; j < produk.length; j++) {
      var card    = produk[j];
      var teks    = card.getAttribute('data-search') || '';
      var prodMatch = (!q || teks.indexOf(q) > -1);
      if (katMatch && prodMatch) {
        card.classList.remove('is-hidden');
        visCount++;
        totalVisible++;
      } else {
        card.classList.add('is-hidden');
      }
    }

    if (visCount > 0) {
      acc.classList.remove('is-hidden');
      if (filterAktif) {
        var body = document.getElementById('pos-cat-body-' + katId);
        if (body) body.classList.remove('is-hidden');
        acc.classList.add('is-open');
        var hdr = acc.querySelector('.pos-cat-acc-header');
        if (hdr) hdr.setAttribute('aria-expanded', 'true');
      }
    } else {
      acc.classList.add('is-hidden');
    }
  }

  var noResult = document.getElementById('pos-no-result');
  if (noResult) {
    if (totalVisible === 0 && filterAktif) {
      noResult.classList.remove('is-hidden');
    } else {
      noResult.classList.add('is-hidden');
    }
  }
}

// ----------------------------------------------------------------
// Custom Searchable Dropdown (Combobox) Kategori
// ----------------------------------------------------------------
function initPosKatCombobox() {
  var combo  = document.getElementById('pos-cat-combo');
  var hidden = document.getElementById('pos-filter-kat');
  var input  = document.getElementById('pos-cat-combo-input');
  var clear  = document.getElementById('pos-cat-combo-clear');
  var toggle = document.getElementById('pos-cat-combo-toggle');
  var menu   = document.getElementById('pos-cat-combo-menu');
  var empty  = document.getElementById('pos-cat-combo-empty');
  if (!combo || !hidden || !input || !menu) return;

  var options = Array.prototype.slice.call(menu.querySelectorAll('.pos-cat-combo-opt'));
  var activeIndex = -1;
  var selectedLabel = '';

  function updateClearBtn() {
    if (!clear) return;
    if (hidden.value !== '' || input.value.trim() !== '') {
      clear.classList.remove('is-hidden');
    } else {
      clear.classList.add('is-hidden');
    }
  }

  function getVisibleOptions() {
    return options.filter(function(opt) {
      return !opt.classList.contains('is-hidden');
    });
  }

  function highlightOption(idx) {
    var vis = getVisibleOptions();
    options.forEach(function(o) { o.classList.remove('is-focused'); });
    if (vis.length === 0) {
      activeIndex = -1;
      return;
    }
    if (idx < 0) idx = vis.length - 1;
    if (idx >= vis.length) idx = 0;
    activeIndex = idx;
    vis[activeIndex].classList.add('is-focused');
    vis[activeIndex].scrollIntoView({ block: 'nearest' });
  }

  function filterOptions(keyword) {
    var term = (keyword || '').toLowerCase().trim();
    var visibleCount = 0;
    options.forEach(function(opt) {
      var lbl = (opt.getAttribute('data-label') || '').toLowerCase();
      var val = opt.getAttribute('data-value') || '';
      var match = (!term || lbl.indexOf(term) > -1 || (val === '' && 'semua'.indexOf(term) > -1));
      if (match) {
        opt.classList.remove('is-hidden');
        visibleCount++;
      } else {
        opt.classList.add('is-hidden');
      }
    });
    if (empty) {
      if (visibleCount === 0) {
        empty.classList.remove('is-hidden');
      } else {
        empty.classList.add('is-hidden');
      }
    }
    activeIndex = -1;
    options.forEach(function(o) { o.classList.remove('is-focused'); });
  }

  function openMenu(showAll) {
    if (showAll) filterOptions('');
    menu.classList.remove('is-hidden');
    combo.classList.add('is-open');
    input.setAttribute('aria-expanded', 'true');
  }

  function closeMenu(restoreLabel) {
    menu.classList.add('is-hidden');
    combo.classList.remove('is-open');
    input.setAttribute('aria-expanded', 'false');
    activeIndex = -1;
    options.forEach(function(o) { o.classList.remove('is-focused'); });
    if (restoreLabel) {
      input.value = hidden.value === '' ? '' : selectedLabel;
      updateClearBtn();
    }
  }

  function selectOption(opt) {
    if (!opt) return;
    var val = opt.getAttribute('data-value') || '';
    var lbl = opt.getAttribute('data-label') || 'Semua Kategori';
    hidden.value = val;
    selectedLabel = val === '' ? '' : lbl;
    input.value = selectedLabel;

    options.forEach(function(o) {
      var isSel = (o === opt);
      o.classList.toggle('is-selected', isSel);
      o.setAttribute('aria-selected', isSel ? 'true' : 'false');
    });

    updateClearBtn();
    closeMenu(false);
    onPosFilter();
  }

  input.addEventListener('focus', function() {
    openMenu(true);
    if (input.value !== '') {
      input.select();
    }
  });

  input.addEventListener('click', function() {
    if (menu.classList.contains('is-hidden')) {
      openMenu(true);
    }
  });

  input.addEventListener('input', function() {
    openMenu(false);
    filterOptions(input.value);
    updateClearBtn();
    if (input.value.trim() === '' && hidden.value !== '') {
      selectOption(options[0]);
      openMenu(true);
    }
  });

  input.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (menu.classList.contains('is-hidden')) openMenu(true);
      highlightOption(activeIndex + 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (menu.classList.contains('is-hidden')) openMenu(true);
      highlightOption(activeIndex - 1);
    } else if (e.key === 'Enter') {
      if (!menu.classList.contains('is-hidden')) {
        e.preventDefault();
        var vis = getVisibleOptions();
        if (activeIndex >= 0 && vis[activeIndex]) {
          selectOption(vis[activeIndex]);
        } else if (vis.length > 0) {
          selectOption(vis[0]);
        }
      }
    } else if (e.key === 'Escape') {
      closeMenu(true);
      input.blur();
    }
  });

  if (toggle) {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      if (menu.classList.contains('is-hidden')) {
        input.focus();
        openMenu(true);
      } else {
        closeMenu(true);
      }
    });
  }

  if (clear) {
    clear.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      selectOption(options[0]);
      input.focus();
    });
  }

  options.forEach(function(opt) {
    opt.addEventListener('mousedown', function(e) {
      e.preventDefault();
      selectOption(opt);
    });
  });

  document.addEventListener('click', function(e) {
    if (!combo.contains(e.target) && !menu.classList.contains('is-hidden')) {
      closeMenu(true);
    }
  });

  updateClearBtn();
}

// ----------------------------------------------------------------
// Toggle Accordion Kategori
// ----------------------------------------------------------------
function togglePosKat(katId, headerEl) {
  var acc  = document.getElementById('pos-cat-' + katId);
  var body = document.getElementById('pos-cat-body-' + katId);
  if (!body) return;
  var sedangTerbuka = !body.classList.contains('is-hidden');
  if (sedangTerbuka) {
    body.classList.add('is-hidden');
    if (acc) acc.classList.remove('is-open');
    if (headerEl) headerEl.setAttribute('aria-expanded', 'false');
  } else {
    body.classList.remove('is-hidden');
    if (acc) acc.classList.add('is-open');
    if (headerEl) headerEl.setAttribute('aria-expanded', 'true');
  }
}

// ----------------------------------------------------------------
// Validasi sebelum submit
// ----------------------------------------------------------------
function validasiCheckout() {
  if (keranjang.length === 0) {
    if (typeof window.showAlertModal === 'function') {
      window.showAlertModal('Pilih minimal 1 produk ke dalam keranjang sebelum memproses pembayaran!', {
        title: 'Keranjang Masih Kosong',
        tone: 'warn',
        focusEl: '#pos-search'
      });
    } else if (typeof window.sidrippToast === 'function') {
      window.sidrippToast('Pilih minimal 1 produk ke dalam keranjang sebelum memproses pembayaran!', 'warn');
    }
    return false;
  }
  renderKeranjang(); // Pastikan hidden inputs tersinkron
  return true;
}

// ----------------------------------------------------------------
// Inisialisasi DOMContentLoaded
// ----------------------------------------------------------------
window.addEventListener('DOMContentLoaded', function() {
  // Auto-pilih mitra dari URL param (setelah daftar mitra baru berhasil)
  var urlParams    = new URLSearchParams(window.location.search);
  var selectedCust = urlParams.get('selected_customer');
  if (selectedCust) {
    var sel = document.getElementById('select_customer');
    if (sel) {
      sel.value = selectedCust;
      pilihMitra(selectedCust);
    }
  }

  initPosKatCombobox();
  renderKeranjang();
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
