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

  async function requireAuth() {
    if (!window.SkaApi) {
      showError('Admin scripts failed to load. Refresh the page.');
      return null;
    }
    try {
      var session = await withTimeout(SkaApi.adminSession(), 10000);
      if (!session) {
        location.href = 'login.html';
        return null;
      }
      var emailEl = document.getElementById('adminUserEmail');
      if (emailEl && session.user && session.user.email) {
        emailEl.textContent = session.user.email;
      }
      return session;
    } catch (e) {
      showError(e.message || 'Authentication failed');
      return null;
    }
  }

  /* ── Dashboard ── */
  async function loadDashboard() {
    if (!document.getElementById('statRooms')) return;

    var session = await requireAuth();
    if (!session) return;

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
            '<td>USD ' + esc(Number(b.total || 0).toFixed(0)) + '</td>' +
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
        '<td>USD ' + esc(Number(b.total || 0).toFixed(0)) + '</td>' +
        '<td>' + statusBadge(b.status) + '</td>' +
        '<td><div class="d-flex gap-2">' + actions + '</div></td>' +
        '</tr>';
    }).join('');
  }

  async function loadBookingsPage() {
    if (!document.getElementById('bookingsTableBody')) return;
    var session = await requireAuth();
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
    var session = await requireAuth();
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
    var session = await requireAuth();
    if (!session) return;

    hideError();
    try {
      var promos = await withTimeout(SkaApi.adminFetchPromotions());
      if (!promos.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="ska-table-empty">No promotions yet.</td></tr>';
        return;
      }
      tbody.innerHTML = promos.map(function (p) {
        var disc = p.discount_type === 'percent'
          ? esc(p.discount_value) + '%'
          : p.discount_type === 'free_night'
            ? esc(p.discount_value) + ' free night(s)'
            : 'USD ' + esc(p.discount_value);
        return '<tr>' +
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
          document.getElementById('promoModalTitle').textContent = 'Edit Promotion';
          document.getElementById('promoId').value = promo.id;
          document.getElementById('promoTitle').value = promo.title || '';
          document.getElementById('promoBranch').value = promo.branch || 'Both';
          document.getElementById('promoType').value = promo.discount_type || 'percent';
          document.getElementById('promoValue').value = promo.discount_value || 0;
          document.getElementById('promoMinNights').value = promo.min_nights || 1;
          document.getElementById('promoActive').value = promo.active ? 'true' : 'false';
          document.getElementById('promoDesc').value = promo.description || '';
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
      tbody.innerHTML = '<tr><td colspan="6" class="ska-table-empty">Failed to load promotions.</td></tr>';
    }
  }

  function initPromotionsPage() {
    document.getElementById('btnAddPromo')?.addEventListener('click', function () {
      document.getElementById('promoForm').reset();
      document.getElementById('promoId').value = '';
      document.getElementById('promoModalTitle').textContent = 'Add Promotion';
      openModal('promoModal');
    });

    ['promoModalClose', 'promoModalCancel'].forEach(function (id) {
      document.getElementById(id)?.addEventListener('click', function () { closeModal('promoModal'); });
    });

    document.getElementById('promoForm')?.addEventListener('submit', async function (e) {
      e.preventDefault();
      var fd = new FormData(e.target);
      var data = {};
      fd.forEach(function (v, k) { data[k] = v; });
      data.active = data.active === 'true';
      try {
        await SkaApi.adminSavePromotion(data);
        closeModal('promoModal');
        showToast('Promotion saved.');
        loadPromotionsPage();
      } catch (err) {
        showError(err.message || 'Save failed');
      }
    });
  }

  /* ── Inquiries page ── */
  async function loadInquiriesPage() {
    var tbody = document.getElementById('inquiriesTableBody');
    if (!tbody) return;
    var session = await requireAuth();
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

  function boot() {
    if (page === 'bookings') loadBookingsPage();
    else if (page === 'rooms') loadRoomsPage();
    else if (page === 'promotions') loadPromotionsPage();
    else if (page === 'inquiries') loadInquiriesPage();
    else loadDashboard();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
