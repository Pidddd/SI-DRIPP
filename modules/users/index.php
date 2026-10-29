<?php
/**
 * MODUL PENGGUNA — MANAJEMEN AKUN & HAK AKSES (RBAC)
 * File: modules/users/index.php
 * Fungsi: Menampilkan daftar akun personel dari Supabase PostgreSQL beserta aksi Edit & Hapus.
 */

$root       = '../../';
$page_title = 'Manajemen Akun Pengguna';
$page_sub   = 'Pengaturan akun personel internal & pembagian hak akses (RBAC) · MKP Store';
$active     = 'users';
$allowed    = ['super_admin'];

require_once __DIR__ . '/../../layouts/header.php';

// Ambil daftar pengguna dari tabel users di Supabase
$q_users   = "SELECT id_user, nama, jenis_kelamin, username, role FROM users ORDER BY id_user ASC";
$res_users = pg_query($conn, $q_users);
$jml_user  = $res_users ? pg_num_rows($res_users) : 0;
?>

<?php if (isset($_GET['pesan'])): ?>
  <div class="callout success rise">
    <?= icon('check', 18) ?>
    <div>
      <?php
        if ($_GET['pesan'] === 'sukses_tambah_pegawai') echo 'Akun pengguna baru berhasil ditambahkan ke dalam sistem.';
        elseif ($_GET['pesan'] === 'sukses_update_pegawai') echo 'Data akun pengguna berhasil diperbarui.';
        elseif ($_GET['pesan'] === 'sukses_hapus_pegawai') echo 'Akun pengguna berhasil dihapus dari sistem.';
        else echo e($_GET['pesan']);
      ?>
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div><?= e($_GET['error']) ?></div>
  </div>
<?php endif; ?>

<!-- Bar Aksi Atas -->
<div class="page-actions rise">
  <div>
    <span class="badge info"><i class="dot"></i>Sistem Otentikasi Role-Based Access Control (RBAC)</span>
  </div>
  <div class="grow"></div>
  <a href="<?= BASE_URL ?>modules/users/create.php" class="btn btn-primary">
    <?= icon('plus', 18) ?> Tambah Pengguna Baru
  </a>
</div>

<!-- Tabel Daftar Pengguna -->
<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Daftar Akun Personel MKP Store</h2>
      <p>Daftar pengguna aktif yang memiliki otorisasi akses ke dalam Sistem Informasi SI-DRIPP</p>
    </div>
    <span class="badge success"><i class="dot"></i><?= (int)$jml_user ?> Akun Terdaftar</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Username</th>
          <th>Nama Lengkap</th>
          <th>Role</th>
          <th>Status</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res_users && $jml_user > 0): ?>
          <?php $no = 1; while ($u = pg_fetch_assoc($res_users)):
              $role_norm = normalisasi_role($u['role']);
          ?>
            <tr>
              <td class="mono muted"><?= $no++ ?></td>
              <td class="mono"><strong>@<?= e($u['username']) ?></strong></td>
              <td>
                <div class="cell-user">
                  <span class="avatar sm"><?= e(initials($u['nama'])) ?></span>
                  <div>
                    <strong><?= e($u['nama']) ?></strong>
                    <span><?= e($u['jenis_kelamin']) ?></span>
                  </div>
                </div>
              </td>
              <td><?= role_badge($role_norm) ?></td>
              <td><span class="badge success"><i class="dot"></i>Aktif</span></td>
              <td class="text-right">
                <div class="row-actions">
                  <a href="<?= BASE_URL ?>modules/users/create.php?edit=<?= (int)$u['id_user'] ?>" class="btn btn-soft btn-sm">
                    <?= icon('edit', 14) ?> Edit
                  </a>
                  <a href="<?= BASE_URL ?>modules/users/process/create.php?hapus_id=<?= (int)$u['id_user'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus akun @<?= e($u['username']) ?>?')">
                    <?= icon('trash', 14) ?> Hapus
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="table-empty">Belum ada akun pengguna yang terdaftar.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
