<?php
/**
 * Serve the web app manifest with the correct Content-Type.
 * Hostinger/Apache often serve .webmanifest as text/plain, which breaks
 * Add-to-Home-Screen / installability checks.
 */
define('ABS_PATH', str_replace('\\', '/', dirname(__FILE__) . '/'));
require_once ABS_PATH . 'oc-load.php';

if (function_exists('pngm_pwa_ensure_manifest')) {
    pngm_pwa_ensure_manifest();
}

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$path = ABS_PATH . 'manifest.webmanifest';
if (is_readable($path)) {
    readfile($path);
} else {
    echo '{"name":"PNGMarket","display":"standalone","start_url":"/"}';
}
exit;
