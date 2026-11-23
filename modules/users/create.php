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
    $res_edit = pg_query_params($conn, "SELECT id_user, nama, jenis_kelamin, username, email, no_telp, role FROM users WHERE id_user = $1", [$edit_id]);
    if ($res_edit && pg_num_rows($res_edit) > 0) {
        $edit_user = pg_fetch_assoc($res_edit);
    }
}

$page_title = $edit_user ? 'Edit Akun Pengguna' : 'Tambah Pengguna Baru';
$page_sub   = 'Pengaturan kredensial login dan pembagian hak akses personel MKP Store';

require_once __DIR__ . '/../../layouts/header.php';

$active_uid = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : (isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0);
$is_editing_self = ($edit_user && $active_uid > 0 && (int)$edit_user['id_user'] === $active_uid);
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
        elseif ($_GET['error'] === 'email_terpakai') echo 'Email tersebut sudah terdaftar pada akun lain. Gunakan alamat email berbeda.';
        elseif ($_GET['error'] === 'email_tidak_valid') echo 'Format alamat email yang dimasukkan tidak valid.';
        elseif ($_GET['error'] === 'admin_password_kosong') echo 'Konfirmasi gagal: Kata sandi Super Admin wajib diisi untuk menyimpan perubahan.';
        elseif ($_GET['error'] === 'admin_password_salah') echo 'Konfirmasi gagal: Kata sandi Super Admin salah.';
        elseif ($_GET['error'] === 'turun_role_sendiri') echo 'Perubahan ditolak: Anda tidak dapat menurunkan peran (role) akun Anda sendiri yang sedang aktif login.';
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
        <label for="email">Email Resmi *</label>
        <input type="email" id="email" name="email" class="input" maxlength="100" value="<?= e($edit_user['email'] ?? '') ?>" placeholder="Contoh: rina@mkpstore.id" required>
      </div>

      <div class="field">
        <label for="no_telp">Nomor Telepon</label>
        <input type="tel" id="no_telp" name="no_telp" class="input mono" maxlength="20" value="<?= e($edit_user['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label for="role">Role / Hak Akses Sistem</label>
        <?php $r_val = isset($edit_user['role']) ? normalisasi_role($edit_user['role']) : 'kasir'; ?>
        <?php if ($is_editing_self): ?>
          <input type="hidden" name="role" value="super_admin">
          <select id="role" class="select" disabled>
            <option value="super_admin" selected>Super Admin (Owner)</option>
          </select>
          <small class="field-hint">Peran akun Super Admin yang sedang aktif login tidak dapat diturunkan.</small>
        <?php else: ?>
          <select id="role" name="role" class="select" required>
            <option value="super_admin" <?= $r_val === 'super_admin' ? 'selected' : '' ?>>Super Admin (Owner)</option>
            <option value="admin" <?= $r_val === 'admin' ? 'selected' : '' ?>>Admin Operasional</option>
            <option value="kasir" <?= $r_val === 'kasir' ? 'selected' : '' ?>>Kasir (Point of Sale)</option>
            <option value="staf_gudang" <?= $r_val === 'staf_gudang' ? 'selected' : '' ?>>Staf Gudang &amp; Logistik</option>
          </select>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="password">Password <?= $edit_user ? '(Kosongkan jika tidak diubah)' : 'Baru' ?></label>
        <input type="password" id="password" name="password" class="input" placeholder="<?= $edit_user ? 'Biarkan kosong bila tetap' : 'Minimal 6 karakter' ?>" <?= $edit_user ? '' : 'required' ?>>
      </div>
    </div>

    <?php if ($edit_user): ?>
      <div class="vendor-box-card mb-4">
        <div class="field field-tight">
          <label for="admin_password">Kata Sandi Super Admin (Otorisasi) *</label>
          <input type="password" id="admin_password" name="admin_password" class="input" placeholder="Masukkan kata sandi akun Super Admin Anda..." autocomplete="current-password" required>
          <small class="field-hint">Perubahan data atau peran akun memerlukan konfirmasi kata sandi Super Admin yang sedang aktif login.</small>
        </div>
      </div>
    <?php endif; ?>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <?= icon('check', 18) ?> <?= $edit_user ? 'Simpan Perubahan' : 'Daftarkan Akun' ?>
      </button>
      <a href="<?= BASE_URL ?>modules/users/index.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
