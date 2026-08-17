<?php
/**
 * Admin session & authentication helpers.
 *
 * Usage:
 *   require_once __DIR__ . '/auth.php';
 *   ska_admin_require();                          // protect a page
 *   ska_admin_login($username, $password);        // on login form POST
 *
 * ska_admin_login() requires config/db.php to be loaded first ($conn).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to login if the admin session is not active.
 */
function ska_admin_require(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Attempt admin login. Supports bcrypt (password_hash) and legacy MD5 hashes.
 * On successful MD5 login, upgrades the stored hash to bcrypt automatically.
 *
 * @return bool True when credentials are valid and session is set.
 */
function ska_admin_login(string $username, string $password): bool
{
    global $conn;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new RuntimeException('Database connection ($conn) is not available. Include config/db.php first.');
    }

    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = $conn->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin  = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$admin) {
        return false;
    }

    $stored = (string) $admin['password'];
    $valid = false;
    $needsUpgrade = false;

    if (password_get_info($stored)['algo'] !== 0) {
        $valid = password_verify($password, $stored);
    } elseif (strlen($stored) === 32 && ctype_xdigit($stored)) {
        $valid = hash_equals(strtolower($stored), md5($password));
        $needsUpgrade = $valid;
    } else {
        $valid = hash_equals($stored, md5($password));
        $needsUpgrade = $valid;
    }

    if (!$valid) {
        return false;
    }

    if ($needsUpgrade) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');
        if ($up) {
            $adminId = (int) $admin['id'];
            $up->bind_param('si', $newHash, $adminId);
            $up->execute();
            $up->close();
        }
    }

    $_SESSION['admin'] = $admin['username'];
    return true;
}
