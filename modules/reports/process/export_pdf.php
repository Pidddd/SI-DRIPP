<?php
/**
 * BACKEND MODUL LAPORAN — EXPORT PDF & ANALITIK CTE (COMMON TABLE EXPRESSION)
 * File: modules/reports/process/export_pdf.php
 * Fitur PBL: Menggunakan Common Table Expression (WITH ... AS) untuk menghitung kontribusi laba & omzet.
 */

$root       = '../../../';
$page_title = 'Cetak Laporan Analitik Penjualan (CTE)';
$page_sub   = 'Dokumen resmi rekapitulasi finansial CV. Mitra Kembar Pradipta (MKP Store)';
$active     = 'reports';
$allowed    = ['super_admin'];

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin']);

$tgl_mulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_akhir = !empty($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Query Common Table Expression / CTE (Syarat Wajib PBL Basis Data Lanjut)
$q_cte = "WITH ringkasan_produk AS (
              SELECT p.id_product,
                     p.nama_barang,
                     COALESCE(c.nama_category, 'Umum') AS kategori,
                     p.harga_beli,
                     p.harga_jual,
                     COALESCE(SUM(td.kuantitas), 0) AS unit_terjual,
                     COALESCE(SUM(td.subtotal_harga), 0) AS total_omzet,
                     COALESCE(SUM(td.kuantitas * p.harga_beli), 0) AS total_modal
              FROM products p
              LEFT JOIN category c ON p.categoryid_category = c.id_category
              LEFT JOIN transaction_details td ON td.productsid_product = p.id_product
              LEFT JOIN transactions t ON td.transactionsid_transaksi = t.id_transaksi
                    AND DATE(t.waktu_transaksi) BETWEEN $1 AND $2
              GROUP BY p.id_product, p.nama_barang, c.nama_category, p.harga_beli, p.harga_jual
          ),
          evaluasi_margin AS (
              SELECT id_product,
                     nama_barang,
                     kategori,
                     unit_terjual,
                     total_omzet,
                     total_modal,
                     (total_omzet - total_modal) AS laba_kotor
              FROM ringkasan_produk
          )
          SELECT * FROM evaluasi_margin
          ORDER BY laba_kotor DESC, unit_terjual DESC, nama_barang ASC";

$res_cte = pg_query_params($conn, $q_cte, [$tgl_mulai, $tgl_akhir]);
$data_cte = [];
$grand_omzet = 0;
$grand_modal = 0;
$grand_laba  = 0;

if ($res_cte) {
    while ($row = pg_fetch_assoc($res_cte)) {
        $grand_omzet += (int)$row['total_omzet'];
        $grand_modal += (int)$row['total_modal'];
        $grand_laba  += (int)$row['laba_kotor'];
        $data_cte[]   = $row;
    }
}

require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="page-actions no-print rise">
  <a href="<?= BASE_URL ?>modules/reports/index.php?tgl_mulai=<?= urlencode($tgl_mulai) ?>&amp;tgl_akhir=<?= urlencode($tgl_akhir) ?>" class="btn btn-ghost">
    &larr; Kembali ke Rekap Laporan
  </a>
  <div class="grow"></div>
  <button type="button" class="btn btn-primary" onclick="window.print()">
    <?= icon('download', 18) ?> Cetak / Simpan sebagai PDF
  </button>
</div>

<div class="invoice-box rise">
  <div class="invoice-head">
    <div>
      <h2>LAPORAN REKAPITULASI PENJUALAN &amp; LABA KOTOR</h2>
      <p class="muted text-xs">CV. Mitra Kembar Pradipta (MKP Store) · Periode: <?= e($tgl_mulai) ?> s/d <?= e($tgl_akhir) ?></p>
    </div>
    <div class="text-right">
      <span class="badge role-super_admin"><i class="dot"></i>Analitik CTE PostgreSQL</span>
      <div class="text-xs muted mt-2">Dicetak: <?= e(date('d M Y H:i')) ?></div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Produk</th>
          <th>Kategori</th>
          <th class="text-center">Terjual</th>
          <th class="text-right">Omzet Kotor</th>
          <th class="text-right">Total HPP</th>
          <th class="text-right">Laba Kotor</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($data_cte) > 0): ?>
          <?php $no = 1; foreach ($data_cte as $item): ?>
            <tr>
              <td class="mono muted"><?= $no++ ?></td>
              <td>
                <strong><?= e($item['nama_barang']) ?></strong>
                <div class="muted text-xs">SKU: <?= e($item['id_product']) ?></div>
              </td>
              <td><?= e($item['kategori']) ?></td>
              <td class="text-center mono"><strong><?= (int)$item['unit_terjual'] ?></strong></td>
              <td class="text-right mono"><?= rupiah($item['total_omzet']) ?></td>
              <td class="text-right mono text-danger"><?= rupiah($item['total_modal']) ?></td>
              <td class="text-right mono text-success"><strong><?= rupiah($item['laba_kotor']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="table-empty">Tidak ada data produk untuk diekspor.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="divider"></div>

  <div class="kv">
    <span>Total Omzet Kotor Penjualan</span>
    <strong class="mono"><?= rupiah($grand_omzet) ?></strong>
  </div>
  <div class="kv">
    <span>Total Harga Pokok Penjualan (HPP)</span>
    <strong class="mono text-danger"><?= rupiah($grand_modal) ?></strong>
  </div>
  <div class="sum-total">
    <span>Total Laba Kotor Bersih</span>
    <strong class="mono"><?= rupiah($grand_laba) ?></strong>
  </div>
</div>

<script>
window.addEventListener('load', function () {
  window.print();
});
</script>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
