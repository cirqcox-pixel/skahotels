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

function ska_admin_role_pages(string $role): array
{
    switch ($role) {
        case 'super_admin':
            return ['dashboard', 'rooms', 'promotions', 'packages', 'bookings', 'inquiries', 'pages', 'gallery', 'settings', 'staff'];
        case 'manager':
            return ['dashboard', 'rooms', 'promotions', 'packages', 'bookings', 'inquiries'];
        case 'reservations':
            return ['dashboard', 'bookings', 'inquiries'];
        case 'marketing':
            return ['dashboard', 'promotions', 'packages'];
        default:
            return ['dashboard', 'bookings'];
    }
}

function ska_admin_role_label(string $role): string
{
    $labels = [
        'super_admin' => 'Super Admin',
        'manager' => 'Manager',
        'reservations' => 'Reservations',
        'marketing' => 'Marketing',
    ];
    return $labels[$role] ?? $role;
}

function ska_admin_page_key(): string
{
    $file = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    $map = [
        'add_room' => 'rooms',
        'edit_room' => 'rooms',
        'delete_room' => 'rooms',
        'delete_image' => 'rooms',
        'booking_action' => 'bookings',
    ];
    return $map[$file] ?? $file;
}

function ska_admin_can(string $page): bool
{
    $role = (string) ($_SESSION['admin_role'] ?? 'super_admin');
    $pages = ska_admin_role_pages($role);
    return in_array($page, $pages, true);
}

/**
 * Redirect to login if the admin session is not active.
 * Optionally gate by dashboard section (rooms, bookings, staff, …).
 */
function ska_admin_require(?string $page = null): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: login.php');
        exit;
    }
    if (empty($_SESSION['admin_role'])) {
        $_SESSION['admin_role'] = 'super_admin';
    }
    $page = $page ?? ska_admin_page_key();
    if ($page && $page !== 'login' && $page !== 'logout' && $page !== 'setup' && !ska_admin_can($page)) {
        header('Location: dashboard.php?denied=1');
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

    $stmt = $conn->prepare('SELECT id, username, password, role FROM admins WHERE username = ? LIMIT 1');
    if (!$stmt) {
        $stmt = $conn->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
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
    $_SESSION['admin_role'] = $admin['role'] ?? 'super_admin';
    if ($_SESSION['admin_role'] === '') {
        $_SESSION['admin_role'] = 'super_admin';
    }
    return true;
}
