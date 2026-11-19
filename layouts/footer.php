<?php
/**
 * LAYOUT FOOTER
 * File: layouts/footer.php
 * Fungsi: Menutup struktur halaman, merender floating Role Switcher
 *         (aktif penuh untuk Super Admin, terkunci sesuai ranah untuk role lain),
 *         toast container, dan memuat JS.
 */
$footer_role          = isset($role) ? $role : 'super_admin';
$footer_view_role     = isset($view_role) ? $view_role : $footer_role;
$footer_is_superadmin = ($footer_role === 'super_admin');
?>
      <p class="footer">&copy; <?= date('Y') ?> SI-DRIPP · MKP Store Malang — Supplier HORECA Sirup DRIPP · <em>UI Prototype</em></p>
    </div><!-- /.content -->
  </div><!-- /.main -->
</div><!-- /.app -->

<?php if (in_array($footer_role, ['super_admin', 'admin'], true)): ?>
<!-- Floating Role Switcher (Hanya untuk Super Admin & Admin) -->
<nav class="role-switcher" id="role-switcher" aria-label="Navigasi ranah role">
  <span class="rs-label">
    <i class="pulse"></i><?= $footer_is_superadmin ? 'Akses · Role' : 'Role · Terkunci' ?>
  </span>
  <?php foreach (role_catalog() as $key => $r): ?>
    <?php if ($footer_is_superadmin || $key === $footer_role): ?>
      <a class="rs-btn <?= $key === $footer_view_role ? 'active' : '' ?>" id="rs-<?= e($key) ?>" href="<?= e(role_url($key)) ?>" title="<?= e($r['desc']) ?>">
        <?= e($r['label']) ?>
      </a>
    <?php else: ?>
      <span class="rs-btn is-locked" id="rs-<?= e($key) ?>" title="Terkunci — Anda login sebagai <?= e($me['label']) ?> (hanya bisa di ranah sendiri)">
        <?= icon('lock', 13) ?> <?= e($r['label']) ?>
      </span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

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
