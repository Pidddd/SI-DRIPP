<?php
/**
 * MODUL LAPORAN — REKAP PENJUALAN & LABA KOTOR
 * File: modules/reports/index.php
 * Fungsi: Antarmuka Rekap Penjualan statis dilengkapi filter periode & tombol Export PDF.
 */

$root       = '../../';
$page_title = 'Rekap Laporan Penjualan';
$page_sub   = 'Ringkasan finansial omzet, HPP, dan laba kotor penjualan HORECA · Khusus Super Admin';
$active     = 'reports';
$allowed    = ['super_admin'];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Filter Periode Statis -->
<div class="card rise" style="margin-bottom: 20px;">
  <div class="card-head">
    <div>
      <h2>Filter Periode Laporan</h2>
      <p>Pilih rentang tanggal dan kategori transaksi untuk menampilkan rekapitulasi pendapatan</p>
    </div>
    <span class="badge role-super_admin"><i class="dot"></i>Otorisasi Owner / Super Admin</span>
  </div>

  <div class="form-row">
    <div class="field" style="margin-bottom: 0;">
      <label for="tgl_mulai">Tanggal Mulai</label>
      <input type="date" id="tgl_mulai" class="input" value="2026-10-01">
    </div>
    <div class="field" style="margin-bottom: 0;">
      <label for="tgl_akhir">Tanggal Akhir</label>
      <input type="date" id="tgl_akhir" class="input" value="2026-10-08">
    </div>
  </div>

  <div class="page-actions" style="margin: 16px 0 0;">
    <div class="chips">
      <button type="button" class="chip active">Minggu Ini</button>
      <button type="button" class="chip">Bulan Ini (Okt 2026)</button>
      <button type="button" class="chip">Kuartal III</button>
    </div>
    <div class="grow"></div>
    <button type="button" class="btn btn-soft" onclick="alert('Filter periode laporan berhasil diterapkan!')">
      <?= icon('search', 16) ?> Terapkan Filter
    </button>
  </div>
</div>

<!-- Tabel Ringkasan Pendapatan -->
<div class="card flush rise">
  <div class="card-head pad">
    <div>
      <h2>Tabel Ringkasan Pendapatan Penjualan</h2>
      <p>Rekapitulasi transaksi penjualan produk DRIPP, Multibev, dan Ramoe periode 01 Okt – 08 Okt 2026</p>
    </div>
    <span class="badge success"><i class="dot"></i>Total Omzet Bersih: Rp 14.850.000</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Kategori Produk</th>
          <th>Unit Terjual</th>
          <th class="text-right">Total Omzet Kotor</th>
          <th class="text-right">Potongan Diskon</th>
          <th class="text-right">Pendapatan Bersih</th>
          <th class="text-right">Estimasi Laba Kotor</th>
        </tr>
      </thead>
      <tbody>
        <!-- Baris 1 -->
        <tr>
          <td class="mono muted">1</td>
          <td>
            <strong>DRIPP Flavour Syrup 760ml</strong>
            <div class="muted">Caramel, Vanilla, Hazelnut, Pandan, Lychee</div>
          </td>
          <td class="mono"><strong>84 Botol</strong> (7 Karton)</td>
          <td class="text-right mono">Rp 9.324.000</td>
          <td class="text-right mono" style="color: var(--danger);">- Rp 324.000</td>
          <td class="text-right mono"><strong>Rp 9.000.000</strong></td>
          <td class="text-right mono" style="color: var(--success);"><strong>+ Rp 1.608.000</strong></td>
        </tr>

        <!-- Baris 2 -->
        <tr>
          <td class="mono muted">2</td>
          <td>
            <strong>DRIPP Fruit Pulp 760ml</strong>
            <div class="muted">Blueberry, Mango Harum Manis</div>
          </td>
          <td class="mono"><strong>20 Botol</strong></td>
          <td class="text-right mono">Rp 3.330.000</td>
          <td class="text-right mono" style="color: var(--danger);">- Rp 130.000</td>
          <td class="text-right mono"><strong>Rp 3.200.000</strong></td>
          <td class="text-right mono" style="color: var(--success);"><strong>+ Rp 560.000</strong></td>
        </tr>

        <!-- Baris 3 -->
        <tr>
          <td class="mono muted">3</td>
          <td>
            <strong>DRIPP Powder Drink 760g</strong>
            <div class="muted">Red Velvet, Dark Chocolate</div>
          </td>
          <td class="mono"><strong>12 Pouch</strong></td>
          <td class="text-right mono">Rp 1.872.000</td>
          <td class="text-right mono" style="color: var(--danger);">- Rp 72.000</td>
          <td class="text-right mono"><strong>Rp 1.800.000</strong></td>
          <td class="text-right mono" style="color: var(--success);"><strong>+ Rp 300.000</strong></td>
        </tr>

        <!-- Baris 4 -->
        <tr>
          <td class="mono muted">4</td>
          <td>
            <strong>Multibev Syrup 1 Liter</strong>
            <div class="muted">Butterscotch, Strawberry</div>
          </td>
          <td class="mono"><strong>11 Botol</strong></td>
          <td class="text-right mono">Rp 866.800</td>
          <td class="text-right mono" style="color: var(--danger);">- Rp 16.800</td>
          <td class="text-right mono"><strong>Rp 850.000</strong></td>
          <td class="text-right mono" style="color: var(--success);"><strong>+ Rp 157.000</strong></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Footer Ringkasan & Tombol Besar Export PDF -->
  <div style="padding: 22px; border-top: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="muted">Total Akumulasi Pendapatan Bersih (01 – 08 Oktober 2026)</div>
      <strong class="mono" style="font-size: 24px; color: var(--primary);">Rp 14.850.000</strong>
      <span class="badge success" style="margin-left: 8px;">Laba Kotor: Rp 2.625.000</span>
    </div>

    <button type="button" class="btn btn-primary btn-lg" onclick="alert('Meng-generate laporan PDF...'); window.print();">
      <?= icon('download', 20) ?> Export PDF (Cetak Laporan)
    </button>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
