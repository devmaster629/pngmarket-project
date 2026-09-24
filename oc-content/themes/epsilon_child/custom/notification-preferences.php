<?php
/**
 * Notification Preferences — rendered inside user-custom.php shell.
 */

if (!osc_is_web_user_logged_in()) {
    header('Location: ' . osc_user_login_url());
    exit;
}

if (!function_exists('pngm_notif_prefs_get')) {
    require_once dirname(__FILE__) . '/../includes/notification_prefs.php';
}

if (function_exists('pngm_webpush_ensure_sw_file')) {
    pngm_webpush_ensure_sw_file();
}

$user_id = osc_logged_user_id();
$saved_flash = false;

if (Params::getParam('pngm_notif_save') === '1' && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST') {
    if (function_exists('osc_csrf_check')) {
        osc_csrf_check();
    }
    $input = array(
        'allow' => Params::getParam('allow'),
        'digest' => Params::getParam('digest'),
        'items' => Params::getParam('items'),
    );
    pngm_notif_prefs_save($user_id, $input);
    $url = pngm_notif_prefs_url();
    $sep = (strpos($url, '?') !== false) ? '&' : '?';
    header('Location: ' . $url . $sep . 'saved=1');
    exit;
}

$prefs = pngm_notif_prefs_get($user_id);
$schema = pngm_notif_prefs_schema();
$show_saved = Params::getParam('saved') === '1';
$section_num = 0;
?>

<div class="pngm-notif" data-allow="<?php echo (int) $prefs['allow']; ?>">
  <div class="pngm-notif-head">
    <div>
      <h1><?php _e('Notification Preferences', 'epsilon'); ?></h1>
      <p><?php _e('Choose how and when PNGMarket contacts you.', 'epsilon'); ?></p>
    </div>
    <?php if ($show_saved) { ?>
      <div class="pngm-notif-toast" role="status" id="pngm-notif-toast">
        <i class="fas fa-check-circle" aria-hidden="true"></i>
        <span><?php _e('Preferences saved', 'epsilon'); ?></span>
        <button type="button" class="pngm-notif-toast-close" aria-label="<?php echo osc_esc_html(__('Close', 'epsilon')); ?>">&times;</button>
      </div>
    <?php } ?>
  </div>

  <form method="post" action="<?php echo osc_esc_html(pngm_notif_prefs_url()); ?>" class="pngm-notif-form">
    <input type="hidden" name="pngm_notif_save" value="1" />
    <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>

    <div class="pngm-notif-top">
      <label class="pngm-notif-card pngm-notif-allow">
        <span class="pngm-notif-card-copy">
          <strong><?php _e('Allow notifications', 'epsilon'); ?></strong>
          <em><?php _e('Receive updates about your account, listings and activity.', 'epsilon'); ?></em>
        </span>
        <span class="pngm-notif-switch">
          <input type="checkbox" name="allow" value="1" id="pngm_notif_allow" <?php echo !empty($prefs['allow']) ? 'checked' : ''; ?> />
          <span class="pngm-notif-switch-ui" aria-hidden="true"></span>
        </span>
      </label>
    </div>

    <section class="pngm-notif-push-panel" id="pngm-notif-push-panel" aria-labelledby="pngm-push-heading">
      <div class="pngm-notif-push-panel-head">
        <span class="pngm-notif-push-ico" aria-hidden="true"><i class="fas fa-bell"></i></span>
        <div>
          <h2 id="pngm-push-heading"><?php _e('Browser push notifications', 'epsilon'); ?></h2>
          <p><?php _e('Allow browser permission so PNGMarket can alert you on this device — including when the site is closed (messages, listing expiry, saved searches).', 'epsilon'); ?></p>
        </div>
      </div>

      <ul class="pngm-notif-push-status" aria-live="polite">
        <li>
          <span><?php _e('Browser support', 'epsilon'); ?></span>
          <strong data-push-support>—</strong>
        </li>
        <li>
          <span><?php _e('Permission', 'epsilon'); ?></span>
          <strong data-push-permission>—</strong>
        </li>
        <li>
          <span><?php _e('Service worker', 'epsilon'); ?></span>
          <strong data-push-sw>—</strong>
        </li>
      </ul>

      <div class="pngm-notif-push-actions">
        <button type="button" class="pngm-ua-btn" id="pngm-notif-push-enable">
          <i class="fas fa-unlock-alt" aria-hidden="true"></i>
          <?php _e('Enable browser notifications', 'epsilon'); ?>
        </button>
        <button type="button" class="pngm-ua-btn is-secondary" id="pngm-notif-push-test" hidden>
          <i class="fas fa-paper-plane" aria-hidden="true"></i>
          <?php _e('Send test notification', 'epsilon'); ?>
        </button>
      </div>
      <p class="pngm-notif-push-hint" data-push-hint></p>
    </section>

    <div class="pngm-notif-table-head" aria-hidden="true">
      <span></span>
      <span><?php _e('Email', 'epsilon'); ?></span>
      <span><?php _e('Push', 'epsilon'); ?></span>
    </div>

    <?php foreach ($schema as $section) {
      $section_num++;
    ?>
      <section class="pngm-notif-section">
        <h2><span><?php echo (int) $section_num; ?>.</span> <?php echo osc_esc_html($section['title']); ?></h2>
        <div class="pngm-notif-rows">
          <?php foreach ($section['items'] as $item) {
            $id = $item['id'];
            $vals = isset($prefs['items'][$id]) ? $prefs['items'][$id] : array('email' => 0, 'push' => 0);
            $mandatory = !empty($item['mandatory']);
          ?>
            <div class="pngm-notif-row<?php echo $mandatory ? ' is-mandatory' : ''; ?>">
              <div class="pngm-notif-row-label">
                <span class="pngm-notif-row-ico" aria-hidden="true"><i class="<?php echo osc_esc_html($item['icon']); ?>"></i></span>
                <span>
                  <?php echo osc_esc_html($item['label']); ?>
                  <?php if ($mandatory) { ?>
                    <small><?php _e('(Mandatory)', 'epsilon'); ?></small>
                  <?php } elseif (!empty($item['note'])) { ?>
                    <small><?php echo osc_esc_html($item['note']); ?></small>
                  <?php } ?>
                </span>
              </div>
              <label class="pngm-notif-switch">
                <input type="checkbox" name="items[<?php echo osc_esc_html($id); ?>][email]" value="1"
                  <?php echo !empty($vals['email']) ? 'checked' : ''; ?>
                  <?php echo $mandatory ? 'checked disabled' : ''; ?>
                  data-channel="email" />
                <?php if ($mandatory) { ?>
                  <input type="hidden" name="items[<?php echo osc_esc_html($id); ?>][email]" value="1" />
                <?php } ?>
                <span class="pngm-notif-switch-ui" aria-hidden="true"></span>
                <span class="is-sr-only"><?php _e('Email', 'epsilon'); ?></span>
              </label>
              <label class="pngm-notif-switch">
                <input type="checkbox" name="items[<?php echo osc_esc_html($id); ?>][push]" value="1"
                  <?php echo !empty($vals['push']) ? 'checked' : ''; ?>
                  <?php echo $mandatory ? 'checked disabled' : ''; ?>
                  data-channel="push" />
                <?php if ($mandatory) { ?>
                  <input type="hidden" name="items[<?php echo osc_esc_html($id); ?>][push]" value="1" />
                <?php } ?>
                <span class="pngm-notif-switch-ui" aria-hidden="true"></span>
                <?php if ($mandatory) { ?><i class="fas fa-lock pngm-notif-lock" aria-hidden="true"></i><?php } ?>
                <span class="is-sr-only"><?php _e('Push', 'epsilon'); ?></span>
              </label>
            </div>
          <?php } ?>
        </div>
      </section>
    <?php } ?>

    <section class="pngm-notif-digest">
      <h2><?php _e('Email digest', 'epsilon'); ?></h2>
      <p><?php _e('Choose how often you want to receive a summary of activity.', 'epsilon'); ?></p>
      <div class="pngm-notif-digest-opts" role="radiogroup" aria-label="<?php echo osc_esc_html(__('Email digest', 'epsilon')); ?>">
        <?php
          $digests = array(
            'immediate' => __('Immediately', 'epsilon'),
            'daily' => __('Daily summary', 'epsilon'),
            'weekly' => __('Weekly summary', 'epsilon'),
          );
          foreach ($digests as $val => $label) {
        ?>
          <label class="pngm-notif-radio">
            <input type="radio" name="digest" value="<?php echo osc_esc_html($val); ?>" <?php echo ($prefs['digest'] === $val) ? 'checked' : ''; ?> />
            <span><?php echo osc_esc_html($label); ?></span>
          </label>
        <?php } ?>
      </div>
    </section>

    <div class="pngm-notif-actions">
      <button type="submit" class="pngm-ua-btn pngm-notif-save"><?php _e('Save preferences', 'epsilon'); ?></button>
    </div>
  </form>
</div>

<script>
(function () {
  var root = document.querySelector('.pngm-notif');
  if (!root) return;

  var allow = document.getElementById('pngm_notif_allow');
  var toast = document.getElementById('pngm-notif-toast');
  var enableBtn = document.getElementById('pngm-notif-push-enable');
  var testBtn = document.getElementById('pngm-notif-push-test');
  var elSupport = root.querySelector('[data-push-support]');
  var elPerm = root.querySelector('[data-push-permission]');
  var elSw = root.querySelector('[data-push-sw]');
  var elHint = root.querySelector('[data-push-hint]');
  var boot = <?php
    if (function_exists('pngm_webpush_prefs_boot_json')) {
      pngm_webpush_prefs_boot_json();
    } else {
      echo json_encode(array(
        'swUrl' => osc_base_url() . 'sw.js',
        'vapidPublicKey' => '',
        'subscribeUrl' => '',
        'testUrl' => '',
      ));
    }
  ?>;
  var swUrl = boot.swUrl || <?php echo json_encode(osc_base_url() . 'sw.js'); ?>;
  var vapidKey = boot.vapidPublicKey || '';
  var subscribeUrl = boot.subscribeUrl || '';
  var testUrl = boot.testUrl || '';
  var iconUrl = <?php echo json_encode(osc_base_url() . 'pwa/icon-192.png'); ?>;
  var L = {
    yes: <?php echo json_encode(__('Supported', 'epsilon')); ?>,
    no: <?php echo json_encode(__('Not supported', 'epsilon')); ?>,
    granted: <?php echo json_encode(__('Granted', 'epsilon')); ?>,
    denied: <?php echo json_encode(__('Blocked', 'epsilon')); ?>,
    default: <?php echo json_encode(__('Not asked yet', 'epsilon')); ?>,
    swOn: <?php echo json_encode(__('Registered', 'epsilon')); ?>,
    swOff: <?php echo json_encode(__('Not registered', 'epsilon')); ?>,
    swChecking: <?php echo json_encode(__('Checking…', 'epsilon')); ?>,
    unsupported: <?php echo json_encode(__('Push notifications are not supported in this browser.', 'epsilon')); ?>,
    blocked: <?php echo json_encode(__('Notifications are blocked. Allow them in your browser site settings, then reload.', 'epsilon')); ?>,
    enableCta: <?php echo json_encode(__('Enable browser notifications', 'epsilon')); ?>,
    enabledCta: <?php echo json_encode(__('Notifications enabled', 'epsilon')); ?>,
    hintReady: <?php echo json_encode(__('Permission granted and this device is subscribed. Use “Send test notification” — it should arrive even if you close this tab.', 'epsilon')); ?>,
    hintAsk: <?php echo json_encode(__('Click “Enable browser notifications” to open the browser permission prompt.', 'epsilon')); ?>,
    hintNeedHttps: <?php echo json_encode(__('Web Push needs HTTPS (or localhost). Open the site over a secure URL to finish setup.', 'epsilon')); ?>,
    hintSubscribing: <?php echo json_encode(__('Subscribing this device…', 'epsilon')); ?>,
    hintSubscribeFail: <?php echo json_encode(__('Could not subscribe this device for push. Try again or check browser settings.', 'epsilon')); ?>,
    testTitle: <?php echo json_encode(__('PNGMarket test notification', 'epsilon')); ?>,
    testBody: <?php echo json_encode(__('Browser push is working on this device.', 'epsilon')); ?>,
    testFail: <?php echo json_encode(__('Could not send a test push. Enable notifications, then try again.', 'epsilon')); ?>,
    testOk: <?php echo json_encode(__('Test push sent. Check your system notification tray.', 'epsilon')); ?>
  };

  function syncAllow() {
    var on = !!(allow && allow.checked);
    root.classList.toggle('is-muted', !on);
    root.querySelectorAll('.pngm-notif-row:not(.is-mandatory) input[type="checkbox"]').forEach(function (el) {
      el.disabled = !on;
    });
  }

  function setText(el, text, state) {
    if (!el) return;
    el.textContent = text;
    el.classList.remove('is-ok', 'is-warn', 'is-bad');
    if (state) el.classList.add(state);
  }

  function setHint(text) {
    if (elHint) elHint.textContent = text || '';
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }

  function postJsonForm(url, fields) {
    var body = Object.keys(fields).map(function (k) {
      return encodeURIComponent(k) + '=' + encodeURIComponent(fields[k]);
    }).join('&');
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body
    }).then(function (r) { return r.json().catch(function () { return { ok: false }; }); });
  }

  function saveSubscription(sub) {
    if (!subscribeUrl || !sub) return Promise.resolve({ ok: false });
    var json = sub.toJSON ? sub.toJSON() : sub;
    return postJsonForm(subscribeUrl, { subscription: JSON.stringify(json) });
  }

  function registerSw() {
    if (!('serviceWorker' in navigator)) {
      return Promise.resolve(null);
    }
    return navigator.serviceWorker.register(swUrl, { scope: '/' }).then(function (reg) {
      return reg;
    }).catch(function () {
      return null;
    });
  }

  function subscribePush(reg) {
    if (!reg || !('pushManager' in reg) || !vapidKey) {
      return Promise.reject(new Error('no_push'));
    }
    return reg.pushManager.getSubscription().then(function (existing) {
      if (existing) return existing;
      return reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey)
      });
    }).then(function (sub) {
      return saveSubscription(sub).then(function (res) {
        if (!res || !res.ok) throw new Error('save_failed');
        return sub;
      });
    });
  }

  function showLocalTest(reg) {
    var opts = {
      body: L.testBody,
      icon: iconUrl,
      tag: 'pngm-test-' + Date.now(),
      data: { url: window.location.href }
    };
    if (reg && typeof reg.showNotification === 'function') {
      return reg.showNotification(L.testTitle, opts);
    }
    if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
      try {
        new Notification(L.testTitle, opts);
        return Promise.resolve();
      } catch (e) {
        return Promise.reject(e);
      }
    }
    return Promise.reject(new Error('unavailable'));
  }

  function refreshPushStatus() {
    var supported = ('Notification' in window) && ('serviceWorker' in navigator) && ('PushManager' in window);
    var secure = window.isSecureContext === true;
    setText(elSupport, supported ? L.yes : L.no, supported ? 'is-ok' : 'is-bad');

    if (!supported) {
      setText(elPerm, L.no, 'is-bad');
      setText(elSw, L.no, 'is-bad');
      if (enableBtn) {
        enableBtn.disabled = true;
        enableBtn.textContent = L.unsupported;
      }
      if (testBtn) testBtn.hidden = true;
      setHint(!secure ? L.hintNeedHttps : L.unsupported);
      return;
    }

    if (!secure && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
      setHint(L.hintNeedHttps);
    }

    var perm = Notification.permission;
    if (perm === 'granted') {
      setText(elPerm, L.granted, 'is-ok');
      setHint(L.hintReady);
      if (enableBtn) {
        enableBtn.disabled = true;
        enableBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> ' + L.enabledCta;
      }
      if (testBtn) testBtn.hidden = false;
    } else if (perm === 'denied') {
      setText(elPerm, L.denied, 'is-bad');
      setHint(L.blocked);
      if (enableBtn) {
        enableBtn.disabled = true;
        enableBtn.innerHTML = '<i class="fas fa-ban" aria-hidden="true"></i> ' + L.denied;
      }
      if (testBtn) testBtn.hidden = true;
    } else {
      setText(elPerm, L.default, 'is-warn');
      setHint(L.hintAsk);
      if (enableBtn) {
        enableBtn.disabled = false;
        enableBtn.innerHTML = '<i class="fas fa-unlock-alt" aria-hidden="true"></i> ' + L.enableCta;
      }
      if (testBtn) testBtn.hidden = true;
    }

    setText(elSw, L.swChecking, 'is-warn');
    navigator.serviceWorker.getRegistration('/').then(function (reg) {
      if (reg) {
        setText(elSw, L.swOn, 'is-ok');
      } else {
        setText(elSw, L.swOff, 'is-warn');
      }
    }).catch(function () {
      setText(elSw, L.swOff, 'is-warn');
    });
  }

  if (allow) {
    allow.addEventListener('change', syncAllow);
    syncAllow();
  }

  if (toast) {
    var closer = toast.querySelector('.pngm-notif-toast-close');
    if (closer) {
      closer.addEventListener('click', function () { toast.hidden = true; });
    }
    window.setTimeout(function () {
      if (toast) toast.hidden = true;
    }, 4000);
  }

  if (enableBtn) {
    enableBtn.addEventListener('click', function () {
      if (!('Notification' in window) || !('PushManager' in window)) {
        setHint(L.unsupported);
        return;
      }
      if (!vapidKey) {
        setHint(L.hintSubscribeFail);
        return;
      }
      enableBtn.disabled = true;
      setHint(L.hintSubscribing);
      Notification.requestPermission().then(function (perm) {
        if (perm !== 'granted') {
          refreshPushStatus();
          return null;
        }
        return registerSw().then(function (reg) {
          if (!reg) throw new Error('no_sw');
          return subscribePush(reg);
        }).then(function () {
          refreshPushStatus();
        });
      }).catch(function () {
        setHint(L.hintSubscribeFail);
        refreshPushStatus();
      });
    });
  }

  if (testBtn) {
    testBtn.addEventListener('click', function () {
      if (typeof Notification === 'undefined' || Notification.permission !== 'granted') {
        setHint(L.testFail);
        return;
      }
      testBtn.disabled = true;
      var chain = Promise.resolve();
      if (testUrl) {
        chain = postJsonForm(testUrl, {}).then(function (res) {
          if (res && res.ok) {
            setHint(L.testOk);
            return true;
          }
          return false;
        }).catch(function () { return false; });
      } else {
        chain = Promise.resolve(false);
      }
      chain.then(function (serverOk) {
        if (serverOk) return;
        return registerSw().then(function (reg) {
          return showLocalTest(reg);
        }).then(function () {
          setHint(L.hintReady);
        });
      }).catch(function () {
        setHint(L.testFail);
      }).then(function () {
        testBtn.disabled = false;
        refreshPushStatus();
      });
    });
  }

  refreshPushStatus();
  if ('serviceWorker' in navigator && typeof Notification !== 'undefined' && Notification.permission === 'granted' && vapidKey) {
    registerSw().then(function (reg) {
      if (!reg) return;
      return subscribePush(reg).catch(function () {});
    }).then(function () { refreshPushStatus(); });
  }
})();
</script>
