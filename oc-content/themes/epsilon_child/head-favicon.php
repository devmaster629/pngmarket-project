<?php
/**
 * PNGMarket favicons. Replaces the parent Epsilon sample icon (blue cassette)
 * that iOS was using as the home-screen icon.
 */
if (!defined('ABS_PATH')) {
    return;
}

$pngm_icon_base = function_exists('pngm_pwa_root_url')
    ? pngm_pwa_root_url()
    : (rtrim(osc_base_url(), '/') . '/pwa/');
$pngm_icon_ver = defined('PNGM_CHILD_VERSION') ? PNGM_CHILD_VERSION : '1';
$pngm_apple = rtrim(ABS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'pwa' . DIRECTORY_SEPARATOR . 'apple-touch-icon.png';
if (is_readable($pngm_apple)) {
    $pngm_icon_ver .= '.' . (int) filemtime($pngm_apple);
}
$pngm_q = '?v=' . rawurlencode($pngm_icon_ver);
$pngm_apple_href = $pngm_icon_base . 'apple-touch-icon.png' . $pngm_q;
?>
<link rel="shortcut icon" type="image/png" href="<?php echo osc_esc_html($pngm_icon_base . 'icon-192.png' . $pngm_q); ?>" />
<link rel="icon" href="<?php echo osc_esc_html($pngm_icon_base . 'icon-192.png' . $pngm_q); ?>" sizes="192x192" type="image/png" />
<link rel="icon" href="<?php echo osc_esc_html($pngm_icon_base . 'icon-512.png' . $pngm_q); ?>" sizes="512x512" type="image/png" />
<link rel="apple-touch-icon" href="<?php echo osc_esc_html($pngm_apple_href); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo osc_esc_html($pngm_apple_href); ?>">
<link rel="apple-touch-icon-precomposed" href="<?php echo osc_esc_html($pngm_apple_href); ?>">
