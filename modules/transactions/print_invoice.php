<?php
/**
 * MODUL TRANSAKSI — CETAK STRUK / INVOICE PENJUALAN
 * File: modules/transactions/print_invoice.php
 * Fungsi: Merender struk/invoice resmi berdasarkan id_transaksi dengan Multi-table JOIN.
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
    // 1. Ambil header transaksi + nama kasir
    $q_head = "SELECT t.*, u.nama AS nama_kasir
               FROM transactions t
               LEFT JOIN users u ON t.userid_user = u.id_user
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
      <?= icon('report', 18) ?> Cetak Invoice Sekarang
    </button>
  <?php endif; ?>
</div>

<?php if (!$header_trx): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div>Data nota transaksi dengan nomor <strong><?= e($id_trx ?: '-') ?></strong> tidak ditemukan di database.</div>
  </div>
<?php else: ?>
  <div class="invoice-box rise">
    <div class="invoice-head">
      <div>
        <h2>CV. MITRA KEMBAR PRADIPTA (MKP STORE)</h2>
        <p class="muted text-xs">Distributor Resmi HORECA Sirup DRIPP, Multibev, &amp; Ramoe · Kota Malang</p>
      </div>
      <div class="text-right">
        <span class="badge <?= (int)$header_trx['status_pembayaran'] === 1 ? 'success' : 'warn' ?>">
          <i class="dot"></i><?= (int)$header_trx['status_pembayaran'] === 1 ? 'LUNAS' : 'PIUTANG TEMPO' ?>
        </span>
        <div class="mono mt-2"><strong>#<?= e($header_trx['id_transaksi']) ?></strong></div>
      </div>
    </div>

    <div class="invoice-meta">
      <div>
        <div class="muted text-xs">Kepada Pelanggan / Mitra:</div>
        <strong><?= e($header_trx['nama_pelanggan'] ?: 'Pelanggan Walk-In') ?></strong>
        <div class="text-xs muted">Kontak: <?= e($header_trx['kontak_pelanggan'] ?: '-') ?></div>
      </div>
      <div class="text-right">
        <div class="muted text-xs">Waktu Transaksi:</div>
        <strong><?= e(date('d M Y · H:i WIB', strtotime($header_trx['waktu_transaksi']))) ?></strong>
        <div class="text-xs muted">Kasir: <?= e($header_trx['nama_kasir'] ?? 'Admin Kasir') ?></div>
        <?php if (!empty($header_trx['jatuh_tempo'])): ?>
          <div class="text-xs text-danger">Jatuh Tempo: <?= e($header_trx['jatuh_tempo']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-no">No</th>
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
              <td class="mono muted"><?= $no++ ?></td>
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

    <div class="divider"></div>

    <div class="kv">
      <span>Subtotal Pembelian</span>
      <strong class="mono"><?= rupiah($subtotal_kotor) ?></strong>
    </div>
    <div class="kv">
      <span>Potongan Diskon Mitra</span>
      <strong class="mono text-success">- <?= rupiah($header_trx['diskon'] ?? 0) ?></strong>
    </div>
    <div class="sum-total">
      <span>Total Tagihan Akhir</span>
      <strong class="mono"><?= rupiah($header_trx['total_harga']) ?></strong>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
