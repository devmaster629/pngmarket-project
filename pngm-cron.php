#!/usr/bin/env php
<?php
/**
 * PNG Market — server cron entry point.
 *
 * Schedule every 5 minutes (Linux crontab or Windows Task Scheduler):
 *
 *   php /path/to/pngm-cron.php
 *
 * Runs each Osclass cron type only when its next_exec is due, so it is safe
 * to invoke frequently. Prefer this over auto_cron on production.
 *
 * Optional flags:
 *   --force=hourly   Force one type (minutely|hourly|daily|weekly|monthly|yearly)
 *   --print          Print Osclass cron report lines when possible
 *   --status         Print health JSON and exit (no cron run)
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    echo "CLI only\n";
    exit(1);
}

$root = str_replace('\\', '/', dirname(__FILE__));
$php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
$index = $root . '/index.php';

if (!is_readable($index)) {
    fwrite(STDERR, "index.php not found\n");
    exit(1);
}

$force_type = '';
$do_print = false;
$status_only = false;
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--force=') === 0) {
        $force_type = strtolower(substr($arg, 8));
    } elseif ($arg === '--print') {
        $do_print = true;
    } elseif ($arg === '--status') {
        $status_only = true;
    }
}

// Lightweight bootstrap for status / due checks.
require_once $root . '/oc-load.php';

if ($status_only) {
    if (function_exists('pngm_cron_health')) {
        echo json_encode(pngm_cron_health(), JSON_PRETTY_PRINT) . PHP_EOL;
        exit(0);
    }
    fwrite(STDERR, "pngm_cron_health unavailable (theme not loaded?)\n");
    exit(1);
}

$types = array(
    'MINUTELY' => 'minutely',
    'HOURLY' => 'hourly',
    'DAILY' => 'daily',
    'WEEKLY' => 'weekly',
    'MONTHLY' => 'monthly',
    'YEARLY' => 'yearly',
);

$shift = 60;
$now = time();
$ran = array();
$failed = 0;

if ($force_type !== '') {
    if (!in_array($force_type, $types, true)) {
        fwrite(STDERR, "Unknown --force type: {$force_type}\n");
        exit(1);
    }
    $to_run = array($force_type);
} else {
    $to_run = array();
    foreach ($types as $db_type => $cli_type) {
        $row = Cron::newInstance()->getCronByType($db_type);
        if (!is_array($row) || empty($row['d_next_exec'])) {
            continue;
        }
        $next = strtotime($row['d_next_exec']);
        if ($next !== false && ($now - $next + $shift) >= 0) {
            $to_run[] = $cli_type;
        }
    }
}

if (count($to_run) === 0) {
    echo date('Y-m-d H:i:s') . " nothing due\n";
    exit(0);
}

foreach ($to_run as $cli_type) {
    // getopt('p:t:') expects -pcron -thourly (no space); do not escape the flag values.
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($index) . ' -pcron -t' . $cli_type;
    $output = array();
    $code = 0;
    exec($cmd . ' 2>&1', $output, $code);
    $ran[] = $cli_type . ($code === 0 ? ':ok' : ':fail' . $code);
    if ($code !== 0) {
        $failed++;
    }
    if ($do_print && count($output) > 0) {
        echo implode(PHP_EOL, $output) . PHP_EOL;
    }
}

echo date('Y-m-d H:i:s') . ' ran ' . implode(', ', $ran) . PHP_EOL;
exit($failed > 0 ? 1 : 0);
