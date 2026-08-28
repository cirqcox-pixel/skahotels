<?php
/**
 * SKA Hotels — database connection
 *
 * Modes:
 *   mysql (default) — local / cPanel MySQL (backward compatible)
 *   supabase        — PostgREST via service role key (optional PHP hosting)
 *
 * There is no Laravel .env/database.php here. Credentials come from the
 * project-root `.env` file (copy `.env.example`). GitHub Pages does not use
 * this file — the static site talks to Supabase from assets/js/ska-config.js.
 */
require_once __DIR__ . '/env.php';
ska_load_env();

$driver = ska_env('DB_CONNECTION', 'mysql');

if ($driver === 'supabase') {
    require_once __DIR__ . '/SupabaseClient.php';
    $supabase = new SupabaseClient(
        ska_env('SUPABASE_URL'),
        ska_env('SUPABASE_SERVICE_ROLE_KEY') ?: ska_env('SUPABASE_ANON_KEY', '')
    );
    /** @var SupabaseClient $supabase Global REST client — use where mysqli is not available */
    $conn = null;
} else {
    $host = ska_env('DB_HOST', '127.0.0.1');
    $db   = ska_env('DB_DATABASE', 'skabcwvw_ska001');
    $user = ska_env('DB_USERNAME', 'skabcwvw_skabcwvw');
    $pass = ska_env('DB_PASSWORD', '');

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        $envPath = dirname(__DIR__) . '/.env';
        $hint = is_readable($envPath)
            ? ' Check DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD in .env.'
            : ' Copy .env.example to .env and set your MySQL credentials. See DEPLOY_CPANEL.md.';
        http_response_code(500);
        die('MySQL connection failed: ' . htmlspecialchars($conn->connect_error) . '.' . $hint);
    }

    $conn->set_charset('utf8mb4');

    // Admin pages include db.php without cms.php. Bootstrap here so a fresh
    // database gets rooms / bookings / admins / CMS tables on first request.
    require_once __DIR__ . '/cms.php';
}
