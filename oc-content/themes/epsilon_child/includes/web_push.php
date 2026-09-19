<?php
/**
 * PNG Market — Browser notification delivery via Service Worker.
 *
 * True offline Web Push (VAPID/FCM) needs libsodium; this stack ships without it.
 * We register a root service worker and show queued notifications with
 * registration.showNotification() when the user next loads the site — works in
 * background tabs better than `new Notification()`, and keeps prefs/UI ready
 * for a later VAPID upgrade.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'web_push.php'
) {
    exit;
}

/**
 * Public URL of the service worker (must be at site root for full scope).
 *
 * @return string
 */
function pngm_webpush_sw_url()
{
    return osc_base_url() . 'sw.js';
}

/**
 * Copy theme sw.js to site root so scope can be "/".
 */
function pngm_webpush_ensure_sw_file()
{
    if (!defined('ABS_PATH')) {
        return;
    }
    $root = ABS_PATH . 'sw.js';
    $theme_sw = dirname(__FILE__) . '/../sw.js';
    if (!is_readable($theme_sw)) {
        return;
    }
    $need = !is_readable($root) || (@filemtime($theme_sw) > @filemtime($root));
    if ($need) {
        @copy($theme_sw, $root);
    }
}

/**
 * Register SW + deliver queued notifications (logged-in users).
 */
function pngm_webpush_footer()
{
    if (!function_exists('osc_is_web_user_logged_in') || !osc_is_web_user_logged_in()) {
        return;
    }

    pngm_webpush_ensure_sw_file();

    $queue = array();
    if (function_exists('pngm_notif_push_queue_take')) {
        $queue = pngm_notif_push_queue_take((int) osc_logged_user_id());
    }
    $payload = array();
    foreach ($queue as $row) {
        if (!is_array($row)) {
            continue;
        }
        $payload[] = array(
            'title' => isset($row['title']) ? (string) $row['title'] : '',
            'body' => isset($row['body']) ? (string) $row['body'] : '',
            'url' => isset($row['url']) ? (string) $row['url'] : '',
        );
    }

    $sw_url = pngm_webpush_sw_url();
    $icon = osc_base_url();
    ?>
<script>
(function () {
  var swUrl = <?php echo json_encode($sw_url); ?>;
  var items = <?php echo json_encode($payload); ?>;
  var icon = <?php echo json_encode($icon); ?>;

  function showViaSW(reg, n) {
    if (!reg || typeof reg.showNotification !== 'function') {
      return false;
    }
    try {
      reg.showNotification(n.title || 'PNG Market', {
        body: n.body || '',
        icon: icon,
        data: { url: n.url || '' },
        tag: 'pngm-' + String(n.title || '').slice(0, 40) + '-' + String(Date.now())
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  function showFallback(n) {
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') {
      return;
    }
    try {
      var note = new Notification(n.title || 'PNG Market', { body: n.body || '', icon: icon });
      if (n.url) {
        note.onclick = function () {
          window.focus();
          window.location.href = n.url;
        };
      }
    } catch (e) {}
  }

  function deliver(reg) {
    if (!items || !items.length) {
      return;
    }
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') {
      return;
    }
    items.forEach(function (n, i) {
      window.setTimeout(function () {
        if (!showViaSW(reg, n)) {
          showFallback(n);
        }
      }, i * 400);
    });
  }

  if (!('serviceWorker' in navigator)) {
    deliver(null);
    return;
  }

  navigator.serviceWorker.register(swUrl, { scope: '/' }).then(function (reg) {
    deliver(reg);
  }).catch(function () {
    deliver(null);
  });
})();
</script>
    <?php
}

/**
 * Prefs page helper: expose SW URL for the enable-push button.
 *
 * @return void
 */
function pngm_webpush_prefs_boot_json()
{
    pngm_webpush_ensure_sw_file();
    echo json_encode(array(
        'swUrl' => pngm_webpush_sw_url(),
        'unsupported' => __('Push notifications are not supported in this browser.', 'epsilon'),
        'blocked' => __('Notifications are blocked in your browser settings.', 'epsilon'),
        'enabled' => __('Enabled', 'epsilon'),
        'ask' => __('Ask permission', 'epsilon'),
    ));
}

// Replace on-page Notification() footer with Service Worker delivery.
osc_add_hook('init', function () {
    if (function_exists('osc_remove_hook')) {
        osc_remove_hook('footer', 'pngm_notif_push_footer');
    }
}, 8);
osc_add_hook('footer', 'pngm_webpush_footer', 10);
