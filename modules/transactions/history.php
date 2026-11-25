<?php
/**
 * MODUL TRANSAKSI — RIWAYAT TRANSAKSI PENJUALAN
 * File: modules/transactions/history.php
 * Fungsi: Menampilkan arsip nota penjualan dari Supabase menggunakan Multi-table JOIN.
 * Revisi Mitra MKP Store Tahap 3 (Timothy, 25 Nov 2026):
 *   - Tambahkan kolom t.sumber_transaksi dan t.ongkir di SELECT.
 *   - Badge sumber transaksi (Umum / Shopee).
 *   - Rincian ongkir bila > 0.
 *   - Format waktu menggunakan format_waktu_wib() (tanpa mikrodetik).
 * Aturan Arsitektur: PHP Native Prosedural, pg_query_params, ZERO INLINE CSS.
 */

$root       = '../../';
$page_title = 'Riwayat Transaksi';
$page_sub   = 'Arsip nota penjualan B2B/B2C & pencetakan ulang struk · MKP Store Malang';
$active     = 'tx_history';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';

// ============================================================
// Helper: format waktu WIB tanpa mikrodetik
// ============================================================
if (!function_exists('format_waktu_wib')) {
    function format_waktu_wib($ts_string)
    {
        if (empty($ts_string)) return '-';
        // Potong mikrodetik jika ada (format: 2026-11-25 10:30:00.123456)
        $ts_clean = preg_replace('/\.\d+$/', '', trim($ts_string));
        $ts       = strtotime($ts_clean);
        if (!$ts) return e($ts_string);
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts)
             . ' &middot; ' . date('H:i', $ts) . ' WIB';
    }
}

// ============================================================
// Query Riwayat — sertakan sumber_transaksi & ongkir
// ============================================================
$q_history = "SELECT t.id_transaksi, t.waktu_transaksi, t.nama_pelanggan, t.kontak_pelanggan,
                     t.total_harga, t.diskon, t.status_pembayaran,
                     t.sumber_transaksi, t.ongkir,
                     u.nama AS nama_kasir,
                     (SELECT COALESCE(SUM(td.kuantitas), 0)
                      FROM transaction_details td
                      WHERE td.transactionsid_transaksi = t.id_transaksi) AS total_botol
              FROM transactions t
              LEFT JOIN users u ON t.userid_user = u.id_user
              ORDER BY t.waktu_transaksi DESC";
$res_history   = pg_query($conn, $q_history);
$total_riwayat = $res_history ? pg_num_rows($res_history) : 0;
?>

<?php if (isset($_SESSION['success'])): ?>
  <div class="callout success rise">
    <?= icon('check', 18) ?>
    <div><?= e($_SESSION['success']) ?></div>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div><?= e($_SESSION['error']) ?></div>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

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
          <th>Tanggal &amp; Waktu</th>
          <th>Pelanggan</th>
          <th>Kasir</th>
          <th>Sumber</th>
          <th>Status Bayar</th>
          <th class="text-right">Total Tagihan</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res_history && $total_riwayat > 0): ?>
          <?php while ($trx = pg_fetch_assoc($res_history)):
              $lunas        = (int)$trx['status_pembayaran'] === 1;
              $ongkir       = (int)($trx['ongkir'] ?? 0);
              $sumber       = trim($trx['sumber_transaksi'] ?? 'Umum');
              $is_shopee    = (strtolower($sumber) === 'shopee');
          ?>
            <tr>
              <td class="mono"><strong>#<?= e($trx['id_transaksi']) ?></strong></td>
              <td class="nowrap"><?= format_waktu_wib($trx['waktu_transaksi']) ?></td>
              <td>
                <strong><?= e($trx['nama_pelanggan'] ?: 'Pelanggan Umum') ?></strong>
                <div class="muted text-xs">
                  <?= (int)$trx['total_botol'] ?> Unit
                  <?php if (!empty($trx['kontak_pelanggan']) && $trx['kontak_pelanggan'] !== '-'): ?>
                    &middot; <?= e($trx['kontak_pelanggan']) ?>
                  <?php endif; ?>
                </div>
                <?php if ($ongkir > 0): ?>
                  <div class="muted text-xs">
                    <span class="badge neutral">
                      <?= icon('truck', 11) ?> Ongkir: <?= rupiah($ongkir) ?>
                    </span>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= e($trx['nama_kasir'] ?? 'Kasir') ?></td>
              <td>
                <?php if ($is_shopee): ?>
                  <span class="badge warn"><i class="dot"></i>Shopee</span>
                <?php else: ?>
                  <span class="badge neutral"><i class="dot"></i>Umum</span>
                <?php endif; ?>
              </td>
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
                  <?php if (!empty($is_superadmin)): ?>
                    <button type="button" class="btn btn-danger-soft btn-sm" data-delete-trx="<?= e($trx['id_transaksi']) ?>" data-return-to="history">
                      <?= icon('trash', 14) ?> Hapus
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="table-empty">Belum ada riwayat transaksi penjualan yang tercatat.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
