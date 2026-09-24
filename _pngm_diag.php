<?php
/**
 * Temporary staging diagnostic — delete after the 500 is fixed.
 * Visit: /_pngm_diag.php
 */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "diag:start\n";
echo "php:" . PHP_VERSION . "\n";

try {
    if (!is_readable(__DIR__ . '/config.php')) {
        echo "fail:config.php missing\n";
        exit;
    }
    echo "ok:config.php readable\n";

    // Minimal DB ping without full Osclass (parse DB_* from config if defined after include).
    require __DIR__ . '/config.php';
    echo "ok:config loaded\n";

    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        echo "fail:DB constants missing\n";
        exit;
    }
    echo "ok:DB constants present\n";

    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, defined('DB_PORT') ? (int) DB_PORT : 3306);
    if ($mysqli->connect_errno) {
        echo "fail:mysqli " . $mysqli->connect_errno . " " . $mysqli->connect_error . "\n";
        exit;
    }
    echo "ok:mysqli connected\n";
    $mysqli->close();

    // Full Osclass boot (this is what index.php does).
    echo "boot:oc-load…\n";
    require __DIR__ . '/oc-load.php';
    echo "ok:oc-load\n";
    echo "ok:site should work — if you still see 500 on /, check theme/plugin hooks next\n";
} catch (Throwable $e) {
    echo "THROWABLE:" . get_class($e) . "\n";
    echo "message:" . $e->getMessage() . "\n";
    echo "file:" . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "trace:\n" . $e->getTraceAsString() . "\n";
}
