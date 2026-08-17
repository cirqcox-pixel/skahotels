<?php
require_once '../config/db.php';
require_once '../config/cms.php';
require_once 'includes/auth.php';
ska_admin_require();

$c = cms_conn();
$error = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (strlen($username) < 3 || strlen($password) < 6) {
            $error = 'Username min 3 chars, password min 6 chars.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $c->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->bind_param('ss', $username, $hash);
            if ($stmt->execute()) {
                $msg = 'Admin user created.';
            } else {
                $error = 'Username may already exist.';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $me = $_SESSION['admin'] ?? '';
        $chk = $c->prepare("SELECT username FROM admins WHERE id = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $u = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($u && $u['username'] === $me) {
            $error = 'You cannot delete your own account while logged in.';
        } else {
            $del = $c->prepare("DELETE FROM admins WHERE id = ?");
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();
            $msg = 'Admin removed.';
        }
    }

    if ($action === 'reset_password') {
        $id = (int)$_POST['id'];
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $c->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            $stmt->close();
            $msg = 'Password updated.';
        }
    }
}

$admins = $c->query("SELECT id, username FROM admins ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$activePage = 'staff';
$pageTitle = 'Admin Users';
$pageBreadcrumb = 'Manage who can access this portal';
if ($msg) { $toastMsg = $msg; $toastType = 'success'; $includeToast = true; }
include 'includes/layout-start.php';
?>

<?php if ($error): ?><div class="ska-alert ska-alert--danger mb-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="ska-card">
      <div class="ska-card__header"><div class="ska-card__title">Add Admin User</div></div>
      <div class="ska-card__body ska-card__body--padded">
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="mb-3"><label class="ska-label">Username</label><input name="username" class="ska-input" required></div>
          <div class="mb-3"><label class="ska-label">Password</label><input type="password" name="password" class="ska-input" required minlength="6"></div>
          <button type="submit" class="ska-btn ska-btn--gold"><i class="fa-solid fa-user-plus"></i> Create User</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="ska-card">
      <div class="ska-card__header"><div class="ska-card__title">Existing Admins</div></div>
      <div class="ska-card__body">
        <table class="ska-table">
          <thead><tr><th>Username</th><th>Reset Password</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($admins as $a): ?>
            <tr>
              <td><strong><?= htmlspecialchars($a['username']) ?></strong><?= ($a['username'] === ($_SESSION['admin'] ?? '')) ? ' <span class="ska-badge ska-badge--confirmed">You</span>' : '' ?></td>
              <td>
                <form method="POST" class="d-flex gap-2">
                  <input type="hidden" name="action" value="reset_password">
                  <input type="hidden" name="id" value="<?= $a['id'] ?>">
                  <input type="password" name="password" class="ska-input" placeholder="New password" minlength="6" style="max-width:160px">
                  <button class="ska-btn ska-btn--outline">Update</button>
                </form>
              </td>
              <td>
                <?php if ($a['username'] !== ($_SESSION['admin'] ?? '')): ?>
                <form method="POST" onsubmit="return confirm('Remove this admin?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="ska-btn ska-btn--ghost-del"><i class="fa-regular fa-trash-can"></i></button></form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/layout-end.php'; ?>
