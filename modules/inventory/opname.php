<?php
/**
 * MODUL INVENTARIS — STOCK OPNAME GUDANG
 * File: modules/inventory/opname.php
 * Fungsi: Antarmuka Perbandingan Stok Sistem vs Stok Fisik Gudang beserta penyesuaian.
 */

$root       = '../../';
$page_title = 'Stock Opname Gudang';
$page_sub   = 'Audit kesesuaian stok sistem terhadap perhitungan fisik rak gudang · MKP Store';
$active     = 'stock_opname';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Callout Informasi Opname -->
<div class="callout amber rise">
  <?= icon('clipboard', 20) ?>
  <div>
    <strong>Audit Stock Opname Berkala:</strong> Baris dengan indikator warna merah menunjukkan adanya selisih antara catatan <em>Stok Sistem</em> dan hasil hitung <em>Stok Fisik</em> di rak gudang. Klik <strong>Sesuaikan</strong> untuk menyelaraskan stok.
  </div>
</div>

<!-- Tabel Perbandingan Stok Sistem vs Stok Fisik -->
<div class="card flush rise">
  <div class="card-head pad">
    <div>
      <h2>Tabel Perbandingan Stok (Sistem vs Fisik)</h2>
      <p>Hasil pemeriksaan fisik gudang HORECA oleh Staf Logistik pada <?= e(tgl_id()) ?></p>
    </div>
    <span class="badge danger"><i class="dot"></i>2 Produk Selisih Ditemukan</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th>Stok Sistem</th>
          <th>Stok Fisik</th>
          <th>Selisih</th>
          <th>Status Audit</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Baris 1: Sesuai -->
        <tr>
          <td class="mono muted">1</td>
          <td>
            <strong>DRIPP Flavour Syrup Caramel 760ml</strong>
            <div class="muted">SKU: DRP-CRM-760 · Rak A-01</div>
          </td>
          <td><span class="badge info">DRIPP Syrup</span></td>
          <td class="mono"><strong>48 Botol</strong></td>
          <td class="mono"><strong>48 Botol</strong></td>
          <td class="mono">0 Botol</td>
          <td><span class="badge success"><i class="dot"></i>Sesuai</span></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-ghost btn-sm" onclick="confirm('Terapkan penyesuaian stok ke dalam sistem?')">
                <?= icon('check', 14) ?> Sesuaikan
              </button>
            </div>
          </td>
        </tr>

        <!-- Baris 2: Selisih (Teks Merah) -->
        <tr style="color: var(--danger); background: var(--danger-bg);">
          <td class="mono"><strong>2</strong></td>
          <td>
            <strong>DRIPP Flavour Syrup Hazelnut 760ml</strong>
            <div>SKU: DRP-HZN-760 · Rak A-03 (Selisih -2 Botol)</div>
          </td>
          <td><span class="badge danger">DRIPP Syrup</span></td>
          <td class="mono"><strong>18 Botol</strong></td>
          <td class="mono"><strong>16 Botol</strong></td>
          <td class="mono"><strong>-2 Botol</strong></td>
          <td><span class="badge danger"><i class="dot"></i>Selisih Kurang</span></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-primary btn-sm" onclick="confirm('Terapkan penyesuaian stok ke dalam sistem?')">
                <?= icon('clipboard', 14) ?> Sesuaikan
              </button>
            </div>
          </td>
        </tr>

        <!-- Baris 3: Sesuai -->
        <tr>
          <td class="mono muted">3</td>
          <td>
            <strong>DRIPP Flavour Syrup Vanilla 760ml</strong>
            <div class="muted">SKU: DRP-VNL-760 · Rak A-02</div>
          </td>
          <td><span class="badge info">DRIPP Syrup</span></td>
          <td class="mono"><strong>36 Botol</strong></td>
          <td class="mono"><strong>36 Botol</strong></td>
          <td class="mono">0 Botol</td>
          <td><span class="badge success"><i class="dot"></i>Sesuai</span></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-ghost btn-sm" onclick="confirm('Terapkan penyesuaian stok ke dalam sistem?')">
                <?= icon('check', 14) ?> Sesuaikan
              </button>
            </div>
          </td>
        </tr>

        <!-- Baris 4: Selisih (Teks Merah) -->
        <tr style="color: var(--danger); background: var(--danger-bg);">
          <td class="mono"><strong>4</strong></td>
          <td>
            <strong>DRIPP Fruit Pulp Blueberry 760ml</strong>
            <div>SKU: DRP-PLP-BLU · Rak B-01 (Selisih -1 Botol)</div>
          </td>
          <td><span class="badge danger">Fruit Pulp</span></td>
          <td class="mono"><strong>25 Botol</strong></td>
          <td class="mono"><strong>24 Botol</strong></td>
          <td class="mono"><strong>-1 Botol</strong></td>
          <td><span class="badge danger"><i class="dot"></i>Selisih Kurang</span></td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-primary btn-sm" onclick="confirm('Terapkan penyesuaian stok ke dalam sistem?')">
                <?= icon('clipboard', 14) ?> Sesuaikan
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
