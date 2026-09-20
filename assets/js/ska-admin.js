/**
 * SKA Admin — GitHub Pages CMS (Supabase Auth)
 */
(function () {
  'use strict';

  var page = document.body.dataset.adminPage || '';

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fmtDate(a, b) {
    if (!a || !b) return '—';
    return esc(a) + ' → ' + esc(b);
  }

  function fmtShortDate(iso) {
    if (!iso) return '—';
    try {
      return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) {
      return esc(iso);
    }
  }

  function statusBadge(status) {
    var s = (status || 'pending').toLowerCase();
    var cls = s === 'confirmed' ? 'ska-badge--confirmed' : s === 'cancelled' ? 'ska-badge--cancelled' : 'ska-badge--pending';
    return '<span class="ska-badge ' + cls + '">' + esc(status || 'pending') + '</span>';
  }

  function showError(msg) {
    var el = document.getElementById('adminError');
    if (el) {
      el.textContent = msg;
      el.style.display = 'flex';
    } else {
      alert(msg);
    }
  }

  function hideError() {
    var el = document.getElementById('adminError');
    if (el) el.style.display = 'none';
  }

  function showToast(msg) {
    var el = document.getElementById('adminToast');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'flex';
    setTimeout(function () { el.style.display = 'none'; }, 4000);
  }

  function withTimeout(promise, ms) {
    return Promise.race([
      promise,
      new Promise(function (_, reject) {
        setTimeout(function () { reject(new Error('Request timed out — check Supabase key and network.')); }, ms || 15000);
      })
    ]);
  }

  function failTable(tbodyId, cols, msg) {
    var el = document.getElementById(tbodyId);
    if (el) {
      el.innerHTML = '<tr><td colspan="' + cols + '" class="ska-table-empty">' + esc(msg) + '</td></tr>';
    }
  }

  var adminProfile = null;
  var ROLE_LABELS = {
    super_admin: 'Super Admin',
    manager: 'Manager',
    reservations: 'Reservations',
    marketing: 'Marketing'
  };
  var ROLE_PAGES = {
    super_admin: ['dashboard', 'rooms', 'promotions', 'packages', 'bookings', 'inquiries', 'users'],
    manager: ['dashboard', 'rooms', 'promotions', 'packages', 'bookings', 'inquiries'],
    reservations: ['dashboard', 'bookings', 'inquiries'],
    marketing: ['dashboard', 'promotions', 'packages']
  };

  function canAccess(pageKey) {
    var pages = (adminProfile && adminProfile.pages) || ROLE_PAGES.manager;
    if (!pages || !pages.length) return true;
    return pages.indexOf(pageKey) >= 0;
  }

  function applyNavAccess() {
    var pages = (adminProfile && adminProfile.pages) || null;
    document.querySelectorAll('[data-nav-page]').forEach(function (a) {
      var key = a.getAttribute('data-nav-page');
      if (!pages) return;
      a.classList.toggle('is-hidden', pages.indexOf(key) < 0);
    });
    var roleEl = document.getElementById('adminUserRole');
    if (roleEl && adminProfile && adminProfile.role) {
      roleEl.textContent = ROLE_LABELS[adminProfile.role] || adminProfile.role;
    }
  }

  async function requireAuth() {
    if (!window.SkaApi) {
      showError('Admin scripts failed to load. Refresh the page.');
      return null;
    }
    try {
      var session = await withTimeout(SkaApi.adminSession(), 12000);
      if (!session) {
        location.href = 'login.html?reason=session';
        return null;
      }
      var emailEl = document.getElementById('adminUserEmail');
      if (emailEl && session.user && session.user.email) {
        emailEl.textContent = session.user.email;
      }
      try {
        if (SkaApi.adminGetProfile) {
          adminProfile = await withTimeout(SkaApi.adminGetProfile(), 12000);
        }
      } catch (pe) {
        adminProfile = null;
      }
      if (adminProfile && adminProfile.ok === false) {
        await SkaApi.adminSignOut();
        location.href = 'login.html?reason=forbidden';
        return null;
      }
      if (adminProfile && adminProfile.pages) {
        applyNavAccess();
        if (page && !canAccess(page)) {
          var first = (adminProfile.pages || [])[0] || 'dashboard';
          location.href = first + '.html';
          return null;
        }
      } else {
        applyNavAccess();
      }
      return session;
    } catch (e) {
      showError(e.message || 'Authentication failed');
      setTimeout(function () { location.href = 'login.html?reason=auth'; }, 2000);
      return null;
    }
  }

  async function ensureAuth(tbodyId, cols) {
    var session = await requireAuth();
    if (!session) {
      if (tbodyId) failTable(tbodyId, cols, 'Sign in required — redirecting…');
      return null;
    }
    return session;
  }

  /* ── Dashboard ── */
  async function loadDashboard() {
    if (!document.getElementById('statRooms')) return;

    var session = await ensureAuth('bookingsBody', 6);
    if (!session) {
      failTable('inquiriesBody', 5, 'Sign in required — redirecting…');
      return;
    }

    hideError();
    try {
      var rooms = await withTimeout(SkaApi.adminFetchRooms());
      var bookings = await withTimeout(SkaApi.adminFetchBookings());
      var inquiries = await withTimeout(SkaApi.adminFetchInquiries());

      document.getElementById('statRooms').textContent = String(rooms.length);
      document.getElementById('statBookings').textContent = String(bookings.length);
      document.getElementById('statInquiries').textContent = String(inquiries.length);

      var pending = bookings.filter(function (b) {
        return (b.status || 'pending').toLowerCase() === 'pending';
      }).length;
      document.getElementById('statPending').textContent = String(pending);

      var bBody = document.getElementById('bookingsBody');
      if (!bookings.length) {
        bBody.innerHTML = '<tr><td colspan="6" class="ska-table-empty">No bookings yet.</td></tr>';
      } else {
        bBody.innerHTML = bookings.slice(0, 15).map(function (b) {
          return '<tr>' +
            '<td><strong>' + esc(b.name) + '</strong><br><small>' + esc(b.email) + '</small></td>' +
            '<td>' + esc(b.branch) + '</td>' +
            '<td>' + esc(b.room_type) + '</td>' +
            '<td>' + fmtDate(b.checkin, b.checkout) + '</td>' +
            '<td>' + esc(b.currency || 'USD') + ' ' + esc(Number(b.total || 0).toFixed(0)) + '</td>' +
            '<td>' + statusBadge(b.status) + '</td>' +
            '</tr>';
        }).join('');
      }

      var iBody = document.getElementById('inquiriesBody');
      if (!inquiries.length) {
        iBody.innerHTML = '<tr><td colspan="5" class="ska-table-empty">No inquiries yet.</td></tr>';
      } else {
        iBody.innerHTML = inquiries.slice(0, 15).map(function (q) {
          var msg = (q.message || '').slice(0, 80);
          if ((q.message || '').length > 80) msg += '…';
          return '<tr>' +
            '<td>' + esc(q.name) + '</td>' +
            '<td>' + esc(q.email) + '</td>' +
            '<td>' + esc(q.subject) + '</td>' +
            '<td>' + esc(msg) + '</td>' +
            '<td>' + (q.is_read ? 'Yes' : '<strong>New</strong>') + '</td>' +
            '</tr>';
        }).join('');
      }
    } catch (e) {
      console.error('[SKA Admin]', e);
      showError('Could not load dashboard: ' + (e.message || e));
      ['bookingsBody', 'inquiriesBody'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.innerHTML = '<tr><td colspan="6" class="ska-table-empty">Failed to load data.</td></tr>';
      });
    }
  }

  /* ── Bookings page ── */
  var allBookings = [];

  function renderBookingsTable() {
    var tbody = document.getElementById('bookingsTableBody');
    if (!tbody) return;

    var status = document.getElementById('filterStatus')?.value || 'all';
    var branch = document.getElementById('filterBranch')?.value || 'all';

    var filtered = allBookings.filter(function (b) {
      if (status !== 'all' && (b.status || 'pending').toLowerCase() !== status) return false;
      if (branch !== 'all' && b.branch !== branch) return false;
      return true;
    });

    if (!filtered.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="ska-table-empty">No bookings match your filters.</td></tr>';
      return;
    }

    tbody.innerHTML = filtered.map(function (b) {
      var st = (b.status || 'pending').toLowerCase();
      var actions = '';
      if (st === 'pending') {
        actions = '<button type="button" class="ska-btn ska-btn--success ska-btn--sm" data-action="confirm" data-id="' + b.id + '">Confirm</button> ' +
          '<button type="button" class="ska-btn ska-btn--danger ska-btn--sm" data-action="cancel" data-id="' + b.id + '">Cancel</button>';
      } else if (st === 'confirmed') {
        actions = '<button type="button" class="ska-btn ska-btn--danger ska-btn--sm" data-action="cancel" data-id="' + b.id + '">Cancel</button>';
      } else {
        actions = '<span class="text-muted">—</span>';
      }

      return '<tr>' +
        '<td><strong>' + esc(b.name) + '</strong><br><small>' + esc(b.email) + '</small></td>' +
        '<td>' + esc(b.branch) + '</td>' +
        '<td>' + esc(b.room_type) + '</td>' +
        '<td>' + fmtDate(b.checkin, b.checkout) + '</td>' +
        '<td>' + esc(b.currency || 'USD') + ' ' + esc(Number(b.total || 0).toFixed(0)) + '</td>' +
        '<td>' + statusBadge(b.status) + '</td>' +
        '<td><div class="d-flex gap-2">' + actions + '</div></td>' +
        '</tr>';
    }).join('');
  }

  async function loadBookingsPage() {
    if (!document.getElementById('bookingsTableBody')) return;
    var session = await ensureAuth('bookingsTableBody', 7);
    if (!session) return;

    hideError();
    try {
      allBookings = await withTimeout(SkaApi.adminFetchBookings());
      renderBookingsTable();
    } catch (e) {
      showError('Could not load bookings: ' + (e.message || e));
      document.getElementById('bookingsTableBody').innerHTML =
        '<tr><td colspan="7" class="ska-table-empty">Failed to load bookings.</td></tr>';
    }
  }

  async function handleBookingAction(e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    var id = btn.dataset.id;
    var action = btn.dataset.action;
    var status = action === 'confirm' ? 'confirmed' : 'cancelled';
    if (!confirm('Mark this booking as ' + status + '?')) return;

    try {
      await SkaApi.adminUpdateBookingStatus(id, status);
      showToast('Booking updated to ' + status + '.');
      await loadBookingsPage();
    } catch (err) {
      showError(err.message || 'Update failed');
    }
  }

  /* ── Rooms page ── */
  function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('open');
  }

  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('open');
  }

  async function loadRoomsPage() {
    var tbody = document.getElementById('roomsTableBody');
    if (!tbody) return;
    var session = await ensureAuth('roomsTableBody', 6);
    if (!session) return;

    hideError();
    try {
      var rooms = await withTimeout(SkaApi.adminFetchRooms());
      if (!rooms.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="ska-table-empty">No rooms yet. Click Add Room to create one.</td></tr>';
        return;
      }
      tbody.innerHTML = rooms.map(function (r) {
        return '<tr>' +
          '<td><strong>' + esc(r.name) + '</strong></td>' +
          '<td>' + esc(r.branch) + '</td>' +
          '<td>$' + esc(Number(r.price_low || r.price || 0).toFixed(0)) + '</td>' +
          '<td>$' + esc(Number(r.price_shoulder || r.price || 0).toFixed(0)) + '</td>' +
          '<td>$' + esc(Number(r.price_high || r.price || 0).toFixed(0)) + '</td>' +
          '<td><div class="d-flex gap-2">' +
          '<button type="button" class="ska-btn ska-btn--edit ska-btn--sm" data-edit-room="' + r.id + '"><i class="fa fa-pen"></i> Edit</button>' +
          '<button type="button" class="ska-btn ska-btn--delete ska-btn--sm" data-delete-room="' + r.id + '"><i class="fa fa-trash"></i></button>' +
          '</div></td></tr>';
      }).join('');

      tbody.querySelectorAll('[data-edit-room]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var room = rooms.find(function (r) { return String(r.id) === btn.dataset.editRoom; });
          if (!room) return;
          document.getElementById('roomModalTitle').textContent = 'Edit Room';
          document.getElementById('roomId').value = room.id;
          document.getElementById('roomName').value = room.name || '';
          document.getElementById('roomBranch').value = room.branch || 'Naguru';
          document.getElementById('roomPrice').value = room.price || '';
          document.getElementById('roomPriceLow').value = room.price_low || '';
          document.getElementById('roomPriceShoulder').value = room.price_shoulder || '';
          document.getElementById('roomPriceHigh').value = room.price_high || '';
          document.getElementById('roomDesc').value = room.description || '';
          openModal('roomModal');
        });
      });

      tbody.querySelectorAll('[data-delete-room]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
          if (!confirm('Delete this room?')) return;
          try {
            await SkaApi.adminDeleteRoom(btn.dataset.deleteRoom);
            showToast('Room deleted.');
            loadRoomsPage();
          } catch (err) {
            showError(err.message || 'Delete failed');
          }
        });
      });
    } catch (e) {
      showError('Could not load rooms: ' + (e.message || e));
      tbody.innerHTML = '<tr><td colspan="6" class="ska-table-empty">Failed to load rooms.</td></tr>';
    }
  }

  function initRoomsPage() {
    document.getElementById('btnAddRoom')?.addEventListener('click', function () {
      document.getElementById('roomForm').reset();
      document.getElementById('roomId').value = '';
      document.getElementById('roomModalTitle').textContent = 'Add Room';
      openModal('roomModal');
    });

    ['roomModalClose', 'roomModalCancel'].forEach(function (id) {
      document.getElementById(id)?.addEventListener('click', function () { closeModal('roomModal'); });
    });

    document.getElementById('roomForm')?.addEventListener('submit', async function (e) {
      e.preventDefault();
      var fd = new FormData(e.target);
      var data = {};
      fd.forEach(function (v, k) { data[k] = v; });
      try {
        await SkaApi.adminSaveRoom(data);
        closeModal('roomModal');
        showToast('Room saved.');
        loadRoomsPage();
      } catch (err) {
        showError(err.message || 'Save failed');
      }
    });
  }

  /* ── Promotions page ── */
  async function loadPromotionsPage() {
    var tbody = document.getElementById('promosTableBody');
    if (!tbody) return;
    var session = await ensureAuth('promosTableBody', 7);
    if (!session) return;

    hideError();
    try {
      var promos = await withTimeout(SkaApi.adminFetchPromotions());
      if (!promos.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="ska-table-empty">No promotions yet.</td></tr>';
        return;
      }
      tbody.innerHTML = promos.map(function (p) {
        var disc = p.discount_type === 'percent'
          ? esc(p.discount_value) + '%'
          : p.discount_type === 'free_night'
            ? esc(p.discount_value) + ' free night(s)'
            : 'USD ' + esc(p.discount_value);
        var thumb = mediaUrl(p.image);
        return '<tr>' +
          '<td>' + (thumb
            ? '<img src="' + esc(thumb) + '" alt="" class="ska-table-thumb">'
            : '<div class="ska-table-thumb-empty"><i class="fa-regular fa-image"></i></div>') + '</td>' +
          '<td><strong>' + esc(p.title) + '</strong></td>' +
          '<td>' + esc(p.branch) + '</td>' +
          '<td>' + disc + '</td>' +
          '<td>' + esc(p.valid_from || '—') + ' → ' + esc(p.valid_to || '—') + '</td>' +
          '<td>' + (p.active ? '<span class="ska-badge ska-badge--confirmed">Active</span>' : '<span class="ska-badge ska-badge--cancelled">Off</span>') + '</td>' +
          '<td><div class="d-flex gap-2">' +
          '<button type="button" class="ska-btn ska-btn--edit ska-btn--sm" data-edit-promo="' + p.id + '"><i class="fa fa-pen"></i> Edit</button>' +
          '<button type="button" class="ska-btn ska-btn--delete ska-btn--sm" data-delete-promo="' + p.id + '"><i class="fa fa-trash"></i></button>' +
          '</div></td></tr>';
      }).join('');

      tbody.querySelectorAll('[data-edit-promo]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var promo = promos.find(function (p) { return String(p.id) === btn.dataset.editPromo; });
          if (!promo) return;
          fillPromoForm(promo);
          openModal('promoModal');
        });
      });

      tbody.querySelectorAll('[data-delete-promo]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
          if (!confirm('Delete this promotion?')) return;
          try {
            await SkaApi.adminDeletePromotion(btn.dataset.deletePromo);
            showToast('Promotion deleted.');
            loadPromotionsPage();
          } catch (err) {
            showError(err.message || 'Delete failed');
          }
        });
      });
    } catch (e) {
      showError('Could not load promotions: ' + (e.message || e));
      tbody.innerHTML = '<tr><td colspan="7" class="ska-table-empty">Failed to load promotions.</td></tr>';
    }
  }

  function setPromoImagePreview(path) {
    var wrap = document.getElementById('promoImagePreviewWrap');
    var img = document.getElementById('promoImagePreview');
    var hidden = document.getElementById('promoImage');
    var pathField = document.getElementById('promoImagePath');
    if (hidden) hidden.value = path || '';
    if (pathField && pathField !== document.activeElement) pathField.value = path || '';
    if (!wrap || !img) return;
    if (path) {
      wrap.style.display = '';
      img.src = mediaUrl(path);
    } else {
      wrap.style.display = 'none';
      img.removeAttribute('src');
    }
  }

  function fillPromoForm(promo) {
    document.getElementById('promoModalTitle').textContent = promo && promo.id ? 'Edit Promotion' : 'Add Promotion';
    document.getElementById('promoId').value = (promo && promo.id) || '';
    document.getElementById('promoTitle').value = (promo && promo.title) || '';
    document.getElementById('promoBranch').value = (promo && promo.branch) || 'Both';
    document.getElementById('promoType').value = (promo && promo.discount_type) || 'percent';
    document.getElementById('promoValue').value = (promo && promo.discount_value) || 0;
    document.getElementById('promoMinNights').value = (promo && promo.min_nights) || 1;
    document.getElementById('promoActive').value = !promo || promo.active ? 'true' : 'false';
    document.getElementById('promoDesc').value = (promo && promo.description) || '';
    var file = document.getElementById('promoImageFile');
    if (file) file.value = '';
    setPromoImagePreview((promo && promo.image) || '');
  }

  function initPromotionsPage() {
    document.getElementById('btnAddPromo')?.addEventListener('click', function () {
      document.getElementById('promoForm').reset();
      fillPromoForm(null);
      openModal('promoModal');
    });

    ['promoModalClose', 'promoModalCancel'].forEach(function (id) {
      document.getElementById(id)?.addEventListener('click', function () { closeModal('promoModal'); });
    });

    document.getElementById('promoUploadZone')?.addEventListener('click', function () {
      document.getElementById('promoImageFile')?.click();
    });
    document.getElementById('promoImageReplace')?.addEventListener('click', function (ev) {
      ev.preventDefault();
      document.getElementById('promoImageFile')?.click();
    });
    document.getElementById('promoImageFile')?.addEventListener('change', function () {
      var file = this.files && this.files[0];
      if (!file) return;
      setPromoImagePreview(URL.createObjectURL(file));
    });
    document.getElementById('promoImagePath')?.addEventListener('input', function () {
      setPromoImagePreview(this.value.trim());
    });

    document.getElementById('promoForm')?.addEventListener('submit', async function (e) {
      e.preventDefault();
      hideError();
      var fd = new FormData(e.target);
      var data = {};
      fd.forEach(function (v, k) {
        if (typeof File !== 'undefined' && v instanceof File) return;
        data[k] = v;
      });
      data.active = data.active === 'true';
      var pathField = document.getElementById('promoImagePath');
      if (pathField && pathField.value.trim()) data.image = pathField.value.trim();
      var fileInput = document.getElementById('promoImageFile');
      var file = fileInput && fileInput.files && fileInput.files[0];
      try {
        if (file) {
          data.image = await SkaApi.adminUploadPublicFile('promotions', file);
        }
        await SkaApi.adminSavePromotion(data);
        closeModal('promoModal');
        showToast('Promotion saved.');
        loadPromotionsPage();
      } catch (err) {
        var msg = err.message || 'Save failed';
        if (/bucket|not found|row-level security/i.test(msg)) {
          msg = 'Image upload needs the ska-uploads bucket. In Supabase SQL Editor run supabase/migrations/010_storage_uploads.sql, then try again.';
        }
        showError(msg);
      }
    });
  }

  /* ── Packages page ── */
  function optionsToText(raw) {
    if (!raw) return '';
    var arr = raw;
    if (typeof raw === 'string') {
      try { arr = JSON.parse(raw); } catch (e) { return raw; }
    }
    if (!Array.isArray(arr)) return String(raw);
    return arr.map(function (o) {
      return [o.label || '', o.price || 0, o.detail || '', o.pricing || ''].join('|');
    }).join('\n');
  }

  function textToOptionsJson(text) {
    text = (text || '').trim();
    if (!text) return '[]';
    try {
      var parsed = JSON.parse(text);
      if (Array.isArray(parsed)) return JSON.stringify(parsed);
    } catch (e) {}
    var lines = text.split(/\r?\n/).filter(Boolean).map(function (line) {
      var p = line.split('|').map(function (s) { return s.trim(); });
      return { label: p[0], price: parseFloat(p[1] || 0), detail: p[2] || '', pricing: p[3] || 'fixed' };
    });
    return JSON.stringify(lines);
  }

  function mediaUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.indexOf('data:') === 0 || path.indexOf('blob:') === 0) return path;
    return '../' + String(path).replace(/^\.\.\//, '').replace(/^\//, '');
  }

  function setPackageImagePreview(path) {
    var wrap = document.getElementById('pkgImagePreviewWrap');
    var img = document.getElementById('pkgImagePreview');
    var hidden = document.getElementById('pkgImage');
    var pathField = document.getElementById('pkgImagePath');
    if (hidden) hidden.value = path || '';
    if (pathField && pathField !== document.activeElement) pathField.value = path || '';
    if (!wrap || !img) return;
    if (path) {
      wrap.style.display = '';
      img.src = mediaUrl(path);
    } else {
      wrap.style.display = 'none';
      img.removeAttribute('src');
    }
  }

  function fillPackageForm(pkg) {
    document.getElementById('pkgModalTitle').textContent = pkg && pkg.id ? 'Edit Package' : 'Add Package';
    document.getElementById('pkgId').value = (pkg && pkg.id) || '';
    document.getElementById('pkgTitle').value = (pkg && pkg.title) || '';
    document.getElementById('pkgTag').value = (pkg && pkg.tag) || '';
    document.getElementById('pkgBranch').value = (pkg && pkg.branch) || 'Naguru';
    document.getElementById('pkgCurrency').value = (pkg && pkg.currency) || 'UGX';
    document.getElementById('pkgPrice').value = (pkg && pkg.price) || 0;
    document.getElementById('pkgPricing').value = (pkg && pkg.pricing_mode) || 'fixed';
    document.getElementById('pkgActive').value = !pkg || pkg.active ? 'true' : 'false';
    document.getElementById('pkgDesc').value = (pkg && pkg.description) || '';
    document.getElementById('pkgInc').value = (pkg && pkg.inclusions) || '';
    document.getElementById('pkgOptions').value = optionsToText(pkg && pkg.options);
    var file = document.getElementById('pkgImageFile');
    if (file) file.value = '';
    setPackageImagePreview((pkg && pkg.image) || '');
  }

  async function loadPackagesPage() {
    var tbody = document.getElementById('packagesTableBody');
    if (!tbody) return;
    var session = await ensureAuth('packagesTableBody', 6);
    if (!session) return;
    hideError();
    try {
      var pkgs = await withTimeout(SkaApi.adminFetchPackages());
      if (!pkgs.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="ska-table-empty">No packages yet.</td></tr>';
        return;
      }
      tbody.innerHTML = pkgs.map(function (p) {
        var thumb = mediaUrl(p.image);
        return '<tr>' +
          '<td>' + (thumb
            ? '<img src="' + esc(thumb) + '" alt="" class="ska-table-thumb">'
            : '<div class="ska-table-thumb-empty"><i class="fa-regular fa-image"></i></div>') + '</td>' +
          '<td><strong>' + esc(p.title) + '</strong></td>' +
          '<td>' + esc(p.branch) + '</td>' +
          '<td>' + esc(p.currency || 'UGX') + ' ' + esc(Number(p.price || 0).toFixed(0)) + '</td>' +
          '<td>' + (p.active ? '<span class="ska-badge ska-badge--confirmed">Active</span>' : '<span class="ska-badge ska-badge--cancelled">Off</span>') + '</td>' +
          '<td><div class="d-flex gap-2">' +
          '<button type="button" class="ska-btn ska-btn--edit ska-btn--sm" data-edit-pkg="' + p.id + '"><i class="fa fa-pen"></i> Edit</button>' +
          '<button type="button" class="ska-btn ska-btn--delete ska-btn--sm" data-delete-pkg="' + p.id + '"><i class="fa fa-trash"></i></button>' +
          '</div></td></tr>';
      }).join('');

      tbody.querySelectorAll('[data-edit-pkg]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var pkg = pkgs.find(function (p) { return String(p.id) === btn.dataset.editPkg; });
          if (!pkg) return;
          fillPackageForm(pkg);
          openModal('pkgModal');
        });
      });
      tbody.querySelectorAll('[data-delete-pkg]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
          if (!confirm('Delete this package?')) return;
          try {
            await SkaApi.adminDeletePackage(btn.dataset.deletePkg);
            showToast('Package deleted.');
            loadPackagesPage();
          } catch (err) {
            showError(err.message || 'Delete failed');
          }
        });
      });
    } catch (e) {
      var msg = e.message || String(e);
      if (/does not exist|schema cache|PGRST205|Could not find the table/i.test(msg)) {
        msg = 'Packages are not in this database yet. In Supabase SQL Editor run supabase/migrations/009_packages_and_staff_roles.sql, then refresh.';
      }
      showError(msg);
      tbody.innerHTML = '<tr><td colspan="6" class="ska-table-empty">' + esc(msg) + '</td></tr>';
    }
  }

  function initPackagesPage() {
    document.getElementById('btnAddPkg')?.addEventListener('click', function () {
      fillPackageForm(null);
      document.getElementById('pkgForm').reset();
      fillPackageForm(null);
      openModal('pkgModal');
    });
    ['pkgModalClose', 'pkgModalCancel'].forEach(function (id) {
      document.getElementById(id)?.addEventListener('click', function () { closeModal('pkgModal'); });
    });
    document.getElementById('pkgUploadZone')?.addEventListener('click', function () {
      document.getElementById('pkgImageFile')?.click();
    });
    document.getElementById('pkgImageReplace')?.addEventListener('click', function (ev) {
      ev.preventDefault();
      document.getElementById('pkgImageFile')?.click();
    });
    document.getElementById('pkgImageFile')?.addEventListener('change', function () {
      var file = this.files && this.files[0];
      if (!file) return;
      setPackageImagePreview(URL.createObjectURL(file));
    });
    document.getElementById('pkgImagePath')?.addEventListener('input', function () {
      setPackageImagePreview(this.value.trim());
    });
    document.getElementById('pkgForm')?.addEventListener('submit', async function (e) {
      e.preventDefault();
      hideError();
      var fd = new FormData(e.target);
      var data = {};
      fd.forEach(function (v, k) {
        if (typeof File !== 'undefined' && v instanceof File) return;
        data[k] = v;
      });
      data.active = data.active === 'true';
      data.options = textToOptionsJson(data.options);
      var pathField = document.getElementById('pkgImagePath');
      if (pathField && pathField.value.trim()) data.image = pathField.value.trim();
      var fileInput = document.getElementById('pkgImageFile');
      var file = fileInput && fileInput.files && fileInput.files[0];
      try {
        if (file) {
          data.image = await SkaApi.adminUploadPublicFile('packages', file);
        }
        await SkaApi.adminSavePackage(data);
        closeModal('pkgModal');
        showToast('Package saved.');
        loadPackagesPage();
      } catch (err) {
        var msg = err.message || 'Save failed';
        if (/bucket|not found|row-level security/i.test(msg)) {
          msg = 'Image upload needs the ska-uploads bucket. In Supabase SQL Editor run supabase/migrations/010_storage_uploads.sql, then try again.';
        }
        showError(msg);
      }
    });
  }

  /* ── Inquiries page ── */
  async function loadInquiriesPage() {
    var tbody = document.getElementById('inquiriesTableBody');
    if (!tbody) return;
    var session = await ensureAuth('inquiriesTableBody', 7);
    if (!session) return;

    hideError();
    try {
      var inquiries = await withTimeout(SkaApi.adminFetchInquiries());
      if (!inquiries.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="ska-table-empty">No inquiries yet.</td></tr>';
        return;
      }
      tbody.innerHTML = inquiries.map(function (q) {
        var msg = (q.message || '').slice(0, 100);
        if ((q.message || '').length > 100) msg += '…';
        return '<tr>' +
          '<td>' + esc(q.name) + '</td>' +
          '<td>' + esc(q.email) + '</td>' +
          '<td>' + esc(q.subject || '—') + '</td>' +
          '<td>' + esc(msg) + '</td>' +
          '<td>' + fmtShortDate(q.created_at) + '</td>' +
          '<td>' + (q.is_read ? 'Read' : '<strong>New</strong>') + '</td>' +
          '<td>' +
          (q.is_read
            ? '<span class="text-muted">—</span>'
            : '<button type="button" class="ska-btn ska-btn--primary ska-btn--sm" data-mark-read="' + q.id + '">Mark read</button>') +
          '</td></tr>';
      }).join('');

      tbody.querySelectorAll('[data-mark-read]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
          try {
            await SkaApi.adminMarkInquiryRead(btn.dataset.markRead, true);
            showToast('Marked as read.');
            loadInquiriesPage();
          } catch (err) {
            showError(err.message || 'Update failed');
          }
        });
      });
    } catch (e) {
      showError('Could not load inquiries: ' + (e.message || e));
      tbody.innerHTML = '<tr><td colspan="7" class="ska-table-empty">Failed to load inquiries.</td></tr>';
    }
  }

  function staffList(data) {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'string') {
      try { return staffList(JSON.parse(data)); } catch (e) { return []; }
    }
    return [];
  }

  function pageLabels(pages) {
    if (!pages || !pages.length) return '—';
    return pages.map(function (p) {
      return p.charAt(0).toUpperCase() + p.slice(1);
    }).join(', ');
  }

  function roleSelectHtml(email, role, disabled) {
    var opts = ['super_admin', 'manager', 'reservations', 'marketing'];
    return '<select class="ska-input" data-staff-role="' + esc(email) + '"' + (disabled ? ' disabled' : '') + '>' +
      opts.map(function (r) {
        return '<option value="' + r + '"' + (r === role ? ' selected' : '') + '>' + esc(ROLE_LABELS[r] || r) + '</option>';
      }).join('') + '</select>';
  }

  async function loadUsersPage() {
    var tbody = document.getElementById('staffTableBody');
    if (!tbody) return;
    var session = await ensureAuth('staffTableBody', 5);
    if (!session) return;

    hideError();
    var isSuper = adminProfile && adminProfile.role === 'super_admin';
    var form = document.getElementById('staffForm');
    if (form) form.style.display = isSuper ? '' : 'none';
    var hint = document.getElementById('roleHint');
    if (hint && !isSuper) {
      hint.textContent = 'Only a Super Admin can add or change staff access.';
    }

    try {
      var rows = staffList(await withTimeout(SkaApi.adminListStaff()));
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="ska-table-empty">No staff yet.</td></tr>';
        return;
      }
      var me = (session.user && session.user.email || '').toLowerCase();
      tbody.innerHTML = rows.map(function (u) {
        var email = u.email || '';
        var status = u.status === 'invited' ? 'Invited' : 'Active';
        var canRemove = isSuper && email.toLowerCase() !== me;
        return '<tr>' +
          '<td>' + esc(email) + '</td>' +
          '<td>' + roleSelectHtml(email, u.role || 'manager', !isSuper) + '</td>' +
          '<td>' + esc(pageLabels(u.pages)) + '</td>' +
          '<td>' + esc(status) + '</td>' +
          '<td class="ska-staff-actions">' +
          (isSuper
            ? '<button type="button" class="ska-btn ska-btn--outline ska-btn--sm" data-staff-resend="' + esc(email) + '" data-staff-resend-role="' + esc(u.role || 'manager') + '">Resend invite</button>'
            : '') +
          (canRemove
            ? '<button type="button" class="ska-btn ska-btn--ghost-del" data-staff-remove="' + esc(email) + '" title="Remove"><i class="fa-regular fa-trash-can"></i></button>'
            : (isSuper ? '' : '—')) +
          '</td></tr>';
      }).join('');

      tbody.querySelectorAll('[data-staff-role]').forEach(function (sel) {
        sel.addEventListener('change', async function () {
          try {
            await SkaApi.adminUpdateStaff(sel.getAttribute('data-staff-role'), sel.value);
            showToast('Role updated.');
            loadUsersPage();
          } catch (err) {
            showError(err.message || 'Could not update role');
          }
        });
      });
      tbody.querySelectorAll('[data-staff-resend]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
          hideError();
          btn.disabled = true;
          var label = btn.textContent;
          btn.textContent = 'Sending…';
          try {
            var sent = await SkaApi.adminResendInvite(
              btn.getAttribute('data-staff-resend'),
              btn.getAttribute('data-staff-resend-role') || 'manager'
            );
            if (sent && sent.emailed === false) {
              showError(sent.error || 'Invite was not emailed.');
            } else {
              showToast('Invite sent to ' + btn.getAttribute('data-staff-resend') + '. Check inbox and spam.');
            }
          } catch (err) {
            showError(err.message || 'Could not resend invite');
          }
          btn.disabled = false;
          btn.textContent = label;
        });
      });
      tbody.querySelectorAll('[data-staff-remove]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
          if (!confirm('Remove this staff member from the admin dashboard?')) return;
          try {
            await SkaApi.adminRemoveStaff(btn.getAttribute('data-staff-remove'));
            showToast('Staff removed.');
            loadUsersPage();
          } catch (err) {
            showError(err.message || 'Could not remove staff');
          }
        });
      });
    } catch (e) {
      var msg = e.message || String(e);
      if (/does not exist|schema cache|PGRST|function/i.test(msg)) {
        msg = 'Staff roles are not in this database yet. In Supabase SQL Editor run supabase/migrations/009_packages_and_staff_roles.sql, then refresh.';
      }
      showError(msg);
      tbody.innerHTML = '<tr><td colspan="5" class="ska-table-empty">' + esc(msg) + '</td></tr>';
    }

    if (form && !form.dataset.bound) {
      form.dataset.bound = '1';
      form.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        var email = (document.getElementById('staffEmail') || {}).value || '';
        var role = (document.getElementById('staffRole') || {}).value || 'manager';
        try {
          var res = await SkaApi.adminInviteStaff(email.trim(), role);
          var status = res && res.emailed === false
            ? 'User saved, but the invite email did not send. Use Resend invite.'
            : 'Invite emailed. Ask them to check inbox and spam.';
          showToast(status);
          form.reset();
          loadUsersPage();
        } catch (err) {
          showError(err.message || 'Could not add user');
        }
      });
    }
  }

  /* ── Init ── */
  document.getElementById('adminLogout')?.addEventListener('click', async function () {
    await SkaApi.adminSignOut();
    location.href = 'login.html';
  });

  document.getElementById('filterStatus')?.addEventListener('change', renderBookingsTable);
  document.getElementById('filterBranch')?.addEventListener('change', renderBookingsTable);
  document.getElementById('bookingsTableBody')?.addEventListener('click', handleBookingAction);

  initRoomsPage();
  initPromotionsPage();
  initPackagesPage();

  function boot() {
    if (page === 'bookings') loadBookingsPage();
    else if (page === 'rooms') loadRoomsPage();
    else if (page === 'promotions') loadPromotionsPage();
    else if (page === 'packages') loadPackagesPage();
    else if (page === 'inquiries') loadInquiriesPage();
    else if (page === 'users') loadUsersPage();
    else loadDashboard();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
