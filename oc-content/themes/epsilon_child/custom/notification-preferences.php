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

      <button type="button" class="pngm-notif-card pngm-notif-push" id="pngm-notif-push-btn">
        <span class="pngm-notif-push-ico" aria-hidden="true"><i class="fas fa-mobile-alt"></i></span>
        <span class="pngm-notif-card-copy">
          <strong>
            <?php _e('Push notifications', 'epsilon'); ?>
            <span class="pngm-notif-pill" data-push-pill><?php _e('Enabled', 'epsilon'); ?></span>
          </strong>
          <em><?php _e('Manage browser permission', 'epsilon'); ?></em>
        </span>
        <i class="fas fa-chevron-right pngm-notif-chevron" aria-hidden="true"></i>
      </button>
    </div>

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
  var pushBtn = document.getElementById('pngm-notif-push-btn');
  var pushPill = root.querySelector('[data-push-pill]');

  function syncAllow() {
    var on = !!(allow && allow.checked);
    root.classList.toggle('is-muted', !on);
    root.querySelectorAll('.pngm-notif-row:not(.is-mandatory) input[type="checkbox"]').forEach(function (el) {
      el.disabled = !on;
    });
  }

  function syncPushPill() {
    if (!pushPill || !('Notification' in window)) {
      if (pushPill) pushPill.textContent = '<?php echo osc_esc_js(__('Unavailable', 'epsilon')); ?>';
      return;
    }
    var perm = Notification.permission;
    if (perm === 'granted') {
      pushPill.textContent = '<?php echo osc_esc_js(__('Enabled', 'epsilon')); ?>';
      pushPill.classList.add('is-on');
      pushPill.classList.remove('is-off');
    } else if (perm === 'denied') {
      pushPill.textContent = '<?php echo osc_esc_js(__('Blocked', 'epsilon')); ?>';
      pushPill.classList.add('is-off');
      pushPill.classList.remove('is-on');
    } else {
      pushPill.textContent = '<?php echo osc_esc_js(__('Ask permission', 'epsilon')); ?>';
      pushPill.classList.remove('is-on', 'is-off');
    }
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

  if (pushBtn) {
    pushBtn.addEventListener('click', function () {
      if (!('Notification' in window)) {
        window.alert('<?php echo osc_esc_js(__('Push notifications are not supported in this browser.', 'epsilon')); ?>');
        return;
      }
      var finish = function () { syncPushPill(); };
      Notification.requestPermission().then(function (perm) {
        if (perm === 'granted' && 'serviceWorker' in navigator) {
          var swUrl = <?php echo json_encode(function_exists('pngm_webpush_sw_url') ? pngm_webpush_sw_url() : (osc_base_url() . 'sw.js')); ?>;
          navigator.serviceWorker.register(swUrl, { scope: '/' }).catch(function () {});
        }
        finish();
      });
    });
  }
  syncPushPill();
  if ('serviceWorker' in navigator && typeof Notification !== 'undefined' && Notification.permission === 'granted') {
    var swUrlBoot = <?php echo json_encode(function_exists('pngm_webpush_sw_url') ? pngm_webpush_sw_url() : (osc_base_url() . 'sw.js')); ?>;
    navigator.serviceWorker.register(swUrlBoot, { scope: '/' }).catch(function () {});
  }
})();
</script>
