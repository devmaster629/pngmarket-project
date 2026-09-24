<?php
/**
 * Staging health check — no Osclass boot.
 * URL: /oc-content/themes/epsilon_child/pngm-health.php
 */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "health:ok\n";
echo "php:" . PHP_VERSION . "\n";
echo "time:" . gmdate('c') . "\n";

$root = dirname(__DIR__, 3); // oc-content/themes/epsilon_child -> repo root? 
// __DIR__ = .../oc-content/themes/epsilon_child
// dirname 1 = themes, 2 = oc-content, 3 = ABS_PATH
echo "dir:" . __DIR__ . "\n";
echo "root_guess:" . $root . "\n";

$checks = array(
    'web_push' => __DIR__ . '/includes/web_push.php',
    'web_push_crypto' => __DIR__ . '/includes/web_push_crypto.php',
    'public_profile_gate' => __DIR__ . '/includes/public_profile_gate.php',
    'functions_child' => __DIR__ . '/functions_child.php',
    'root_sw' => $root . '/sw.js',
    'theme_sw' => __DIR__ . '/sw.js',
    'hUsers' => $root . '/oc-includes/osclass/helpers/hUsers.php',
    'config' => $root . '/config.php',
    'oc_load' => $root . '/oc-load.php',
);

foreach ($checks as $label => $path) {
    $exists = is_readable($path);
    echo ($exists ? 'OK ' : 'MISSING ') . $label . ' ' . $path . "\n";
    if ($exists && in_array($label, array('web_push', 'theme_sw', 'root_sw', 'hUsers', 'public_profile_gate'), true)) {
        $snippet = @file_get_contents($path, false, null, 0, 2500);
        if ($label === 'web_push') {
            echo '  marker_eager_init:' . (strpos($snippet, 'Eagerly ensure keys') !== false ? 'YES(BAD)' : 'no') . "\n";
            echo '  marker_lazy_comment:' . (strpos($snippet, 'Do NOT generate VAPID') !== false ? 'YES(GOOD)' : 'no') . "\n";
        }
        if ($label === 'theme_sw' || $label === 'root_sw') {
            if (preg_match("/PNGM_SW_CACHE\s*=\s*'([^']+)'/", $snippet, $m)) {
                echo '  cache:' . $m[1] . "\n";
            }
        }
        if ($label === 'hUsers') {
            echo '  marker_isset_fix:' . (strpos($snippet, 'Use isset before reading flags') !== false ? 'YES(GOOD)' : 'no') . "\n";
            // Need more of the file for the function — search full file
            $full = @file_get_contents($path);
            echo '  marker_isset_fix_full:' . (strpos((string) $full, 'Use isset before reading flags') !== false ? 'YES(GOOD)' : 'no') . "\n";
            echo '  marker_old_assign:' . (preg_match('/b_enabled\'\]\s*=\s*0/', (string) $full) ? 'YES(BAD)' : 'no') . "\n";
        }
        if ($label === 'public_profile_gate') {
            $full = @file_get_contents($path);
            echo '  marker_fresh_reread:' . (strpos((string) $full, 'Re-read from DB') !== false ? 'YES(OLD)' : 'no') . "\n";
            echo '  marker_use_passed_row:' . (strpos((string) $full, 'Use the row already passed in') !== false ? 'YES(GOOD)' : 'no') . "\n";
        }
    }
}

// Step boot: config + mysqli only
echo "\n--- db ping ---\n";
try {
    if (!is_readable($root . '/config.php')) {
        echo "fail: no config\n";
        exit;
    }
    require $root . '/config.php';
    echo "config:loaded\n";
    $port = defined('DB_PORT') ? (int) DB_PORT : 3306;
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, $port);
    if ($mysqli->connect_errno) {
        echo 'fail:mysqli ' . $mysqli->connect_errno . ' ' . $mysqli->connect_error . "\n";
        exit;
    }
    echo "mysqli:ok\n";

    // Check for huge/corrupt vapid prefs
    $prefix = defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : 'oc_';
    $sql = "SELECT s_section, s_name, CHAR_LENGTH(s_value) AS len FROM {$prefix}t_preference WHERE s_section LIKE '%webpush%' OR s_name LIKE '%vapid%' LIMIT 20";
    $res = $mysqli->query($sql);
    if ($res) {
        echo "vapid_prefs:\n";
        while ($row = $res->fetch_assoc()) {
            echo '  ' . $row['s_section'] . '/' . $row['s_name'] . ' len=' . $row['len'] . "\n";
        }
        $res->free();
    } else {
        echo 'pref_query_err:' . $mysqli->error . "\n";
    }
    $mysqli->close();
} catch (Throwable $e) {
    echo 'THROW:' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo "\n--- include theme files (syntax/runtime) ---\n";
try {
    // Simulate what functions_child does without full Osclass — will fail on missing funcs
    // Just compile-check via token_get_all
    foreach (array('web_push_crypto.php', 'web_push.php', 'public_profile_gate.php') as $f) {
        $p = __DIR__ . '/includes/' . $f;
        $code = file_get_contents($p);
        token_get_all($code);
        echo "parse_ok:$f\n";
    }
} catch (Throwable $e) {
    echo 'parse_THROW:' . $e->getMessage() . "\n";
}

echo "\n--- attempt oc-load ---\n";
try {
    if (!defined('ABS_PATH')) {
        define('ABS_PATH', str_replace('\\', '/', $root . '/'));
    }
    require ABS_PATH . 'oc-load.php';
    echo "oc-load:OK\n";
} catch (Throwable $e) {
    echo 'oc-load THROW:' . get_class($e) . ':' . $e->getMessage() . "\n";
    echo 'at:' . $e->getFile() . ':' . $e->getLine() . "\n";
    echo substr($e->getTraceAsString(), 0, 2000) . "\n";
}

echo "\nhealth:done\n";
