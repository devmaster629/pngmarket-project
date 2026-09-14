<?php
/**
 * Two-step verification — enter authenticator TOTP after password/social login.
 */

if (!function_exists('pngm_sec_2fa_url')) {
    require_once dirname(__FILE__) . '/../includes/account_security.php';
}

$verify_url = pngm_sec_2fa_url();
$pending = pngm_sec_2fa_pending();

if (!$pending) {
    osc_add_flash_warning_message(__('Your verification session expired. Please sign in again.', 'epsilon'));
    header('Location: ' . osc_user_login_url());
    exit;
}

$action = Params::getParam('pngm_2fa_action');

if (strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST' && $action === 'verify') {
    if (function_exists('osc_csrf_check')) {
        osc_csrf_check();
    }

    $code = Params::getParam('code');
    $pending = pngm_sec_2fa_pending();
    if (!$pending) {
        osc_add_flash_warning_message(__('Your verification session expired. Please sign in again.', 'epsilon'));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    $tries = (int) $pending['tries'];
    if ($tries >= 5) {
        pngm_sec_2fa_clear_pending();
        osc_add_flash_error_message(__('Too many incorrect attempts. Please sign in again.', 'epsilon'));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    $sec = pngm_sec_get((int) $pending['uid']);
    $secret = isset($sec['totp_secret']) ? (string) $sec['totp_secret'] : '';
    if ($secret === '' || !pngm_sec_totp_verify($secret, $code)) {
        Session::newInstance()->_set('pngm_2fa_tries', $tries + 1);
        osc_add_flash_error_message(__('That code is incorrect. Please try again.', 'epsilon'));
        header('Location: ' . $verify_url);
        exit;
    }

    $redirect = pngm_sec_2fa_complete_login($pending);
    osc_add_flash_ok_message(__('You are signed in.', 'epsilon'));
    header('Location: ' . $redirect);
    exit;
}

$pending = pngm_sec_2fa_pending();
if (!$pending) {
    header('Location: ' . osc_user_login_url());
    exit;
}
?>

<div class="pngm-twofa">
  <div class="pngm-twofa-box">
    <h1><?php _e('Two-step verification', 'epsilon'); ?></h1>
    <p class="pngm-twofa-lead">
      <?php _e('Open Google Authenticator (or your authenticator app) and enter the 6-digit code for this account.', 'epsilon'); ?>
    </p>

    <form method="post" action="<?php echo osc_esc_html($verify_url); ?>" class="pngm-twofa-form" autocomplete="one-time-code">
      <input type="hidden" name="pngm_2fa_action" value="verify" />
      <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
      <div class="pngm-twofa-field">
        <label for="pngm-2fa-code"><?php _e('Authenticator code', 'epsilon'); ?></label>
        <input
          id="pngm-2fa-code"
          type="text"
          name="code"
          inputmode="numeric"
          pattern="[0-9]*"
          maxlength="6"
          minlength="6"
          required
          autofocus
          placeholder="••••••"
        />
      </div>
      <button type="submit" class="btn"><?php _e('Verify and sign in', 'epsilon'); ?></button>
    </form>

    <a class="pngm-twofa-back" href="<?php echo osc_esc_html(osc_user_login_url()); ?>"><?php _e('Back to sign in', 'epsilon'); ?></a>
  </div>
</div>

<script type="text/javascript">
(function () {
  var input = document.getElementById('pngm-2fa-code');
  if (!input) return;
  input.addEventListener('input', function () {
    this.value = this.value.replace(/\D+/g, '').slice(0, 6);
  });
})();
</script>
