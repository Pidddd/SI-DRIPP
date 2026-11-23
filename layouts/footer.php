<?php
/**
 * LAYOUT FOOTER
 * File: layouts/footer.php
 * Fungsi: Menutup struktur halaman, merender floating Role Switcher
 *         (aktif penuh untuk Super Admin, terkunci sesuai ranah untuk role lain),
 *         toast container, dan memuat JS.
 */
$footer_role          = isset($role) ? normalisasi_role($role) : 'super_admin';
$footer_orig_role     = isset($_SESSION['original_role']) ? normalisasi_role($_SESSION['original_role']) : $footer_role;
$footer_view_role     = isset($view_role) ? $view_role : $footer_role;
$footer_is_superadmin = ($footer_orig_role === 'super_admin' && $footer_role === 'super_admin');
?>
      <p class="footer">&copy; <?= date('Y') ?> SI-DRIPP · MKP Store Malang — Supplier HORECA Sirup DRIPP · <em>UI Prototype</em></p>
    </div><!-- /.content -->
  </div><!-- /.main -->
</div><!-- /.app -->

<?php if ($footer_is_superadmin): ?>
<!-- Floating Role Switcher (HANYA dirender untuk Super Admin asli) -->
<nav class="role-switcher" id="role-switcher" aria-label="Navigasi ranah role">
  <span class="rs-label">
    <i class="pulse"></i>Akses · Role
  </span>
  <?php foreach (role_catalog() as $key => $r): ?>
    <a class="rs-btn <?= $key === $footer_view_role ? 'active' : '' ?>" id="rs-<?= e($key) ?>" href="<?= e(role_url($key)) ?>" title="<?= e($r['desc']) ?>">
      <?= e($r['label']) ?>
    </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<!-- Global Password Confirmation Modal untuk Hapus Arsip Transaksi (Soft Caramel Cream) -->
<div class="confirm-backdrop" id="sidripp-pw-delete-backdrop" role="dialog" aria-modal="true" aria-labelledby="pw-delete-modal-title" aria-describedby="pw-delete-modal-msg" aria-hidden="true">
  <div class="confirm-modal is-danger" id="sidripp-pw-delete-modal" role="document">
    <div class="confirm-icon" aria-hidden="true">
      <?= icon('lock', 24) ?>
    </div>
    <h3 class="confirm-title" id="pw-delete-modal-title">Otorisasi Hapus Transaksi</h3>
    <p class="confirm-msg" id="pw-delete-modal-msg">Masukkan kata sandi akun Super Admin Anda untuk menghapus arsip transaksi ini secara permanen.</p>

    <form action="<?= BASE_URL ?>modules/reports/process/delete_report.php" method="POST" id="form-pw-delete-trx" class="confirm-pw-form">
      <input type="hidden" name="id_transaksi" id="pw-delete-id-transaksi" value="">
      <input type="hidden" name="return_to" id="pw-delete-return-to" value="reports">
      <div class="field confirm-pw-field">
        <label for="pw-delete-input">Kata Sandi Akun Super Admin</label>
        <input type="password" name="password_otorisasi" id="pw-delete-input" class="input" placeholder="Masukkan kata sandi akun Anda..." autocomplete="current-password" required>
      </div>
      <div class="confirm-actions">
        <button type="button" class="btn btn-ghost" id="pw-delete-btn-cancel">Batal</button>
        <button type="submit" class="btn btn-primary confirm-btn-confirm">Ya, Hapus Data</button>
      </div>
    </form>
  </div>
</div>

<!-- Global Custom Confirmation Modal (SI-DRIPP Soft Caramel Cream) -->
<div class="confirm-backdrop" id="sidripp-confirm-backdrop" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-msg" aria-hidden="true">
  <div class="confirm-modal" id="sidripp-confirm-modal" role="document">
    <div class="confirm-icon" aria-hidden="true">
      <?= icon('alert', 24) ?>
    </div>
    <h3 class="confirm-title" id="confirm-modal-title">Konfirmasi Tindakan</h3>
    <p class="confirm-msg" id="confirm-modal-msg">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
    <div class="confirm-actions">
      <button type="button" class="btn btn-ghost" id="confirm-btn-cancel">Batal</button>
      <button type="button" class="btn btn-primary confirm-btn-confirm" id="confirm-btn-ok">Ya, Lanjutkan</button>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toast-wrap" aria-live="polite"></div>

<script src="<?= $root ?>assets/js/app.js"></script>
</body>
</html>
