<?php
/**
 * MODUL TRANSAKSI — RIWAYAT TRANSAKSI PENJUALAN
 * File: modules/transactions/history.php
 * Fungsi: Menampilkan arsip nota penjualan dari Supabase menggunakan Multi-table JOIN.
 */

$root       = '../../';
$page_title = 'Riwayat Transaksi';
$page_sub   = 'Arsip nota penjualan B2B/B2C & pencetakan ulang struk · MKP Store Malang';
$active     = 'tx_history';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';

// Query Multi-table JOIN (transactions + users + subquery jumlah item)
$q_history = "SELECT t.id_transaksi, t.waktu_transaksi, t.nama_pelanggan, t.kontak_pelanggan,
                     t.total_harga, t.diskon, t.status_pembayaran, u.nama AS nama_kasir,
                     (SELECT COALESCE(SUM(td.kuantitas), 0)
                      FROM transaction_details td
                      WHERE td.transactionsid_transaksi = t.id_transaksi) AS total_botol
              FROM transactions t
              LEFT JOIN users u ON t.userid_user = u.id_user
              ORDER BY t.waktu_transaksi DESC";
$res_history   = pg_query($conn, $q_history);
$total_riwayat = $res_history ? pg_num_rows($res_history) : 0;
?>

<!-- Bar Aksi & Info -->
<div class="page-actions rise">
  <div>
    <span class="badge info"><i class="dot"></i><?= (int)$total_riwayat ?> Transaksi Tercatat di Database</span>
  </div>
  <div class="grow"></div>
  <a href="<?= BASE_URL ?>modules/transactions/index.php" class="btn btn-primary">
    <?= icon('cart', 18) ?> Transaksi Kasir Baru
  </a>
</div>

<!-- Tabel Riwayat Transaksi Dinamis -->
<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Daftar Riwayat Transaksi Penjualan</h2>
      <p>Klik tombol Cetak Nota pada kolom aksi untuk membuka halaman struk invoice resmi</p>
    </div>
    <span class="badge neutral">Data Live Supabase</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No Invoice</th>
          <th>Tanggal</th>
          <th>Pelanggan</th>
          <th>Kasir</th>
          <th>Status Bayar</th>
          <th class="text-right">Total Tagihan</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res_history && $total_riwayat > 0): ?>
          <?php while ($trx = pg_fetch_assoc($res_history)):
              $lunas = (int)$trx['status_pembayaran'] === 1;
          ?>
            <tr>
              <td class="mono"><strong>#<?= e($trx['id_transaksi']) ?></strong></td>
              <td class="nowrap"><?= e(date('d M Y · H:i', strtotime($trx['waktu_transaksi']))) ?></td>
              <td>
                <strong><?= e($trx['nama_pelanggan'] ?: 'Pelanggan Umum') ?></strong>
                <div class="muted text-xs"><?= (int)$trx['total_botol'] ?> Unit · Kontak: <?= e($trx['kontak_pelanggan'] ?: '-') ?></div>
              </td>
              <td><?= e($trx['nama_kasir'] ?? 'Kasir') ?></td>
              <td>
                <?php if ($lunas): ?>
                  <span class="badge success"><i class="dot"></i>Lunas</span>
                <?php else: ?>
                  <span class="badge warn"><i class="dot"></i>Piutang Tempo</span>
                <?php endif; ?>
              </td>
              <td class="text-right mono"><strong><?= rupiah($trx['total_harga']) ?></strong></td>
              <td class="text-right">
                <div class="row-actions">
                  <a href="<?= BASE_URL ?>modules/transactions/print_invoice.php?id=<?= urlencode($trx['id_transaksi']) ?>" class="btn btn-soft btn-sm">
                    <?= icon('report', 15) ?> Cetak Nota
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="table-empty">Belum ada riwayat transaksi penjualan yang tercatat.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
