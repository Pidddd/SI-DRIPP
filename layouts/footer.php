<?php
/**
 * LAYOUT FOOTER (PROTOTYPE)
 * File: layouts/footer.php
 * Fungsi: Menutup struktur halaman, merender floating Role Switcher
 *         (simulasi $_SESSION['role']), toast container, dan memuat JS.
 */
?>
      <p class="footer">&copy; <?= date('Y') ?> SI-DRIPP · MKP Store Malang — Supplier HORECA Sirup DRIPP · <em>UI Prototype</em></p>
    </div><!-- /.content -->
  </div><!-- /.main -->
</div><!-- /.app -->

<!-- Floating Prototype Role Switcher -->
<nav class="role-switcher" id="role-switcher" aria-label="Simulasi role prototype">
  <span class="rs-label"><i class="pulse"></i>Prototype · Role</span>
  <?php foreach (role_catalog() as $key => $r): ?>
    <a class="rs-btn <?= $key === $role ? 'active' : '' ?>" id="rs-<?= e($key) ?>" href="<?= e(role_url($key)) ?>" title="<?= e($r['desc']) ?>">
      <?= e($r['label']) ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="toast-wrap" id="toast-wrap" aria-live="polite"></div>

<script src="<?= $root ?>assets/js/app.js"></script>
</body>
</html>
