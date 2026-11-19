<?php
/**
 * BACKEND MODUL LAPORAN — EXPORT PDF & ANALITIK CTE (COMMON TABLE EXPRESSION)
 * File: modules/reports/process/export_pdf.php
 * Fitur PBL: Menggunakan Common Table Expression (WITH ... AS) untuk menghitung kontribusi laba & omzet,
 *            mendukung filter Mode Rentang Tanggal maupun Mode Per Bulan (Multi-Bulan).
 */

$root       = '../../../';
$page_title = 'Cetak Laporan Analitik Penjualan (CTE)';
$page_sub   = 'Dokumen resmi rekapitulasi finansial CV. Mitra Kembar Pradipta (MKP Store)';
$active     = 'reports';
$allowed    = ['super_admin'];

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';
require_once __DIR__ . '/../filter_helper.php';

cek_hak_akses(['super_admin']);

$filter        = report_parse_filter($_GET);
$label_periode = $filter['label_periode'];
$min_unit      = $filter['min_unit'];
$back_qs       = report_build_query_string($filter);

$period_t  = report_sql_period_clause($filter, 't.waktu_transaksi', 1);
$param_min = '$' . $period_t['next_index'];
$params    = array_merge($period_t['params'], [$min_unit]);

// Query Common Table Expression / CTE (Syarat Wajib PBL Basis Data Lanjut)
$q_cte = "WITH ringkasan_produk AS (
              SELECT p.id_product,
                     p.nama_barang,
                     c.id_category,
                     COALESCE(c.nama_category, 'Umum') AS kategori,
                     p.harga_beli,
                     p.harga_jual,
                     COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.kuantitas ELSE 0 END), 0) AS unit_terjual,
                     COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.subtotal_harga ELSE 0 END), 0) AS total_omzet,
                     COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.kuantitas * p.harga_beli ELSE 0 END), 0) AS total_modal
              FROM products p
              LEFT JOIN category c ON p.categoryid_category = c.id_category
              LEFT JOIN transaction_details td ON td.productsid_product = p.id_product
              LEFT JOIN transactions t ON td.transactionsid_transaksi = t.id_transaksi
                    AND ({$period_t['sql']})
              GROUP BY p.id_product, p.nama_barang, c.id_category, c.nama_category, p.harga_beli, p.harga_jual
          ),
          kategori_lolos AS (
              SELECT id_category
              FROM ringkasan_produk
              GROUP BY id_category
              HAVING SUM(unit_terjual) >= {$param_min}
          ),
          evaluasi_margin AS (
              SELECT rp.id_product,
                     rp.nama_barang,
                     rp.kategori,
                     rp.unit_terjual,
                     rp.total_omzet,
                     rp.total_modal,
                     (rp.total_omzet - rp.total_modal) AS laba_kotor
              FROM ringkasan_produk rp
              INNER JOIN kategori_lolos kl ON rp.id_category IS NOT DISTINCT FROM kl.id_category
          )
          SELECT * FROM evaluasi_margin
          ORDER BY laba_kotor DESC, unit_terjual DESC, nama_barang ASC";

$res_cte     = pg_query_params($conn, $q_cte, $params);
$data_cte    = [];
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
  <a href="<?= BASE_URL ?>modules/reports/index.php?<?= e($back_qs) ?>" class="btn btn-ghost">
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
      <p class="muted text-xs">CV. Mitra Kembar Pradipta (MKP Store) · Periode: <?= e($label_periode) ?></p>
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
            <td colspan="7" class="table-empty">Tidak ada data produk untuk diekspor pada periode ini.</td>
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
