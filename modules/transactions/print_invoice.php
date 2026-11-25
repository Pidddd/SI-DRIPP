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
?>
  <div class="invoice-box rise">
    <!-- Header Invoice Fisik MKP Store -->
    <div class="invoice-header">
      <div class="invoice-brand">
        <img src="<?= BASE_URL ?>assets/img/Logo.jpeg" alt="Logo MKP Store" class="invoice-logo">
        <div class="invoice-brand-text">
          <h2>CV. MITRA KEMBAR PRADIPTA</h2>
          <div class="invoice-company-sub">MKP STORE MALANG · Distributor Resmi Sirup HORECA DRIPP, Multibev &amp; Ramoe</div>
          <div class="invoice-company-contact">Jl. Moch Juki No. 40 Malang · Telp: 081237569188 / 08999452617 · Email: cvmitrakembarpradipta@gmail.com</div>
        </div>
      </div>
      <div class="invoice-head-meta">
        <div class="invoice-title">INVOICE PENJUALAN</div>
        <div class="invoice-no mono">#<?= e($header_trx['id_transaksi']) ?></div>
        <div class="invoice-badges">
          <span class="badge <?= $is_lunas ? 'success' : 'warn' ?>">
            <i class="dot"></i><?= $is_lunas ? 'LUNAS' : 'PIUTANG TEMPO' ?>
          </span>
          <span class="badge info">
            <i class="dot"></i><?= e($sumber_trx) ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Metadata Invoice: Detail Pelanggan / Mitra PT & Info Transaksi -->
    <div class="invoice-meta-grid">
      <div class="invoice-meta-col">
        <div class="invoice-meta-label">Kepada Pelanggan / Mitra PT:</div>
        <div class="invoice-meta-value"><?= e($nama_pelanggan) ?></div>
        <?php if (!empty($header_trx['nama_pj'])): ?>
          <div class="text-xs">PJ: <strong><?= e($header_trx['nama_pj']) ?></strong><?php if (!empty($header_trx['nik_pj'])): ?> (NIK: <?= e($header_trx['nik_pj']) ?>)<?php endif; ?></div>
        <?php endif; ?>
        <div class="text-xs muted">Kontak / Telp: <?= e($kontak_pelanggan) ?></div>
        <?php if (!empty($header_trx['alamat_toko'])): ?>
          <div class="text-xs muted">Alamat Toko: <?= e($header_trx['alamat_toko']) ?></div>
        <?php endif; ?>
      </div>

      <div class="invoice-meta-col text-right">
        <div class="invoice-meta-label">Informasi Faktur:</div>
        <div class="text-xs">Waktu Transaksi: <strong><?= e($waktu_trx_fmt) ?></strong></div>
        <div class="text-xs muted">Kasir / Operator: <strong><?= e($header_trx['nama_kasir'] ?? 'Admin Kasir') ?></strong></div>
        <div class="text-xs muted">Sumber Transaksi: <span class="mono font-semibold"><?= e($sumber_trx) ?></span></div>
        <?php if (!empty($header_trx['jatuh_tempo'])): ?>
          <div class="text-xs text-danger font-bold mt-1">Jatuh Tempo: <?= e(date('d M Y', strtotime($header_trx['jatuh_tempo']))) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabel Rincian Produk Belanja -->
    <div class="invoice-table-wrap">
      <table class="invoice-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th>Deskripsi Produk</th>
            <th class="text-right">Harga Satuan</th>
            <th class="text-center">Qty</th>
            <th class="text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $subtotal_kotor = 0;
          foreach ($detail_items as $item):
              $subtotal_kotor += (int)$item['subtotal_harga'];
          ?>
            <tr>
              <td class="mono muted text-center"><?= $no++ ?></td>
              <td>
                <strong><?= e($item['nama_barang']) ?></strong>
                <div class="muted text-xs">SKU: <?= e($item['id_product']) ?> · <?= e($item['nama_category'] ?? 'Umum') ?></div>
              </td>
              <td class="text-right mono"><?= rupiah($item['harga_jual']) ?></td>
              <td class="text-center mono"><strong><?= (int)$item['kuantitas'] ?></strong></td>
              <td class="text-right mono"><strong><?= rupiah($item['subtotal_harga']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Bagian Bawah: Terbilang, Bank, Retur & Kalkulasi Total -->
    <div class="invoice-bottom-grid">
      <div class="invoice-info-pane">
        <div class="invoice-terbilang">
          <strong>Terbilang:</strong> <em># <?= e($terbilang_teks) ?> #</em>
        </div>

        <div class="invoice-bank-box">
          <div class="invoice-bank-title">Rekening Pembayaran Resmi:</div>
          <div>BCA: <strong>8161762920</strong> a.n. Mia Setyaningpala</div>
          <div>BRI: <strong>0368.01.0261.28.501</strong> a.n. Mia Setyaningpala</div>
        </div>

        <div class="invoice-retur-note">
          *Barang yang sudah dibeli tidak dapat dikembalikan atau ditukar kembali
        </div>
      </div>

      <div class="invoice-calc-pane">
        <div class="invoice-calc-row">
          <span>Subtotal Pembelian</span>
          <strong class="mono"><?= rupiah($subtotal_kotor) ?></strong>
        </div>
        <div class="invoice-calc-row">
          <span>Potongan Diskon</span>
          <strong class="mono text-success">- <?= rupiah($diskon) ?></strong>
        </div>
        <div class="invoice-calc-row">
          <span>Ongkos Kirim</span>
          <strong class="mono"><?= rupiah($ongkir) ?></strong>
        </div>
        <div class="invoice-calc-total">
          <span>Total Tagihan Akhir</span>
          <strong class="mono"><?= rupiah($total_harga) ?></strong>
        </div>
      </div>
    </div>

    <!-- Kolom Tanda Tangan Penerima & Pengirim -->
    <div class="invoice-signatures">
      <div class="invoice-sign-box">
        <div class="invoice-sign-title">Penerima</div>
        <div class="invoice-sign-space"></div>
        <div class="invoice-sign-name">_____________________________</div>
      </div>
      <div class="invoice-sign-box">
        <div class="invoice-sign-title">CV. MITRA KEMBAR PRADIPTA</div>
        <div class="invoice-sign-space"></div>
        <div class="invoice-sign-name">______________________________</div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
