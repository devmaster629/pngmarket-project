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

    // Path-absolute URLs so local/stage/prod share one manifest shape (no host baked in).
    $start = '/?utm_source=pwa';
    $scope = '/';
    $icon_192 = '/pwa/icon-192.png';
    $icon_512 = '/pwa/icon-512.png';

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
                'src' => $icon_512,
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
 * Register service worker + mobile install UX (Android prompt / iOS A2HS guide).
 * Responsive UI alone is not a PWA — this exposes the real install path.
 */
function pngm_pwa_register_sw_footer()
{
    if (defined('OC_ADMIN') && OC_ADMIN) {
        return;
    }

    if (function_exists('pngm_webpush_ensure_sw_file')) {
        pngm_webpush_ensure_sw_file();
    }

    $sw = function_exists('pngm_webpush_sw_url')
        ? pngm_webpush_sw_url()
        : (rtrim(osc_base_url(), '/') . '/sw.js');
    $icon = pngm_pwa_root_url() . 'icon-192.png';
    $name = function_exists('osc_page_title') ? trim((string) osc_page_title()) : 'PNGMarket';
    if ($name === '') {
        $name = 'PNGMarket';
    }
    $L = array(
        'title' => sprintf(__('Install %s', 'epsilon'), $name),
        'body' => __('Add to your home screen for a faster, full-screen app experience.', 'epsilon'),
        'install' => __('Install app', 'epsilon'),
        'iosTitle' => __('Add to Home Screen', 'epsilon'),
        'iosBody' => __('On iPhone/iPad: tap Share, then “Add to Home Screen”.', 'epsilon'),
        'gotIt' => __('Got it', 'epsilon'),
        'dismiss' => __('Not now', 'epsilon'),
    );
    ?>
<div id="pngm-pwa-install" class="pngm-pwa-install" hidden data-pngm-pwa-install>
  <div class="pngm-pwa-install-inner">
    <img class="pngm-pwa-install-icon" src="<?php echo osc_esc_html($icon); ?>" width="48" height="48" alt="" />
    <div class="pngm-pwa-install-copy">
      <strong data-pngm-pwa-title><?php echo osc_esc_html($L['title']); ?></strong>
      <em data-pngm-pwa-body><?php echo osc_esc_html($L['body']); ?></em>
    </div>
    <div class="pngm-pwa-install-actions">
      <button type="button" class="pngm-pwa-install-btn" data-pngm-pwa-primary><?php echo osc_esc_html($L['install']); ?></button>
      <button type="button" class="pngm-pwa-install-dismiss" data-pngm-pwa-dismiss aria-label="<?php echo osc_esc_html($L['dismiss']); ?>">&times;</button>
    </div>
  </div>
</div>
<script>
(function () {
  var swUrl = <?php echo json_encode($sw); ?>;
  var L = <?php echo json_encode($L); ?>;
  var deferredPrompt = null;
  var banner = document.querySelector('[data-pngm-pwa-install]');
  var storageKey = 'pngm_pwa_install_dismissed_v1';

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

  function isMobileish() {
    try {
      return window.matchMedia('(max-width: 900px)').matches || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent || '');
    } catch (e) {
      return true;
    }
  }

  function wasDismissed() {
    try {
      return window.localStorage.getItem(storageKey) === '1';
    } catch (e) {
      return false;
    }
  }

  function dismiss() {
    if (!banner) return;
    banner.hidden = true;
    try { window.localStorage.setItem(storageKey, '1'); } catch (e) {}
  }

  function showBanner(mode) {
    if (!banner || !isMobileish() || wasDismissed()) return;
    if (mode === 'standalone') return;
    var title = banner.querySelector('[data-pngm-pwa-title]');
    var body = banner.querySelector('[data-pngm-pwa-body]');
    var primary = banner.querySelector('[data-pngm-pwa-primary]');
    if (isIos()) {
      if (title) title.textContent = L.iosTitle;
      if (body) body.textContent = L.iosBody;
      if (primary) primary.textContent = L.gotIt;
      banner.setAttribute('data-mode', 'ios');
    } else if (deferredPrompt) {
      if (title) title.textContent = L.title;
      if (body) body.textContent = L.body;
      if (primary) primary.textContent = L.install;
      banner.setAttribute('data-mode', 'android');
    } else {
      // Android/desktop Chrome may delay beforeinstallprompt — still show guidance.
      if (title) title.textContent = L.title;
      if (body) body.textContent = L.body;
      if (primary) {
        primary.textContent = L.gotIt;
      }
      banner.setAttribute('data-mode', 'guide');
    }
    banner.hidden = false;
  }

  function bindBanner() {
    if (!banner) return;
    var primary = banner.querySelector('[data-pngm-pwa-primary]');
    var dismissBtn = banner.querySelector('[data-pngm-pwa-dismiss]');
    if (dismissBtn) {
      dismissBtn.addEventListener('click', dismiss);
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

  // Account & Security / any page install buttons
  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('[data-pngm-pwa-install-btn]') : null;
    if (!btn) return;
    e.preventDefault();
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function () { deferredPrompt = null; }).catch(function () {});
      return;
    }
    if (isIos()) {
      window.alert(L.iosBody);
      return;
    }
    showBanner(detectDisplayMode());
  });

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    document.documentElement.setAttribute('data-pngm-pwa-installable', '1');
    showBanner(detectDisplayMode());
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    dismiss();
    detectDisplayMode();
  });

  var mode = detectDisplayMode();
  bindBanner();
  // iOS never fires beforeinstallprompt — show A2HS guide after a short delay.
  if (isIos() && mode !== 'standalone') {
    window.setTimeout(function () { showBanner(mode); }, 1800);
  } else if (!isIos() && mode !== 'standalone') {
    window.setTimeout(function () {
      if (!deferredPrompt) showBanner(detectDisplayMode());
    }, 4000);
  }

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
  }
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
.pngm-pwa-install{
  position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom,0px));
  z-index:10050;pointer-events:none
}
.pngm-pwa-install-inner{
  pointer-events:auto;display:flex;align-items:center;gap:12px;
  padding:12px;border-radius:14px;background:#fff;border:1px solid #e6eaf0;
  box-shadow:0 10px 28px rgba(22,32,42,.16)
}
.pngm-pwa-install-icon{width:48px;height:48px;border-radius:12px;flex:0 0 48px;object-fit:cover}
.pngm-pwa-install-copy{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:2px}
.pngm-pwa-install-copy strong{font-size:14px;color:#16202a;line-height:1.3}
.pngm-pwa-install-copy em{font-style:normal;font-size:12px;color:#5a6570;line-height:1.35}
.pngm-pwa-install-actions{display:flex;align-items:center;gap:6px;flex:0 0 auto}
.pngm-pwa-install-btn{
  appearance:none;border:0;border-radius:10px;background:#059669;color:#fff;
  font-weight:700;font-size:13px;padding:10px 12px;cursor:pointer;white-space:nowrap
}
.pngm-pwa-install-dismiss{
  appearance:none;border:0;background:transparent;color:#8a94a0;
  font-size:22px;line-height:1;padding:4px 6px;cursor:pointer
}
html.pngm-pwa-standalone .pngm-pwa-install{display:none!important}
@media (min-width:901px){
  .pngm-pwa-install{left:auto;right:20px;bottom:20px;width:380px;max-width:calc(100vw - 40px)}
}
</style>
    <?php
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init', 'pngm_pwa_ensure_manifest', 3);
    osc_add_hook('header', 'pngm_pwa_head', 8);
    osc_add_hook('header', 'pngm_pwa_install_css', 9);
    osc_add_hook('footer', 'pngm_pwa_register_sw_footer', 9);
}
