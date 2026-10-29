<?php
/**
 * MODUL LAPORAN — REKAP PENJUALAN & LABA KOTOR (BASIS DATA LANJUT / PBL)
 * File: modules/reports/index.php
 * Fungsi: Mengonsumsi Database VIEW (v_laporan_penjualan) & Agregasi GROUP BY / HAVING.
 */

$root       = '../../';
$page_title = 'Rekap Laporan Penjualan';
$page_sub   = 'Ringkasan finansial omzet, HPP, dan laba kotor penjualan HORECA · Khusus Super Admin';
$active     = 'reports';
$allowed    = ['super_admin'];

require_once __DIR__ . '/../../layouts/header.php';

$tgl_mulai  = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_akhir  = !empty($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$min_unit   = isset($_GET['min_unit']) ? max(0, (int)$_GET['min_unit']) : 0;

// 1. Query Agregasi Multi-table JOIN + GROUP BY + HAVING (Syarat Wajib PBL)
$q_rekap_kategori = "SELECT c.id_category,
                            COALESCE(c.nama_category, 'Tanpa Kategori') AS nama_category,
                            COALESCE(SUM(td.kuantitas), 0) AS total_unit,
                            COALESCE(SUM(td.subtotal_harga), 0) AS omzet_kotor,
                            COALESCE(SUM(td.kuantitas * p.harga_beli), 0) AS total_hpp,
                            COALESCE(SUM(td.subtotal_harga - (td.kuantitas * p.harga_beli)), 0) AS laba_kotor
                     FROM category c
                     LEFT JOIN products p ON p.categoryid_category = c.id_category
                     LEFT JOIN transaction_details td ON td.productsid_product = p.id_product
                     LEFT JOIN transactions t ON td.transactionsid_transaksi = t.id_transaksi
                           AND DATE(t.waktu_transaksi) BETWEEN $1 AND $2
                     GROUP BY c.id_category, c.nama_category
                     HAVING COALESCE(SUM(td.kuantitas), 0) >= $3
                     ORDER BY omzet_kotor DESC, c.nama_category ASC";
$res_rekap = pg_query_params($conn, $q_rekap_kategori, [$tgl_mulai, $tgl_akhir, $min_unit]);

$rekap_rows  = [];
$akum_omzet  = 0;
$akum_laba   = 0;
$akum_unit   = 0;

if ($res_rekap) {
    while ($r = pg_fetch_assoc($res_rekap)) {
        $akum_omzet += (int)$r['omzet_kotor'];
        $akum_laba  += (int)$r['laba_kotor'];
        $akum_unit  += (int)$r['total_unit'];
        $rekap_rows[] = $r;
    }
}

// 2. Mengonsumsi Database VIEW v_laporan_penjualan (Syarat Wajib PBL)
$q_view_penjualan = "SELECT waktu_transaksi, id_transaksi, nama_barang, kuantitas, subtotal_harga, kasir
                     FROM v_laporan_penjualan
                     WHERE DATE(waktu_transaksi) BETWEEN $1 AND $2
                     ORDER BY waktu_transaksi DESC
                     LIMIT 25";
$res_view = pg_query_params($conn, $q_view_penjualan, [$tgl_mulai, $tgl_akhir]);
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

<!-- Filter Periode Laporan Dinamis -->
<div class="card mb-5 rise">
  <div class="card-head">
    <div>
      <h2>Filter Periode &amp; Ambang Kuantitas (HAVING Clause)</h2>
      <p>Pilih rentang tanggal dan batas minimal unit terjual per kategori untuk menyaring rekapitulasi pendapatan</p>
    </div>
    <span class="badge role-super_admin"><i class="dot"></i>Otorisasi Owner / Super Admin</span>
  </div>

  <form action="" method="GET">
    <div class="form-row">
      <div class="field field-tight">
        <label for="tgl_mulai">Tanggal Mulai</label>
        <input type="date" id="tgl_mulai" name="tgl_mulai" class="input" value="<?= e($tgl_mulai) ?>">
      </div>
      <div class="field field-tight">
        <label for="tgl_akhir">Tanggal Akhir</label>
        <input type="date" id="tgl_akhir" name="tgl_akhir" class="input" value="<?= e($tgl_akhir) ?>">
      </div>
    </div>

    <div class="page-actions page-actions-compact">
      <div class="field field-tight">
        <label for="min_unit">Minimal Unit Terjual per Kategori (Filter HAVING)</label>
        <input type="number" id="min_unit" name="min_unit" class="input mono" min="0" value="<?= (int)$min_unit ?>">
      </div>
      <div class="grow"></div>
      <a href="<?= BASE_URL ?>modules/reports/index.php" class="btn btn-ghost">Reset</a>
      <button type="submit" class="btn btn-soft">
        <?= icon('search', 16) ?> Terapkan Filter
      </button>
    </div>
  </form>
</div>

<!-- Tabel 1: Ringkasan Pendapatan per Kategori (GROUP BY & HAVING) -->
<div class="card flush mb-5 rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Tabel Agregasi Pendapatan per Kategori Produk</h2>
      <p>Periode <?= e($tgl_mulai) ?> s/d <?= e($tgl_akhir) ?> · Total Terjual: <?= (int)$akum_unit ?> Unit</p>
    </div>
    <span class="badge success"><i class="dot"></i>Total Omzet: <?= rupiah($akum_omzet) ?></span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Kategori Produk</th>
          <th>Unit Terjual</th>
          <th class="text-right">Total Omzet Kotor</th>
          <th class="text-right">Total HPP (Modal)</th>
          <th class="text-right">Estimasi Laba Kotor</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($rekap_rows) > 0): ?>
          <?php $no = 1; foreach ($rekap_rows as $r): ?>
            <tr>
              <td class="mono muted"><?= $no++ ?></td>
              <td><strong><?= e($r['nama_category']) ?></strong></td>
              <td class="mono"><strong><?= (int)$r['total_unit'] ?> Unit</strong></td>
              <td class="text-right mono"><strong><?= rupiah($r['omzet_kotor']) ?></strong></td>
              <td class="text-right mono text-danger"><?= rupiah($r['total_hpp']) ?></td>
              <td class="text-right mono text-success"><strong>+ <?= rupiah($r['laba_kotor']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="table-empty">Tidak ada kategori yang memenuhi kriteria filter pada periode ini.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Footer Ringkasan & Tombol Export PDF (CTE) -->
  <div class="card-foot-bar">
    <div>
      <div class="muted">Total Akumulasi Pendapatan (<?= e($tgl_mulai) ?> s/d <?= e($tgl_akhir) ?>)</div>
      <strong class="mono text-lg-num"><?= rupiah($akum_omzet) ?></strong>
      <span class="badge success ml-2">Estimasi Laba Kotor: <?= rupiah($akum_laba) ?></span>
    </div>

    <a href="<?= BASE_URL ?>modules/reports/process/export_pdf.php?tgl_mulai=<?= urlencode($tgl_mulai) ?>&amp;tgl_akhir=<?= urlencode($tgl_akhir) ?>" class="btn btn-primary btn-lg">
      <?= icon('download', 20) ?> Export PDF (Laporan CTE)
    </a>
  </div>
</div>

<!-- Tabel 2: Rincian Item Penjualan dari Database VIEW (v_laporan_penjualan) -->
<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Rincian Penjualan Item (Sumber: VIEW <code>v_laporan_penjualan</code>)</h2>
      <p>Menampilkan rincian transaksi item langsung dari objek VIEW PostgreSQL</p>
    </div>
    <span class="badge info">Database VIEW Active</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Waktu Transaksi</th>
          <th>No. Invoice</th>
          <th>Nama Produk</th>
          <th class="text-center">Qty</th>
          <th class="text-right">Subtotal</th>
          <th>Kasir</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res_view && pg_num_rows($res_view) > 0): ?>
          <?php while ($v = pg_fetch_assoc($res_view)): ?>
            <tr>
              <td class="nowrap"><?= e(date('d M Y · H:i', strtotime($v['waktu_transaksi']))) ?></td>
              <td class="mono"><strong>#<?= e($v['id_transaksi']) ?></strong></td>
              <td><strong><?= e($v['nama_barang']) ?></strong></td>
              <td class="text-center mono"><?= (int)$v['kuantitas'] ?></td>
              <td class="text-right mono"><strong><?= rupiah($v['subtotal_harga']) ?></strong></td>
              <td><?= e($v['kasir']) ?></td>
              <td class="text-right">
                <form action="<?= BASE_URL ?>modules/reports/process/delete_report.php" method="POST">
                  <input type="hidden" name="id_transaksi" value="<?= e($v['id_transaksi']) ?>">
                  <button type="submit" class="btn btn-danger-soft btn-sm" onclick="return confirm('Hapus arsip transaksi #<?= e($v['id_transaksi']) ?> dari laporan?')">
                    <?= icon('trash', 14) ?> Hapus
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="table-empty">Belum ada rincian item penjualan pada rentang tanggal ini.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
