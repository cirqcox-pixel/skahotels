<?php
require_once '../config/site.php';
require_once '../config/db.php';
require_once 'includes/auth.php';

if (!empty($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$needsSetup = false;
if (isset($conn) && $conn instanceof mysqli) {
    $n = $conn->query("SELECT COUNT(*) AS n FROM admins");
    $needsSetup = $n && (int)$n->fetch_assoc()['n'] === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (ska_admin_login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Invalid username or password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Sign In | SKA The Boutique</title>
  <link rel="icon" href="../assets/images/favicon.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
      <h4>SKA <span>Admin Portal</span></h4>
      <p class="logo-sub">Property management &amp; reservations</p>
    </div>

    <?php if ($needsSetup): ?>
      <div class="error" role="alert">
        <i class="fa-solid fa-circle-info"></i>
        No admin account yet. <a href="setup.php">Create the first admin</a> to finish database setup.
      </div>
    <?php elseif ($error): ?>
      <div class="error" role="alert">
        <i class="fa-solid fa-circle-xmark"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="ska-login-form" autocomplete="on">
      <div class="mb-3">
        <label class="form-label-ska" for="username">Username</label>
        <div class="input-icon-wrap">
          <i class="fa-regular fa-user"></i>
          <input type="text" name="username" id="username" class="form-control"
                 placeholder="Enter your username" required autofocus
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label-ska" for="password">Password</label>
        <div class="input-icon-wrap">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="password" class="form-control"
                 placeholder="Enter your password" required>
          <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-login">
        <span>Sign In</span>
        <i class="fa-solid fa-arrow-right"></i>
      </button>
    </form>

    <div class="login-footer">
      <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Back to website</a>
      <span class="login-footer__dot"></span>
      <a href="<?= BUILDER_URL ?>" target="_blank" rel="noopener noreferrer">Powered by <?= BUILDER_NAME ?></a>
    </div>

  </div>
</div>

<script>
document.getElementById('togglePw')?.addEventListener('click', function () {
  var input = document.getElementById('password');
  var icon  = this.querySelector('i');
  var show  = input.type === 'password';
  input.type = show ? 'text' : 'password';
  icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
});
</script>

</body>
</html>
