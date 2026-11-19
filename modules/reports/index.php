<?php
/**
 * MODUL LAPORAN — REKAP PENJUALAN & LABA KOTOR (BASIS DATA LANJUT / PBL)
 * File: modules/reports/index.php
 * Fungsi: Filter periode dua mode (Segmented Pill Switcher & Clickable Month Chips),
 *         Tabel Agregasi Kategori Expandable (Opsi B Accordion Rincian Produk),
 *         Database VIEW v_laporan_penjualan (LIMIT 5), Export PDF CTE & Excel Multi-Sheet,
 *         serta proteksi Retensi Data Audit dengan PIN Otorisasi Owner.
 */

$root       = '../../';
$page_title = 'Rekap Laporan Penjualan';
$page_sub   = 'Ringkasan finansial omzet, HPP, dan laba kotor penjualan HORECA · Khusus Super Admin';
$active     = 'reports';
$allowed    = ['super_admin'];

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/filter_helper.php';

$filter         = report_parse_filter($_GET);
$mode           = $filter['mode'];
$tgl_mulai      = $filter['tgl_mulai'];
$tgl_akhir      = $filter['tgl_akhir'];
$tahun          = $filter['tahun'];
$bulan_terpilih = $filter['bulan'];
$min_unit       = $filter['min_unit'];
$label_periode  = $filter['label_periode'];
$daftar_bulan   = report_daftar_bulan();
$export_qs      = report_build_query_string($filter);

// Daftar pilihan tahun (3 tahun ke belakang s/d 1 tahun ke depan)
$tahun_sekarang = (int)date('Y');
$daftar_tahun   = [];
for ($y = $tahun_sekarang + 1; $y >= $tahun_sekarang - 3; $y--) {
    $daftar_tahun[] = $y;
}
if (!in_array($tahun, $daftar_tahun, true)) {
    $daftar_tahun[] = $tahun;
    rsort($daftar_tahun);
}

// 1. Query Agregasi Kategori — Multi-table JOIN + GROUP BY + HAVING (Syarat Wajib PBL)
$period_t     = report_sql_period_clause($filter, 't.waktu_transaksi', 1);
$param_min    = '$' . $period_t['next_index'];
$params_rekap = array_merge($period_t['params'], [$min_unit]);

$q_rekap_kategori = "SELECT COALESCE(c.id_category, 0) AS id_category,
                            COALESCE(c.nama_category, 'Tanpa Kategori') AS nama_category,
                            COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.kuantitas ELSE 0 END), 0) AS total_unit,
                            COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.subtotal_harga ELSE 0 END), 0) AS omzet_kotor,
                            COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.kuantitas * p.harga_beli ELSE 0 END), 0) AS total_hpp,
                            COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.subtotal_harga - (td.kuantitas * p.harga_beli) ELSE 0 END), 0) AS laba_kotor
                     FROM category c
                     LEFT JOIN products p ON p.categoryid_category = c.id_category
                     LEFT JOIN transaction_details td ON td.productsid_product = p.id_product
                     LEFT JOIN transactions t ON td.transactionsid_transaksi = t.id_transaksi
                           AND ({$period_t['sql']})
                     GROUP BY c.id_category, c.nama_category
                     HAVING COALESCE(SUM(CASE WHEN t.id_transaksi IS NOT NULL THEN td.kuantitas ELSE 0 END), 0) >= {$param_min}
                     ORDER BY omzet_kotor DESC, c.nama_category ASC";
$res_rekap = pg_query_params($conn, $q_rekap_kategori, $params_rekap);

$rekap_rows = [];
$akum_omzet = 0;
$akum_hpp   = 0;
$akum_laba  = 0;
$akum_unit  = 0;

if ($res_rekap) {
    while ($r = pg_fetch_assoc($res_rekap)) {
        $akum_omzet += (int)$r['omzet_kotor'];
        $akum_hpp   += (int)$r['total_hpp'];
        $akum_laba  += (int)$r['laba_kotor'];
        $akum_unit  += (int)$r['total_unit'];
        $rekap_rows[] = $r;
    }
}

// 2. Query Rincian Produk per Kategori untuk Sub-Tabel Accordion Card 2 (dikelompokkan per produk, BUKAN per batch)
$period_prod = report_sql_period_clause($filter, 't.waktu_transaksi', 1);
$q_rincian_produk = "SELECT COALESCE(c.id_category, 0) AS id_category,
                            p.id_product,
                            p.nama_barang,
                            SUM(td.kuantitas) AS total_unit,
                            SUM(td.subtotal_harga) AS omzet_kotor,
                            SUM(td.kuantitas * p.harga_beli) AS total_hpp,
                            SUM(td.subtotal_harga - (td.kuantitas * p.harga_beli)) AS laba_kotor
                     FROM transaction_details td
                     INNER JOIN transactions t ON td.transactionsid_transaksi = t.id_transaksi
                     INNER JOIN products p ON td.productsid_product = p.id_product
                     LEFT JOIN category c ON p.categoryid_category = c.id_category
                     WHERE ({$period_prod['sql']})
                     GROUP BY c.id_category, p.id_product, p.nama_barang
                     ORDER BY total_unit DESC, p.nama_barang ASC";
$res_rincian_produk = pg_query_params($conn, $q_rincian_produk, $period_prod['params']);

$produk_by_cat = [];
if ($res_rincian_produk) {
    while ($pr = pg_fetch_assoc($res_rincian_produk)) {
        $cid = (string)$pr['id_category'];
        if (!isset($produk_by_cat[$cid])) {
            $produk_by_cat[$cid] = [];
        }
        $produk_by_cat[$cid][] = $pr;
    }
}

// 3. Query Card 3 — Database VIEW v_laporan_penjualan (Dibatasi 5 item terbaru)
$period_view = report_sql_period_clause($filter, 'waktu_transaksi', 1);
$q_view_penjualan = "SELECT waktu_transaksi, id_transaksi, nama_barang, kuantitas, subtotal_harga, kasir
                     FROM v_laporan_penjualan
                     WHERE ({$period_view['sql']})
                     ORDER BY waktu_transaksi DESC
                     LIMIT 5";
$res_view = pg_query_params($conn, $q_view_penjualan, $period_view['params']);
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

<!-- Peringatan Kebijakan Retensi Data Audit -->
<div class="callout amber rise">
  <?= icon('shield', 20) ?>
  <div>
    <strong>Kebijakan Retensi Data Audit MKP Store:</strong> Seluruh rekaman transaksi finansial bersifat permanen untuk keperluan audit akuntansi. Penghapusan arsip transaksi dikunci dan wajib menggunakan verifikasi <strong>Kata Sandi Akun Super Admin / Owner</strong>.
  </div>
</div>

<!-- Card 1: Filter Periode (Segmented Pill Switcher) & Ambang Kuantitas -->
<div class="card mb-5 rise">
  <div class="card-head">
    <div>
      <h2>Filter Periode &amp; Ambang Kuantitas (HAVING Clause)</h2>
      <p>Pilih mode rentang tanggal atau multi-bulan serta batas minimal unit terjual per kategori</p>
    </div>
    <span class="badge role-super_admin"><i class="dot"></i>Otorisasi Owner / Super Admin</span>
  </div>

  <form action="" method="GET" id="form-report-filter">
    <!-- Segmented Pill Switcher -->
    <div class="report-pill-switch" role="tablist" aria-label="Mode Filter Periode">
      <label class="report-pill-opt <?= $mode === 'range' ? 'is-active' : '' ?>" id="tab-mode-range">
        <input type="radio" name="mode" value="range" <?= $mode === 'range' ? 'checked' : '' ?>>
        <span>Rentang Tanggal</span>
      </label>
      <label class="report-pill-opt <?= $mode === 'monthly' ? 'is-active' : '' ?>" id="tab-mode-monthly">
        <input type="radio" name="mode" value="monthly" <?= $mode === 'monthly' ? 'checked' : '' ?>>
        <span>Per Bulan (Multi-Bulan)</span>
      </label>
    </div>

    <!-- Panel Mode A: Rentang Tanggal (3 Input Sejajar Proporsional) -->
    <div id="panel-mode-range" class="<?= $mode === 'range' ? '' : 'is-hidden' ?>">
      <div class="report-range-grid">
        <div class="field field-tight">
          <label for="tgl_mulai">Tanggal Mulai</label>
          <input type="date" id="tgl_mulai" name="tgl_mulai" class="input" value="<?= e($tgl_mulai) ?>">
        </div>
        <div class="field field-tight">
          <label for="tgl_akhir">Tanggal Akhir</label>
          <input type="date" id="tgl_akhir" name="tgl_akhir" class="input" value="<?= e($tgl_akhir) ?>">
        </div>
        <div class="field field-tight">
          <label for="min_unit_range">Minimal Unit Terjual per Kategori (Filter HAVING)</label>
          <input type="number" id="min_unit_range" name="min_unit" class="input mono" min="0" value="<?= (int)$min_unit ?>" <?= $mode === 'monthly' ? 'disabled' : '' ?>>
        </div>
      </div>
    </div>

    <!-- Panel Mode B: Per Bulan (Tahun Kompak + Grid 6x2 Clickable Month Chips) -->
    <div id="panel-mode-monthly" class="<?= $mode === 'monthly' ? '' : 'is-hidden' ?>">
      <div class="report-monthly-top">
        <div class="field field-tight report-year-field">
          <label for="tahun">Pilih Tahun</label>
          <select id="tahun" name="tahun" class="select mono">
            <?php foreach ($daftar_tahun as $th): ?>
              <option value="<?= (int)$th ?>" <?= $th === $tahun ? 'selected' : '' ?>><?= (int)$th ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field field-tight report-having-compact">
          <label for="min_unit_monthly">Minimal Unit Terjual per Kategori (Filter HAVING)</label>
          <input type="number" id="min_unit_monthly" name="min_unit" class="input mono" min="0" value="<?= (int)$min_unit ?>" <?= $mode === 'range' ? 'disabled' : '' ?>>
        </div>
      </div>

      <div class="report-month-label-row">
        <label>Pilih Bulan</label>
        <div class="report-month-quick">
          <button type="button" class="report-quick-pill" id="btn-bulan-semua">Pilih Semua</button>
          <button type="button" class="report-quick-pill" id="btn-bulan-ini">Bulan Ini</button>
          <button type="button" class="report-quick-pill is-subtle" id="btn-bulan-reset">Reset Bulan</button>
        </div>
      </div>

      <div class="month-chip-grid">
        <?php foreach ($daftar_bulan as $num_b => $nama_b):
            $is_checked = in_array($num_b, $bulan_terpilih, true);
        ?>
          <label class="month-chip <?= $is_checked ? 'is-checked' : '' ?>">
            <input type="checkbox" name="bulan[]" value="<?= (int)$num_b ?>" <?= $is_checked ? 'checked' : '' ?>>
            <span class="month-chip-check" aria-hidden="true">&#10003;</span>
            <span><?= e($nama_b) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="report-filter-foot">
      <a href="<?= BASE_URL ?>modules/reports/index.php" class="btn btn-ghost">Reset</a>
      <button type="submit" class="btn btn-primary">
        <?= icon('search', 16) ?> Terapkan Filter
      </button>
    </div>
  </form>
</div>

<!-- Card 2: Tabel Agregasi Pendapatan per Kategori Produk + Rincian Expandable (Opsi B Accordion) -->
<div class="card flush mb-5 rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Tabel Agregasi Pendapatan per Kategori Produk</h2>
      <p>Periode <?= e($label_periode) ?> · Total Terjual: <?= (int)$akum_unit ?> Unit · Klik baris kategori untuk melihat rincian produk</p>
    </div>
    <div class="report-head-tools">
      <?php if (count($rekap_rows) > 0): ?>
        <button type="button" class="btn btn-soft btn-sm" id="btn-toggle-all-report-cat" aria-pressed="false">
          Buka Semua Rincian
        </button>
      <?php endif; ?>
      <span class="badge success"><i class="dot"></i>Total Omzet: <?= rupiah($akum_omzet) ?></span>
    </div>
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
          <?php $no = 1; foreach ($rekap_rows as $r):
              $cid        = (string)$r['id_category'];
              $detail_id  = 'report-cat-detail-' . $cid . '-' . $no;
              $cat_prods  = isset($produk_by_cat[$cid]) ? $produk_by_cat[$cid] : [];
              $jml_prods  = count($cat_prods);
          ?>
            <tr class="report-cat-row" data-report-acc="<?= e($detail_id) ?>" tabindex="0" role="button" aria-expanded="false">
              <td class="mono muted"><?= $no++ ?></td>
              <td>
                <div class="report-cat-name-cell">
                  <span class="report-acc-arrow" aria-hidden="true">&#9656;</span>
                  <div>
                    <strong><?= e($r['nama_category']) ?></strong>
                    <div class="report-cat-count"><?= $jml_prods ?> produk terjual</div>
                  </div>
                </div>
              </td>
              <td class="mono"><strong><?= (int)$r['total_unit'] ?> Unit</strong></td>
              <td class="text-right mono"><strong><?= rupiah($r['omzet_kotor']) ?></strong></td>
              <td class="text-right mono text-danger"><?= rupiah($r['total_hpp']) ?></td>
              <td class="text-right mono text-success"><strong>+ <?= rupiah($r['laba_kotor']) ?></strong></td>
            </tr>

            <tr id="<?= e($detail_id) ?>" class="report-detail-row is-hidden">
              <td colspan="6" class="report-detail-cell">
                <?php if ($jml_prods > 0): ?>
                  <div class="report-subtable-wrap">
                    <table class="report-subtable">
                      <thead>
                        <tr>
                          <th class="col-no">No</th>
                          <th>Nama Produk &amp; SKU</th>
                          <th class="text-center">Unit Terjual</th>
                          <th class="text-right">Total Omzet Kotor</th>
                          <th class="text-right">Total HPP</th>
                          <th class="text-right">Estimasi Laba Kotor</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $np = 1; foreach ($cat_prods as $prod): ?>
                          <tr>
                            <td class="mono muted"><?= $np++ ?></td>
                            <td>
                              <strong><?= e($prod['nama_barang']) ?></strong>
                              <div class="muted text-xs mono">SKU: <?= e($prod['id_product']) ?></div>
                            </td>
                            <td class="text-center mono"><strong><?= (int)$prod['total_unit'] ?> Unit</strong></td>
                            <td class="text-right mono"><?= rupiah($prod['omzet_kotor']) ?></td>
                            <td class="text-right mono text-danger"><?= rupiah($prod['total_hpp']) ?></td>
                            <td class="text-right mono text-success"><strong>+ <?= rupiah($prod['laba_kotor']) ?></strong></td>
                          </tr>
                        <?php endforeach; ?>
                        <tr class="report-subtotal-row">
                          <td colspan="2">Subtotal <?= e($r['nama_category']) ?></td>
                          <td class="text-center mono"><?= (int)$r['total_unit'] ?> Unit</td>
                          <td class="text-right mono"><?= rupiah($r['omzet_kotor']) ?></td>
                          <td class="text-right mono text-danger"><?= rupiah($r['total_hpp']) ?></td>
                          <td class="text-right mono text-success">+ <?= rupiah($r['laba_kotor']) ?></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="report-detail-empty">
                    Belum ada produk terjual pada kategori <strong><?= e($r['nama_category']) ?></strong> di periode <?= e($label_periode) ?>.
                  </div>
                <?php endif; ?>
              </td>
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

  <!-- Footer Ringkasan & 2 Tombol Export (PDF CTE + Excel Multi-Sheet) -->
  <div class="card-foot-bar">
    <div>
      <div class="muted">Total Akumulasi Pendapatan (<?= e($label_periode) ?>)</div>
      <strong class="mono text-lg-num"><?= rupiah($akum_omzet) ?></strong>
      <span class="badge success ml-2">Estimasi Laba Kotor: <?= rupiah($akum_laba) ?></span>
    </div>

    <div class="report-export-actions">
      <a href="<?= BASE_URL ?>modules/reports/process/export_pdf.php?<?= e($export_qs) ?>" class="btn btn-primary">
        <?= icon('download', 18) ?> Export PDF (Laporan CTE)
      </a>
      <a href="<?= BASE_URL ?>modules/reports/export_excel.php?<?= e($export_qs) ?>" class="btn btn-excel">
        <?= icon('download', 18) ?> Export Excel (.xlsx)
      </a>
      <button type="button" class="btn btn-gsheet" id="btn-export-gsheet" data-endpoint="<?= BASE_URL ?>modules/reports/export_gsheet.php?<?= e($export_qs) ?>">
        <?= icon('sheet', 18) ?> <span class="btn-gsheet-label">Simpan ke Google Sheets</span>
      </button>
    </div>
  </div>
</div>

<!-- Card 3: Rincian Penjualan Item dari Database VIEW (v_laporan_penjualan — 5 Terbaru) -->
<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Rincian Penjualan Item (Sumber: VIEW <code>v_laporan_penjualan</code>)</h2>
      <p>Menampilkan 5 transaksi item terbaru dari objek VIEW PostgreSQL sesuai filter periode aktif</p>
    </div>
    <span class="badge info">5 Item Terbaru · VIEW Active</span>
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
          <th class="text-right">Aksi Audit</th>
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
                <div class="row-actions">
                  <a href="<?= BASE_URL ?>modules/transactions/print_invoice.php?id=<?= urlencode($v['id_transaksi']) ?>" class="btn btn-soft btn-sm">
                    <?= icon('report', 14) ?> Nota
                  </a>
                  <button type="button" class="btn btn-danger-soft btn-sm" data-delete-trx="<?= e($v['id_transaksi']) ?>" data-return-to="reports">
                    <?= icon('trash', 14) ?> Hapus
                  </button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="table-empty">Belum ada rincian item penjualan pada periode ini.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card-foot-bar">
    <span class="muted text-xs">Menampilkan maksimal 5 item transaksi terbaru. Buka halaman Riwayat Transaksi untuk melihat seluruh catatan lengkap.</span>
    <a href="../transactions/index.php" class="btn btn-soft">
      Lihat Selengkapnya &rarr;
    </a>
  </div>
</div>

<!-- Modal Hasil Ekspor Google Sheets (Soft Caramel Cream, Zero Inline CSS) -->
<div class="confirm-backdrop" id="gsheet-result-backdrop" role="dialog" aria-modal="true" aria-labelledby="gsheet-modal-title" aria-describedby="gsheet-modal-msg" aria-hidden="true">
  <div class="confirm-modal" id="gsheet-result-modal" role="document">
    <div class="confirm-icon gsheet-modal-icon" id="gsheet-modal-icon" aria-hidden="true">
      <?= icon('sheet', 24) ?>
    </div>
    <h3 class="confirm-title" id="gsheet-modal-title">Berhasil Disimpan ke Google Sheets</h3>
    <p class="confirm-msg" id="gsheet-modal-msg">Laporan penjualan telah berhasil disimpan ke Google Drive Anda.</p>
    <div class="confirm-actions">
      <button type="button" class="btn btn-ghost" id="gsheet-btn-close">Tutup</button>
      <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-gsheet" id="gsheet-btn-open">
        <?= icon('sheet', 16) ?> Buka Google Sheets &#8599;
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
