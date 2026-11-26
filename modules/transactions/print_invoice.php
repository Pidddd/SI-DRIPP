<?php
/**
 * MODUL TRANSAKSI — CETAK STRUK / INVOICE PENJUALAN
 * File: modules/transactions/print_invoice.php
 * Fungsi: Merender struk/invoice resmi berdasarkan id_transaksi dengan Multi-table JOIN (transactions + users + customers + details + products).
 * Standar Mitra MKP Store (A5 Landscape):
 *   - Logo resmi MKP Store: assets/img/Logo.jpeg (Huruf L kapital)
 *   - Identitas Toko & Kontak: Jl. Moch Juki No. 40 Malang | Telp: 081237569188 / 08999452617 | Email: cvmitrakembarpradipta@gmail.com
 *   - Penanda Sumber Transaksi & Rincian Ongkos Kirim (Ongkir)
 *   - Blok Terbilang Rupiah Otomatis (terbilang_rupiah)
 *   - Informasi Rekening Pembayaran Transfer (BCA & BRI a.n. Mia Setyaningpala)
 *   - Catatan Retur Barang & Kolom Tanda Tangan Dual (Penerima & Pengirim)
 *   - Format Waktu Standar WIB Bebas Mikrodetik (format_waktu_wib)
 *   - WAJIB ZERO INLINE CSS (Semua styling diatur via assets/css/style.css)
 */

$root       = '../../';
$page_title = 'Cetak Nota Transaksi';
$page_sub   = 'Bukti pembayaran resmi CV. Mitra Kembar Pradipta (MKP Store Malang)';
$active     = 'tx_history';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';

$id_trx = trim($_GET['id'] ?? '');
$header_trx = null;
$detail_items = [];

if ($id_trx !== '') {
    // 1. Ambil header transaksi + nama kasir + data pelanggan/mitra PT (Revisi Poin 6)
    $q_head = "SELECT t.*, u.nama AS nama_kasir,
                      c.nama_pt, c.nama_pj, c.nik_pj, c.no_telp AS telp_pt, c.alamat_toko
               FROM transactions t
               LEFT JOIN users u ON t.userid_user = u.id_user
               LEFT JOIN customers c ON t.customerid_customer = c.id_customer
               WHERE t.id_transaksi = $1";
    $res_head = pg_query_params($conn, $q_head, [$id_trx]);
    if ($res_head && pg_num_rows($res_head) > 0) {
        $header_trx = pg_fetch_assoc($res_head);
    }

    // 2. Ambil rincian barang dengan Multi-table JOIN (transaction_details + products + category)
    $q_det = "SELECT td.kuantitas, td.subtotal_harga, p.id_product, p.nama_barang, p.harga_jual, c.nama_category
              FROM transaction_details td
              JOIN products p ON td.productsid_product = p.id_product
              LEFT JOIN category c ON p.categoryid_category = c.id_category
              WHERE td.transactionsid_transaksi = $1
              ORDER BY td.id_detail ASC";
    $res_det = pg_query_params($conn, $q_det, [$id_trx]);
    if ($res_det) {
        while ($d = pg_fetch_assoc($res_det)) {
            $detail_items[] = $d;
        }
    }
}
?>

<div class="page-actions no-print rise">
  <a href="<?= BASE_URL ?>modules/transactions/history.php" class="btn btn-ghost">
    &larr; Kembali ke Riwayat Transaksi
  </a>
  <div class="grow"></div>
  <?php if ($header_trx): ?>
    <button type="button" class="btn btn-primary" onclick="window.print()">
      <?= icon('report', 18) ?> Cetak Invoice (A5 Landscape)
    </button>
  <?php endif; ?>
</div>

<?php if (!$header_trx): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div>Data nota transaksi dengan nomor <strong><?= e($id_trx ?: '-') ?></strong> tidak ditemukan di database.</div>
  </div>
<?php else:
  $is_lunas         = (int)$header_trx['status_pembayaran'] === 1;
  $sumber_trx       = $header_trx['sumber_transaksi'] ?? 'Umum';
  $ongkir           = (int)($header_trx['ongkir'] ?? 0);
  $diskon           = (int)($header_trx['diskon'] ?? 0);
  $total_harga      = (int)($header_trx['total_harga'] ?? 0);
  $nama_pelanggan   = $header_trx['nama_pt'] ?: ($header_trx['nama_pelanggan'] ?: 'Pelanggan Walk-In');
  $kontak_pelanggan = $header_trx['kontak_pelanggan'] ?: ($header_trx['telp_pt'] ?? '-');
  $waktu_trx_fmt    = format_waktu_wib($header_trx['waktu_transaksi']);
  $terbilang_teks   = terbilang_rupiah($total_harga);

  $subtotal_kotor = 0;
  foreach ($detail_items as $di_row) {
      $subtotal_kotor += (int)$di_row['subtotal_harga'];
  }
  $diskon_pct_label = '';
  if ($diskon > 0 && $subtotal_kotor > 0) {
      $pct_val = ($diskon / $subtotal_kotor) * 100;
      $diskon_pct_label = ' (' . (abs($pct_val - round($pct_val)) < 0.05 ? (int)round($pct_val) : number_format($pct_val, 1, ',', '.')) . '%)';
  }
?>
  <div class="invoice-box invoice-a5-sheet rise">
    <!-- Kop Surat & Info Customer 2 Kolom Sejajar (Standar Nota Fisik A5 Landscape MKP Store) -->
    <div class="invoice-top-band">
      <div class="invoice-brand-col">
        <img src="<?= BASE_URL ?>assets/img/Logo.jpeg" alt="Logo MKP Store" class="invoice-logo">
        <div class="invoice-brand-text">
          <h2>CV. MITRA KEMBAR PRADIPTA</h2>
          <div class="invoice-company-sub">MKP STORE MALANG &middot; Distributor Sirup HORECA DRIPP, Multibev &amp; Ramoe</div>
          <div class="invoice-company-contact">Jl. Moch Juki No. 40 Malang &middot; Telp: 081237569188 / 08999452617</div>
          <div class="invoice-company-contact">Email: cvmitrakembarpradipta@gmail.com &middot; Kasir: <strong><?= e($header_trx['nama_kasir'] ?? 'Admin Kasir') ?></strong></div>
        </div>
      </div>

      <div class="invoice-cust-col">
        <div class="invoice-cust-title-row">
          <div class="invoice-title-group">
            <span class="invoice-title">INVOICE</span>
            <span class="invoice-no mono">#<?= e($header_trx['id_transaksi']) ?></span>
          </div>
          <div class="invoice-badges">
            <span class="badge <?= $is_lunas ? 'success' : 'warn' ?>">
              <i class="dot"></i><?= $is_lunas ? 'LUNAS' : 'PIUTANG TOP' ?>
            </span>
            <span class="badge info">
              <i class="dot"></i><?= e($sumber_trx) ?>
            </span>
          </div>
        </div>

        <div class="invoice-cust-meta">
          <div class="invoice-kv-line">
            <span class="invoice-kv-k">Tanggal</span>
            <span class="invoice-kv-v">
              <strong><?= e($waktu_trx_fmt) ?></strong>
              <?php if (!$is_lunas && !empty($header_trx['jatuh_tempo'])): ?>
                &middot; <span class="text-danger">JT: <?= e(date('d M Y', strtotime($header_trx['jatuh_tempo']))) ?></span>
              <?php endif; ?>
            </span>
          </div>
          <div class="invoice-kv-line">
            <span class="invoice-kv-k">Customer</span>
            <span class="invoice-kv-v">
              <strong><?= e($nama_pelanggan) ?></strong>
              <?php if (!empty($header_trx['nama_pj'])): ?>
                (PJ: <?= e($header_trx['nama_pj']) ?>)
              <?php endif; ?>
            </span>
          </div>
          <div class="invoice-kv-line">
            <span class="invoice-kv-k">Alamat/Telp</span>
            <span class="invoice-kv-v">
              <?php if (!empty($header_trx['alamat_toko'])): ?><?= e($header_trx['alamat_toko']) ?> &middot; <?php endif; ?><?= e($kontak_pelanggan) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Rincian Produk 6 Kolom 1 Baris Tipis per Item (Muat 13-15+ Item per Lembar A5) -->
    <div class="invoice-table-wrap">
      <table class="invoice-table invoice-table-compact">
        <colgroup>
          <col class="inv-col-no">
          <col class="inv-col-sku">
          <col class="inv-col-nama">
          <col class="inv-col-harga">
          <col class="inv-col-qty">
          <col class="inv-col-total">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">NO</th>
            <th>SKU</th>
            <th>NAMA BARANG</th>
            <th class="text-right">HARGA / BOTOL</th>
            <th class="text-center">QTY</th>
            <th class="text-right">TOTAL</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          foreach ($detail_items as $item):
          ?>
            <tr>
              <td class="mono text-center"><?= $no++ ?></td>
              <td class="mono inv-cell-nowrap"><?= e($item['id_product']) ?></td>
              <td class="inv-cell-name"><?= e($item['nama_barang']) ?></td>
              <td class="text-right mono inv-cell-nowrap"><?= rupiah($item['harga_jual']) ?></td>
              <td class="text-center mono inv-cell-nowrap"><strong><?= (int)$item['kuantitas'] ?></strong></td>
              <td class="text-right mono inv-cell-nowrap"><strong><?= rupiah($item['subtotal_harga']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Blok Footer Terpadu (Terbilang, Rekening, Ringkasan Total & Tanda Tangan — Anti Terpotong) -->
    <div class="invoice-footer-block">
      <div class="invoice-bottom-grid">
        <div class="invoice-info-pane">
          <div class="invoice-terbilang">
            <strong>Terbilang:</strong> <em># <?= e($terbilang_teks) ?> #</em>
          </div>

          <div class="invoice-bank-box">
            <span class="invoice-bank-title">Transfer Resmi:</span>
            <span>BCA <strong>8161762920</strong> &middot; BRI <strong>0368.01.0261.28.501</strong> (a.n. Mia Setyaningpala)</span>
          </div>

          <div class="invoice-retur-note">
            *Barang yang sudah dibeli tidak dapat dikembalikan atau ditukar kembali
          </div>
        </div>

        <div class="invoice-calc-pane">
          <div class="invoice-calc-row">
            <span>Subtotal</span>
            <strong class="mono"><?= rupiah($subtotal_kotor) ?></strong>
          </div>
          <div class="invoice-calc-row">
            <span>Diskon<?= e($diskon_pct_label) ?></span>
            <strong class="mono text-success">- <?= rupiah($diskon) ?></strong>
          </div>
          <div class="invoice-calc-row">
            <span>Ongkos Kirim</span>
            <strong class="mono"><?= rupiah($ongkir) ?></strong>
          </div>
          <div class="invoice-calc-total">
            <span>Total Tagihan</span>
            <strong class="mono"><?= rupiah($total_harga) ?></strong>
          </div>
        </div>
      </div>

      <!-- Kolom Tanda Tangan Penerima & Pengirim -->
      <div class="invoice-signatures">
        <div class="invoice-sign-box">
          <div class="invoice-sign-title">Penerima</div>
          <div class="invoice-sign-space"></div>
          <div class="invoice-sign-name">( ...................................... )</div>
        </div>
        <div class="invoice-sign-box">
          <div class="invoice-sign-title">Pengirim &middot; CV. MITRA KEMBAR PRADIPTA</div>
          <div class="invoice-sign-space"></div>
          <div class="invoice-sign-name">( ...................................... )</div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
