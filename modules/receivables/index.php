<?php
/**
 * UI MODUL MANAJEMEN PIUTANG & TOP (TERM OF PAYMENT)
 * File: modules/receivables/index.php
 * Fungsi: Monitoring piutang pelanggan kafe/resto HORECA dari Supabase PostgreSQL,
 *         peringatan jatuh tempo, pengiriman draf WA, & validasi pelunasan.
 */

$root       = '../../';
$page_title = 'Manajemen Piutang';
$page_sub   = 'Pengawasan Term of Payment (TOP) & penagihan tagihan kafe/resto · MKP Store';
$active     = 'receivables';
$allowed    = ['super_admin', 'admin'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil transaksi piutang (status_pembayaran = 2) maupun riwayat yang sudah lunas namun punya jatuh_tempo
$q_piutang = "SELECT t.id_transaksi, t.waktu_transaksi, t.total_harga, t.nama_pelanggan,
                     t.kontak_pelanggan, t.status_pembayaran, t.jatuh_tempo, u.nama AS kasir
              FROM transactions t
              LEFT JOIN users u ON t.userid_user = u.id_user
              WHERE t.status_pembayaran = 2 OR t.jatuh_tempo IS NOT NULL
              ORDER BY t.status_pembayaran DESC, t.jatuh_tempo ASC NULLS LAST, t.waktu_transaksi DESC";
$res_piutang = pg_query($conn, $q_piutang);

$daftar_piutang = [];
$total_piutang_aktif = 0;
$jml_overdue = 0;
$jml_aktif = 0;
$today = date('Y-m-d');

if ($res_piutang) {
    while ($r = pg_fetch_assoc($res_piutang)) {
        $is_paid = (int)$r['status_pembayaran'] === 1;
        $tempo   = $r['jatuh_tempo'] ?: date('Y-m-d', strtotime($r['waktu_transaksi'] . ' +14 days'));
        $overdue = !$is_paid && ($tempo < $today);

        if (!$is_paid) {
            $total_piutang_aktif += (int)$r['total_harga'];
            $jml_aktif++;
            if ($overdue) {
                $jml_overdue++;
            }
        }

        $r['tempo_norm'] = $tempo;
        $r['is_paid']    = $is_paid;
        $r['is_overdue'] = $overdue;
        $daftar_piutang[] = $r;
    }
}
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

<!-- Ringkasan Statistik Piutang (Bento 3 Kartu) -->
<div class="bento mb-5">
  <div class="card stat span-4 rise">
    <div class="stat-top">
      <span class="stat-label">Total Piutang Berjalan</span>
      <span class="stat-icon info"><?= icon('wallet', 20) ?></span>
    </div>
    <div class="stat-value mono"><?= rupiah_short($total_piutang_aktif) ?></div>
    <div class="stat-foot">
      <span class="badge info"><i class="dot"></i><?= $jml_aktif ?> Nota Belum Lunas</span>
    </div>
  </div>

  <div class="card stat span-4 rise">
    <div class="stat-top">
      <span class="stat-label">Melewati Jatuh Tempo (Overdue)</span>
      <span class="stat-icon danger"><?= icon('alert', 20) ?></span>
    </div>
    <div class="stat-value mono"><?= $jml_overdue ?> <small>Mitra</small></div>
    <div class="stat-foot">
      <span class="badge danger"><i class="dot"></i>Prioritas Penagihan</span>
    </div>
  </div>

  <div class="card stat span-4 rise">
    <div class="stat-top">
      <span class="stat-label">Total Arsip Nota TOP</span>
      <span class="stat-icon success"><?= icon('check', 20) ?></span>
    </div>
    <div class="stat-value mono"><?= count($daftar_piutang) ?> <small>Transaksi</small></div>
    <div class="stat-foot">
      <span class="badge success"><i class="dot"></i>Terhubung Supabase</span>
    </div>
  </div>
</div>

<!-- Tabel Daftar Piutang Mitra HORECA -->
<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Daftar Tagihan Piutang Mitra HORECA</h2>
      <p>Urutan otomatis diprioritaskan dari tagihan yang belum lunas dan paling dekat dengan tanggal jatuh tempo</p>
    </div>
    <a href="<?= BASE_URL ?>modules/transactions/index.php" class="btn btn-primary btn-sm">
      <?= icon('plus', 15) ?> Buat Transaksi Tempo
    </a>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>No. Invoice / Tanggal</th>
          <th>Pelanggan / Mitra</th>
          <th class="col-tempo">Jatuh Tempo</th>
          <th class="text-right">Nominal Tagihan</th>
          <th class="col-status">Status Pembayaran</th>
          <th class="col-aksi-wide">Aksi Penagihan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($daftar_piutang) > 0): ?>
          <?php $no = 1; foreach ($daftar_piutang as $row):
              $no_wa = preg_replace('/[^0-9]/', '', (string)$row['kontak_pelanggan']);
              if (strpos($no_wa, '0') === 0) {
                  $no_wa = '62' . substr($no_wa, 1);
              }
              $pesan_wa = rawurlencode(
                  "Halo *" . ($row['nama_pelanggan'] ?: 'Mitra MKP Store') . "*,\n" .
                  "Kami dari *CV. Mitra Kembar Pradipta (MKP Store Malang)* mengingatkan tagihan Invoice *#" . $row['id_transaksi'] . "* " .
                  "sebesar *" . rupiah($row['total_harga']) . "* dengan jatuh tempo *" . $row['tempo_norm'] . "*.\n\n" .
                  "Mohon konfirmasi apabila pembayaran telah dilakukan. Terima kasih."
              );
          ?>
            <tr>
              <td class="mono muted"><?= $no++ ?></td>
              <td>
                <strong class="mono">#<?= e($row['id_transaksi']) ?></strong>
                <div class="muted text-xs"><?= e(date('d M Y', strtotime($row['waktu_transaksi']))) ?></div>
              </td>
              <td>
                <strong><?= e($row['nama_pelanggan'] ?: 'Mitra HORECA') ?></strong>
                <div class="muted text-xs">WA: <?= e($row['kontak_pelanggan'] ?: '-') ?></div>
              </td>
              <td class="mono">
                <strong class="<?= $row['is_overdue'] ? 'text-danger' : '' ?>"><?= e($row['tempo_norm']) ?></strong>
              </td>
              <td class="text-right mono">
                <strong><?= rupiah($row['total_harga']) ?></strong>
              </td>
              <td>
                <?php if ($row['is_paid']): ?>
                  <span class="badge success"><i class="dot"></i>Sudah Lunas</span>
                <?php elseif ($row['is_overdue']): ?>
                  <span class="badge danger"><i class="dot"></i>Lewat Jatuh Tempo</span>
                <?php else: ?>
                  <span class="badge warn"><i class="dot"></i>Belum Lunas (TOP)</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="row-actions">
                  <?php if (!$row['is_paid']): ?>
                    <?php if ($no_wa !== ''): ?>
                      <a href="https://wa.me/<?= e($no_wa) ?>?text=<?= $pesan_wa ?>" target="_blank" rel="noopener" class="btn-wa">
                        <?= icon('whatsapp', 14) ?> Tagih WA
                      </a>
                    <?php endif; ?>
                    <form action="<?= BASE_URL ?>modules/receivables/process/update_status.php" method="POST">
                      <input type="hidden" name="id_transaksi" value="<?= e($row['id_transaksi']) ?>">
                      <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Tandai tagihan #<?= e($row['id_transaksi']) ?> sebagai LUNAS?')">
                        <?= icon('check', 14) ?> Tandai Lunas
                      </button>
                    </form>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>modules/transactions/print_invoice.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-ghost btn-sm">
                      <?= icon('report', 14) ?> Nota Lunas
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="table-empty">Belum ada data piutang mitra yang tercatat.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
