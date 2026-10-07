<?php
/**
 * MODUL PENGGUNA — MANAJEMEN AKUN & HAK AKSES (RBAC)
 * File: modules/users/index.php
 * Fungsi: Antarmuka Daftar Pengguna statis bergaya Bento Grid Toska MKP Store.
 */

$root       = '../../';
$page_title = 'Manajemen Akun Pengguna';
$page_sub   = 'Pengaturan akun personel internal & pembagian hak akses (RBAC) · MKP Store';
$active     = 'users';
$allowed    = ['super_admin'];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Bar Aksi Atas -->
<div class="page-actions rise">
  <div>
    <span class="badge info"><i class="dot"></i>Sistem Otentikasi Role-Based Access Control (RBAC)</span>
  </div>
  <div class="grow"></div>
  <button type="button" class="btn btn-primary" onclick="alert('Memuat form pendaftaran pengguna baru...')">
    <?= icon('plus', 18) ?> Tambah Pengguna Baru
  </button>
</div>

<!-- Tabel Daftar Pengguna -->
<div class="card flush rise">
  <div class="card-head pad">
    <div>
      <h2>Daftar Akun Personel MKP Store</h2>
      <p>Daftar pengguna aktif yang memiliki otorisasi akses ke dalam Sistem Informasi SI-DRIPP</p>
    </div>
    <span class="badge success"><i class="dot"></i>3 Akun Aktif</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Username</th>
          <th>Nama Lengkap</th>
          <th>Role</th>
          <th>Status</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Baris 1: Zicco Muhammad -->
        <tr>
          <td class="mono muted">1</td>
          <td class="mono"><strong>@zicco.muhammad</strong></td>
          <td>
            <div class="cell-user">
              <span class="avatar sm">ZM</span>
              <div>
                <strong>Zicco Muhammad</strong>
                <span>Owner / Super Admin MKP Store</span>
              </div>
            </div>
          </td>
          <td>
            <span class="badge role-super_admin"><i class="dot"></i>Super Admin</span>
          </td>
          <td>
            <span class="badge success"><i class="dot"></i>Aktif</span>
          </td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-soft btn-sm" onclick="alert('Memuat data form edit...')">
                <?= icon('edit', 14) ?> Edit
              </button>
              <button type="button" class="btn btn-danger-soft btn-sm" onclick="return confirm('Apakah Anda yakin ingin menonaktifkan akun ini?')">
                <?= icon('power', 14) ?> Nonaktifkan
              </button>
            </div>
          </td>
        </tr>

        <!-- Baris 2: Zulfikar Almiski -->
        <tr>
          <td class="mono muted">2</td>
          <td class="mono"><strong>@zulfikar.almiski</strong></td>
          <td>
            <div class="cell-user">
              <span class="avatar sm">ZA</span>
              <div>
                <strong>Zulfikar Almiski</strong>
                <span>Staf Kasir & Pelayanan Front-Desk</span>
              </div>
            </div>
          </td>
          <td>
            <span class="badge role-kasir"><i class="dot"></i>Kasir</span>
          </td>
          <td>
            <span class="badge success"><i class="dot"></i>Aktif</span>
          </td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-soft btn-sm" onclick="alert('Memuat data form edit...')">
                <?= icon('edit', 14) ?> Edit
              </button>
              <button type="button" class="btn btn-danger-soft btn-sm" onclick="return confirm('Apakah Anda yakin ingin menonaktifkan akun ini?')">
                <?= icon('power', 14) ?> Nonaktifkan
              </button>
            </div>
          </td>
        </tr>

        <!-- Baris 3: Daffa Febrianto -->
        <tr>
          <td class="mono muted">3</td>
          <td class="mono"><strong>@daffa.febrianto</strong></td>
          <td>
            <div class="cell-user">
              <span class="avatar sm">DF</span>
              <div>
                <strong>Daffa Febrianto</strong>
                <span>Staf Logistik & Gudang HORECA</span>
              </div>
            </div>
          </td>
          <td>
            <span class="badge role-staf_gudang"><i class="dot"></i>Staf Gudang</span>
          </td>
          <td>
            <span class="badge success"><i class="dot"></i>Aktif</span>
          </td>
          <td class="text-right">
            <div class="row-actions">
              <button type="button" class="btn btn-soft btn-sm" onclick="alert('Memuat data form edit...')">
                <?= icon('edit', 14) ?> Edit
              </button>
              <button type="button" class="btn btn-danger-soft btn-sm" onclick="return confirm('Apakah Anda yakin ingin menonaktifkan akun ini?')">
                <?= icon('power', 14) ?> Nonaktifkan
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
