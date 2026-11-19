/* ==========================================================================
   SI-DRIPP — UI Prototype Interactions (Vanilla JS)
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var rupiah = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };

  /* ---------- Toast ---------- */
  function toast(msg, type) {
    var wrap = $('#toast-wrap');
    if (!wrap) return;
    var t = document.createElement('div');
    t.className = 'toast' + (type ? ' ' + type : '');
    t.innerHTML = '<svg class="ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><span></span>';
    t.lastChild.textContent = msg;
    wrap.appendChild(t);
    setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 300); }, 3200);
  }
  window.sidrippToast = toast;

  /* ---------- Dark Mode / Light Mode Toggle ---------- */
  var btnTheme = $('#btn-theme-toggle');
  if (btnTheme) {
    btnTheme.addEventListener('click', function () {
      var html = document.documentElement;
      var isDark = html.getAttribute('data-theme') === 'dark';
      var next = isDark ? 'light' : 'dark';
      if (next === 'dark') {
        html.setAttribute('data-theme', 'dark');
      } else {
        html.removeAttribute('data-theme');
      }
      try { localStorage.setItem('sidripp_theme', next); } catch (e) {}
    });
  }

  /* ---------- Sidebar mobile ---------- */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-nav-toggle]')) document.body.classList.toggle('nav-open');
    else if (e.target.closest('[data-nav-close]')) document.body.classList.remove('nav-open');

    /* tutup dropdown profil bila klik di luar */
    var prof = $('#profile-menu');
    if (prof && prof.open && !e.target.closest('#profile-menu')) prof.open = false;
  });

  /* ---------- Modal ---------- */
  function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('open'); var f = $('input,select,textarea', m); if (f) setTimeout(function () { f.focus(); }, 60); } }
  function closeModal(m) { if (m) m.classList.remove('open'); }
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-modal-open]');
    if (o) { e.preventDefault(); openModal(o.getAttribute('data-modal-open')); return; }
    if (e.target.closest('[data-modal-close]')) { closeModal(e.target.closest('.modal')); return; }
    if (e.target.classList && e.target.classList.contains('modal')) closeModal(e.target);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (confirmBackdrop && confirmBackdrop.classList.contains('is-open')) {
        e.preventDefault();
        settleConfirm(false);
        return;
      }
      $$('.modal.open').forEach(closeModal);
    }
  });

  /* ---------- Global Custom Confirmation Modal (Promise-based) ---------- */
  var confirmBackdrop = $('#sidripp-confirm-backdrop');
  var confirmModalEl  = $('#sidripp-confirm-modal');
  var confirmTitleEl  = $('#confirm-modal-title');
  var confirmMsgEl    = $('#confirm-modal-msg');
  var confirmBtnOk    = $('#confirm-btn-ok');
  var confirmBtnCancel = $('#confirm-btn-cancel');
  var confirmResolver = null;

  function settleConfirm(result) {
    if (!confirmBackdrop) return;
    confirmBackdrop.classList.remove('is-open');
    confirmBackdrop.setAttribute('aria-hidden', 'true');
    if (confirmModalEl) confirmModalEl.classList.remove('is-danger');
    var resolve = confirmResolver;
    confirmResolver = null;
    if (typeof resolve === 'function') resolve(Boolean(result));
  }

  function sidrippConfirm(message, options) {
    var opts = options || {};
    if (!confirmBackdrop || !confirmModalEl || !confirmBtnOk || !confirmBtnCancel) {
      return Promise.resolve(window.confirm(message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?'));
    }

    // Jika ada modal konfirmasi sebelumnya yang belum selesai, tutup dengan false
    if (confirmResolver) settleConfirm(false);

    confirmTitleEl.textContent   = opts.title || 'Konfirmasi Tindakan';
    confirmMsgEl.textContent     = message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    confirmBtnCancel.textContent = opts.cancelText || 'Batal';
    confirmBtnOk.textContent     = opts.okText || 'Ya, Lanjutkan';

    if (opts.danger) {
      confirmModalEl.classList.add('is-danger');
    } else {
      confirmModalEl.classList.remove('is-danger');
    }

    confirmBackdrop.classList.add('is-open');
    confirmBackdrop.setAttribute('aria-hidden', 'false');
    setTimeout(function () { confirmBtnOk.focus(); }, 60);

    return new Promise(function (resolve) {
      confirmResolver = resolve;
    });
  }
  window.sidrippConfirm = sidrippConfirm;

  if (confirmBackdrop && confirmBtnOk && confirmBtnCancel) {
    confirmBtnOk.addEventListener('click', function () { settleConfirm(true); });
    confirmBtnCancel.addEventListener('click', function () { settleConfirm(false); });
    confirmBackdrop.addEventListener('click', function (e) {
      if (e.target === confirmBackdrop) settleConfirm(false);
    });
  }

  function extractConfirmOptions(el) {
    var isDanger = el.getAttribute('data-confirm-tone') === 'danger' ||
      el.classList.contains('btn-danger-soft') ||
      el.classList.contains('btn-danger');
    return {
      title: el.getAttribute('data-confirm-title') || (isDanger ? 'Konfirmasi Hapus Data' : 'Konfirmasi Tindakan'),
      okText: el.getAttribute('data-confirm-ok') || (isDanger ? 'Ya, Hapus' : 'Ya, Lanjutkan'),
      cancelText: el.getAttribute('data-confirm-cancel') || 'Batal',
      danger: isDanger
    };
  }

  // Intersepsi klik pada tautan <a> atau tombol <button> dengan atribut data-confirm
  document.addEventListener('click', function (e) {
    var el = e.target.closest('a[data-confirm], button[data-confirm], input[type="submit"][data-confirm]');
    if (!el) return;

    if (el.dataset.confirmBypass === '1') {
      delete el.dataset.confirmBypass;
      return;
    }

    e.preventDefault();
    var msg  = el.getAttribute('data-confirm');
    var opts = extractConfirmOptions(el);

    sidrippConfirm(msg, opts).then(function (ok) {
      if (!ok) return;

      if (el.tagName === 'A' && el.href) {
        window.location.href = el.href;
        return;
      }

      var isSubmitBtn = (el.tagName === 'BUTTON' && (!el.type || el.type === 'submit')) ||
        (el.tagName === 'INPUT' && el.type === 'submit');
      var form = el.form || el.closest('form');

      if (isSubmitBtn && form) {
        if (typeof form.reportValidity === 'function' && !form.reportValidity()) return;
        form.dataset.confirmPassed = '1';
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(el);
        } else {
          form.submit();
        }
        return;
      }

      el.dataset.confirmBypass = '1';
      el.click();
    });
  });

  // Intersepsi submit pada <form data-confirm="...">
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm')) return;
    if (e.defaultPrevented) return;

    if (form.dataset.confirmPassed === '1') {
      delete form.dataset.confirmPassed;
      return;
    }

    e.preventDefault();
    var msg  = form.getAttribute('data-confirm');
    var opts = extractConfirmOptions(form);

    sidrippConfirm(msg, opts).then(function (ok) {
      if (!ok) return;
      form.dataset.confirmPassed = '1';
      form.submit();
    });
  });

  /* ---------- Form prototype (tanpa backend) ---------- */
  $$('form[data-proto]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var m = f.closest('.modal');
      closeModal(m);
      toast(f.getAttribute('data-proto'));
      f.reset();
    });
  });

  /* ---------- Filter tabel ---------- */
  $$('[data-filter-table]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var q = inp.value.toLowerCase();
      $$('#' + inp.getAttribute('data-filter-table') + ' tbody tr').forEach(function (tr) {
        tr.style.display = tr.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
      });
    });
  });

  /* ---------- Demo chips (login) ---------- */
  $$('[data-demo-user]').forEach(function (b) {
    b.addEventListener('click', function () {
      $('#username').value = b.getAttribute('data-demo-user');
      $('#password').value = 'demo1234';
      $('#password').focus();
    });
  });

  /* ---------- Reports Page: Filter Periode 2 Mode & Accordion Kategori ---------- */
  var reportFilterForm = $('#form-report-filter');
  if (reportFilterForm) {
    var modeRadios   = $$('input[name="mode"]', reportFilterForm);
    var tabRange     = $('#tab-mode-range');
    var tabMonthly   = $('#tab-mode-monthly');
    var panelRange   = $('#panel-mode-range');
    var panelMonthly = $('#panel-mode-monthly');
    var minUnitRange = $('#min_unit_range');
    var minUnitMonth = $('#min_unit_monthly');
    var monthChips   = $$('.month-chip', reportFilterForm);

    function syncMonthChipClasses() {
      monthChips.forEach(function (chip) {
        var cb = $('input[type="checkbox"]', chip);
        if (cb) chip.classList.toggle('is-checked', cb.checked);
      });
    }

    function syncReportModeUI() {
      var checked   = $('input[name="mode"]:checked', reportFilterForm);
      var isMonthly = checked && checked.value === 'monthly';

      if (tabRange) tabRange.classList.toggle('is-active', !isMonthly);
      if (tabMonthly) tabMonthly.classList.toggle('is-active', isMonthly);
      if (panelRange) panelRange.classList.toggle('is-hidden', isMonthly);
      if (panelMonthly) panelMonthly.classList.toggle('is-hidden', !isMonthly);

      // Pastikan hanya 1 input min_unit aktif yang dikirim ke server
      if (minUnitRange && minUnitMonth) {
        if (isMonthly) {
          minUnitMonth.value    = minUnitRange.value;
          minUnitRange.disabled = true;
          minUnitMonth.disabled = false;
        } else {
          minUnitRange.value    = minUnitMonth.value;
          minUnitRange.disabled = false;
          minUnitMonth.disabled = true;
        }
      }
    }

    modeRadios.forEach(function (r) {
      r.addEventListener('change', syncReportModeUI);
    });

    if (minUnitRange && minUnitMonth) {
      minUnitRange.addEventListener('input', function () { minUnitMonth.value = minUnitRange.value; });
      minUnitMonth.addEventListener('input', function () { minUnitRange.value = minUnitMonth.value; });
    }

    monthChips.forEach(function (chip) {
      var cb = $('input[type="checkbox"]', chip);
      if (cb) cb.addEventListener('change', syncMonthChipClasses);
    });

    var btnBulanSemua = $('#btn-bulan-semua');
    if (btnBulanSemua) {
      btnBulanSemua.addEventListener('click', function () {
        $$('input[name="bulan[]"]', reportFilterForm).forEach(function (cb) { cb.checked = true; });
        syncMonthChipClasses();
      });
    }

    var btnBulanIni = $('#btn-bulan-ini');
    if (btnBulanIni) {
      btnBulanIni.addEventListener('click', function () {
        var curMonth = String(new Date().getMonth() + 1);
        $$('input[name="bulan[]"]', reportFilterForm).forEach(function (cb) { cb.checked = (cb.value === curMonth); });
        syncMonthChipClasses();
      });
    }

    var btnBulanReset = $('#btn-bulan-reset');
    if (btnBulanReset) {
      btnBulanReset.addEventListener('click', function () {
        $$('input[name="bulan[]"]', reportFilterForm).forEach(function (cb) { cb.checked = false; });
        syncMonthChipClasses();
      });
    }

    reportFilterForm.addEventListener('submit', function (e) {
      var checkedMode = $('input[name="mode"]:checked', reportFilterForm);
      if (checkedMode && checkedMode.value === 'monthly') {
        var checkedMonths = $$('input[name="bulan[]"]:checked', reportFilterForm);
        if (checkedMonths.length === 0) {
          e.preventDefault();
          toast('Centang minimal 1 bulan sebelum menerapkan filter Per Bulan.', 'warn');
        }
      }
    });

    syncMonthChipClasses();
    syncReportModeUI();
  }

  // Accordion Rincian Produk di dalam Tabel Agregasi Kategori (Card 2)
  var reportCatRows  = $$('.report-cat-row[data-report-acc]');
  var btnToggleAllCat = $('#btn-toggle-all-report-cat');

  if (reportCatRows.length > 0) {
    function setCatRowExpanded(row, expand) {
      var targetId = row.getAttribute('data-report-acc');
      var detail   = targetId ? document.getElementById(targetId) : null;
      var arrow    = $('.report-acc-arrow', row);
      row.classList.toggle('is-open', expand);
      row.setAttribute('aria-expanded', expand ? 'true' : 'false');
      if (arrow) arrow.textContent = expand ? '\u25BE' : '\u25B8';
      if (detail) detail.classList.toggle('is-hidden', !expand);
    }

    function syncToggleAllBtn() {
      if (!btnToggleAllCat) return;
      var allOpen = reportCatRows.every(function (r) { return r.classList.contains('is-open'); });
      btnToggleAllCat.textContent = allOpen ? 'Tutup Semua Rincian' : 'Buka Semua Rincian';
      btnToggleAllCat.setAttribute('aria-pressed', allOpen ? 'true' : 'false');
    }

    reportCatRows.forEach(function (row) {
      row.addEventListener('click', function () {
        setCatRowExpanded(row, !row.classList.contains('is-open'));
        syncToggleAllBtn();
      });
      row.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          setCatRowExpanded(row, !row.classList.contains('is-open'));
          syncToggleAllBtn();
        }
      });
    });

    if (btnToggleAllCat) {
      btnToggleAllCat.addEventListener('click', function () {
        var allOpen = reportCatRows.every(function (r) { return r.classList.contains('is-open'); });
        reportCatRows.forEach(function (r) { setCatRowExpanded(r, !allOpen); });
        syncToggleAllBtn();
      });
      syncToggleAllBtn();
    }
  }

  // Ekspor Langsung ke Google Sheets (AJAX fetch -> Modal Soft Caramel Cream)
  var btnExportGsheet  = $('#btn-export-gsheet');
  var gsheetBackdrop   = $('#gsheet-result-backdrop');
  var gsheetModal      = $('#gsheet-result-modal');
  var gsheetTitle      = $('#gsheet-modal-title');
  var gsheetMsg        = $('#gsheet-modal-msg');
  var gsheetBtnOpen    = $('#gsheet-btn-open');
  var gsheetBtnClose   = $('#gsheet-btn-close');

  function closeGsheetModal() {
    if (!gsheetBackdrop) return;
    gsheetBackdrop.classList.remove('is-open');
    gsheetBackdrop.setAttribute('aria-hidden', 'true');
  }

  function showGsheetModal(isSuccess, title, message, url) {
    if (!gsheetBackdrop || !gsheetModal) {
      toast(message, isSuccess ? 'success' : 'warn');
      return;
    }
    gsheetModal.classList.toggle('is-success', Boolean(isSuccess));
    gsheetModal.classList.toggle('is-danger', !isSuccess);
    if (gsheetTitle) gsheetTitle.textContent = title;
    if (gsheetMsg) gsheetMsg.textContent = message;

    if (gsheetBtnOpen) {
      if (isSuccess && url) {
        gsheetBtnOpen.href = url;
        gsheetBtnOpen.classList.remove('is-hidden');
      } else {
        gsheetBtnOpen.href = '#';
        gsheetBtnOpen.classList.add('is-hidden');
      }
    }

    gsheetBackdrop.classList.add('is-open');
    gsheetBackdrop.setAttribute('aria-hidden', 'false');
    setTimeout(function () {
      if (isSuccess && url && gsheetBtnOpen) gsheetBtnOpen.focus();
      else if (gsheetBtnClose) gsheetBtnClose.focus();
    }, 60);
  }

  if (gsheetBackdrop) {
    if (gsheetBtnClose) gsheetBtnClose.addEventListener('click', closeGsheetModal);
    gsheetBackdrop.addEventListener('click', function (e) {
      if (e.target === gsheetBackdrop) closeGsheetModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && gsheetBackdrop.classList.contains('is-open')) {
        e.preventDefault();
        closeGsheetModal();
      }
    });
  }

  if (btnExportGsheet) {
    btnExportGsheet.addEventListener('click', function () {
      var endpoint = btnExportGsheet.getAttribute('data-endpoint');
      if (!endpoint) return;

      var labelEl      = $('.btn-gsheet-label', btnExportGsheet);
      var originalText = labelEl ? labelEl.textContent : 'Simpan ke Google Sheets';

      btnExportGsheet.disabled = true;
      btnExportGsheet.classList.add('is-loading');
      if (labelEl) labelEl.textContent = 'Menyimpan ke Drive...';

      fetch(endpoint, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(function (res) {
          return res.text().then(function (text) {
            var parsed = null;
            try { parsed = JSON.parse(text); } catch (err) {}
            return { ok: res.ok, status: res.status, data: parsed, raw: text };
          });
        })
        .then(function (result) {
          var data = result.data;
          if (result.ok && data && data.status === 'success' && data.url) {
            toast('Laporan berhasil disimpan ke Google Sheets!', 'success');
            showGsheetModal(
              true,
              'Berhasil Disimpan ke Google Sheets',
              data.message || 'Laporan penjualan berhasil disimpan ke Google Drive Anda.',
              data.url
            );
          } else {
            var errMsg = (data && data.message)
              ? data.message
              : ('Gagal menyimpan ke Google Sheets (HTTP ' + result.status + ').');
            toast(errMsg, 'warn');
            showGsheetModal(false, 'Gagal Menyimpan ke Google Sheets', errMsg, '');
          }
        })
        .catch(function (err) {
          var netMsg = 'Terjadi kesalahan jaringan saat menghubungi server: ' + (err && err.message ? err.message : 'Koneksi terputus');
          toast(netMsg, 'warn');
          showGsheetModal(false, 'Koneksi Gagal', netMsg, '');
        })
        .finally(function () {
          btnExportGsheet.disabled = false;
          btnExportGsheet.classList.remove('is-loading');
          if (labelEl) labelEl.textContent = originalText;
        });
    });
  }

  /* ======================================================================
     POS — Keranjang Kasir
     Data produk disuplai halaman via window.POS_PRODUCTS & POS_SEED
     ====================================================================== */
  if (window.POS_PRODUCTS) {
    var MOQ = 24, DISC = 0.05;
    var products = {};
    window.POS_PRODUCTS.forEach(function (p) { products[p.id] = p; });
    var cart = {};
    (window.POS_SEED || []).forEach(function (s) { cart[s.id] = s.qty; });

    var elItems = $('#cart-items'), elSub = $('#sum-sub'), elDisc = $('#sum-disc'),
        elDiscRow = $('#sum-disc-row'), elTotal = $('#sum-total'), elBtn = $('#btn-checkout'),
        elCount = $('#cart-count'), elHint = $('#moq-hint');

    function totals() {
      var sub = 0, qty = 0;
      Object.keys(cart).forEach(function (id) { sub += products[id].jual * cart[id]; qty += cart[id]; });
      var disc = qty >= MOQ ? Math.round(sub * DISC) : 0;
      return { sub: sub, qty: qty, disc: disc, total: sub - disc };
    }

    function render() {
      var ids = Object.keys(cart);
      if (!ids.length) {
        elItems.innerHTML = '<div class="cart-empty"><svg class="ico" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg><strong>Keranjang kosong</strong><p class="muted" style="font-size:12.5px">Pilih produk dari katalog untuk memulai transaksi.</p></div>';
      } else {
        elItems.innerHTML = ids.map(function (id) {
          var p = products[id], q = cart[id];
          var hasGrosir = q >= 24;
          return '<div class="cart-item" data-id="' + id + '">' +
            '<div class="thumb">' + p.thumb + '</div>' +
            '<div><h4>' + p.nama + '</h4><div class="unit">' + rupiah(p.jual) + ' · ' + p.satuan + '</div>' +
            (hasGrosir ? '<div style="margin-top:3px"><span class="badge success" style="font-size:10px;padding:2px 7px;"><i class="dot"></i>Diskon Grosir 2+ Karton Aktif</span></div>' : '') +
            '<div style="display:flex;align-items:center;gap:8px;margin-top:6px">' +
            '<div class="qty"><button type="button" data-act="dec" aria-label="Kurangi">&minus;</button>' +
            '<input type="number" min="1" max="' + p.stok + '" value="' + q + '" data-act="set" aria-label="Kuantitas ' + p.nama + '">' +
            '<button type="button" data-act="inc" aria-label="Tambah">+</button></div>' +
            '<button type="button" class="cart-remove" data-act="del" aria-label="Hapus item"><svg class="ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button></div></div>' +
            '<div class="line-total">' + rupiah(p.jual * q) + '</div></div>';
        }).join('');
      }
      var t = totals();
      elSub.textContent = rupiah(t.sub);
      elDisc.textContent = '− ' + rupiah(t.disc);
      elDiscRow.style.display = t.disc ? '' : 'none';
      elTotal.textContent = rupiah(t.total);
      elCount.textContent = t.qty + ' unit';
      elBtn.disabled = !ids.length;
      elBtn.querySelector('span').textContent = ids.length ? 'Proses & Cetak Invoice (' + rupiah(t.total) + ')' : 'Proses & Cetak Invoice';
      if (!ids.length) { elHint.style.display = 'none'; }
      else {
        elHint.style.display = 'flex';
        if (t.qty >= MOQ) { elHint.className = 'moq-hint ok'; elHint.querySelector('span').textContent = 'Diskon grosir 5% aktif (MOQ 2 Karton / 24 botol terpenuhi)'; }
        else { elHint.className = 'moq-hint'; elHint.querySelector('span').textContent = 'Tambah ' + (MOQ - t.qty) + ' botol lagi untuk diskon grosir 5% (min. 2 karton)'; }
      }
    }

    function add(id, n) {
      var p = products[id];
      if (!p || p.stok <= 0) return;
      var next = (cart[id] || 0) + n;
      if (next > p.stok) { toast('Stok ' + p.nama + ' hanya ' + p.stok + ' botol/unit', 'warn'); next = p.stok; }
      if (next < 1) { delete cart[id]; } else { cart[id] = next; }
      render();
    }

    document.addEventListener('click', function (e) {
      var addBtn = e.target.closest('[data-add]');
      if (addBtn) { add(addBtn.getAttribute('data-add'), 1); var p = products[addBtn.getAttribute('data-add')]; toast(p.nama + ' ditambahkan'); return; }
      var row = e.target.closest('.cart-item'), act = e.target.closest('[data-act]');
      if (row && act) {
        var id = row.getAttribute('data-id'), a = act.getAttribute('data-act');
        if (a === 'inc') add(id, 1);
        if (a === 'dec') add(id, -1);
        if (a === 'del') { delete cart[id]; render(); }
      }
    });
    document.addEventListener('change', function (e) {
      if (e.target.matches('.cart-item input[data-act="set"]')) {
        var id = e.target.closest('.cart-item').getAttribute('data-id');
        var v = parseInt(e.target.value, 10);
        delete cart[id];
        if (isNaN(v) || v < 1) { render(); } else { add(id, v); }
      }
    });

    var clr = $('#btn-clear'); if (clr) clr.addEventListener('click', function () { cart = {}; render(); });

    /* Filter katalog */
    var cat = 'all', q = '';
    function filterCatalog() {
      $$('.product-card').forEach(function (c) {
        var okCat = cat === 'all' || c.getAttribute('data-cat') === cat;
        var okQ = c.getAttribute('data-name').toLowerCase().indexOf(q) > -1;
        c.style.display = okCat && okQ ? '' : 'none';
      });
    }
    $$('[data-cat-filter]').forEach(function (b) {
      b.addEventListener('click', function () {
        $$('[data-cat-filter]').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active'); cat = b.getAttribute('data-cat-filter'); filterCatalog();
      });
    });
    var search = $('#pos-search'); if (search) search.addEventListener('input', function () { q = search.value.toLowerCase(); filterCatalog(); });

    /* Metode bayar */
    var method = 'lunas', due = $('#due-box');
    $$('[data-pay]').forEach(function (b) {
      b.addEventListener('click', function () {
        $$('[data-pay]').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active'); method = b.getAttribute('data-pay');
        due.classList.toggle('show', method === 'hutang');
      });
    });

    /* Checkout & Pratinjau Invoice */
    elBtn.addEventListener('click', function () {
      var t = totals();
      var custName = ($('#cust-name') && $('#cust-name').value) || 'Pelanggan Umum HORECA';
      var custPhone = ($('#cust-phone') && $('#cust-phone').value) || '0812-xxxx-xxxx';
      var dueDate = ($('#due-date') && $('#due-date').value) || '';

      // Isi modal invoice preview
      if ($('#inv-cust-name')) $('#inv-cust-name').textContent = custName;
      if ($('#inv-cust-phone')) $('#inv-cust-phone').textContent = custPhone;
      if ($('#inv-subtotal')) $('#inv-subtotal').textContent = rupiah(t.sub);
      if ($('#inv-discount')) $('#inv-discount').textContent = '− ' + rupiah(t.disc);
      if ($('#inv-disc-row')) $('#inv-disc-row').style.display = t.disc ? 'flex' : 'none';
      if ($('#inv-grandtotal')) $('#inv-grandtotal').textContent = rupiah(t.total);

      var badge = $('#inv-status-badge');
      var dueInfo = $('#inv-due-info');
      if (badge && dueInfo) {
        if (method === 'hutang') {
          badge.className = 'badge warn';
          badge.textContent = 'PIUTANG (TOP)';
          dueInfo.innerHTML = '<span style="color:var(--warn)">Jatuh Tempo: ' + dueDate + '</span>';
        } else {
          badge.className = 'badge success';
          badge.textContent = 'LUNAS';
          dueInfo.textContent = 'Tunai / Transfer BCA';
        }
      }

      var tbody = $('#inv-items-body');
      if (tbody) {
        tbody.innerHTML = Object.keys(cart).map(function (id) {
          var p = products[id], q = cart[id];
          return '<tr style="border-bottom:1px dashed var(--line);">' +
            '<td style="padding:6px 0"><strong>' + p.nama + '</strong><br><small class="muted">' + p.satuan + '</small></td>' +
            '<td style="text-align:center;padding:6px 0" class="mono">' + q + '</td>' +
            '<td style="text-align:right;padding:6px 0" class="mono">' + rupiah(p.jual) + '</td>' +
            '<td style="text-align:right;padding:6px 0" class="mono"><strong>' + rupiah(p.jual * q) + '</strong></td>' +
            '</tr>';
        }).join('');
      }

      openModal('modal-invoice');
      toast('Transaksi diproses! Stok produk otomatis terpotong secara real-time.', 'success');
    });

    render();
  }

})();
