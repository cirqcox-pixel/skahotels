<?php
require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();

// ── Fetch rooms ──
$rooms = $conn->query("SELECT id, name, price, branch FROM rooms ORDER BY id DESC");

// ── Page config ──
$activePage     = 'rooms';
$pageTitle      = 'Rooms';
$pageBreadcrumb = 'Property → Rooms';
$topbarAction   = [
    'label' => 'Add Room',
    'href'  => 'add_room.php',
    'icon'  => 'fa-plus'
];
include 'includes/layout-start.php';
?>

<div class="ska-card">
  <div class="ska-card__header">
    <div class="ska-card__title">
      Rooms Management
      <span><?= $rooms->num_rows ?> rooms configured</span>
    </div>

    <a href="add_room.php" class="ska-btn ska-btn--primary">
      <i class="fa-solid fa-plus"></i> Add Room
    </a>
  </div>

  <div class="ska-card__body">
    <table class="ska-table">
      <thead>
        <tr>
          <th>Room</th>
          <th>Branch</th>
          <th>Price</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
      <?php if ($rooms && $rooms->num_rows > 0): 
        while($r = $rooms->fetch_assoc()): ?>
        <tr>
          <td>
            <div class="ska-room-name">
              <span class="ska-room-dot"></span>
              <strong><?= htmlspecialchars($r['name']) ?></strong>
            </div>
          </td>

          <td class="text-muted"><?= htmlspecialchars($r['branch']) ?></td>

          <td>
            <span class="ska-price-badge">
              $<?= number_format($r['price'],0) ?>
            </span>
          </td>

          <td>
            <div class="d-flex gap-2">

              <a href="edit_room.php?id=<?= $r['id'] ?>" class="ska-btn ska-btn--edit">
                <i class="fa fa-pen"></i> Edit
              </a>

              <a href="delete_room.php?id=<?= $r['id'] ?>"
                 class="ska-btn ska-btn--delete"
                 onclick="return confirm('Delete this room?');">
                <i class="fa fa-trash"></i>
              </a>

            </div>
          </td>
        </tr>
      <?php endwhile; else: ?>

        <tr>
          <td colspan="4">
            <div class="ska-empty">
              <i class="fa fa-bed"></i><br><br>
              No rooms yet.<br>
              Click <strong>Add Room</strong>
            </div>
          </td>
        </tr>

      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'includes/layout-end.php'; ?>
