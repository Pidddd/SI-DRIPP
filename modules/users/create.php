<?php
/**
 * MODUL PENGGUNA — FORM TAMBAH / EDIT AKUN PERSONEL
 * File: modules/users/create.php
 * Fungsi: Antarmuka form registrasi dan pembaruan akun pengguna (RBAC).
 */

$root   = '../../';
$active = 'users';
$allowed = ['super_admin'];

require_once __DIR__ . '/../../config/bootstrap.php';

$edit_id   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_user = null;

if ($edit_id > 0) {
    $res_edit = pg_query_params($conn, "SELECT id_user, nama, jenis_kelamin, username, role FROM users WHERE id_user = $1", [$edit_id]);
    if ($res_edit && pg_num_rows($res_edit) > 0) {
        $edit_user = pg_fetch_assoc($res_edit);
    }
}

$page_title = $edit_user ? 'Edit Akun Pengguna' : 'Tambah Pengguna Baru';
$page_sub   = 'Pengaturan kredensial login dan pembagian hak akses personel MKP Store';

require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="page-actions rise">
  <a href="<?= BASE_URL ?>modules/users/index.php" class="btn btn-ghost">
    &larr; Kembali ke Daftar Pengguna
  </a>
</div>

<?php if (isset($_GET['error'])): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div>
      <?php
        if ($_GET['error'] === 'data_tidak_lengkap') echo 'Mohon lengkapi seluruh kolom wajib pada form.';
        elseif ($_GET['error'] === 'username_terpakai') echo 'Username tersebut sudah digunakan oleh personel lain. Gunakan username berbeda.';
        else echo 'Gagal menyimpan data pengguna ke database.';
      ?>
    </div>
  </div>
<?php endif; ?>

<div class="card card-form rise">
  <div class="card-head">
    <div>
      <h2><?= $edit_user ? 'Form Perbarui Data Personel' : 'Form Registrasi Personel Baru' ?></h2>
      <p>Pastikan username bersifat unik dan pilih role sesuai tanggung jawab kerja</p>
    </div>
    <span class="badge role-super_admin"><i class="dot"></i>Otorisasi Owner</span>
  </div>

  <form action="<?= BASE_URL ?>modules/users/process/create.php" method="POST">
    <?php if ($edit_user): ?>
      <input type="hidden" name="id_user" value="<?= (int)$edit_user['id_user'] ?>">
    <?php endif; ?>

    <div class="field">
      <label for="nama">Nama Lengkap Personel</label>
      <input type="text" id="nama" name="nama" class="input" maxlength="100" value="<?= e($edit_user['nama'] ?? '') ?>" placeholder="Contoh: Rina Wulandari" required>
    </div>

    <div class="form-row">
      <div class="field">
        <label for="username">Username Login</label>
        <input type="text" id="username" name="username" class="input mono" maxlength="50" value="<?= e($edit_user['username'] ?? '') ?>" placeholder="Contoh: rina.admin" required>
      </div>

      <div class="field">
        <label for="jenis_kelamin">Jenis Kelamin</label>
        <?php $jk_val = $edit_user['jenis_kelamin'] ?? 'Laki-laki'; ?>
        <select id="jenis_kelamin" name="jenis_kelamin" class="select" required>
          <option value="Laki-laki" <?= strtolower($jk_val) === 'laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
          <option value="Perempuan" <?= strtolower($jk_val) === 'perempuan' ? 'selected' : '' ?>>Perempuan</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label for="role">Role / Hak Akses Sistem</label>
        <?php $r_val = isset($edit_user['role']) ? normalisasi_role($edit_user['role']) : 'kasir'; ?>
        <select id="role" name="role" class="select" required>
          <option value="super_admin" <?= $r_val === 'super_admin' ? 'selected' : '' ?>>Super Admin (Owner)</option>
          <option value="admin" <?= $r_val === 'admin' ? 'selected' : '' ?>>Admin Operasional</option>
          <option value="kasir" <?= $r_val === 'kasir' ? 'selected' : '' ?>>Kasir (Point of Sale)</option>
          <option value="staf_gudang" <?= $r_val === 'staf_gudang' ? 'selected' : '' ?>>Staf Gudang &amp; Logistik</option>
        </select>
      </div>

      <div class="field">
        <label for="password">Password <?= $edit_user ? '(Kosongkan jika tidak diubah)' : 'Baru' ?></label>
        <input type="password" id="password" name="password" class="input" placeholder="<?= $edit_user ? 'Biarkan kosong bila tetap' : 'Minimal 6 karakter' ?>" <?= $edit_user ? '' : 'required' ?>>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <?= icon('check', 18) ?> <?= $edit_user ? 'Simpan Perubahan' : 'Daftarkan Akun' ?>
      </button>
      <a href="<?= BASE_URL ?>modules/users/index.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
