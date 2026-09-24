<?php
/**
 * Staging deploy integrity check (no Osclass boot).
 * URL: /oc-content/themes/epsilon_child/pngm-health.php
 */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "health:ok\n";
echo "php:" . PHP_VERSION . "\n";

$dir = __DIR__;
$root = dirname(__DIR__, 3);

$files = array(
    'duplicate_listings.php' => $dir . '/includes/duplicate_listings.php',
    'web_push.php' => $dir . '/includes/web_push.php',
    'public_profile_gate.php' => $dir . '/includes/public_profile_gate.php',
    'functions_child.php' => $dir . '/functions_child.php',
    'hDefines.php' => $root . '/oc-includes/osclass/helpers/hDefines.php',
    'hUsers.php' => $root . '/oc-includes/osclass/helpers/hUsers.php',
    'root_sw.js' => $root . '/sw.js',
);

echo "\n--- file sizes / markers ---\n";
foreach ($files as $label => $path) {
    if (!is_readable($path)) {
        echo "MISSING $label\n";
        continue;
    }
    $raw = file_get_contents($path);
    $bytes = strlen($raw);
    $lines = substr_count($raw, "\n") + 1;
    echo "$label bytes=$bytes lines~$lines";
    if (preg_match('/pngm:[a-z0-9_-]+-ok-\d+/i', $raw, $m)) {
        echo " marker={$m[0]}";
    }
    // Detect truncated "/**" with no closing before EOF (rough)
    if (preg_match('/\/\*\*?[^*]*\z/s', $raw)) {
        echo " WARN:possible_open_comment_at_eof";
    }
    $lint = array();
    $code = 0;
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $lint, $code);
    echo $code === 0 ? " lint=OK" : (" lint=FAIL " . implode(' ', $lint));
    echo "\n";
}

echo "\n--- include chain (theme requires) ---\n";
try {
    // Only syntax-check via php -l already done; attempt token_get_all
    foreach (glob($dir . '/includes/*.php') as $p) {
        $code = file_get_contents($p);
        token_get_all($code);
    }
    echo "token_get_all:OK for includes/*.php\n";
} catch (Throwable $e) {
    echo "token_THROW:" . $e->getMessage() . "\n";
}

echo "\nhealth:done\n";
