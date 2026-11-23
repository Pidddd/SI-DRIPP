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
$q_users   = "SELECT id_user, nama, jenis_kelamin, username, email, no_telp, role FROM users ORDER BY id_user ASC";
$res_users = pg_query($conn, $q_users);
$jml_user  = $res_users ? pg_num_rows($res_users) : 0;
$active_uid = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : (isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0);
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
          <th>Email</th>
          <th>No. Telepon</th>
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
                  <div>
                    <strong><?= e($u['nama']) ?></strong>
                    <span><?= e($u['jenis_kelamin']) ?></span>
                  </div>
                </div>
              </td>
              <td class="mono"><?= e($u['email'] ?? '-') ?></td>
              <td class="mono muted"><?= !empty($u['no_telp']) ? e($u['no_telp']) : '-' ?></td>
              <td><?= role_badge($role_norm) ?></td>
              <td><span class="badge success"><i class="dot"></i>Aktif</span></td>
              <td class="text-right">
                <div class="row-actions">
                  <a href="<?= BASE_URL ?>modules/users/create.php?edit=<?= (int)$u['id_user'] ?>" class="btn btn-soft btn-sm">
                    <?= icon('edit', 14) ?> Edit
                  </a>
                  <?php if ((int)$u['id_user'] === $active_uid): ?>
                    <button type="button" class="btn btn-ghost btn-sm" disabled title="Akun Anda yang sedang aktif login tidak dapat dihapus">
                      <?= icon('lock', 14) ?> Akun Aktif
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn btn-danger-soft btn-sm" data-delete-user="<?= (int)$u['id_user'] ?>" data-delete-username="<?= e($u['username']) ?>" data-delete-nama="<?= e($u['nama']) ?>">
                      <?= icon('trash', 14) ?> Hapus
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="table-empty">Belum ada akun pengguna yang terdaftar.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Konfirmasi Hapus Akun dengan Verifikasi Kata Sandi Super Admin (Soft Caramel Cream) -->
<div class="confirm-backdrop" id="sidripp-pw-delete-user-backdrop" role="dialog" aria-modal="true" aria-labelledby="pw-delete-user-title" aria-describedby="pw-delete-user-msg" aria-hidden="true">
  <div class="confirm-modal is-danger" id="sidripp-pw-delete-user-modal" role="document">
    <div class="confirm-icon" aria-hidden="true">
      <?= icon('lock', 24) ?>
    </div>
    <h3 class="confirm-title" id="pw-delete-user-title">Otorisasi Hapus Akun Pengguna</h3>
    <p class="confirm-msg" id="pw-delete-user-msg">Masukkan kata sandi akun Super Admin Anda untuk menghapus akun pengguna ini secara permanen.</p>

    <form action="<?= BASE_URL ?>modules/users/process/delete.php" method="POST" id="form-pw-delete-user" class="confirm-pw-form">
      <input type="hidden" name="id_user" id="pw-delete-user-id" value="">
      <div class="field confirm-pw-field">
        <label for="pw-delete-user-input">Kata Sandi Akun Super Admin</label>
        <input type="password" name="password_otorisasi" id="pw-delete-user-input" class="input" placeholder="Masukkan kata sandi akun Anda..." autocomplete="current-password" required>
      </div>
      <div class="confirm-actions">
        <button type="button" class="btn btn-ghost" id="pw-delete-user-cancel">Batal</button>
        <button type="submit" class="btn btn-primary confirm-btn-confirm">Ya, Hapus Akun</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
