<?php
/**
 * FILE: admin/setup.php
 * One-time first-admin bootstrap for the cPanel / MySQL deployment.
 *
 * - Ensures all tables exist (via config/cms.php → cms_bootstrap()).
 * - Creates the FIRST admin account (bcrypt) ONLY while the admins table is empty.
 * - Refuses to run once any admin exists (so it can't be abused to add admins).
 *
 * SECURITY: delete this file after creating your admin, or it stays a no-op
 *           but is best removed. It self-disables once an admin exists.
 */
require_once '../config/site.php';
require_once '../config/db.php';
require_once '../config/cms.php'; // cms_bootstrap() creates every table incl. admins

$c = $conn;

// Already configured? Self-disable.
$existing = 0;
if ($res = $c->query("SELECT COUNT(*) AS n FROM admins")) {
    $existing = (int) $res->fetch_assoc()['n'];
}

$error   = '';
$created  = false;

if ($existing === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($username === '' || strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $c->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->bind_param('ss', $username, $hash);
        if ($stmt->execute()) {
            $created = true;
        } else {
            $error = 'Could not create admin: ' . htmlspecialchars($c->error);
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Setup | SKA The Boutique</title>
  <link rel="icon" href="../assets/images/favicon.png" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/login.css">
</head>
<body class="ska-login">

<div class="ska-login-wrap">
  <div class="login-card">

    <div class="logo">
      <img src="../assets/images/ska_logo1.png" alt="SKA The Boutique" width="120" height="auto">
      <h4>SKA <span>Admin Setup</span></h4>
      <p class="logo-sub">Create your first administrator</p>
    </div>

    <?php if ($existing > 0): ?>
      <div class="error" role="alert">
        <i class="fa-solid fa-circle-check"></i>
        Setup is already complete — an admin account exists.
      </div>
      <p style="text-align:center">
        For security, please <strong>delete <code>admin/setup.php</code></strong> from your server.
      </p>
      <div class="login-footer">
        <a href="login.php"><i class="fa-solid fa-arrow-right"></i> Go to sign in</a>
      </div>

    <?php elseif ($created): ?>
      <div class="error" role="alert" style="background:#e7f6ec;color:#1a7f37">
        <i class="fa-solid fa-circle-check"></i>
        Admin created successfully.
      </div>
      <p style="text-align:center">
        <strong>Important:</strong> delete <code>admin/setup.php</code> now, then sign in.
      </p>
      <div class="login-footer">
        <a href="login.php"><i class="fa-solid fa-arrow-right"></i> Go to sign in</a>
      </div>

    <?php else: ?>
      <?php if ($error): ?>
        <div class="error" role="alert">
          <i class="fa-solid fa-circle-xmark"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="ska-login-form" autocomplete="off">
        <div class="mb-3">
          <label class="form-label-ska" for="username">Username</label>
          <div class="input-icon-wrap">
            <i class="fa-regular fa-user"></i>
            <input type="text" name="username" id="username" class="form-control"
                   placeholder="Choose an admin username" required autofocus minlength="3"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label-ska" for="password">Password</label>
          <div class="input-icon-wrap">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" id="password" class="form-control"
                   placeholder="At least 8 characters" required minlength="8">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label-ska" for="confirm">Confirm password</label>
          <div class="input-icon-wrap">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="confirm" id="confirm" class="form-control"
                   placeholder="Re-enter password" required minlength="8">
          </div>
        </div>

        <button type="submit" class="btn btn-login">
          <span>Create Admin</span>
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </form>

      <div class="login-footer">
        <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Back to website</a>
      </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
