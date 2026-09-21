<?php
/**
 * Public PWA status JSON — confirms standalone display mode without an install test.
 */
define('ABS_PATH', str_replace('\\', '/', dirname(__FILE__) . '/'));
require_once ABS_PATH . 'oc-load.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$status = function_exists('pngm_pwa_public_status')
    ? pngm_pwa_public_status()
    : array('ok' => false, 'display' => 'unknown', 'standalone_configured' => false);

echo json_encode($status, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
