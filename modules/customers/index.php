<?php
/**
 * Modul Kelola Mitra (Master Pelanggan / Mitra PT HORECA)
 * File: modules/customers/index.php
 */

$root       = '../../';
$page_title = 'Kelola Mitra';
$page_sub   = 'Manajemen data mitra usaha, kafe, hotel, dan pelanggan HORECA · MKP Store';
$active     = 'customers';
$allowed    = ['super_admin', 'admin', 'kasir'];

require_once __DIR__ . '/../../layouts/header.php';

$q = trim((string)($_GET['q'] ?? ''));
$customers = [];
$total_all = 0;
$db_error = null;

try {
    $pdo = get_pdo_connection();

    $stmt_count = $pdo->query('select count(*) as total from public.customers');
    $count_row = $stmt_count->fetch();
    $total_all = (int)($count_row['total'] ?? 0);

    if ($q !== '') {
        $sql = 'select id_customer, nama_pt, nama_pj, nik_pj, no_telp, alamat_toko, created_at
                from public.customers
                where lower(nama_pt) like :q_pt
                   or lower(nama_pj) like :q_pj
                   or lower(no_telp) like :q_telp
                order by nama_pt asc, id_customer desc';
        $stmt = $pdo->prepare($sql);
        $like = '%' . strtolower($q) . '%';
        $stmt->execute([
            ':q_pt'   => $like,
            ':q_pj'   => $like,
            ':q_telp' => $like,
        ]);
    } else {
        $sql = 'select id_customer, nama_pt, nama_pj, nik_pj, no_telp, alamat_toko, created_at
                from public.customers
                order by nama_pt asc, id_customer desc';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_error = 'Gagal memuat data mitra dari database. Silakan coba muat ulang halaman.';
}

$role_ui = (isset($view_role) && !empty($view_role))
    ? normalisasi_role($view_role)
    : normalisasi_role($_SESSION['role'] ?? '');
$can_delete = in_array($role_ui, ['super_admin', 'admin'], true);
$jml_tampil = count($customers);
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

<?php if ($db_error !== null): ?>
  <div class="callout danger rise">
    <?= icon('alert', 18) ?>
    <div><?= e($db_error) ?></div>
  </div>
<?php endif; ?>

<div class="page-actions rise">
  <form method="GET" action="" class="customer-search-form" id="form-search-customer">
    <div class="customer-search-box">
      <span class="customer-search-icon" aria-hidden="true"><?= icon('search', 16) ?></span>
      <input
        type="search"
        name="q"
        id="input-search-customer"
        class="input"
        value="<?= e($q) ?>"
        placeholder="Cari nama PT, nama PJ, atau nomor telepon..."
        autocomplete="off"
      >
    </div>
    <button type="submit" class="btn btn-soft">Cari</button>
    <?php if ($q !== ''): ?>
      <a href="<?= BASE_URL ?>modules/customers/index.php" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
  <div class="grow"></div>
  <button type="button" class="btn btn-primary" id="btn-open-add-customer">
    <?= icon('plus', 18) ?> Tambah Mitra
  </button>
</div>

<div class="card flush rise">
  <div class="card-head pad pb-4">
    <div>
      <h2>Daftar Mitra &amp; Pelanggan HORECA</h2>
      <p>
        <?php if ($q !== ''): ?>
          Menampilkan <?= (int)$jml_tampil ?> mitra untuk pencarian "<?= e($q) ?>" dari total <?= (int)$total_all ?> mitra terdaftar
        <?php else: ?>
          Data profil mitra resmi CV. Mitra Kembar Pradipta untuk transaksi kasir dan tagihan piutang
        <?php endif; ?>
      </p>
    </div>
    <span class="badge success" id="badge-customer-count"><i class="dot"></i><?= (int)$jml_tampil ?> Mitra</span>
  </div>

  <div class="table-wrap">
    <table class="table" id="table-customers">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th>Nama PT / Instansi</th>
          <th>Penanggung Jawab</th>
          <th>No. Telepon</th>
          <th>Alamat Toko</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($jml_tampil > 0): ?>
          <?php $no = 1; foreach ($customers as $c):
              $search_index = strtolower(trim(($c['nama_pt'] ?? '') . ' ' . ($c['nama_pj'] ?? '') . ' ' . ($c['no_telp'] ?? '')));
              $nik_val = trim((string)($c['nik_pj'] ?? ''));
              $created_label = !empty($c['created_at']) ? format_waktu_wib($c['created_at']) : '-';
          ?>
            <tr data-customer-search="<?= e($search_index) ?>">
              <td class="mono muted"><?= $no++ ?></td>
              <td>
                <strong><?= e($c['nama_pt']) ?></strong>
                <span class="customer-meta-sub">Terdaftar: <?= e($created_label) ?></span>
              </td>
              <td>
                <strong><?= e($c['nama_pj']) ?></strong>
                <span class="customer-meta-sub mono">NIK: <?= $nik_val !== '' ? e($nik_val) : '-' ?></span>
              </td>
              <td class="mono"><?= e($c['no_telp']) ?></td>
              <td>
                <div class="customer-address-cell"><?= e($c['alamat_toko']) ?></div>
              </td>
              <td class="text-right">
                <div class="row-actions">
                  <button
                    type="button"
                    class="btn btn-soft btn-sm btn-edit-customer"
                    data-id="<?= (int)$c['id_customer'] ?>"
                    data-nama-pt="<?= e($c['nama_pt']) ?>"
                    data-nama-pj="<?= e($c['nama_pj']) ?>"
                    data-nik-pj="<?= e($nik_val) ?>"
                    data-no-telp="<?= e($c['no_telp']) ?>"
                    data-alamat-toko="<?= e($c['alamat_toko']) ?>"
                  >
                    <?= icon('edit', 14) ?> Edit
                  </button>
                  <?php if ($can_delete): ?>
                    <form
                      method="POST"
                      action="<?= BASE_URL ?>modules/customers/process/delete.php"
                      class="inline-form"
                      data-confirm="Hapus data mitra <?= e($c['nama_pt']) ?> secara permanen?"
                      data-confirm-tone="danger"
                      data-confirm-title="Hapus Data Mitra"
                      data-confirm-ok="Ya, Hapus Mitra"
                    >
                      <input type="hidden" name="id_customer" value="<?= (int)$c['id_customer'] ?>">
                      <button type="submit" class="btn btn-danger-soft btn-sm">
                        <?= icon('trash', 14) ?> Hapus
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="table-empty">
              <?= $q !== '' ? 'Tidak ditemukan mitra yang cocok dengan kata kunci pencarian Anda.' : 'Belum ada data mitra yang terdaftar.' ?>
            </td>
          </tr>
        <?php endif; ?>
        <tr id="row-customer-empty-filter" class="is-hidden">
          <td colspan="6" class="table-empty">Tidak ditemukan mitra yang cocok dengan filter pencarian.</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div id="modal-kelola-mitra" class="modal-backdrop hidden" aria-modal="true" role="dialog" aria-labelledby="modal-customer-title">
  <div class="modal-dialog">
    <div class="modal-head">
      <div>
        <h2 id="modal-customer-title">Tambah Mitra Baru</h2>
        <p id="modal-customer-sub">Lengkapi identitas mitra usaha resmi yang terdaftar di MKP Store</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" id="btn-close-customer-modal" aria-label="Tutup modal">&times;</button>
    </div>

    <form action="<?= BASE_URL ?>modules/customers/process/save.php" method="POST" id="form-customer-modal" novalidate>
      <input type="hidden" name="id_customer" id="field_id_customer" value="">

      <div class="modal-body-pad">
        <div class="field">
          <label for="field_nama_pt">Nama PT / Nama Usaha / Kafe *</label>
          <input
            type="text"
            id="field_nama_pt"
            name="nama_pt"
            class="input"
            maxlength="100"
            placeholder="Contoh: PT. Kopi Bersama / Kopi Studio 24"
            required
          >
        </div>

        <div class="form-row">
          <div class="field">
            <label for="field_nama_pj">Penanggung Jawab (PJ) *</label>
            <input
              type="text"
              id="field_nama_pj"
              name="nama_pj"
              class="input"
              maxlength="100"
              placeholder="Nama lengkap PJ"
              required
            >
          </div>
          <div class="field">
            <label for="field_nik_pj">NIK Penanggung Jawab (Opsional)</label>
            <input
              type="text"
              id="field_nik_pj"
              name="nik_pj"
              class="input mono"
              maxlength="20"
              placeholder="Opsional (16 digit NIK KTP)"
            >
          </div>
        </div>

        <div class="field">
          <label for="field_no_telp">No. Telepon / WhatsApp Toko *</label>
          <input
            type="text"
            id="field_no_telp"
            name="no_telp"
            class="input mono"
            maxlength="20"
            placeholder="Contoh: 081234567890"
            required
          >
        </div>

        <div class="field">
          <label for="field_alamat_toko">Alamat Toko / Outlet HORECA *</label>
          <textarea
            id="field_alamat_toko"
            name="alamat_toko"
            class="textarea"
            placeholder="Alamat lengkap outlet atau kedai mitra"
            required
          ></textarea>
        </div>
      </div>

      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" id="btn-cancel-customer-modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="btn-submit-customer-modal">
          <?= icon('check', 16) ?> <span id="btn-submit-customer-label">Simpan Mitra</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('modal-kelola-mitra');
  var form = document.getElementById('form-customer-modal');
  var titleEl = document.getElementById('modal-customer-title');
  var subEl = document.getElementById('modal-customer-sub');
  var submitLabelEl = document.getElementById('btn-submit-customer-label');

  var fId = document.getElementById('field_id_customer');
  var fNamaPt = document.getElementById('field_nama_pt');
  var fNamaPj = document.getElementById('field_nama_pj');
  var fNikPj = document.getElementById('field_nik_pj');
  var fNoTelp = document.getElementById('field_no_telp');
  var fAlamat = document.getElementById('field_alamat_toko');

  var btnOpenAdd = document.getElementById('btn-open-add-customer');
  var btnClose = document.getElementById('btn-close-customer-modal');
  var btnCancel = document.getElementById('btn-cancel-customer-modal');

  function openCustomerModal(mode, data) {
    if (!modal || !form) return;
    if (mode === 'edit' && data) {
      titleEl.textContent = 'Edit Data Mitra';
      subEl.textContent = 'Perbarui profil usaha, kontak, atau alamat mitra terdaftar';
      submitLabelEl.textContent = 'Simpan Perubahan';
      fId.value = data.id || '';
      fNamaPt.value = data.namaPt || '';
      fNamaPj.value = data.namaPj || '';
      fNikPj.value = data.nikPj || '';
      fNoTelp.value = data.noTelp || '';
      fAlamat.value = data.alamatToko || '';
    } else {
      titleEl.textContent = 'Tambah Mitra Baru';
      subEl.textContent = 'Lengkapi identitas mitra usaha resmi yang terdaftar di MKP Store';
      submitLabelEl.textContent = 'Simpan Mitra';
      form.reset();
      fId.value = '';
    }
    modal.classList.remove('hidden');
    window.setTimeout(function () {
      if (fNamaPt) fNamaPt.focus();
    }, 40);
  }

  function closeCustomerModal() {
    if (!modal) return;
    modal.classList.add('hidden');
  }

  if (btnOpenAdd) {
    btnOpenAdd.addEventListener('click', function () {
      openCustomerModal('create', null);
    });
  }
  if (btnClose) btnClose.addEventListener('click', closeCustomerModal);
  if (btnCancel) btnCancel.addEventListener('click', closeCustomerModal);

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeCustomerModal();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
      closeCustomerModal();
    }
  });

  var editButtons = document.querySelectorAll('.btn-edit-customer');
  editButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      openCustomerModal('edit', {
        id: btn.getAttribute('data-id') || '',
        namaPt: btn.getAttribute('data-nama-pt') || '',
        namaPj: btn.getAttribute('data-nama-pj') || '',
        nikPj: btn.getAttribute('data-nik-pj') || '',
        noTelp: btn.getAttribute('data-no-telp') || '',
        alamatToko: btn.getAttribute('data-alamat-toko') || ''
      });
    });
  });

  function showValidationAlert(message, focusEl) {
    if (typeof window.showAlertModal === 'function') {
      window.showAlertModal(message, {
        title: 'Validasi Data Mitra',
        tone: 'warn',
        focusEl: focusEl
      });
    } else if (focusEl) {
      focusEl.focus();
    }
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      if (fNamaPt.value.trim() === '') {
        e.preventDefault();
        showValidationAlert('Nama PT / Nama Usaha wajib diisi terlebih dahulu.', fNamaPt);
        return;
      }
      if (fNamaPj.value.trim() === '') {
        e.preventDefault();
        showValidationAlert('Nama Penanggung Jawab (PJ) wajib diisi terlebih dahulu.', fNamaPj);
        return;
      }
      if (fNoTelp.value.trim() === '') {
        e.preventDefault();
        showValidationAlert('Nomor telepon / WhatsApp mitra wajib diisi terlebih dahulu.', fNoTelp);
        return;
      }
      if (fAlamat.value.trim() === '') {
        e.preventDefault();
        showValidationAlert('Alamat toko / outlet mitra wajib diisi terlebih dahulu.', fAlamat);
        return;
      }
    });
  }

  var searchInput = document.getElementById('input-search-customer');
  var rows = document.querySelectorAll('#table-customers tbody tr[data-customer-search]');
  var emptyFilterRow = document.getElementById('row-customer-empty-filter');
  var countBadge = document.getElementById('badge-customer-count');

  if (searchInput && rows.length > 0) {
    searchInput.addEventListener('input', function () {
      var keyword = searchInput.value.trim().toLowerCase();
      var visible = 0;

      rows.forEach(function (row) {
        var hay = row.getAttribute('data-customer-search') || '';
        var match = keyword === '' || hay.indexOf(keyword) !== -1;
        row.classList.toggle('is-hidden', !match);
        if (match) visible++;
      });

      if (emptyFilterRow) {
        emptyFilterRow.classList.toggle('is-hidden', visible > 0);
      }
      if (countBadge) {
        countBadge.innerHTML = '<i class="dot"></i>' + visible + ' Mitra';
      }
    });
  }
})();
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
