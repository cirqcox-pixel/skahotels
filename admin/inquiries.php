<?php
require_once '../config/db.php';
require_once '../config/cms.php';
require_once 'includes/auth.php';
ska_admin_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $id = (int)$_POST['id'];
        $c = cms_conn();
        $stmt = $c->prepare("UPDATE inquiries SET is_read = 1 WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $c = cms_conn();
        $stmt = $c->prepare("DELETE FROM inquiries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: inquiries.php?deleted=1');
        exit;
    }
    header('Location: inquiries.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$c = cms_conn();
$sql = "SELECT * FROM inquiries";
if ($filter === 'unread') $sql .= " WHERE is_read = 0";
$sql .= " ORDER BY created_at DESC LIMIT 200";
$inquiries = $c->query($sql)->fetch_all(MYSQLI_ASSOC);
$unread = (int)$c->query("SELECT COUNT(*) AS n FROM inquiries WHERE is_read = 0")->fetch_assoc()['n'];

$activePage = 'inquiries';
$pageTitle = 'Inquiries';
$pageBreadcrumb = $unread ? "$unread unread message(s)" : 'Contact form submissions';
if (isset($_GET['deleted'])) { $toastMsg = 'Inquiry deleted.'; $toastType = 'success'; $includeToast = true; }
include 'includes/layout-start.php';
?>

<div class="ska-card">
  <div class="ska-card__header">
    <div class="ska-card__title">Contact Inquiries <span><?= count($inquiries) ?> shown</span></div>
    <div class="ska-filter-bar">
      <a href="?filter=all" class="ska-btn ska-btn--outline <?= $filter === 'all' ? 'active' : '' ?>">All</a>
      <a href="?filter=unread" class="ska-btn ska-btn--gold <?= $filter === 'unread' ? 'active' : '' ?>">Unread (<?= $unread ?>)</a>
    </div>
  </div>
  <div class="ska-card__body" style="overflow-x:auto">
    <?php if (empty($inquiries)): ?>
      <div class="ska-empty"><i class="fa-regular fa-envelope"></i><p>No inquiries yet.</p></div>
    <?php else: ?>
    <table class="ska-table">
      <thead><tr><th>Date</th><th>Guest</th><th>Subject</th><th>Message</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($inquiries as $inq): ?>
        <tr class="<?= $inq['is_read'] ? '' : 'fw-semibold' ?>">
          <td class="ska-muted" style="white-space:nowrap;font-size:12px"><?= date('d M Y H:i', strtotime($inq['created_at'])) ?></td>
          <td>
            <strong><?= htmlspecialchars($inq['name']) ?></strong><br>
            <a href="mailto:<?= htmlspecialchars($inq['email']) ?>"><?= htmlspecialchars($inq['email']) ?></a>
            <?php if ($inq['phone']): ?><br><span class="ska-muted"><?= htmlspecialchars($inq['phone']) ?></span><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($inq['subject'] ?: '—') ?></td>
          <td style="max-width:280px"><?= nl2br(htmlspecialchars(mb_strimwidth($inq['message'], 0, 120, '…'))) ?></td>
          <td><span class="ska-badge ska-badge--<?= $inq['is_read'] ? 'muted' : 'pending' ?>"><?= $inq['is_read'] ? 'Read' : 'New' ?></span></td>
          <td>
            <div class="d-flex gap-1">
              <a href="mailto:<?= htmlspecialchars($inq['email']) ?>?subject=Re: <?= urlencode($inq['subject'] ?: 'SKA Inquiry') ?>" class="ska-btn ska-btn--ghost-edit"><i class="fa-solid fa-reply"></i></a>
              <?php if (!$inq['is_read']): ?>
              <form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= $inq['id'] ?>"><button class="ska-btn ska-btn--outline" title="Mark read"><i class="fa-solid fa-check"></i></button></form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this inquiry?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $inq['id'] ?>"><button class="ska-btn ska-btn--ghost-del"><i class="fa-regular fa-trash-can"></i></button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/layout-end.php'; ?>
