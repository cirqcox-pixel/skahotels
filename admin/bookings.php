<?php
/**
 * FILE: admin/bookings.php
 */

require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();

/* ═══════════════════════════════════════════════════════════
   FETCH & FILTER DATA
═══════════════════════════════════════════════════════════ */
$filterStatus = $_GET['status'] ?? 'all';
$filterBranch = $_GET['branch'] ?? 'all';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = ["1=1"];
$params = [];
$types  = "";

if ($filterStatus !== 'all') {
    $where[]  = "status = ?";
    $params[] = $filterStatus;
    $types   .= "s";
}
if ($filterBranch !== 'all') {
    $where[]  = "branch = ?";
    $params[] = $filterBranch;
    $types   .= "s";
}
if ($search) {
    $where[]  = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR room_type LIKE ?)";
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
    $types   .= "ssss";
}

$whereSQL = implode(' AND ', $where);

$cStmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE {$whereSQL}");
if ($types) $cStmt->bind_param($types, ...$params);
$cStmt->execute();
$totalCount = $cStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalCount / $perPage));

$qStmt = $conn->prepare(
    "SELECT * FROM bookings WHERE {$whereSQL} ORDER BY created_at DESC LIMIT ? OFFSET ?"
);
$allTypes  = $types . "ii";
$allParams = array_merge($params, [$perPage, $offset]);
$qStmt->bind_param($allTypes, ...$allParams);
$qStmt->execute();
$bookings = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$counts = [];
foreach (['pending', 'confirmed', 'cancelled'] as $st) {
    $cs = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status='$st'");
    $counts[$st] = (int)$cs->fetch_assoc()['c'];
}
$counts['all'] = array_sum($counts);

$rev = $conn->query(
    "SELECT COALESCE(SUM(total),0) AS t FROM bookings WHERE status='confirmed'"
)->fetch_assoc()['t'] ?? 0;

/* Toast message from redirect */
$toastMsg  = '';
$toastType = '';
if (isset($_GET['updated'])) {
    if ($_GET['updated'] === 'confirmed') {
        $toastMsg  = 'Booking confirmed — guest notified.';
        $toastType = 'success';
    } elseif ($_GET['updated'] === 'cancelled') {
        $toastMsg  = 'Booking cancelled — guest notified.';
        $toastType = 'error';
    }
} elseif (isset($_GET['error'])) {
    $toastMsg  = 'Error: ' . htmlspecialchars($_GET['error']);
    $toastType = 'error';
}

$activePage     = 'bookings';
$pageTitle      = 'Bookings';
$pageBreadcrumb = 'Manage guest reservations';
$includeToast   = true;

include 'includes/layout-start.php';
?>

<div class="ska-stats-grid">
      <div class="ska-stat-card ska-stat-card--gold">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Total Bookings</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-calendar-days"></i></div>
        </div>
        <div class="ska-stat-card__value"><?= $counts['all'] ?></div>
        <div class="ska-stat-card__sub">All time</div>
      </div>
      <div class="ska-stat-card ska-stat-card--info">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Pending</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-clock"></i></div>
        </div>
        <div class="ska-stat-card__value"><?= $counts['pending'] ?></div>
        <div class="ska-stat-card__sub">Awaiting review</div>
      </div>
      <div class="ska-stat-card ska-stat-card--success">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Confirmed</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="ska-stat-card__value"><?= $counts['confirmed'] ?></div>
        <div class="ska-stat-card__sub">Revenue: USD <?= number_format($rev, 0) ?></div>
      </div>
      <div class="ska-stat-card ska-stat-card--danger">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Cancelled</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-circle-xmark"></i></div>
        </div>
        <div class="ska-stat-card__value"><?= $counts['cancelled'] ?></div>
        <div class="ska-stat-card__sub">Unavailable</div>
      </div>
    </div>

    <div class="ska-card">
      <div class="ska-card__header">
        <div class="ska-card__title">
          All Reservations
          <span><?= $totalCount ?> booking<?= $totalCount !== 1 ? 's' : '' ?> found</span>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <div class="ska-status-tabs">
            <?php
            $statuses = ['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','cancelled'=>'Cancelled'];
            foreach ($statuses as $key => $label):
              $active = ($filterStatus === $key) ? "active-{$key}" : '';
            ?>
            <a href="?status=<?= $key ?>&branch=<?= urlencode($filterBranch) ?>&q=<?= urlencode($search) ?>"
               class="ska-status-tab <?= $active ?>">
              <?= $label ?><sup style="font-size:10px;margin-left:3px;opacity:0.7;"><?= $counts[$key] ?? '' ?></sup>
            </a>
            <?php endforeach; ?>
          </div>
          <form method="GET" class="ska-filter-bar">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
            <select name="branch" class="ska-filter-select" onchange="this.form.submit()">
              <option value="all"      <?= $filterBranch==='all'     ?'selected':'' ?>>All Branches</option>
              <option value="Naguru"   <?= $filterBranch==='Naguru'  ?'selected':'' ?>>Naguru</option>
              <option value="Munyonyo" <?= $filterBranch==='Munyonyo'?'selected':'' ?>>Munyonyo</option>
            </select>
            <input type="text" name="q" class="ska-filter-input"
                   placeholder="Search guest, email, room…"
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="ska-btn ska-btn--gold"><i class="fa-solid fa-magnifying-glass"></i></button>
          </form>
        </div>
      </div>

      <div style="overflow-x:auto;">
        <table class="ska-table">
          <thead>
            <tr>
              <th>#</th><th>Guest</th><th>Branch</th><th>Room Type</th>
              <th>Check-in</th><th>Check-out</th><th>Total</th>
              <th>Status</th><th>Placed</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($bookings)): ?>
              <?php foreach ($bookings as $b): ?>
              <tr>
                <td class="ska-muted"><?= $b['id'] ?></td>
                <td>
                  <div class="ska-guest-name"><?= htmlspecialchars($b['name']) ?></div>
                  <div class="ska-guest-email"><?= htmlspecialchars($b['email']) ?></div>
                  <?php if (!empty($b['phone'])): ?>
                  <div class="ska-guest-email"><?= htmlspecialchars($b['phone']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="ska-muted"><?= htmlspecialchars($b['branch'] ?? '—') ?></td>
                <td><?= htmlspecialchars($b['room_type']) ?></td>
                <td class="ska-muted"><?= htmlspecialchars($b['checkin']) ?></td>
                <td class="ska-muted"><?= htmlspecialchars($b['checkout']) ?></td>
                <td><span class="ska-price-tag">USD <?= number_format((float)($b['total']??0),0) ?></span></td>
                <td>
                  <span class="ska-badge ska-badge--<?= $b['status'] ?>">
                    <?= ucfirst($b['status']) ?>
                  </span>
                </td>
                <td class="ska-muted" style="white-space:nowrap;font-size:11.5px;">
                  <?= date('d M Y', strtotime($b['created_at'])) ?>
                </td>
                <td>
                  <div class="d-flex gap-1 align-items-center flex-wrap">
                    <button type="button" class="ska-btn-view"
                            onclick='openDetail(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)'>
                      <i class="fa-regular fa-eye"></i>
                    </button>
                    <?php if ($b['status'] === 'pending'): ?>
                      <button type="button" class="ska-btn-approve" onclick="openConfirm(<?= isset($b['id']) ? (int)$b['id'] : 0 ?>)"> 
                        <i class="fa-solid fa-check"></i> Confirm
                      </button>
                      <button type="button" class="ska-btn-reject" onclick="openReject(<?= (int)$b['id'] ?>)">
                        <i class="fa-solid fa-xmark"></i> Reject
                      </button>
                    <?php elseif ($b['status'] === 'confirmed'): ?>
                      <button type="button" class="ska-btn-reject" onclick="openReject(<?= (int)$b['id'] ?>)">
                        <i class="fa-solid fa-ban"></i> Cancel
                      </button>
                    <?php else: ?>
                      <span class="ska-muted" style="font-size:11.5px;">—</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="10">
                  <div class="ska-empty">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <p>No bookings found<?= $search ? ' for &ldquo;'.htmlspecialchars($search).'&rdquo;' : '' ?>.</p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="ska-pagination">
        <span class="ska-page-info">
          Showing <?= $offset+1 ?>–<?= min($offset+$perPage,$totalCount) ?> of <?= $totalCount ?> bookings
        </span>
        <div class="ska-page-btns">
          <?php if ($page > 1): ?>
          <a class="ska-page-btn" href="?page=<?= $page-1 ?>&status=<?= $filterStatus ?>&branch=<?= urlencode($filterBranch) ?>&q=<?= urlencode($search) ?>">
            <i class="fa-solid fa-chevron-left"></i>
          </a>
          <?php endif; ?>
          <?php for ($p=max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
          <a class="ska-page-btn <?= $p===$page?'active':'' ?>"
             href="?page=<?= $p ?>&status=<?= $filterStatus ?>&branch=<?= urlencode($filterBranch) ?>&q=<?= urlencode($search) ?>">
            <?= $p ?>
          </a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
          <a class="ska-page-btn" href="?page=<?= $page+1 ?>&status=<?= $filterStatus ?>&branch=<?= urlencode($filterBranch) ?>&q=<?= urlencode($search) ?>">
            <i class="fa-solid fa-chevron-right"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

<!-- Detail modal -->
<div class="ska-modal-backdrop" id="detailModal">
  <div class="ska-modal" role="dialog" aria-labelledby="detailTitle">
    <div class="ska-modal__header">
      <div class="ska-modal__title" id="detailTitle">Booking Details</div>
      <button type="button" class="ska-modal__close" onclick="closeModal('detailModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="ska-modal__body" id="detailBody"></div>
    <div class="ska-modal__footer">
      <button type="button" class="ska-btn ska-btn--outline" onclick="closeModal('detailModal')">Close</button>
    </div>
  </div>
</div>

<!-- Confirm modal -->
<div class="ska-modal-backdrop" id="confirmModal">
  <div class="ska-modal" role="dialog">
    <div class="ska-modal__header">
      <div class="ska-modal__title">Confirm Booking</div>
      <button type="button" class="ska-modal__close" onclick="closeModal('confirmModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="booking_action.php">
      <input type="hidden" name="booking_id" id="confirmBookingId">
      <input type="hidden" name="action" value="confirm">
      <div class="ska-modal__body">
        <p class="ska-muted" style="margin-bottom:16px">The guest will receive a confirmation email.</p>
        <label class="ska-modal-label">Message to guest (optional)</label>
        <textarea name="reason" class="ska-modal-textarea" rows="3" placeholder="Your reservation has been confirmed."></textarea>
      </div>
      <div class="ska-modal__footer">
        <button type="button" class="ska-btn ska-btn--outline" onclick="closeModal('confirmModal')">Cancel</button>
        <button type="submit" class="ska-btn ska-btn--gold"><i class="fa-solid fa-check"></i> Confirm Booking</button>
      </div>
    </form>
  </div>
</div>

<!-- Reject / Cancel modal -->
<div class="ska-modal-backdrop" id="rejectModal">
  <div class="ska-modal" role="dialog">
    <div class="ska-modal__header">
      <div class="ska-modal__title" id="rejectTitle">Cancel Booking</div>
      <button type="button" class="ska-modal__close" onclick="closeModal('rejectModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="booking_action.php">
      <input type="hidden" name="booking_id" id="rejectBookingId">
      <input type="hidden" name="action" value="cancel">
      <div class="ska-modal__body">
        <p class="ska-muted" style="margin-bottom:16px">The guest will be notified by email.</p>
        <label class="ska-modal-label">Reason (optional)</label>
        <textarea name="reason" class="ska-modal-textarea" rows="3" placeholder="We are unable to accommodate your request at this time."></textarea>
      </div>
      <div class="ska-modal__footer">
        <button type="button" class="ska-btn ska-btn--outline" onclick="closeModal('rejectModal')">Back</button>
        <button type="submit" class="ska-btn ska-btn--danger"><i class="fa-solid fa-xmark"></i> Confirm Cancellation</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}
function openDetail(booking) {
  var html = '';
  var rows = [
    ['Guest', booking.name],
    ['Email', booking.email],
    ['Phone', booking.phone || '—'],
    ['WhatsApp', booking.whatsapp || '—'],
    ['Property', booking.branch || '—'],
    ['Room', booking.room_type],
    ['Check-in', booking.checkin],
    ['Check-out', booking.checkout],
    ['Total', 'USD ' + Number(booking.total || 0).toLocaleString()],
    ['Status', booking.status],
    ['Season', booking.season || '—'],
    ['Special requests', booking.message || '—']
  ];
  rows.forEach(function(r) {
    html += '<div class="ska-detail-row"><div class="ska-detail-row__label">' + r[0] + '</div><div class="ska-detail-row__value">' + r[1] + '</div></div>';
  });
  document.getElementById('detailBody').innerHTML = html;
  openModal('detailModal');
}
function openConfirm(id) {
  document.getElementById('confirmBookingId').value = id;
  openModal('confirmModal');
}
function openReject(id) {
  document.getElementById('rejectBookingId').value = id;
  openModal('rejectModal');
}
document.querySelectorAll('.ska-modal-backdrop').forEach(function(el) {
  el.addEventListener('click', function(e) {
    if (e.target === el) closeModal(el.id);
  });
});
</script>

<?php include 'includes/layout-end.php'; ?>
