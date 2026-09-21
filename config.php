<?php
/**
 * Osclass config — local (.env) + staging (hardcoded Hostinger).
 *
 * On *.pngmarket.online the DB constants are FIXED and ignore any uploaded
 * local `.env` (that was the usual cause of “database server is not available”).
 *
 * Local development still uses `.env` / `.env.local`.
 */

$host = '';
if (!empty($_SERVER['HTTP_HOST'])) {
    $host = strtolower((string) $_SERVER['HTTP_HOST']);
    $host = preg_replace('/:\d+$/', '', $host);
}

$pngm_remote = ($host !== '' && (
    $host === 'pngmarket.online'
    || $host === 'www.pngmarket.online'
    || substr($host, -17) === '.pngmarket.online'
));

if (!$pngm_remote) {
    // Local / CLI on your PC: load .env
    (function () {
        foreach (array('.env', '.env.local') as $name) {
            $envFile = __DIR__ . DIRECTORY_SEPARATOR . $name;
            if (!is_readable($envFile)) {
                continue;
            }
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                return;
            }
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $name  = trim($name);
                $value = trim($value);
                if ($name === '') {
                    continue;
                }
                if (
                    strlen($value) >= 2
                    && (
                        ($value[0] === '"' && substr($value, -1) === '"')
                        || ($value[0] === "'" && substr($value, -1) === "'")
                    )
                ) {
                    $value = substr($value, 1, -1);
                }
                if (getenv($name) !== false) {
                    continue;
                }
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
            }
            return;
        }
    })();
}

if (!function_exists('osc_env')) {
    function osc_env($key, $default = null)
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        return $default;
    }
}

if (!function_exists('osc_env_bool')) {
    function osc_env_bool($key, $default = false)
    {
        $value = osc_env($key, null);
        if ($value === null) {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if ($pngm_remote) {
    // Staging / production — do not read .env (local .env on server breaks DB).
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u946456524_userstage');
    define('DB_PASSWORD', 'Xke0409!');
    define('DB_NAME', 'u946456524_dbstage');
    define('DB_TABLE_PREFIX', 'oc_');
    define('REL_WEB_URL', '/');
    define('WEB_PATH', 'https://stage.pngmarket.online/');
} else {
    define('DB_HOST', osc_env('DB_HOST', '127.0.0.1'));
    define('DB_USER', osc_env('DB_USER', 'root'));
    define('DB_PASSWORD', osc_env('DB_PASSWORD', ''));
    define('DB_NAME', osc_env('DB_NAME', 'osclass'));
    define('DB_TABLE_PREFIX', osc_env('DB_TABLE_PREFIX', 'oc_'));
    define('REL_WEB_URL', osc_env('REL_WEB_URL', '/'));
    define('WEB_PATH', osc_env('WEB_PATH', 'http://localhost:8000/'));
}

// Long-lived login
ini_set('session.cookie_lifetime', 94608000);
ini_set('session.gc_maxlifetime', 94608000);
session_set_cookie_params(94608000);

if (osc_env_bool('OSC_DEBUG')) {
    define('OSC_DEBUG', true);
}
if (osc_env_bool('OSC_DEBUG_DB')) {
    define('OSC_DEBUG_DB', true);
}
if (osc_env_bool('OSC_DEBUG_LOG')) {
    define('OSC_DEBUG_LOG', true);
}
