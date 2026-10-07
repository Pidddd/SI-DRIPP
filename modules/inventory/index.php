<?php
/**
 * MODUL INVENTARIS — STOK GUDANG HORECA
 * File: modules/inventory/index.php
 * Fungsi: Antarmuka Daftar Stok Gudang HORECA statis bergaya Bento Grid Toska MKP Store.
 */

$root       = '../../';
$page_title = 'Inventaris Stok Gudang';
$page_sub   = 'Monitoring ketersediaan fisik sirup DRIPP, Multibev, & Powder · Gudang MKP Store';
$active     = 'inventory';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Bar Aksi Atas -->
<div class="page-actions rise">
  <div>
    <span class="badge info"><i class="dot"></i>Standar Karton MKP: 1 Karton = 12 Botol</span>
    <span class="badge danger" style="margin-left: 6px;"><i class="dot"></i>Batas Stok Kritis: &lt; 24 Botol</span>
  </div>
  <div class="grow"></div>
  <button type="button" class="btn btn-primary" onclick="alert('Simulasi Demo: Membuka form pencatatan barang masuk dari prinsipal DRIPP.')">
    <?= icon('plus', 18) ?> Catat Barang Masuk
  </button>
</div>

<!-- Tabel Daftar Stok Gudang HORECA -->
<div class="card flush rise">
  <div class="card-head pad" style="padding-bottom: 16px;">
    <div>
      <h2>Daftar Stok Gudang HORECA</h2>
      <p>Data ketersediaan stok aktual gudang beserta indikator peringatan dini (Early Warning System)</p>
    </div>
    <span class="badge neutral">4 Varian Terdaftar</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 60px;">No</th>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th>Sisa Stok</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <!-- Baris 1 -->
        <tr>
          <td class="mono muted">1</td>
          <td>
            <strong>DRIPP Flavour Syrup Caramel 760ml</strong>
            <div class="muted" style="font-size: 12px;">SKU: DRP-CRM-760 · Rak Gudang A-01</div>
          </td>
          <td><span class="badge info">DRIPP Syrup</span></td>
          <td class="mono">
            <strong>48 Botol</strong>
            <span class="muted" style="font-size: 12px;">(4 Karton)</span>
          </td>
          <td>
            <span class="badge success"><i class="dot"></i>Aman</span>
          </td>
        </tr>

        <!-- Baris 2 -->
        <tr>
          <td class="mono muted">2</td>
          <td>
            <strong>DRIPP Flavour Syrup Vanilla 760ml</strong>
            <div class="muted" style="font-size: 12px;">SKU: DRP-VNL-760 · Rak Gudang A-02</div>
          </td>
          <td><span class="badge info">DRIPP Syrup</span></td>
          <td class="mono">
            <strong>36 Botol</strong>
            <span class="muted" style="font-size: 12px;">(3 Karton)</span>
          </td>
          <td>
            <span class="badge success"><i class="dot"></i>Aman</span>
          </td>
        </tr>

        <!-- Baris 3 -->
        <tr>
          <td class="mono muted">3</td>
          <td>
            <strong>DRIPP Flavour Syrup Pandan 760ml</strong>
            <div class="muted" style="font-size: 12px;">SKU: DRP-PDN-760 · Rak Gudang A-05</div>
          </td>
          <td><span class="badge info">DRIPP Syrup</span></td>
          <td class="mono">
            <strong style="color: var(--danger);">10 Botol</strong>
            <span class="muted" style="font-size: 12px;">(&lt; 1 Karton)</span>
          </td>
          <td>
            <span class="badge danger"><i class="dot"></i>Kritis</span>
          </td>
        </tr>

        <!-- Baris 4 -->
        <tr>
          <td class="mono muted">4</td>
          <td>
            <strong>Multibev Syrup Strawberry 1 Liter</strong>
            <div class="muted" style="font-size: 12px;">SKU: MBV-STR-1000 · Rak Gudang C-02</div>
          </td>
          <td><span class="badge neutral">Multibev 1L</span></td>
          <td class="mono">
            <strong style="color: var(--danger);">8 Botol</strong>
            <span class="muted" style="font-size: 12px;">(&lt; 1 Karton)</span>
          </td>
          <td>
            <span class="badge danger"><i class="dot"></i>Kritis</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>