<?php
/**
 * MODUL TRANSAKSI — RIWAYAT TRANSAKSI PENJUALAN
 * File: modules/transactions/history.php
 * Fungsi: Antarmuka Riwayat Transaksi statis dengan fitur cetak nota interaktif.
 */

$root       = '../../';
$page_title = 'Riwayat Transaksi';
$page_sub   = 'Arsip nota penjualan B2B/B2C & pencetakan ulang struk · MKP Store Malang';
$active     = 'tx_history';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Bar Aksi & Filter -->
<div class="page-actions rise">
  <div>
    <span class="badge info"><i class="dot"></i>4 Transaksi Tercatat Hari Ini</span>
    <span class="badge success"><i class="dot"></i>3 Lunas · 1 Piutang Tempo</span>
  </div>
  <div class="grow"></div>
  <a href="<?= BASE_URL ?>modules/transactions/index.php" class="btn btn-primary">
    <?= icon('cart', 18) ?> Transaksi Kasir Baru
  </a>
</div>

<!-- Tabel Riwayat Transaksi Statis -->
<div class="card flush rise">
  <div class="card-head pad">
    <div>
      <h2>Daftar Riwayat Transaksi Penjualan</h2>
      <p>Klik tombol Cetak Nota pada kolom aksi untuk mencetak struk/invoice melalui dialog printer</p>
    </div>
    <span class="badge neutral">Periode: Oktober 2026</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No Invoice</th>
          <th>Tanggal</th>
          <th>Pelanggan</th>
          <th>Status Bayar</th>
          <th class="text-right">Total</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Transaksi 1 -->
        <tr>
          <td class="mono"><strong>#INV-20261008-001</strong></td>
          <td class="nowrap">08 Okt 2026 · 09:15 WIB</td>
          <td>
            <strong>Kopi Studio 24 — Soekarno Hatta</strong>
            <div class="muted">Mitra HORECA (14 Botol · Diskon Grosir)</div>
          </td>
          <td><span class="badge success"><i class="dot"></i>Lunas (QRIS)</span></td>
          <td class="text-right mono"><strong>Rp 1.600.000</strong></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-soft btn-sm" onclick="window.print()">
                <?= icon('report', 15) ?> Cetak Nota
              </button>
            </div>
          </td>
        </tr>

        <!-- Transaksi 2 -->
        <tr>
          <td class="mono"><strong>#INV-20261008-002</strong></td>
          <td class="nowrap">08 Okt 2026 · 10:40 WIB</td>
          <td>
            <strong>Lafayette Coffee & Eatery — Kayutangan</strong>
            <div class="muted">Mitra HORECA (24 Botol · 2 Karton)</div>
          </td>
          <td><span class="badge warn"><i class="dot"></i>Piutang Tempo</span></td>
          <td class="text-right mono"><strong>Rp 2.544.000</strong></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-soft btn-sm" onclick="window.print()">
                <?= icon('report', 15) ?> Cetak Nota
              </button>
            </div>
          </td>
        </tr>

        <!-- Transaksi 3 -->
        <tr>
          <td class="mono"><strong>#INV-20261007-019</strong></td>
          <td class="nowrap">07 Okt 2026 · 16:20 WIB</td>
          <td>
            <strong>Nakoa Cafe — Bondowoso Malang</strong>
            <div class="muted">Mitra HORECA (12 Botol · 1 Karton)</div>
          </td>
          <td><span class="badge success"><i class="dot"></i>Lunas (Transfer)</span></td>
          <td class="text-right mono"><strong>Rp 1.272.000</strong></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-soft btn-sm" onclick="window.print()">
                <?= icon('report', 15) ?> Cetak Nota
              </button>
            </div>
          </td>
        </tr>

        <!-- Transaksi 4 -->
        <tr>
          <td class="mono"><strong>#INV-20261007-018</strong></td>
          <td class="nowrap">07 Okt 2026 · 13:05 WIB</td>
          <td>
            <strong>Pelanggan Walk-In (Retail B2C)</strong>
            <div class="muted">Pembelian Eceran (3 Botol DRIPP Syrup)</div>
          </td>
          <td><span class="badge success"><i class="dot"></i>Lunas (Tunai)</span></td>
          <td class="text-right mono"><strong>Rp 333.000</strong></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-soft btn-sm" onclick="window.print()">
                <?= icon('report', 15) ?> Cetak Nota
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
