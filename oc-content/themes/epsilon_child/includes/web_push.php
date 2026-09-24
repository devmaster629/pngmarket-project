<?php
/**
 * PNG Market — Browser push via Service Worker + VAPID Web Push.
 *
 * - Consent: Notification permission + prefs page “Enable”
 * - Delivery: real Web Push (works when the site is closed) when a subscription
 *   is stored; on-page queue remains a fallback if push fails / no sub.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'web_push.php'
) {
    exit;
}

require_once dirname(__FILE__) . '/web_push_crypto.php';

/**
 * Preference section for VAPID + per-user subscriptions.
 */
define('PNGM_WEBPUSH_PREF_SECTION', 'pngm_webpush');

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
 * mailto:/URL subject used in VAPID JWT.
 *
 * @return string
 */
function pngm_webpush_vapid_subject()
{
    $email = '';
    if (function_exists('osc_contact_email')) {
        $email = trim((string) osc_contact_email());
    }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'mailto:' . $email;
    }
    return rtrim((string) osc_base_url(), '/');
}

/**
 * Ensure VAPID keys exist; return publicKey (b64url), privatePem, privateKey (b64url).
 *
 * @return array{publicKey:string,privateKey:string,privatePem:string}|false
 */
function pngm_webpush_vapid_keys()
{
    try {
        $pub = (string) osc_get_preference('vapid_public', PNGM_WEBPUSH_PREF_SECTION);
        $pem = (string) osc_get_preference('vapid_private_pem', PNGM_WEBPUSH_PREF_SECTION);
        $priv = (string) osc_get_preference('vapid_private', PNGM_WEBPUSH_PREF_SECTION);

        if ($pub !== '' && $pem !== '') {
            return array(
                'publicKey' => $pub,
                'privateKey' => $priv,
                'privatePem' => $pem,
            );
        }

        if (!function_exists('pngm_push_generate_vapid_keys')) {
            return false;
        }

        $keys = pngm_push_generate_vapid_keys();
        if ($keys === false || !is_array($keys)) {
            return false;
        }

        osc_set_preference('vapid_public', $keys['publicKey'], PNGM_WEBPUSH_PREF_SECTION, 'STRING');
        osc_set_preference('vapid_private', $keys['privateKey'], PNGM_WEBPUSH_PREF_SECTION, 'STRING');
        osc_set_preference('vapid_private_pem', $keys['privatePem'], PNGM_WEBPUSH_PREF_SECTION, 'STRING');
        if (class_exists('Preference')) {
            Preference::newInstance()->toArray();
        }

        return $keys;
    } catch (Throwable $e) {
        if (defined('OSC_DEBUG') && OSC_DEBUG && function_exists('error_log')) {
            error_log('pngm_webpush_vapid_keys: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * @return string Base64URL VAPID public key, or empty string
 */
function pngm_webpush_public_key()
{
    $keys = pngm_webpush_vapid_keys();
    return ($keys !== false) ? (string) $keys['publicKey'] : '';
}

/**
 * @param int $user_id
 * @return string
 */
function pngm_webpush_subs_pref_key($user_id)
{
    return 'subs_' . (int) $user_id;
}

/**
 * @param int $user_id
 * @return array<int,array>
 */
function pngm_webpush_get_subscriptions($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return array();
    }
    $raw = osc_get_preference(pngm_webpush_subs_pref_key($user_id), PNGM_WEBPUSH_PREF_SECTION);
    if ($raw === '' || $raw === null || $raw === false) {
        return array();
    }
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? array_values($decoded) : array();
}

/**
 * @param int   $user_id
 * @param array $subs
 */
function pngm_webpush_save_subscriptions($user_id, $subs)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return;
    }
    if (!is_array($subs)) {
        $subs = array();
    }
    // Cap devices per user
    if (count($subs) > 10) {
        $subs = array_slice($subs, -10);
    }
    osc_set_preference(
        pngm_webpush_subs_pref_key($user_id),
        json_encode(array_values($subs)),
        PNGM_WEBPUSH_PREF_SECTION,
        'STRING'
    );
    if (class_exists('Preference')) {
        Preference::newInstance()->toArray();
    }
}

/**
 * Upsert a PushSubscription JSON object for the user.
 *
 * @param int   $user_id
 * @param array $sub  {endpoint, keys:{p256dh,auth}, expirationTime?}
 * @return bool
 */
function pngm_webpush_upsert_subscription($user_id, $sub)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0 || !is_array($sub)) {
        return false;
    }
    $endpoint = isset($sub['endpoint']) ? trim((string) $sub['endpoint']) : '';
    $p256dh = '';
    $auth = '';
    if (isset($sub['keys']) && is_array($sub['keys'])) {
        $p256dh = isset($sub['keys']['p256dh']) ? (string) $sub['keys']['p256dh'] : '';
        $auth = isset($sub['keys']['auth']) ? (string) $sub['keys']['auth'] : '';
    }
    if ($endpoint === '' || $p256dh === '' || $auth === '') {
        return false;
    }
    if (stripos($endpoint, 'https://') !== 0) {
        return false;
    }

    $subs = pngm_webpush_get_subscriptions($user_id);
    $found = false;
    foreach ($subs as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        if (isset($row['endpoint']) && $row['endpoint'] === $endpoint) {
            $subs[$i] = array(
                'endpoint' => $endpoint,
                'p256dh' => $p256dh,
                'auth' => $auth,
                'ts' => time(),
            );
            $found = true;
            break;
        }
    }
    if (!$found) {
        $subs[] = array(
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'ts' => time(),
        );
    }
    pngm_webpush_save_subscriptions($user_id, $subs);
    return true;
}

/**
 * @param int    $user_id
 * @param string $endpoint
 */
function pngm_webpush_remove_subscription($user_id, $endpoint)
{
    $user_id = (int) $user_id;
    $endpoint = trim((string) $endpoint);
    if ($user_id <= 0 || $endpoint === '') {
        return;
    }
    $subs = pngm_webpush_get_subscriptions($user_id);
    $out = array();
    foreach ($subs as $row) {
        if (!is_array($row) || !isset($row['endpoint'])) {
            continue;
        }
        if ($row['endpoint'] === $endpoint) {
            continue;
        }
        $out[] = $row;
    }
    pngm_webpush_save_subscriptions($user_id, $out);
}

/**
 * Send one encrypted Web Push to a stored subscription row.
 *
 * @param array  $row
 * @param string $title
 * @param string $body
 * @param string $url
 * @return array{ok:bool,status:int,gone?:bool,error?:string}
 */
function pngm_webpush_send_to_subscription($row, $title, $body, $url = '')
{
    if (!is_array($row) || empty($row['endpoint']) || empty($row['p256dh']) || empty($row['auth'])) {
        return array('ok' => false, 'status' => 0, 'error' => 'bad_sub');
    }

    $keys = pngm_webpush_vapid_keys();
    if ($keys === false) {
        return array('ok' => false, 'status' => 0, 'error' => 'no_vapid');
    }

    $payload = json_encode(array(
        'title' => (string) $title,
        'body' => (string) $body,
        'url' => (string) $url,
        'icon' => osc_base_url() . 'pwa/icon-192.png',
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return array('ok' => false, 'status' => 0, 'error' => 'json');
    }

    $enc = pngm_push_encrypt_aes128gcm($payload, $row['p256dh'], $row['auth']);
    if ($enc === false) {
        return array('ok' => false, 'status' => 0, 'error' => 'encrypt');
    }

    $endpoint = (string) $row['endpoint'];
    $parts = parse_url($endpoint);
    if (empty($parts['scheme']) || empty($parts['host'])) {
        return array('ok' => false, 'status' => 0, 'error' => 'endpoint');
    }
    $audience = $parts['scheme'] . '://' . $parts['host'];

    $authz = pngm_push_vapid_authorization(
        $audience,
        pngm_webpush_vapid_subject(),
        $keys['publicKey'],
        $keys['privatePem']
    );
    if ($authz === false) {
        return array('ok' => false, 'status' => 0, 'error' => 'vapid');
    }

    $result = pngm_push_http_post($endpoint, $enc['body'], array(
        'Authorization' => $authz,
        'Content-Type' => 'application/octet-stream',
        'Content-Encoding' => 'aes128gcm',
        'TTL' => '86400',
        'Urgency' => 'normal',
        'Content-Length' => (string) strlen($enc['body']),
    ));

    $gone = in_array((int) $result['status'], array(404, 410), true);
    return array(
        'ok' => !empty($result['ok']),
        'status' => (int) $result['status'],
        'gone' => $gone,
        'error' => !empty($result['ok']) ? '' : (string) $result['body'],
    );
}

/**
 * Push to all of a user's devices. Returns number of successful deliveries.
 *
 * @param int    $user_id
 * @param string $title
 * @param string $body
 * @param string $url
 * @return int
 */
function pngm_webpush_send_to_user($user_id, $title, $body, $url = '')
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return 0;
    }

    $subs = pngm_webpush_get_subscriptions($user_id);
    if (empty($subs)) {
        return 0;
    }

    $ok = 0;
    $keep = array();
    foreach ($subs as $row) {
        if (!is_array($row)) {
            continue;
        }
        $res = pngm_webpush_send_to_subscription($row, $title, $body, $url);
        if (!empty($res['gone'])) {
            continue; // drop expired endpoint
        }
        $keep[] = $row;
        if (!empty($res['ok'])) {
            $ok++;
        }
    }

    if (count($keep) !== count($subs)) {
        pngm_webpush_save_subscriptions($user_id, $keep);
    }

    return $ok;
}

/**
 * Whether the user has at least one stored push subscription.
 *
 * @param int $user_id
 * @return bool
 */
function pngm_webpush_user_has_subscription($user_id)
{
    return count(pngm_webpush_get_subscriptions((int) $user_id)) > 0;
}

/**
 * Register SW + deliver queued on-page notifications (fallback).
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
    $vapid = pngm_webpush_public_key();
    $icon = osc_base_url() . 'pwa/icon-192.png';
    $ajax = osc_base_url(true) . '?page=ajax&action=pngm_push_subscribe';
    ?>
<script>
(function () {
  var swUrl = <?php echo json_encode($sw_url); ?>;
  var vapidKey = <?php echo json_encode($vapid); ?>;
  var items = <?php echo json_encode($payload); ?>;
  var icon = <?php echo json_encode($icon); ?>;
  var subscribeUrl = <?php echo json_encode($ajax); ?>;

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }

  function showViaSW(reg, n) {
    if (!reg || typeof reg.showNotification !== 'function') return false;
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
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
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
    if (!items || !items.length) return;
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
    items.forEach(function (n, i) {
      window.setTimeout(function () {
        if (!showViaSW(reg, n)) showFallback(n);
      }, i * 400);
    });
  }

  function postSubscription(sub) {
    if (!sub || !subscribeUrl) return;
    var json = null;
    try { json = sub.toJSON ? sub.toJSON() : null; } catch (e) {}
    if (!json || !json.endpoint) return;
    var body = 'subscription=' + encodeURIComponent(JSON.stringify(json));
    if (window.fetch) {
      fetch(subscribeUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body
      }).catch(function () {});
    }
  }

  function ensurePushSubscription(reg) {
    if (!reg || !vapidKey || !('PushManager' in window)) return;
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
    reg.pushManager.getSubscription().then(function (existing) {
      if (existing) {
        postSubscription(existing);
        return existing;
      }
      return reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey)
      }).then(function (sub) {
        postSubscription(sub);
        return sub;
      });
    }).catch(function () {});
  }

  if (!('serviceWorker' in navigator)) {
    deliver(null);
    return;
  }

  navigator.serviceWorker.register(swUrl, { scope: '/' }).then(function (reg) {
    deliver(reg);
    ensurePushSubscription(reg);
  }).catch(function () {
    deliver(null);
  });
})();
</script>
    <?php
}

/**
 * Prefs page boot JSON (VAPID public key + endpoints).
 *
 * @return void
 */
function pngm_webpush_prefs_boot_json()
{
    pngm_webpush_ensure_sw_file();
    echo json_encode(array(
        'swUrl' => pngm_webpush_sw_url(),
        'vapidPublicKey' => pngm_webpush_public_key(),
        'subscribeUrl' => osc_base_url(true) . '?page=ajax&action=pngm_push_subscribe',
        'unsubscribeUrl' => osc_base_url(true) . '?page=ajax&action=pngm_push_unsubscribe',
        'testUrl' => osc_base_url(true) . '?page=ajax&action=pngm_push_test',
        'hasSubscription' => osc_is_web_user_logged_in()
            ? pngm_webpush_user_has_subscription((int) osc_logged_user_id())
            : false,
        'unsupported' => __('Push notifications are not supported in this browser.', 'epsilon'),
        'blocked' => __('Notifications are blocked in your browser settings.', 'epsilon'),
        'enabled' => __('Enabled', 'epsilon'),
        'ask' => __('Ask permission', 'epsilon'),
    ));
}

/**
 * Parse subscription JSON from AJAX request.
 *
 * @return array|null
 */
function pngm_webpush_ajax_read_subscription()
{
    $raw = Params::getParam('subscription');
    if ($raw === '' || $raw === null) {
        $raw = file_get_contents('php://input');
    }
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }
    // Allow raw JSON body or form field
    $trim = trim($raw);
    if ($trim !== '' && $trim[0] !== '{' && strpos($trim, 'subscription=') === 0) {
        parse_str($trim, $parsed);
        $trim = isset($parsed['subscription']) ? (string) $parsed['subscription'] : '';
    }
    $data = json_decode($trim, true);
    return is_array($data) ? $data : null;
}

/**
 * AJAX: save PushSubscription for logged-in user.
 */
function pngm_ajax_push_subscribe()
{
    header('Content-Type: application/json; charset=utf-8');
    if (!osc_is_web_user_logged_in()) {
        echo json_encode(array('ok' => false, 'error' => 'auth'));
        return;
    }
    $sub = pngm_webpush_ajax_read_subscription();
    if ($sub === null) {
        echo json_encode(array('ok' => false, 'error' => 'bad_json'));
        return;
    }
    $ok = pngm_webpush_upsert_subscription((int) osc_logged_user_id(), $sub);
    echo json_encode(array('ok' => $ok));
}
osc_add_hook('ajax_pngm_push_subscribe', 'pngm_ajax_push_subscribe');

/**
 * AJAX: remove PushSubscription.
 */
function pngm_ajax_push_unsubscribe()
{
    header('Content-Type: application/json; charset=utf-8');
    if (!osc_is_web_user_logged_in()) {
        echo json_encode(array('ok' => false, 'error' => 'auth'));
        return;
    }
    $sub = pngm_webpush_ajax_read_subscription();
    $endpoint = '';
    if (is_array($sub) && isset($sub['endpoint'])) {
        $endpoint = (string) $sub['endpoint'];
    } else {
        $endpoint = (string) Params::getParam('endpoint');
    }
    pngm_webpush_remove_subscription((int) osc_logged_user_id(), $endpoint);
    echo json_encode(array('ok' => true));
}
osc_add_hook('ajax_pngm_push_unsubscribe', 'pngm_ajax_push_unsubscribe');

/**
 * AJAX: send a real VAPID test push to this user's devices.
 */
function pngm_ajax_push_test()
{
    header('Content-Type: application/json; charset=utf-8');
    if (!osc_is_web_user_logged_in()) {
        echo json_encode(array('ok' => false, 'error' => 'auth'));
        return;
    }
    $user_id = (int) osc_logged_user_id();
    $n = pngm_webpush_send_to_user(
        $user_id,
        __('PNGMarket test notification', 'epsilon'),
        __('Browser push is working on this device — even when the site is closed.', 'epsilon'),
        function_exists('pngm_notif_prefs_url') ? pngm_notif_prefs_url() : osc_user_dashboard_url()
    );
    echo json_encode(array(
        'ok' => $n > 0,
        'sent' => $n,
        'hasSubscription' => pngm_webpush_user_has_subscription($user_id),
    ));
}
osc_add_hook('ajax_pngm_push_test', 'pngm_ajax_push_test');

// Do NOT generate VAPID keys on every request — preference writes / OpenSSL during
// init have caused blank HTTP 500s on some hosts. Keys are created lazily when
// the prefs UI or a push send needs them (pngm_webpush_vapid_keys()).

// Replace on-page Notification() footer with Service Worker + VAPID delivery.
osc_add_hook('init', function () {
    if (function_exists('osc_remove_hook')) {
        osc_remove_hook('footer', 'pngm_notif_push_footer');
    }
}, 8);
osc_add_hook('footer', 'pngm_webpush_footer', 10);
