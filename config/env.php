<?php
/**
 * Load .env file into environment (simple parser)
 */
function ska_load_env(?string $path = null): void
{
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $path = $path ?? dirname(__DIR__) . '/.env';
    if (!is_readable($path)) return;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if ($key && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function ska_env(string $key, ?string $default = null): ?string
{
    ska_load_env();
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : $default;
}

function ska_use_supabase(): bool
{
    return ska_env('DB_CONNECTION') === 'supabase' && ska_env('SUPABASE_URL');
}
