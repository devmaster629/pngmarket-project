<?php
/**
 * PNG Market — Progressive Web App (Add to Home Screen / standalone).
 *
 * Serves manifest.webmanifest + icons at site root, Apple meta tags, and
 * registers the service worker for all visitors (installability).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'pwa.php'
) {
    exit;
}

/**
 * Brand colours for splash / status bar.
 *
 * @return array{theme:string,background:string}
 */
function pngm_pwa_colors()
{
    return array(
        'theme' => '#059669',
        'background' => '#ffffff',
    );
}

/**
 * Absolute filesystem dir for root PWA assets.
 *
 * @return string
 */
function pngm_pwa_root_dir()
{
    return rtrim(ABS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'pwa' . DIRECTORY_SEPARATOR;
}

/**
 * Public URL prefix for /pwa/ assets.
 *
 * @return string
 */
function pngm_pwa_root_url()
{
    return rtrim(osc_base_url(), '/') . '/pwa/';
}

/**
 * Theme source icons (Epsilon favicons pack).
 *
 * @return string
 */
function pngm_pwa_source_favicon_dir()
{
    $parent = dirname(WebThemes::newInstance()->getCurrentThemePath()) . '/epsilon/images/favicons/';
    if (is_dir($parent)) {
        return $parent;
    }
    return ABS_PATH . 'oc-content/themes/epsilon/images/favicons/';
}

/**
 * Ensure /pwa icons exist (copy from theme favicons when missing).
 */
function pngm_pwa_ensure_icons()
{
    $dir = pngm_pwa_root_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $src_dir = pngm_pwa_source_favicon_dir();
    $map = array(
        'icon-192.png' => 'android-chrome-192x192.png',
        'icon-512.png' => 'android-chrome-512x512.png',
        'apple-touch-icon.png' => 'apple-touch-icon.png',
    );

    foreach ($map as $dest_name => $src_name) {
        $dest = $dir . $dest_name;
        $src = $src_dir . $src_name;
        if (is_readable($dest)) {
            continue;
        }
        if (is_readable($src)) {
            @copy($src, $dest);
            continue;
        }
        // Fallback: generate a solid green square if GD is available.
        if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
            $size = (strpos($dest_name, '512') !== false) ? 512 : ((strpos($dest_name, '192') !== false) ? 192 : 180);
            $im = imagecreatetruecolor($size, $size);
            $green = imagecolorallocate($im, 5, 150, 105);
            imagefilledrectangle($im, 0, 0, $size, $size, $green);
            imagepng($im, $dest);
            imagedestroy($im);
        }
    }
}

/**
 * Build / refresh root manifest.webmanifest.
 */
function pngm_pwa_ensure_manifest()
{
    pngm_pwa_ensure_icons();

    $colors = pngm_pwa_colors();
    $name = function_exists('osc_page_title') ? trim((string) osc_page_title()) : 'PNG Market';
    if ($name === '') {
        $name = 'PNG Market';
    }
    $short = $name;
    if (function_exists('mb_strlen') && mb_strlen($short) > 12) {
        $short = mb_substr($short, 0, 12);
    } elseif (strlen($short) > 12) {
        $short = substr($short, 0, 12);
    }

    $base = rtrim(osc_base_url(), '/') . '/';
    $icon_base = pngm_pwa_root_url();

    $manifest = array(
        'id' => $base,
        'name' => $name,
        'short_name' => $short,
        'description' => __('Buy, sell and find anything in Papua New Guinea', 'epsilon'),
        'start_url' => $base . '?utm_source=pwa',
        'scope' => $base,
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'theme_color' => $colors['theme'],
        'background_color' => $colors['background'],
        'lang' => 'en',
        'dir' => 'ltr',
        'icons' => array(
            array(
                'src' => $icon_base . 'icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ),
            array(
                'src' => $icon_base . 'icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ),
            array(
                'src' => $icon_base . 'icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ),
        ),
    );

    $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        return;
    }

    $root_file = rtrim(ABS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'manifest.webmanifest';
    $existing = is_readable($root_file) ? (string) file_get_contents($root_file) : '';
    if ($existing !== $json) {
        @file_put_contents($root_file, $json);
    }

    // Theme copy for reference / CDN-style theme URLs.
    $theme_file = dirname(__FILE__) . '/../manifest.webmanifest';
    if (!is_readable($theme_file) || (string) file_get_contents($theme_file) !== $json) {
        @file_put_contents($theme_file, $json);
    }
}

/**
 * Public URL of the web app manifest.
 *
 * @return string
 */
function pngm_pwa_manifest_url()
{
    return rtrim(osc_base_url(), '/') . '/manifest.webmanifest';
}

/**
 * Emit PWA / Apple head tags.
 */
function pngm_pwa_head()
{
    pngm_pwa_ensure_manifest();

    $colors = pngm_pwa_colors();
    $name = function_exists('osc_page_title') ? trim((string) osc_page_title()) : 'PNG Market';
    $icon_base = pngm_pwa_root_url();
    $manifest = pngm_pwa_manifest_url();

    echo '<link rel="manifest" href="' . osc_esc_html($manifest) . '">' . "\n";
    echo '<meta name="theme-color" content="' . osc_esc_html($colors['theme']) . '">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="application-name" content="' . osc_esc_html($name) . '">' . "\n";

    // iOS Add to Home Screen / standalone.
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    echo '<meta name="apple-mobile-web-app-title" content="' . osc_esc_html($name) . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . osc_esc_html($icon_base . 'apple-touch-icon.png') . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . osc_esc_html($icon_base . 'apple-touch-icon.png') . '">' . "\n";
}

/**
 * Register service worker for every visitor (needed for Chrome installability).
 * Logged-in users still get queued notifications via web_push.php.
 */
function pngm_pwa_register_sw_footer()
{
    if (defined('OC_ADMIN') && OC_ADMIN) {
        return;
    }

    // Avoid double-register script when web_push footer already runs for logged-in users.
    if (function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in()) {
        return;
    }

    if (function_exists('pngm_webpush_ensure_sw_file')) {
        pngm_webpush_ensure_sw_file();
    }

    $sw = function_exists('pngm_webpush_sw_url')
        ? pngm_webpush_sw_url()
        : (rtrim(osc_base_url(), '/') . '/sw.js');
    ?>
<script>
(function () {
  if (!('serviceWorker' in navigator)) return;
  navigator.serviceWorker.register(<?php echo json_encode($sw); ?>, { scope: '/' }).catch(function () {});
})();
</script>
    <?php
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init', 'pngm_pwa_ensure_manifest', 3);
    osc_add_hook('header', 'pngm_pwa_head', 8);
    osc_add_hook('footer', 'pngm_pwa_register_sw_footer', 9);
}
