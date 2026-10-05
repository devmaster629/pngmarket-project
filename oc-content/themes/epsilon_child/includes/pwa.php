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
        'theme' => '#006b24',
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
 * Preferred source for home-screen icons (logo mark), then parent favicons.
 *
 * @return string
 */
function pngm_pwa_source_favicon_dir()
{
    $child = WebThemes::newInstance()->getCurrentThemePath() . 'images/pwa/';
    if (is_dir($child) && is_readable($child . 'icon-192.png')) {
        return $child;
    }
    $parent = dirname(WebThemes::newInstance()->getCurrentThemePath()) . '/epsilon/images/favicons/';
    if (is_dir($parent)) {
        return $parent;
    }
    return ABS_PATH . 'oc-content/themes/epsilon/images/favicons/';
}

/**
 * Ensure /pwa icons exist. Prefer theme logo-mark icons; refresh when newer.
 */
function pngm_pwa_ensure_icons()
{
    $dir = pngm_pwa_root_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $src_dir = pngm_pwa_source_favicon_dir();
    $map = array(
        'icon-192.png' => array('icon-192.png', 'android-chrome-192x192.png'),
        'icon-512.png' => array('icon-512.png', 'android-chrome-512x512.png'),
        'icon-512-maskable.png' => array('icon-512-maskable.png', 'icon-512.png', 'android-chrome-512x512.png'),
        'apple-touch-icon.png' => array('apple-touch-icon.png'),
    );

    foreach ($map as $dest_name => $candidates) {
        $dest = $dir . $dest_name;
        $src = '';
        foreach ($candidates as $src_name) {
            $try = $src_dir . $src_name;
            if (is_readable($try)) {
                $src = $try;
                break;
            }
        }
        if ($src === '') {
            // Fallback: solid brand green square if GD is available.
            if (!is_readable($dest) && extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
                $size = (strpos($dest_name, '512') !== false) ? 512 : ((strpos($dest_name, '192') !== false) ? 192 : 180);
                $im = imagecreatetruecolor($size, $size);
                $green = imagecolorallocate($im, 0, 107, 36);
                imagefilledrectangle($im, 0, 0, $size, $size, $green);
                imagepng($im, $dest);
                imagedestroy($im);
            }
            continue;
        }
        $need = !is_readable($dest);
        if (!$need) {
            $src_m = @filemtime($src);
            $dst_m = @filemtime($dest);
            $need = ($src_m && $dst_m && $src_m > $dst_m);
        }
        if ($need) {
            @copy($src, $dest);
        }
    }

    // iOS looks for /apple-touch-icon.png at the site root when adding to Home Screen.
    $apple_src = $dir . 'apple-touch-icon.png';
    if (is_readable($apple_src)) {
        $root = rtrim(ABS_PATH, '/\\') . DIRECTORY_SEPARATOR;
        foreach (array('apple-touch-icon.png', 'apple-touch-icon-precomposed.png', 'apple-touch-icon-180x180.png') as $root_name) {
            $root_dest = $root . $root_name;
            $need_root = !is_readable($root_dest);
            if (!$need_root) {
                $src_m = @filemtime($apple_src);
                $dst_m = @filemtime($root_dest);
                $need_root = ($src_m && $dst_m && $src_m > $dst_m);
            }
            if ($need_root) {
                @copy($apple_src, $root_dest);
            }
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

    // Path-absolute URLs so local/stage/prod share one manifest shape (no host baked in).
    $start = '/?utm_source=pwa';
    $scope = '/';
    $icon_192 = '/pwa/icon-192.png';
    $icon_512 = '/pwa/icon-512.png';
    $icon_mask = is_readable(pngm_pwa_root_dir() . 'icon-512-maskable.png')
        ? '/pwa/icon-512-maskable.png'
        : $icon_512;

    $manifest = array(
        'id' => $scope,
        'name' => $name,
        'short_name' => $short,
        'description' => __('Buy, sell and find anything in Papua New Guinea', 'epsilon'),
        'start_url' => $start,
        'scope' => $scope,
        'display' => 'standalone',
        'display_override' => array('standalone', 'minimal-ui'),
        'orientation' => 'any',
        'theme_color' => $colors['theme'],
        'background_color' => $colors['background'],
        'lang' => 'en',
        'dir' => 'ltr',
        'icons' => array(
            array(
                'src' => $icon_192,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ),
            array(
                'src' => $icon_512,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ),
            array(
                'src' => $icon_mask,
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
 * Public URL of the web app manifest (PHP endpoint = correct Content-Type).
 *
 * @return string
 */
function pngm_pwa_manifest_url()
{
    return rtrim(osc_base_url(), '/') . '/pwa-manifest.php';
}

/**
 * Public installability / standalone status (for auditors + Account & Security).
 *
 * @return array
 */
function pngm_pwa_public_status()
{
    pngm_pwa_ensure_manifest();
    $root = rtrim(ABS_PATH, '/\\') . DIRECTORY_SEPARATOR;
    $manifest_file = $root . 'manifest.webmanifest';
    $sw_file = $root . 'sw.js';
    $icon_192 = pngm_pwa_root_dir() . 'icon-192.png';
    $icon_512 = pngm_pwa_root_dir() . 'icon-512.png';

    $display = 'standalone';
    if (is_readable($manifest_file)) {
        $decoded = json_decode((string) file_get_contents($manifest_file), true);
        if (is_array($decoded) && !empty($decoded['display'])) {
            $display = (string) $decoded['display'];
        }
    }

    return array(
        'ok' => ($display === 'standalone'
            && is_readable($manifest_file)
            && is_readable($sw_file)
            && is_readable($icon_192)
            && is_readable($icon_512)),
        'display' => $display,
        'standalone_configured' => ($display === 'standalone'),
        'manifest_url' => pngm_pwa_manifest_url(),
        'sw_url' => function_exists('pngm_webpush_sw_url')
            ? pngm_webpush_sw_url()
            : (rtrim(osc_base_url(), '/') . '/sw.js'),
        'icons' => array(
            '192' => is_readable($icon_192),
            '512' => is_readable($icon_512),
        ),
        'apple_capable' => true,
        'mobile_install_ui' => true,
        'platforms' => array('android', 'ios'),
    );
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

    $apple = $icon_base . 'apple-touch-icon.png';
    $apple_file = pngm_pwa_root_dir() . 'apple-touch-icon.png';
    $ver = defined('PNGM_CHILD_VERSION') ? PNGM_CHILD_VERSION : '1';
    if (is_readable($apple_file)) {
        $ver .= '.' . (int) filemtime($apple_file);
    }
    $apple .= '?v=' . rawurlencode($ver);

    echo '<link rel="manifest" href="' . osc_esc_html($manifest) . '">' . "\n";
    echo '<meta name="theme-color" content="' . osc_esc_html($colors['theme']) . '">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="application-name" content="' . osc_esc_html($name) . '">' . "\n";

    // iOS Add to Home Screen / standalone.
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    echo '<meta name="apple-mobile-web-app-title" content="' . osc_esc_html($name) . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . osc_esc_html($apple) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="120x120" href="' . osc_esc_html($apple) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="152x152" href="' . osc_esc_html($apple) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="167x167" href="' . osc_esc_html($apple) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . osc_esc_html($apple) . '">' . "\n";
    echo '<link rel="apple-touch-icon-precomposed" href="' . osc_esc_html($apple) . '">' . "\n";
}

/**
 * Register service worker + mobile install UX (Android prompt / iOS A2HS guide).
 * Responsive UI alone is not a PWA — this exposes the real install path.
 */
function pngm_pwa_register_sw_footer()
{
    static $printed = false;
    if ($printed) {
        return;
    }
    if (defined('OC_ADMIN') && OC_ADMIN) {
        return;
    }
    $printed = true;

    if (function_exists('pngm_webpush_ensure_sw_file')) {
        pngm_webpush_ensure_sw_file();
    }

    $sw = function_exists('pngm_webpush_sw_url')
        ? pngm_webpush_sw_url()
        : (rtrim(osc_base_url(), '/') . '/sw.js');
    $icon_file = pngm_pwa_root_dir() . 'apple-touch-icon.png';
    $icon_ver = defined('PNGM_CHILD_VERSION') ? PNGM_CHILD_VERSION : '1';
    if (is_readable($icon_file)) {
        $icon = pngm_pwa_root_url() . 'apple-touch-icon.png?v=' . (int) filemtime($icon_file) . '-' . $icon_ver;
    } else {
        $icon = pngm_pwa_root_url() . 'icon-192.png?v=' . $icon_ver;
    }
    $name = function_exists('osc_page_title') ? trim((string) osc_page_title()) : 'PNGMarket';
    if ($name === '') {
        $name = 'PNGMarket';
    }
    // Keep short brand for titles — never say “PWA” to shoppers.
    $brand = preg_replace('/\s+/', '', $name);
    if ($brand === '') {
        $brand = 'PNGMarket';
    }
    $L = array(
        'title' => sprintf(__('Add %s to your Home Screen', 'epsilon'), $brand),
        'body' => __('Open it like an app — faster and full screen.', 'epsilon'),
        'install' => __('Add to phone', 'epsilon'),
        'iosTitle' => sprintf(__('Add %s to your Home Screen', 'epsilon'), $brand),
        'iosStep1' => __('Tap Share in Safari', 'epsilon'),
        'iosStep2' => __('Tap Add to Home Screen', 'epsilon'),
        'iosStep3' => __('Tap Add', 'epsilon'),
        'androidTitle' => sprintf(__('Add %s to your Home Screen', 'epsilon'), $brand),
        'androidStep1' => __('Tap the Chrome menu', 'epsilon'),
        'androidStep2' => __('Tap Install app', 'epsilon'),
        'androidStep3' => __('Tap Install', 'epsilon'),
        'gotIt' => __('Got it', 'epsilon'),
        'dismiss' => __('Not now', 'epsilon'),
        'later' => __('Not now', 'epsilon'),
    );
    ?>
<div id="pngm-pwa-install" class="pngm-pwa-install" hidden data-pngm-pwa-install>
  <div class="pngm-pwa-install-inner">
    <button type="button" class="pngm-pwa-install-dismiss" data-pngm-pwa-dismiss aria-label="<?php echo osc_esc_html($L['dismiss']); ?>">&times;</button>
    <div class="pngm-pwa-install-top">
      <img class="pngm-pwa-install-icon" src="<?php echo osc_esc_html($icon); ?>" width="48" height="48" alt="" />
      <div class="pngm-pwa-install-copy">
        <strong data-pngm-pwa-title><?php echo osc_esc_html($L['title']); ?></strong>
        <em data-pngm-pwa-body><?php echo osc_esc_html($L['body']); ?></em>
      </div>
    </div>
    <ol class="pngm-pwa-install-steps" data-pngm-pwa-steps hidden></ol>
    <div class="pngm-pwa-install-actions">
      <button type="button" class="pngm-pwa-install-btn" data-pngm-pwa-primary><?php echo osc_esc_html($L['install']); ?></button>
      <button type="button" class="pngm-pwa-install-later" data-pngm-pwa-later><?php echo osc_esc_html($L['later']); ?></button>
    </div>
  </div>
</div>
<script>
(function () {
  if (window.__pngmPwaInstallBooted) {
    var dupes = document.querySelectorAll('[data-pngm-pwa-install]');
    var di;
    for (di = 1; di < dupes.length; di += 1) {
      if (dupes[di].parentNode) dupes[di].parentNode.removeChild(dupes[di]);
    }
    return;
  }
  window.__pngmPwaInstallBooted = true;

  var swUrl = <?php echo json_encode($sw); ?>;
  var L = <?php echo json_encode($L); ?>;
  var loggedIn = <?php echo (function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in()) ? 'true' : 'false'; ?>;
  var deferredPrompt = null;
  var banners = document.querySelectorAll('[data-pngm-pwa-install]');
  var banner = banners[0] || null;
  var bi;
  for (bi = 1; bi < banners.length; bi += 1) {
    if (banners[bi].parentNode) banners[bi].parentNode.removeChild(banners[bi]);
  }
  var storageKey = 'pngm_home_add_dismissed_v5';
  var seenKey = 'pngm_home_add_seen_v1';
  var SHOW_AFTER_MS = 5000;
  var autoShown = false;
  var pendingShow = false;
  var pausedForKeyboard = false;
  var shownThisLoad = false;

  function detectDisplayMode() {
    var mode = 'browser';
    try {
      if (window.matchMedia('(display-mode: standalone)').matches) {
        mode = 'standalone';
      } else if (window.matchMedia('(display-mode: minimal-ui)').matches) {
        mode = 'minimal-ui';
      } else if (window.matchMedia('(display-mode: fullscreen)').matches) {
        mode = 'fullscreen';
      } else if (typeof navigator !== 'undefined' && navigator.standalone === true) {
        mode = 'standalone';
      }
    } catch (e) {}
    document.documentElement.setAttribute('data-pngm-display-mode', mode);
    document.documentElement.classList.toggle('pngm-pwa-standalone', mode === 'standalone');
    var nodes = document.querySelectorAll('[data-pngm-display-mode-label]');
    Array.prototype.forEach.call(nodes, function (el) {
      el.textContent = mode;
      el.classList.toggle('is-ok', mode === 'standalone');
    });
    return mode;
  }

  function isIos() {
    var ua = navigator.userAgent || '';
    return /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  }

  function isPhone() {
    var ua = navigator.userAgent || '';
    if (/Android/i.test(ua)) return true;
    if (/iPhone|iPad|iPod/i.test(ua)) return true;
    if (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) return true;
    try {
      return window.matchMedia('(max-width: 767px)').matches;
    } catch (e) {
      return false;
    }
  }

  function wasDismissed() {
    try {
      if (window.localStorage.getItem(storageKey) === '1') return true;
    } catch (e) {}
    if (document.cookie && document.cookie.indexOf(storageKey + '=1') !== -1) return true;
    return false;
  }

  function seenAlready() {
    if (wasDismissed()) return true;
    try {
      if (window.localStorage.getItem(seenKey) === '1') return true;
    } catch (e) {}
    try {
      if (window.sessionStorage.getItem(seenKey) === '1') return true;
    } catch (e) {}
    if (document.cookie && document.cookie.indexOf(seenKey + '=1') !== -1) return true;
    return false;
  }

  function markSeen() {
    shownThisLoad = true;
    try { window.localStorage.setItem(seenKey, '1'); } catch (e) {}
    try { window.sessionStorage.setItem(seenKey, '1'); } catch (e) {}
    document.cookie = seenKey + '=1; path=/; max-age=31536000; SameSite=Lax';
  }

  function dismiss() {
    if (!banner) return;
    pendingShow = false;
    pausedForKeyboard = false;
    banner.hidden = true;
    banner.setAttribute('hidden', 'hidden');
    banner.removeAttribute('data-shown');
    try { window.localStorage.setItem(storageKey, '1'); } catch (e) {}
    try { window.sessionStorage.setItem(seenKey, '1'); } catch (e) {}
    document.cookie = storageKey + '=1; path=/; max-age=31536000; SameSite=Lax';
  }

  function keepSingleBanner() {
    var nodes = document.querySelectorAll('[data-pngm-pwa-install]');
    var i;
    if (!banner && nodes.length) banner = nodes[0];
    for (i = 0; i < nodes.length; i += 1) {
      if (nodes[i] !== banner && nodes[i].parentNode) {
        nodes[i].parentNode.removeChild(nodes[i]);
      }
    }
  }

  function stepIcon(name) {
    if (name === 'share') {
      return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M16.4 9.4h2.3c1.2 0 2.3 1 2.3 2.3v8c0 1.3-1.1 2.3-2.3 2.3H5.3A2.3 2.3 0 0 1 3 19.7v-8c0-1.3 1-2.3 2.3-2.3h2.3v1.8H5.3c-.3 0-.5.2-.5.5v8c0 .3.2.5.5.5h13.4c.3 0 .5-.2.5-.5v-8c0-.3-.2-.5-.5-.5h-2.3V9.4z"/><path fill="currentColor" d="M12 2.1l4.5 4.5-1.3 1.3-2.3-2.2v9.4h-1.8V5.7L8.8 7.9 7.5 6.6 12 2.1z"/></svg>';
    }
    if (name === 'plus') {
      return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M7 3.5h10A3.5 3.5 0 0 1 20.5 7v10a3.5 3.5 0 0 1-3.5 3.5H7A3.5 3.5 0 0 1 3.5 17V7A3.5 3.5 0 0 1 7 3.5zm0 1.8A1.7 1.7 0 0 0 5.3 7v10c0 .94.76 1.7 1.7 1.7h10c.94 0 1.7-.76 1.7-1.7V7c0-.94-.76-1.7-1.7-1.7H7z"/><path fill="currentColor" d="M11.1 7.6h1.8v8.8h-1.8z"/><path fill="currentColor" d="M7.6 11.1h8.8v1.8H7.6z"/></svg>';
    }
    if (name === 'menu') {
      return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><circle fill="currentColor" cx="12" cy="5.2" r="2.1"/><circle fill="currentColor" cx="12" cy="12" r="2.1"/><circle fill="currentColor" cx="12" cy="18.8" r="2.1"/></svg>';
    }
    if (name === 'download') {
      return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M11.1 3.5h1.8v9.2l3.2-3.2 1.3 1.3-5.5 5.5-5.5-5.5 1.3-1.3 3.4 3.2V3.5z"/><path fill="currentColor" d="M4.5 18.2h15v1.8h-15z"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M9.1 16.7L4.6 12.2l1.5-1.5 3 3 8.7-8.7 1.5 1.5-10.2 10.2z"/></svg>';
  }

  function fillVisualSteps(items) {
    var list = banner ? banner.querySelector('[data-pngm-pwa-steps]') : null;
    if (!list) return;
    list.innerHTML = '';
    var i;
    for (i = 0; i < items.length; i += 1) {
      var item = items[i];
      if (!item || !item.text) continue;
      var li = document.createElement('li');
      li.className = 'pngm-pwa-step pngm-pwa-step--' + (item.icon || 'check');
      li.innerHTML = '<span class="pngm-pwa-step-num">' + (i + 1) + '</span>'
        + '<span class="pngm-pwa-step-ico">' + stepIcon(item.icon) + '</span>'
        + '<span class="pngm-pwa-step-text"></span>';
      li.querySelector('.pngm-pwa-step-text').textContent = item.text;
      list.appendChild(li);
    }
    list.hidden = list.children.length === 0;
  }

  function fillBannerCopy() {
    if (!banner) return;
    var title = banner.querySelector('[data-pngm-pwa-title]');
    var body = banner.querySelector('[data-pngm-pwa-body]');
    var primary = banner.querySelector('[data-pngm-pwa-primary]');
    if (body) body.hidden = true;
    if (isIos()) {
      if (title) title.textContent = L.iosTitle || L.title;
      fillVisualSteps([
        { icon: 'share', text: L.iosStep1 },
        { icon: 'plus', text: L.iosStep2 },
        { icon: 'check', text: L.iosStep3 }
      ]);
      if (primary) primary.textContent = L.gotIt;
      banner.setAttribute('data-mode', 'ios');
      return;
    }
    if (title) title.textContent = L.androidTitle || L.title;
    fillVisualSteps([
      { icon: 'menu', text: L.androidStep1 },
      { icon: 'download', text: L.androidStep2 },
      { icon: 'check', text: L.androidStep3 }
    ]);
    if (deferredPrompt) {
      if (primary) primary.textContent = L.install;
      banner.setAttribute('data-mode', 'android');
    } else {
      if (primary) primary.textContent = L.gotIt;
      banner.setAttribute('data-mode', 'guide');
    }
  }

  function alreadyInstalled() {
    var mode = detectDisplayMode();
    return mode === 'standalone' || mode === 'fullscreen';
  }

  function phoneIntroOpen() {
    var intro = document.getElementById('pngm-phone-intro');
    return !!(intro && !intro.hidden);
  }

  function isChatPage() {
    return !!(document.body && document.body.classList.contains('im-chat-page'));
  }

  function keyboardOpen() {
    var el = document.activeElement;
    if (el) {
      var tag = (el.tagName || '').toLowerCase();
      if (tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable) {
        var type = (el.getAttribute('type') || '').toLowerCase();
        if (type !== 'button' && type !== 'submit' && type !== 'checkbox' && type !== 'radio' && type !== 'file' && type !== 'hidden') {
          return true;
        }
      }
    }
    try {
      if (window.visualViewport && (window.innerHeight - window.visualViewport.height) > 140) {
        return true;
      }
    } catch (e) {}
    return false;
  }

  function canAutoShow() {
    if (!banner || alreadyInstalled() || seenAlready() || !isPhone()) {
      return false;
    }
    if (isChatPage()) {
      return false;
    }
    if (phoneIntroOpen()) {
      return false;
    }
    if (keyboardOpen()) {
      return false;
    }
    return true;
  }

  function revealBanner(force) {
    keepSingleBanner();
    if (!banner || alreadyInstalled()) return;
    if (!force && (wasDismissed() || seenAlready())) return;
    if (banner.getAttribute('data-shown') === '1' && !banner.hidden) return;
    if (banner.parentNode !== document.body) {
      document.body.appendChild(banner);
    }
    fillBannerCopy();
    banner.hidden = false;
    banner.removeAttribute('hidden');
    banner.setAttribute('data-shown', '1');
    autoShown = true;
    pendingShow = false;
    pausedForKeyboard = false;
    markSeen();
  }

  function showBanner(mode, force) {
    if (!banner) return;
    if (!isPhone()) return;
    if (!force && !canAutoShow()) return;
    if (alreadyInstalled()) return;
    if (!force && keyboardOpen()) {
      pendingShow = true;
      return;
    }
    if (!force && banner.getAttribute('data-shown') === '1' && !banner.hidden) {
      return;
    }
    revealBanner(!!force);
  }

  function bindBanner() {
    if (!banner) return;
    var primary = banner.querySelector('[data-pngm-pwa-primary]');
    var dismissBtn = banner.querySelector('[data-pngm-pwa-dismiss]');
    var laterBtn = banner.querySelector('[data-pngm-pwa-later]');
    if (dismissBtn) {
      dismissBtn.addEventListener('click', dismiss);
    }
    if (laterBtn) {
      laterBtn.addEventListener('click', dismiss);
    }
    if (primary) {
      primary.addEventListener('click', function () {
        var mode = banner.getAttribute('data-mode') || '';
        if (mode === 'android' && deferredPrompt) {
          deferredPrompt.prompt();
          deferredPrompt.userChoice.then(function () {
            deferredPrompt = null;
            dismiss();
          }).catch(function () {
            dismiss();
          });
          return;
        }
        dismiss();
      });
    }
  }

  // Account screen / any “Add to phone” buttons
  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('[data-pngm-pwa-install-btn]') : null;
    if (!btn) return;
    e.preventDefault();
    if (detectDisplayMode() === 'standalone') {
      window.alert(<?php echo json_encode(__('PNGMarket is already on this phone’s home screen.', 'epsilon')); ?>);
      return;
    }
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function () { deferredPrompt = null; }).catch(function () {});
      return;
    }
    showBanner(detectDisplayMode(), true);
  });

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    document.documentElement.setAttribute('data-pngm-pwa-installable', '1');
    if (banner && !banner.hidden) {
      fillBannerCopy();
    }
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    dismiss();
    detectDisplayMode();
  });

  var mode = detectDisplayMode();
  if (banner && banner.parentNode !== document.body) {
    document.body.appendChild(banner);
  }
  bindBanner();

  function resumeAfterKeyboard() {
    window.setTimeout(function () {
      pausedForKeyboard = false;
      pendingShow = false;
    }, 350);
  }

  document.addEventListener('focusin', function (e) {
    var t = e.target;
    if (!t || !banner || banner.hidden) return;
    if (!keyboardOpen()) return;
    pausedForKeyboard = true;
    pendingShow = true;
    banner.hidden = true;
  });
  document.addEventListener('focusout', resumeAfterKeyboard);
  if (window.visualViewport && typeof window.visualViewport.addEventListener === 'function') {
    window.visualViewport.addEventListener('resize', function () {
      if (!banner) return;
      if (keyboardOpen() && !banner.hidden) {
        pausedForKeyboard = true;
        pendingShow = true;
        banner.hidden = true;
        return;
      }
      if (!keyboardOpen()) resumeAfterKeyboard();
    });
  }

  (function tryAutoShow(delay) {
    window.setTimeout(function () {
      if (autoShown || shownThisLoad || alreadyInstalled() || seenAlready() || !isPhone() || isChatPage()) {
        return;
      }
      if (phoneIntroOpen() || keyboardOpen()) {
        tryAutoShow(2000);
        return;
      }
      showBanner(detectDisplayMode());
    }, delay);
  })(SHOW_AFTER_MS);

  try {
    var mq = window.matchMedia('(display-mode: standalone)');
    if (mq && typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', detectDisplayMode);
    } else if (mq && typeof mq.addListener === 'function') {
      mq.addListener(detectDisplayMode);
    }
  } catch (e) {}

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(swUrl, { scope: '/' }).catch(function () {});
    navigator.serviceWorker.addEventListener('message', function (event) {
      var data = event && event.data ? event.data : null;
      if (!data) {
        return;
      }
      if (data.type === 'pngm-push-sound') {
        try {
          if (typeof window.pngmPlayNotifyChime === 'function') {
            window.pngmPlayNotifyChime();
          }
        } catch (e) {}
        return;
      }
      if (data.type !== 'pngm-navigate' || !data.url) {
        return;
      }
      try {
        window.location.href = String(data.url);
      } catch (e) {}
    });
  }
})();
</script>
    <?php
    if (function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in()) {
        pngm_pwa_phone_intro_markup($sw, $brand);
    }
}

/**
 * One-time phone modal after login for push notifications (Android / standalone).
 * iPhone Safari never shows this — Home Screen install is covered by the main banner.
 *
 * @param string $sw
 * @param string $brand
 */
function pngm_pwa_phone_intro_markup($sw, $brand)
{
    $vapid = function_exists('pngm_webpush_public_key') ? pngm_webpush_public_key() : '';
    $subscribe = osc_base_url(true) . '?page=ajax&action=runhook&hook=pngm_push_subscribe';
    $copy = array(
        'iosTitle' => sprintf(__('Add %s to your iPhone', 'epsilon'), $brand),
        'ipadTitle' => sprintf(__('Add %s to your iPad', 'epsilon'), $brand),
        'iosBody' => __('Add to Home Screen to receive notifications and get a better experience.', 'epsilon'),
        'showHow' => __('Show me how', 'epsilon'),
        'gotIt' => __('Got it', 'epsilon'),
        'iosStep1' => __('Tap Share in Safari', 'epsilon'),
        'iosStep2' => __('Tap Add to Home Screen', 'epsilon'),
        'iosStep3' => __('Tap Add', 'epsilon'),
        'pushTitle' => __('Enable push notifications', 'epsilon'),
        'pushBody' => __('Get notified when someone messages you or when your listing status changes.', 'epsilon'),
        'enable' => __('Enable notifications', 'epsilon'),
        'later' => __('Not now', 'epsilon'),
        'enabling' => __('Enabling…', 'epsilon'),
        'enabled' => __('Notifications are on', 'epsilon'),
        'denied' => __('Notifications are blocked in your browser settings.', 'epsilon'),
        'missed' => __('Allow notifications from the lock icon in the address bar, then try again from Notification Preferences.', 'epsilon'),
        'failed' => __('Could not enable notifications on this phone. You can turn them on later in Notification Preferences.', 'epsilon'),
    );
    ?>
<div id="pngm-phone-intro" class="pngm-phone-intro" hidden>
  <div class="pngm-phone-intro-card" role="dialog" aria-modal="true" aria-labelledby="pngm-phone-intro-title">
    <div class="pngm-phone-intro-ico" data-ico="phone" hidden>
      <svg viewBox="0 0 48 48" width="34" height="34" aria-hidden="true"><rect x="14" y="6" width="20" height="36" rx="4" fill="none" stroke="#148a38" stroke-width="2.4"/><circle cx="24" cy="36" r="1.6" fill="#148a38"/></svg>
    </div>
    <div class="pngm-phone-intro-ico" data-ico="bell" hidden>
      <svg viewBox="0 0 48 48" width="34" height="34" aria-hidden="true"><path fill="#148a38" d="M24 8a10 10 0 0 0-10 10v6.2c0 2.2-.7 4.4-2 6.2L10 33.2A2 2 0 0 0 11.6 36.5h24.8a2 2 0 0 0 1.6-3.3l-2-2.8a9.6 9.6 0 0 1-2-6.2V18A10 10 0 0 0 24 8zm0 32a4 4 0 0 0 3.8-2.7h-7.6A4 4 0 0 0 24 40z"/></svg>
    </div>
    <h2 id="pngm-phone-intro-title"></h2>
    <p data-pngm-intro-body></p>
    <ol class="pngm-pwa-install-steps pngm-phone-intro-steps" data-pngm-intro-steps hidden></ol>
    <button type="button" class="pngm-phone-intro-go" data-pngm-intro-go>
      <svg class="pngm-phone-intro-btn-ico" data-btn-bell hidden viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7v4.2c0 1.4-.5 2.8-1.3 3.9L2.8 18.4A1 1 0 0 0 3.6 20h16.8a1 1 0 0 0 .8-1.6l-.9-1.3c-.8-1.1-1.3-2.5-1.3-3.9V9a7 7 0 0 0-7-7zm0 20a3 3 0 0 0 2.8-2H9.2A3 3 0 0 0 12 22z"/></svg>
      <span data-pngm-intro-go-label></span>
    </button>
    <button type="button" class="pngm-phone-intro-later" data-pngm-intro-later><?php echo osc_esc_html($copy['later']); ?></button>
  </div>
</div>
<script>
(function () {
  var root = document.getElementById('pngm-phone-intro');
  if (!root) return;
  var storageKey = 'pngm_phone_notice_once_v1';
  var copy = <?php echo json_encode($copy); ?>;
  var swUrl = <?php echo json_encode($sw); ?>;
  var vapid = <?php echo json_encode($vapid); ?>;
  var subscribeUrl = <?php echo json_encode($subscribe); ?>;
  var titleEl = document.getElementById('pngm-phone-intro-title');
  var bodyEl = root.querySelector('[data-pngm-intro-body]');
  var stepsEl = root.querySelector('[data-pngm-intro-steps]');
  var goBtn = root.querySelector('[data-pngm-intro-go]');
  var goLabel = root.querySelector('[data-pngm-intro-go-label]');
  var laterBtn = root.querySelector('[data-pngm-intro-later]');
  var btnBell = root.querySelector('[data-btn-bell]');
  var mode = 'push';
  var stepsOpen = false;
  var busy = false;

  function seen() {
    try { return window.localStorage.getItem(storageKey) === '1'; } catch (e) { return true; }
  }
  function mark() {
    try { window.localStorage.setItem(storageKey, '1'); } catch (e) {}
  }
  function isIpad() {
    var ua = navigator.userAgent || '';
    return /iPad/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1 && !/iPhone/.test(ua));
  }
  function deviceKind() {
    var ua = navigator.userAgent || '';
    var ios = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (ios) return 'ios';
    if (/Android/i.test(ua)) return 'android';
    return 'desktop';
  }
  function isStandalone() {
    try {
      if (window.matchMedia('(display-mode: standalone)').matches) return true;
      if (window.matchMedia('(display-mode: fullscreen)').matches) return true;
      if (navigator.standalone === true) return true;
    } catch (e) {}
    return false;
  }
  function showIco(name) {
    var nodes = root.querySelectorAll('[data-ico]');
    Array.prototype.forEach.call(nodes, function (el) {
      el.hidden = el.getAttribute('data-ico') !== name;
    });
  }
  function stepIcon(name) {
    if (name === 'share') {
      return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M16.4 9.4h2.3c1.2 0 2.3 1 2.3 2.3v8c0 1.3-1.1 2.3-2.3 2.3H5.3A2.3 2.3 0 0 1 3 19.7v-8c0-1.3 1-2.3 2.3-2.3h2.3v1.8H5.3c-.3 0-.5.2-.5.5v8c0 .3.2.5.5.5h13.4c.3 0 .5-.2.5-.5v-8c0-.3-.2-.5-.5-.5h-2.3V9.4z"/><path fill="currentColor" d="M12 2.1l4.5 4.5-1.3 1.3-2.3-2.2v9.4h-1.8V5.7L8.8 7.9 7.5 6.6 12 2.1z"/></svg>';
    }
    if (name === 'plus') {
      return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M7 3.5h10A3.5 3.5 0 0 1 20.5 7v10a3.5 3.5 0 0 1-3.5 3.5H7A3.5 3.5 0 0 1 3.5 17V7A3.5 3.5 0 0 1 7 3.5zm0 1.8A1.7 1.7 0 0 0 5.3 7v10c0 .94.76 1.7 1.7 1.7h10c.94 0 1.7-.76 1.7-1.7V7c0-.94-.76-1.7-1.7-1.7H7z"/><path fill="currentColor" d="M11.1 7.6h1.8v8.8h-1.8z"/><path fill="currentColor" d="M7.6 11.1h8.8v1.8H7.6z"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M9.1 16.7L4.6 12.2l1.5-1.5 3 3 8.7-8.7 1.5 1.5-10.2 10.2z"/></svg>';
  }
  function fillSteps() {
    if (!stepsEl) return;
    var items = [
      { icon: 'share', text: copy.iosStep1 },
      { icon: 'plus', text: copy.iosStep2 },
      { icon: 'check', text: copy.iosStep3 }
    ];
    stepsEl.innerHTML = '';
    var i;
    for (i = 0; i < items.length; i += 1) {
      if (!items[i].text) continue;
      var li = document.createElement('li');
      li.className = 'pngm-pwa-step pngm-pwa-step--' + items[i].icon;
      li.innerHTML = '<span class="pngm-pwa-step-num">' + (i + 1) + '</span>'
        + '<span class="pngm-pwa-step-ico">' + stepIcon(items[i].icon) + '</span>'
        + '<span class="pngm-pwa-step-text"></span>';
      li.querySelector('.pngm-pwa-step-text').textContent = items[i].text;
      stepsEl.appendChild(li);
    }
  }
  function closeIntro() {
    root.hidden = true;
    document.body.classList.remove('pngm-phone-intro-open');
  }
  function openIntro() {
    if (root.parentNode !== document.body) {
      document.body.appendChild(root);
    }
    root.hidden = false;
    root.removeAttribute('hidden');
    document.body.classList.add('pngm-phone-intro-open');
    mark();
    if (goBtn && goBtn.focus) {
      try { goBtn.focus(); } catch (e) {}
    }
  }
  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  function enablePush() {
    if (busy) return;
    if (typeof Notification === 'undefined' || !('serviceWorker' in navigator) || !('PushManager' in window)) {
      if (bodyEl) bodyEl.textContent = copy.failed;
      return;
    }
    var permPromise = Notification.permission === 'granted'
      ? Promise.resolve('granted')
      : Notification.requestPermission();
    busy = true;
    if (goBtn) goBtn.disabled = true;
    if (goLabel) goLabel.textContent = copy.enabling;
    permPromise.then(function (perm) {
      if (perm === 'denied') {
        if (bodyEl) bodyEl.textContent = copy.denied;
        return null;
      }
      if (perm !== 'granted') {
        if (bodyEl) bodyEl.textContent = copy.missed;
        return null;
      }
      if (!vapid || !subscribeUrl) throw new Error('no_vapid');
      return navigator.serviceWorker.register(swUrl, { scope: '/' }).then(function () {
        return navigator.serviceWorker.ready;
      }).then(function (reg) {
        return reg.pushManager.getSubscription().then(function (existing) {
          if (existing) return existing;
          return reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapid)
          });
        });
      }).then(function (sub) {
        var json = sub && sub.toJSON ? sub.toJSON() : null;
        if (!json || !json.endpoint) throw new Error('bad_sub');
        return fetch(subscribeUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: 'subscription=' + encodeURIComponent(JSON.stringify(json))
        }).then(function (r) { return r.json().catch(function () { return { ok: false }; }); });
      }).then(function (res) {
        if (!res || !res.ok) throw new Error('save_failed');
        if (goLabel) goLabel.textContent = copy.enabled;
        window.setTimeout(closeIntro, 700);
      });
    }).catch(function () {
      if (bodyEl) bodyEl.textContent = copy.failed;
    }).then(function () {
      busy = false;
      if (goBtn && (typeof Notification === 'undefined' || Notification.permission !== 'granted')) {
        goBtn.disabled = false;
        if (goLabel) goLabel.textContent = copy.enable;
      }
    });
  }

  var kind = deviceKind();
  if (kind === 'desktop' || seen()) return;
  // iPhone / iPad: the main “Add to Home Screen” banner already covers install steps.
  // Never show this second modal with the same content again.
  if (kind === 'ios' && !isStandalone()) {
    mark();
    return;
  }
  mode = 'push';
  if (typeof Notification === 'undefined' || Notification.permission !== 'default') {
    mark();
    return;
  }

  showIco('bell');
  if (titleEl) titleEl.textContent = copy.pushTitle;
  if (bodyEl) bodyEl.textContent = copy.pushBody;
  if (goLabel) goLabel.textContent = copy.enable;
  if (btnBell) btnBell.hidden = false;

  if (goBtn) {
    goBtn.addEventListener('click', function () {
      enablePush();
    });
  }
  if (laterBtn) laterBtn.addEventListener('click', closeIntro);
  root.addEventListener('click', function (e) {
    if (e.target === root) closeIntro();
  });
  document.addEventListener('keydown', function (e) {
    if (!root.hidden && e.key === 'Escape') closeIntro();
  });

  window.setTimeout(openIntro, 600);
})();
</script>
    <?php
}

/**
 * Lightweight CSS for the mobile install banner.
 */
function pngm_pwa_install_css()
{
    if (defined('OC_ADMIN') && OC_ADMIN) {
        return;
    }
    ?>
<style id="pngm-pwa-install-css">
.pngm-pwa-install[hidden]{display:none!important}
.pngm-pwa-install ~ .pngm-pwa-install{display:none!important}
.pngm-pwa-install{
  position:fixed;left:12px;right:12px;
  bottom:calc(72px + env(safe-area-inset-bottom,0px));
  z-index:10120;pointer-events:none;
  animation:pngmPwaBannerIn .35s ease
}
@keyframes pngmPwaBannerIn{
  from{opacity:0}
  to{opacity:1}
}
.pngm-pwa-install-inner{
  pointer-events:auto;position:relative;display:flex;flex-direction:column;gap:12px;
  padding:16px 14px 12px;border-radius:18px;background:#fff;border:1px solid #e6eaf0;
  box-shadow:0 12px 32px rgba(22,32,42,.18)
}
.pngm-pwa-install-dismiss{
  appearance:none;position:absolute;top:6px;right:6px;width:36px;height:36px;
  border:0;background:transparent;color:#8a94a0;font-size:26px;line-height:1;cursor:pointer
}
.pngm-pwa-install-top{display:flex;align-items:center;gap:12px;padding-right:28px}
.pngm-pwa-install-icon{
  width:52px;height:52px;border-radius:14px;flex:0 0 52px;object-fit:contain;
  background:#fff;border:1px solid #eef1f5;box-shadow:0 1px 2px rgba(22,32,42,.06)
}
.pngm-pwa-install-copy{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:4px}
.pngm-pwa-install-copy strong{font-size:16px;color:#16202a;line-height:1.25;font-weight:800}
.pngm-pwa-install-copy em{font-style:normal;font-size:13px;color:#5a6570;line-height:1.4}
.pngm-pwa-install-copy em[hidden]{display:none!important}
.pngm-pwa-install-steps{
  list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px
}
.pngm-pwa-install-steps[hidden]{display:none!important}
.pngm-pwa-step{
  display:flex;align-items:center;gap:10px;margin:0;padding:10px 12px;
  background:#f3f7f4;border-radius:12px;color:#16202a
}
.pngm-pwa-step-num{
  flex:0 0 22px;width:22px;height:22px;border-radius:50%;background:#006b24;color:#fff;
  font-size:12px;font-weight:800;display:inline-flex;align-items:center;justify-content:center
}
.pngm-pwa-step-ico{
  flex:0 0 36px;width:36px;height:36px;border-radius:10px;background:#fff;color:#006b24;
  border:1px solid #d7e8dc;display:inline-flex;align-items:center;justify-content:center
}
.pngm-pwa-step--share .pngm-pwa-step-ico{color:#007aff;border-color:#cfe0ff}
.pngm-pwa-step--menu .pngm-pwa-step-ico{color:#3c4043;border-color:#dadce0}
.pngm-pwa-step-text{flex:1 1 auto;min-width:0;font-size:14px;font-weight:700;line-height:1.3}
.pngm-pwa-install-actions{display:flex;flex-direction:column;align-items:stretch;gap:2px;width:100%}
.pngm-pwa-install-btn{
  appearance:none;width:100%;border:0;border-radius:12px;background:#006b24;color:#fff;
  font-weight:700;font-size:15px;min-height:44px;padding:10px 12px;cursor:pointer
}
.pngm-pwa-install-later{
  appearance:none;width:100%;border:0;background:transparent;color:#5a6570;
  font-size:14px;font-weight:600;padding:8px;cursor:pointer
}
html.pngm-pwa-standalone .pngm-pwa-install{display:none!important}
.pngm-phone-intro[hidden]{display:none!important}
.pngm-phone-intro{
  position:fixed;inset:0;z-index:10240;display:flex;align-items:center;justify-content:center;
  padding:24px 18px;background:rgba(16,24,32,.48);box-sizing:border-box
}
.pngm-phone-intro-card{
  width:100%;max-width:360px;background:#fff;border-radius:22px;
  padding:28px 22px 18px;text-align:center;
  box-shadow:0 18px 50px rgba(16,24,32,.22);box-sizing:border-box
}
.pngm-phone-intro-ico{
  width:72px;height:72px;margin:0 auto;border-radius:18px;background:#e7f8ec;
  display:flex;align-items:center;justify-content:center
}
.pngm-phone-intro-ico[hidden]{display:none!important}
.pngm-phone-intro-card h2{
  margin:16px 0 8px;font-size:22px;line-height:1.25;font-weight:800;color:#1c1f24
}
.pngm-phone-intro-card p{
  margin:0 0 22px;color:#8b939c;font-size:15px;line-height:1.45
}
.pngm-phone-intro-card p[hidden]{display:none!important}
.pngm-phone-intro-steps{
  margin:0 0 18px;padding:0;text-align:left
}
.pngm-phone-intro-steps[hidden]{display:none!important}
.pngm-phone-intro-go{
  appearance:none;width:100%;min-height:50px;border:0;border-radius:12px;
  background:#129438;color:#fff;font-size:16px;font-weight:700;cursor:pointer;
  display:inline-flex;align-items:center;justify-content:center;gap:8px
}
.pngm-phone-intro-go:disabled{opacity:.7;cursor:wait}
.pngm-phone-intro-btn-ico[hidden]{display:none!important}
.pngm-phone-intro-later{
  appearance:none;display:block;width:100%;margin-top:12px;border:0;background:transparent;
  color:#9aa1aa;font-size:15px;font-weight:600;padding:8px;cursor:pointer
}
.pngm-phone-intro-later[hidden]{display:none!important}
body.pngm-phone-intro-open{overflow:hidden}

</style>
    <?php
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init', 'pngm_pwa_ensure_manifest', 3);
    osc_add_hook('header', 'pngm_pwa_head', 8);
    osc_add_hook('header', 'pngm_pwa_install_css', 9);
    osc_add_hook('footer_after', 'pngm_pwa_register_sw_footer', 9);
}
