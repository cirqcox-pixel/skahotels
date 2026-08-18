/**
 * SKA Admin — GitHub Pages dashboard (Supabase Auth)
 */
(function () {
  'use strict';

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fmtDate(a, b) {
    if (!a || !b) return '—';
    return esc(a) + ' → ' + esc(b);
  }

  function statusBadge(status) {
    var s = (status || 'pending').toLowerCase();
    var cls = s === 'confirmed' ? 'ska-badge--confirmed' : s === 'cancelled' ? 'ska-badge--cancelled' : 'ska-badge--pending';
    return '<span class="ska-badge ' + cls + '">' + esc(status || 'pending') + '</span>';
  }

  async function requireAuth() {
    var session = await SkaApi.adminSession();
    if (!session) {
      location.href = 'login.html';
      return null;
    }
    var emailEl = document.getElementById('adminUserEmail');
    if (emailEl && session.user && session.user.email) {
      emailEl.textContent = session.user.email;
    }
    return session;
  }

  async function loadDashboard() {
    if (!document.getElementById('statsGrid')) return;

    var session = await requireAuth();
    if (!session) return;

    try {
      var rooms = await SkaApi.adminFetchRooms();
      var bookings = await SkaApi.adminFetchBookings();
      var inquiries = await SkaApi.adminFetchInquiries();

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
      alert('Could not load dashboard: ' + (e.message || e));
    }
  }

  document.getElementById('adminLogout')?.addEventListener('click', async function () {
    await SkaApi.adminSignOut();
    location.href = 'login.html';
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadDashboard);
  } else {
    loadDashboard();
  }
})();
