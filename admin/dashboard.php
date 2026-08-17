<?php
require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();

// ── Stats ────────────────────────────────────────────────────────────────────
$totalRooms    = $conn->query("SELECT COUNT(*) AS total FROM rooms")
                       ->fetch_assoc()['total'] ?? 0;

$totalBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")
                       ->fetch_assoc()['total'] ?? 0;

$totalRevenue  = $conn->query("SELECT SUM(total) AS total FROM bookings")
                       ->fetch_assoc()['total'] ?? 0;

$todayCheckins = $conn->query(
    "SELECT COUNT(*) AS total FROM bookings WHERE checkin = CURDATE()"
)->fetch_assoc()['total'] ?? 0;

$recentBookings = $conn->query(
    "SELECT id, name, email, room_type, checkin, checkout, total
     FROM bookings
     ORDER BY created_at DESC
     LIMIT 5"
);

$rooms = $conn->query("SELECT id, name, price, branch FROM rooms ORDER BY id DESC");

// ── Topbar / Sidebar config ───────────────────────────────────────────────────
$activePage     = 'dashboard';
$pageTitle      = 'Dashboard';
$pageBreadcrumb = "Welcome back — here's what's happening today";
$topbarAction   = [
    'label' => 'Add Room',
    'href'  => 'add_room.php',
    'icon'  => 'fa-plus',
];
if (isset($_GET['created'])) {
    $toastMsg = 'Room created successfully.';
    $toastType = 'success';
    $includeToast = true;
}
include 'includes/layout-start.php';
?>

<!-- ── Stat Cards ────────────────────────────── -->
    <div class="ska-stats-grid">

      <div class="ska-stat-card ska-stat-card--gold">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Total Rooms</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-bed"></i></div>
        </div>
        <div class="ska-stat-card__value"><?= $totalRooms ?></div>
        <div class="ska-stat-card__sub">Across all properties</div>
      </div>

      <div class="ska-stat-card ska-stat-card--navy">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Bookings</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-calendar-check"></i></div>
        </div>
        <div class="ska-stat-card__value"><?= $totalBookings ?></div>
        <div class="ska-stat-card__sub">Total reservations</div>
      </div>

      <div class="ska-stat-card ska-stat-card--success">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Revenue</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-dollar-sign"></i></div>
        </div>
        <div class="ska-stat-card__value">$<?= number_format($totalRevenue, 0) ?></div>
        <div class="ska-stat-card__sub">Total earnings (USD)</div>
      </div>

      <div class="ska-stat-card ska-stat-card--warning">
        <div class="ska-stat-card__header">
          <div class="ska-stat-card__label">Today's Check-ins</div>
          <div class="ska-stat-card__icon"><i class="fa-solid fa-door-open"></i></div>
        </div>
        <div class="ska-stat-card__value"><?= $todayCheckins ?></div>
        <div class="ska-stat-card__sub">Guests arriving today</div>
      </div>

    </div>

    <!-- ── Two-col: Rooms + Recent Bookings ──────── -->
    <div class="ska-grid-2">

      <!-- Rooms Table -->
      <div class="ska-card">
        <div class="ska-card__header">
          <div class="ska-card__title">
            Rooms Management
            <span><?= $totalRooms ?> rooms configured</span>
          </div>
          <a href="add_room.php" class="ska-btn ska-btn--primary">
            <i class="fa-solid fa-plus"></i> Add
          </a>
        </div>
        <div class="ska-card__body">
          <table class="ska-table">
            <thead>
              <tr>
                <th>Room</th>
                <th>Branch</th>
                <th>Price / Night</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rooms && $rooms->num_rows > 0):
                while ($r = $rooms->fetch_assoc()): ?>
              <tr>
                <td>
                  <div class="ska-room-name">
                    <span class="ska-room-dot"></span>
                    <strong><?= htmlspecialchars($r['name']) ?></strong>
                  </div>
                </td>
                <td class="muted" style="font-size:12.5px;"><?= htmlspecialchars($r['branch'] ?? '—') ?></td>
                <td><span class="ska-price-badge">$<?= number_format($r['price'], 0) ?></span></td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="edit_room.php?id=<?= $r['id'] ?>" class="ska-btn ska-btn--ghost-edit">
                      <i class="fa-regular fa-pen-to-square"></i> Edit
                    </a>
                    <a href="delete_room.php?id=<?= $r['id'] ?>"
                       class="ska-btn ska-btn--ghost-del"
                       onclick="return confirm('Delete this room? This cannot be undone.')">
                      <i class="fa-regular fa-trash-can"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr>
                <td colspan="4">
                  <div class="ska-empty">
                    <i class="fa-solid fa-bed"></i>
                    <p>No rooms added yet.<br>Click <strong>Add</strong> to create your first room.</p>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Bookings -->
      <div class="ska-card">
        <div class="ska-card__header">
          <div class="ska-card__title">
            Recent Bookings
            <span>Last 5 reservations</span>
          </div>
          <a href="bookings.php" class="ska-btn ska-btn--outline">View All</a>
        </div>
        <div class="ska-card__body">
          <table class="ska-table">
            <thead>
              <tr>
                <th>Guest</th>
                <th>Room Type</th>
                <th>Check-in</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recentBookings && $recentBookings->num_rows > 0):
                while ($b = $recentBookings->fetch_assoc()): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($b['name']) ?></strong><br>
                  <span class="muted" style="font-size:11px;"><?= htmlspecialchars($b['email']) ?></span>
                </td>
                <td class="muted"><?= htmlspecialchars($b['room_type']) ?></td>
                <td class="muted" style="font-size:12.5px;"><?= htmlspecialchars($b['checkin']) ?></td>
                <td><span class="ska-price-badge">$<?= number_format($b['total'], 0) ?></span></td>
              </tr>
              <?php endwhile; else: ?>
              <tr>
                <td colspan="4">
                  <div class="ska-empty">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <p>No bookings recorded yet.</p>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
<?php include 'includes/layout-end.php'; ?>
