<?php
/**
 * SKA Hotels — database connection
 *
 * Modes:
 *   mysql (default) — local / cPanel MySQL (backward compatible)
 *   supabase        — PostgREST via service role key (optional PHP hosting)
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

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
}
