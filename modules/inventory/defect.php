<?php
/**
 * UI MODUL INVENTORY - BARANG DEFECT
 * File: modules/inventory/defect.php
 * Fungsi: Menampilkan tabel laporan barang rusak/cacat beserta persetujuan verifikasi.
 * Business Rule: Entri defect baru WAJIB mengurangi nilai Stok_Aktual pada tabel Products.
 */

$root       = '../../';
$page_title = 'Laporan Barang Defect';
$page_sub   = 'Pencatatan dan verifikasi barang rusak, pecah, atau kedaluwarsa di gudang · MKP Store';
$active     = 'defects';
$allowed    = ['super_admin', 'admin', 'staf_gudang'];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Bar Aksi Atas -->
<div class="page-actions rise">
  <div>
    <span class="badge warn"><i class="dot"></i>3 Laporan Menunggu Verifikasi</span>
  </div>
  <div class="grow"></div>
  <button type="button" class="btn btn-primary" onclick="alert('Membuka form pelaporan barang defect baru...')">
    <?= icon('plus', 18) ?> Lapor Barang Defect
  </button>
</div>

<!-- Tabel Laporan Barang Rusak Statis -->
<div class="card flush rise">
  <div class="card-head pad">
    <div>
      <h2>Daftar Laporan Barang Defect / Rusak</h2>
      <p>Verifikasi laporan kerusakan fisik dari staf gudang sebelum memotong stok aktual</p>
    </div>
    <span class="badge danger"><i class="dot"></i>Total Kerugian Fisik: 5 Unit</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>Produk</th>
          <th>Jumlah</th>
          <th>Keterangan</th>
          <th class="text-right">Aksi Verifikasi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Defect 1 -->
        <tr>
          <td class="mono muted">1</td>
          <td class="nowrap">08 Okt 2026</td>
          <td>
            <strong>DRIPP Flavour Syrup Hazelnut 760ml</strong>
            <div class="muted">SKU: DRP-HZN-760 · Dilaporkan oleh: Daffa Febrianto</div>
          </td>
          <td class="mono"><strong style="color: var(--danger);">2 Botol</strong></td>
          <td>Botol retak pada bagian leher saat bongkar muat ekspedisi karton B-14</td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-sm" style="background: var(--success); color: #fff;" onclick="alert('Status diubah menjadi: Disetujui')">
                <?= icon('check', 14) ?> Setujui
              </button>
              <button type="button" class="btn btn-sm" style="background: var(--danger); color: #fff;" onclick="alert('Status diubah menjadi: Ditolak')">
                <?= icon('x', 14) ?> Tolak
              </button>
            </div>
          </td>
        </tr>

        <!-- Defect 2 -->
        <tr>
          <td class="mono muted">2</td>
          <td class="nowrap">07 Okt 2026</td>
          <td>
            <strong>DRIPP Fruit Pulp Blueberry 760ml</strong>
            <div class="muted">SKU: DRP-PLP-BLU · Dilaporkan oleh: Daffa Febrianto</div>
          </td>
          <td class="mono"><strong style="color: var(--danger);">1 Botol</strong></td>
          <td>Segel tutup botol ditemukan longgar/rembes saat pemeriksaan rak B-01</td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-sm" style="background: var(--success); color: #fff;" onclick="alert('Status diubah menjadi: Disetujui')">
                <?= icon('check', 14) ?> Setujui
              </button>
              <button type="button" class="btn btn-sm" style="background: var(--danger); color: #fff;" onclick="alert('Status diubah menjadi: Ditolak')">
                <?= icon('x', 14) ?> Tolak
              </button>
            </div>
          </td>
        </tr>

        <!-- Defect 3 -->
        <tr>
          <td class="mono muted">3</td>
          <td class="nowrap">05 Okt 2026</td>
          <td>
            <strong>DRIPP Powder Red Velvet 760g</strong>
            <div class="muted">SKU: DRP-PWD-RDV · Dilaporkan oleh: Daffa Febrianto</div>
          </td>
          <td class="mono"><strong style="color: var(--danger);">2 Pouch</strong></td>
          <td>Kemasan aluminium foil tergores dan menggumpal akibat kelembapan palet bawah</td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-sm" style="background: var(--success); color: #fff;" onclick="alert('Status diubah menjadi: Disetujui')">
                <?= icon('check', 14) ?> Setujui
              </button>
              <button type="button" class="btn btn-sm" style="background: var(--danger); color: #fff;" onclick="alert('Status diubah menjadi: Ditolak')">
                <?= icon('x', 14) ?> Tolak
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>