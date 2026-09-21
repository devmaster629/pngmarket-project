<?php
/**
 * Account & Security — rendered inside user-custom.php shell.
 */

if (!osc_is_web_user_logged_in()) {
    header('Location: ' . osc_user_login_url());
    exit;
}

if (!function_exists('pngm_sec_url')) {
    require_once dirname(__FILE__) . '/../includes/account_security.php';
}

$user_id = (int) osc_logged_user_id();
$user = User::newInstance()->findByPrimaryKey($user_id);
if (!is_array($user)) {
    header('Location: ' . osc_user_login_url());
    exit;
}

$sec_url = pngm_sec_url();
$action = Params::getParam('pngm_sec_action');

// --- POST handlers ---
if (strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST' && $action !== '') {
    if (function_exists('osc_csrf_check')) {
        osc_csrf_check();
    }

    if (function_exists('eps_is_demo') && eps_is_demo()) {
        osc_add_flash_error_message(__('You cannot do this on demo site', 'epsilon'));
        header('Location: ' . $sec_url);
        exit;
    }

    if ($action === 'change_password') {
        $current = Params::getParam('password', false, false);
        $new1 = Params::getParam('new_password', false, false);
        $new2 = Params::getParam('new_password2', false, false);

        if ($current === '' || $new1 === '' || $new2 === '') {
            osc_add_flash_warning_message(__('Password cannot be blank', 'epsilon'));
        } elseif (!osc_verify_password($current, $user['s_password'])) {
            osc_add_flash_error_message(__("Current password doesn't match", 'epsilon'));
        } elseif ($new1 !== $new2) {
            osc_add_flash_error_message(__("Passwords don't match", 'epsilon'));
        } elseif (strlen($new1) < 8) {
            osc_add_flash_error_message(__('New password must be at least 8 characters', 'epsilon'));
        } else {
            User::newInstance()->update(
                array('s_password' => osc_hash_password($new1)),
                array('pk_i_id' => $user_id)
            );
            $data = pngm_sec_get($user_id);
            $data['password_changed_at'] = date('Y-m-d H:i:s');
            pngm_sec_save($user_id, $data);
            pngm_sec_log_activity($user_id, 'password', __('Password changed', 'epsilon'));
            osc_add_flash_ok_message(__('Password has been changed', 'epsilon'));
        }
        header('Location: ' . $sec_url);
        exit;
    }

    if ($action === 'change_email') {
        $new_email = trim((string) Params::getParam('new_email'));
        if (!osc_validate_email($new_email)) {
            osc_add_flash_error_message(__('The specified e-mail is not valid', 'epsilon'));
        } else {
            $exists = User::newInstance()->findByEmail($new_email);
            if (isset($exists['pk_i_id'])) {
                osc_add_flash_error_message(__('The specified e-mail is already in use', 'epsilon'));
            } else {
                $userEmailTmp = array(
                    'fk_i_user_id' => $user_id,
                    's_new_email' => $new_email,
                );
                UserEmailTmp::newInstance()->insertOrUpdate($userEmailTmp);
                $code = osc_genRandomPassword(30);
                $date = date('Y-m-d H:i:s');
                User::newInstance()->update(
                    array('s_pass_code' => $code, 's_pass_date' => $date, 's_pass_ip' => osc_get_ip()),
                    array('pk_i_id' => $user_id)
                );
                $validation_url = osc_change_user_email_confirm_url($user_id, $code);
                osc_run_hook('hook_email_new_email', $new_email, $validation_url);
                pngm_sec_log_activity($user_id, 'email', __('Email change requested', 'epsilon'));
                osc_add_flash_ok_message(__('We sent a confirmation link to your new email address', 'epsilon'));
            }
        }
        header('Location: ' . $sec_url);
        exit;
    }

    if ($action === 'twofa_start') {
        pngm_sec_2fa_begin_setup();
        header('Location: ' . $sec_url . ((strpos($sec_url, '?') !== false) ? '&' : '?') . 'setup=1#pngm-sec-twofa');
        exit;
    }

    if ($action === 'twofa_cancel_setup') {
        Session::newInstance()->_drop('pngm_2fa_setup_secret');
        header('Location: ' . $sec_url . '#pngm-sec-twofa');
        exit;
    }

    if ($action === 'twofa_confirm') {
        $setup_secret = pngm_sec_2fa_setup_secret();
        $code = Params::getParam('totp_code');
        if (!$setup_secret) {
            osc_add_flash_warning_message(__('Setup expired. Please start again.', 'epsilon'));
            header('Location: ' . $sec_url . '#pngm-sec-twofa');
            exit;
        }
        if (!pngm_sec_totp_verify($setup_secret, $code)) {
            osc_add_flash_error_message(__('That authenticator code is incorrect. Try again with a fresh code.', 'epsilon'));
            header('Location: ' . $sec_url . ((strpos($sec_url, '?') !== false) ? '&' : '?') . 'setup=1#pngm-sec-twofa');
            exit;
        }
        $data = pngm_sec_get($user_id);
        $data['twofa'] = 1;
        $data['totp_secret'] = $setup_secret;
        pngm_sec_save($user_id, $data);
        Session::newInstance()->_drop('pngm_2fa_setup_secret');
        pngm_sec_trust_current_device($user_id);
        pngm_sec_log_activity($user_id, 'twofa', __('Two-step verification enabled', 'epsilon'));
        osc_add_flash_ok_message(__('Two-step verification is enabled with your authenticator app.', 'epsilon'));
        header('Location: ' . $sec_url . '#pngm-sec-twofa');
        exit;
    }

    if ($action === 'twofa_disable') {
        $data = pngm_sec_get($user_id);
        $data['twofa'] = 0;
        $data['totp_secret'] = '';
        $data['trusted_devices'] = array();
        pngm_sec_save($user_id, $data);
        Cookie::newInstance()->pop(pngm_sec_2fa_cookie_name());
        Cookie::newInstance()->set();
        Session::newInstance()->_drop('pngm_2fa_setup_secret');
        pngm_sec_log_activity($user_id, 'twofa', __('Two-step verification disabled', 'epsilon'));
        osc_add_flash_ok_message(__('Two-step verification has been turned off', 'epsilon'));
        header('Location: ' . $sec_url . '#pngm-sec-twofa');
        exit;
    }

    if ($action === 'connect_toggle') {
        $provider = Params::getParam('provider');
        if (!in_array($provider, array('google', 'facebook'), true)) {
            header('Location: ' . $sec_url);
            exit;
        }
        $data = pngm_sec_get($user_id);
        $on = empty($data['connected'][$provider]);
        $data['connected'][$provider] = $on ? 1 : 0;
        pngm_sec_save($user_id, $data);
        pngm_sec_log_activity(
            $user_id,
            'social',
            $on
                ? sprintf(__('%s connected', 'epsilon'), ucfirst($provider))
                : sprintf(__('%s disconnected', 'epsilon'), ucfirst($provider))
        );
        osc_add_flash_ok_message($on ? __('Account connected', 'epsilon') : __('Account disconnected', 'epsilon'));
        header('Location: ' . $sec_url);
        exit;
    }

    if ($action === 'revoke_session') {
        $sid = Params::getParam('session_id');
        $data = pngm_sec_get($user_id);
        $current_id = pngm_sec_session_fingerprint();
        $kept = array();
        foreach ((array) $data['sessions'] as $s) {
            if (!is_array($s) || empty($s['id'])) {
                continue;
            }
            if ($s['id'] === $sid && $s['id'] !== $current_id) {
                continue;
            }
            $kept[] = $s;
        }
        $data['sessions'] = $kept;
        pngm_sec_save($user_id, $data);
        osc_add_flash_ok_message(__('Session signed out', 'epsilon'));
        header('Location: ' . $sec_url);
        exit;
    }

    if ($action === 'revoke_other_sessions') {
        $current = pngm_sec_current_device_meta();
        $data = pngm_sec_get($user_id);
        $data['sessions'] = array($current);
        pngm_sec_save($user_id, $data);
        pngm_sec_log_activity($user_id, 'session', __('Signed out of other sessions', 'epsilon'));
        osc_add_flash_ok_message(__('Signed out of all other sessions', 'epsilon'));
        header('Location: ' . $sec_url);
        exit;
    }
}

$sec = pngm_sec_get($user_id);
$sessions = pngm_sec_touch_session($user_id);
$sec = pngm_sec_get($user_id);
$activity = isset($sec['activity']) && is_array($sec['activity']) ? $sec['activity'] : array();
$email = (string) osc_logged_user_email();
$twofa_on = pngm_sec_twofa_is_enabled($user_id);
$setup_secret = pngm_sec_2fa_setup_secret();
$show_setup = (!$twofa_on && ($setup_secret || Params::getParam('setup') === '1'));
if ($show_setup && !$setup_secret) {
    $setup_secret = pngm_sec_2fa_begin_setup();
}
$setup_uri = ($show_setup && $setup_secret) ? pngm_sec_totp_otpauth_uri($setup_secret, $email) : '';
$setup_qr = $setup_uri !== '' ? pngm_sec_totp_qr_url($setup_uri) : '';
$pass_changed = !empty($sec['password_changed_at']) ? pngm_sec_time_label($sec['password_changed_at']) : '';
$google_on = !empty($sec['connected']['google']);
$facebook_on = !empty($sec['connected']['facebook']);
$persist = function_exists('pngm_persist_status') ? pngm_persist_status($user_id) : array('ok' => false, 'method' => 'unknown', 'at' => '', 'cookie_active' => false);
$persist_method_labels = array(
    'google' => __('Google', 'epsilon'),
    'facebook' => __('Facebook', 'epsilon'),
    'password' => __('Email & password', 'epsilon'),
    'twofa' => __('Two-step verification', 'epsilon'),
);
$persist_method_label = isset($persist_method_labels[$persist['method']])
    ? $persist_method_labels[$persist['method']]
    : __('Unknown', 'epsilon');
$delete_url = osc_base_url(true) . '?page=user&action=delete&id=' . $user_id . '&secret=' . urlencode((string) $user['s_secret']);
$show_pass = Params::getParam('edit') === 'password';
$show_email = Params::getParam('edit') === 'email';
// Never open both forms at once
if ($show_pass && $show_email) {
    $show_email = false;
}
?>

<div class="pngm-sec">
  <div class="pngm-sec-head">
    <h1><?php _e('Account & Security', 'epsilon'); ?></h1>
    <p><?php _e('Manage your password, sign-in methods and account access.', 'epsilon'); ?></p>
  </div>

  <button type="button" class="pngm-sec-account-menu" data-pngm-ua-menu="1">
    <span><i class="fas fa-list-ul" aria-hidden="true"></i> <?php _e('Account menu', 'epsilon'); ?></span>
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
  </button>

  <div class="pngm-sec-grid">
    <div class="pngm-sec-col">
      <section class="pngm-sec-card" id="pngm-sec-signin">
        <h2><span>1.</span> <?php _e('Sign-in details', 'epsilon'); ?></h2>

        <div class="pngm-sec-row">
          <div class="pngm-sec-row-copy">
            <strong><?php _e('Email', 'epsilon'); ?></strong>
            <em><?php echo osc_esc_html($email); ?></em>
            <?php if (function_exists('pngm_email_is_verified') && pngm_email_is_verified($user)) { ?>
              <span class="pngm-sec-badge is-ok"><?php _e('Verified', 'epsilon'); ?></span>
            <?php } ?>
          </div>
          <button type="button" class="pngm-ua-btn is-ghost pngm-sec-btn" data-pngm-sec-open="email"><?php _e('Change email', 'epsilon'); ?></button>
        </div>

        <div class="pngm-sec-row">
          <div class="pngm-sec-row-copy">
            <strong><?php _e('Password', 'epsilon'); ?></strong>
            <em><?php echo $pass_changed !== '' ? sprintf(__('Last changed %s', 'epsilon'), osc_esc_html($pass_changed)) : __('Set a strong password to protect your account', 'epsilon'); ?></em>
          </div>
          <button type="button" class="pngm-ua-btn is-ghost pngm-sec-btn" data-pngm-sec-open="password"><?php _e('Change password', 'epsilon'); ?></button>
        </div>
      </section>

      <section class="pngm-sec-card pngm-sec-panel<?php echo $show_pass ? ' is-open' : ' is-collapsed'; ?>" id="pngm-sec-password" aria-hidden="<?php echo $show_pass ? 'false' : 'true'; ?>">
        <h2><span>1b.</span> <?php _e('Change password', 'epsilon'); ?></h2>
        <form method="post" action="<?php echo osc_esc_html($sec_url); ?>" class="pngm-sec-form" autocomplete="off">
          <input type="hidden" name="pngm_sec_action" value="change_password" />
          <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>

          <label class="pngm-sec-field">
            <span><?php _e('Current password', 'epsilon'); ?></span>
            <input type="password" name="password" required />
          </label>
          <label class="pngm-sec-field">
            <span><?php _e('New password', 'epsilon'); ?></span>
            <input type="password" name="new_password" id="pngm-sec-new-pass" required minlength="8" />
          </label>
          <label class="pngm-sec-field">
            <span><?php _e('Confirm new password', 'epsilon'); ?></span>
            <input type="password" name="new_password2" required minlength="8" />
          </label>

          <ul class="pngm-sec-pass-rules" id="pngm-sec-pass-rules" aria-live="polite">
            <li data-rule="len"><?php _e('At least 8 characters', 'epsilon'); ?></li>
            <li data-rule="case"><?php _e('Upper and lower case letters', 'epsilon'); ?></li>
            <li data-rule="num"><?php _e('At least one number', 'epsilon'); ?></li>
            <li data-rule="sym"><?php _e('At least one symbol', 'epsilon'); ?></li>
          </ul>

          <div class="pngm-sec-actions">
            <button type="button" class="pngm-ua-btn is-ghost" data-pngm-sec-close="password"><?php _e('Cancel', 'epsilon'); ?></button>
            <button type="submit" class="pngm-ua-btn"><?php _e('Update password', 'epsilon'); ?></button>
          </div>
        </form>
      </section>

      <section class="pngm-sec-card pngm-sec-panel<?php echo $show_email ? ' is-open' : ' is-collapsed'; ?>" id="pngm-sec-email" aria-hidden="<?php echo $show_email ? 'false' : 'true'; ?>">
        <h2><?php _e('Change email', 'epsilon'); ?></h2>
        <form method="post" action="<?php echo osc_esc_html($sec_url); ?>" class="pngm-sec-form">
          <input type="hidden" name="pngm_sec_action" value="change_email" />
          <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
          <label class="pngm-sec-field">
            <span><?php _e('Current e-mail', 'epsilon'); ?></span>
            <input type="email" value="<?php echo osc_esc_html($email); ?>" disabled />
          </label>
          <label class="pngm-sec-field">
            <span><?php _e('New e-mail', 'epsilon'); ?></span>
            <input type="email" name="new_email" required />
          </label>
          <div class="pngm-sec-actions">
            <button type="button" class="pngm-ua-btn is-ghost" data-pngm-sec-close="email"><?php _e('Cancel', 'epsilon'); ?></button>
            <button type="submit" class="pngm-ua-btn"><?php _e('Send confirmation', 'epsilon'); ?></button>
          </div>
        </form>
      </section>

      <section class="pngm-sec-card" id="pngm-sec-twofa">
        <div class="pngm-sec-card-top">
          <h2><span>2.</span> <?php _e('Two-step verification', 'epsilon'); ?></h2>
          <span class="pngm-sec-badge<?php echo $twofa_on ? ' is-ok' : ''; ?>"><?php echo $twofa_on ? __('Enabled', 'epsilon') : __('Not enabled', 'epsilon'); ?></span>
        </div>
        <p class="pngm-sec-help"><?php _e('Protect your account with Google Authenticator (or any TOTP app). We’ll ask for a 6-digit code when you sign in from a new device.', 'epsilon'); ?></p>

        <?php if ($twofa_on) { ?>
          <form method="post" action="<?php echo osc_esc_html($sec_url); ?>" class="pngm-sec-form" onsubmit="return confirm('<?php echo osc_esc_js(__('Turn off two-step verification?', 'epsilon')); ?>');">
            <input type="hidden" name="pngm_sec_action" value="twofa_disable" />
            <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
            <button type="submit" class="pngm-ua-btn is-ghost"><?php _e('Turn off two-step verification', 'epsilon'); ?></button>
          </form>
        <?php } elseif ($show_setup && $setup_secret) { ?>
          <div class="pngm-sec-totp">
            <ol class="pngm-sec-totp-steps">
              <li><?php _e('Install Google Authenticator, Authy, or another authenticator app.', 'epsilon'); ?></li>
              <li><?php _e('Scan this QR code with the app (or enter the key manually).', 'epsilon'); ?></li>
              <li><?php _e('Enter the 6-digit code the app shows to finish setup.', 'epsilon'); ?></li>
            </ol>
            <div class="pngm-sec-totp-pair">
              <div class="pngm-sec-totp-qr">
                <img src="<?php echo osc_esc_html($setup_qr); ?>" width="180" height="180" alt="<?php echo osc_esc_html(__('Authenticator QR code', 'epsilon')); ?>" />
              </div>
              <div class="pngm-sec-totp-manual">
                <span><?php _e('Can’t scan? Enter this key:', 'epsilon'); ?></span>
                <code class="pngm-sec-totp-key"><?php echo osc_esc_html(trim(chunk_split($setup_secret, 4, ' '))); ?></code>
              </div>
            </div>
            <form method="post" action="<?php echo osc_esc_html($sec_url); ?>" class="pngm-sec-form pngm-sec-totp-confirm">
              <input type="hidden" name="pngm_sec_action" value="twofa_confirm" />
              <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
              <label class="pngm-sec-field">
                <span><?php _e('Authenticator code', 'epsilon'); ?></span>
                <input type="text" name="totp_code" inputmode="numeric" pattern="[0-9]*" maxlength="6" minlength="6" required autocomplete="one-time-code" placeholder="••••••" />
              </label>
              <div class="pngm-sec-actions">
                <button type="submit" class="pngm-ua-btn"><?php _e('Confirm and enable', 'epsilon'); ?></button>
              </div>
            </form>
            <form method="post" action="<?php echo osc_esc_html($sec_url); ?>" class="pngm-sec-totp-cancel">
              <input type="hidden" name="pngm_sec_action" value="twofa_cancel_setup" />
              <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
              <button type="submit" class="pngm-ua-btn is-ghost"><?php _e('Cancel setup', 'epsilon'); ?></button>
            </form>
          </div>
        <?php } else { ?>
          <form method="post" action="<?php echo osc_esc_html($sec_url); ?>" class="pngm-sec-form">
            <input type="hidden" name="pngm_sec_action" value="twofa_start" />
            <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
            <button type="submit" class="pngm-ua-btn"><?php _e('Enable two-step verification', 'epsilon'); ?></button>
          </form>
        <?php } ?>
      </section>

      <section class="pngm-sec-card" id="pngm-sec-connected">
        <h2><span>3.</span> <?php _e('Connected accounts', 'epsilon'); ?></h2>

        <div class="pngm-sec-persist" id="pngm-sec-persist">
          <div class="pngm-sec-persist-head">
            <strong><?php _e('Stay signed in on this device', 'epsilon'); ?></strong>
            <span class="pngm-sec-badge<?php echo !empty($persist['ok']) ? ' is-ok' : ''; ?>">
              <?php echo !empty($persist['ok']) ? __('Active', 'epsilon') : __('Not active', 'epsilon'); ?>
            </span>
          </div>
          <p class="pngm-sec-persist-copy">
            <?php _e('Google and Facebook sign-in keep you logged in after you close the browser (same as email login).', 'epsilon'); ?>
          </p>
          <ul class="pngm-sec-persist-meta">
            <li>
              <span><?php _e('Persistent cookie', 'epsilon'); ?></span>
              <strong class="<?php echo !empty($persist['cookie_active']) ? 'is-ok' : 'is-bad'; ?>">
                <?php echo !empty($persist['cookie_active']) ? __('Present', 'epsilon') : __('Missing', 'epsilon'); ?>
              </strong>
            </li>
            <li>
              <span><?php _e('Last sign-in method', 'epsilon'); ?></span>
              <strong><?php echo osc_esc_html($persist_method_label); ?></strong>
            </li>
            <?php if (!empty($persist['at'])) { ?>
              <li>
                <span><?php _e('Last sign-in', 'epsilon'); ?></span>
                <strong><?php echo osc_esc_html(function_exists('pngm_sec_time_label') ? pngm_sec_time_label($persist['at']) : $persist['at']); ?></strong>
              </li>
            <?php } ?>
          </ul>
        </div>

        <?php
          $google_svg = '<svg class="pngm-sec-brand-svg" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
          $fb_svg = '<svg class="pngm-sec-brand-svg" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="#1877F2"/><path fill="#fff" d="M13.28 18.5v-5.68h1.9l.28-2.21h-2.18V9.18c0-.64.18-1.08 1.1-1.08h1.17V6.12c-.2-.03-.9-.09-1.71-.09-1.69 0-2.85 1.03-2.85 2.93v1.63H9.2v2.21h1.79V18.5h2.29z"/></svg>';
        ?>

        <div class="pngm-sec-row pngm-sec-social-row">
          <div class="pngm-sec-row-copy is-social">
            <span class="pngm-sec-brand is-google" aria-hidden="true"><?php echo $google_svg; ?></span>
            <span class="pngm-sec-social-meta">
              <strong>Google</strong>
              <span class="pngm-sec-badge<?php echo $google_on ? ' is-ok' : ''; ?>"><?php echo $google_on ? __('Connected', 'epsilon') : __('Not connected', 'epsilon'); ?></span>
            </span>
          </div>
          <form method="post" action="<?php echo osc_esc_html($sec_url); ?>">
            <input type="hidden" name="pngm_sec_action" value="connect_toggle" />
            <input type="hidden" name="provider" value="google" />
            <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
            <button type="submit" class="pngm-ua-btn is-ghost pngm-sec-btn"><?php echo $google_on ? __('Disconnect', 'epsilon') : __('Connect', 'epsilon'); ?></button>
          </form>
        </div>

        <div class="pngm-sec-row pngm-sec-social-row">
          <div class="pngm-sec-row-copy is-social">
            <span class="pngm-sec-brand is-facebook" aria-hidden="true"><?php echo $fb_svg; ?></span>
            <span class="pngm-sec-social-meta">
              <strong>Facebook</strong>
              <span class="pngm-sec-badge<?php echo $facebook_on ? ' is-ok' : ''; ?>"><?php echo $facebook_on ? __('Connected', 'epsilon') : __('Not connected', 'epsilon'); ?></span>
            </span>
          </div>
          <form method="post" action="<?php echo osc_esc_html($sec_url); ?>">
            <input type="hidden" name="pngm_sec_action" value="connect_toggle" />
            <input type="hidden" name="provider" value="facebook" />
            <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
            <button type="submit" class="pngm-ua-btn is-ghost pngm-sec-btn"><?php echo $facebook_on ? __('Disconnect', 'epsilon') : __('Connect', 'epsilon'); ?></button>
          </form>
        </div>
      </section>
    </div>

    <div class="pngm-sec-col">
      <section class="pngm-sec-card" id="pngm-sec-sessions">
        <h2><span>5.</span> <?php _e('Active sessions', 'epsilon'); ?></h2>
        <ul class="pngm-sec-list">
          <?php foreach ($sessions as $s) {
              if (!is_array($s)) {
                  continue;
              }
              $is_current = !empty($s['current']);
              ?>
            <li class="pngm-sec-row">
              <div class="pngm-sec-row-copy">
                <strong><?php echo osc_esc_html((string) @$s['label']); ?></strong>
                <em>
                  <?php echo osc_esc_html((string) @$s['location']); ?>
                  ·
                  <?php echo $is_current ? __('Active now', 'epsilon') : osc_esc_html(pngm_sec_time_label((string) @$s['last_seen'])); ?>
                </em>
                <?php if ($is_current) { ?>
                  <span class="pngm-sec-badge is-ok"><?php _e('This device', 'epsilon'); ?></span>
                <?php } ?>
              </div>
              <?php if (!$is_current) { ?>
                <form method="post" action="<?php echo osc_esc_html($sec_url); ?>">
                  <input type="hidden" name="pngm_sec_action" value="revoke_session" />
                  <input type="hidden" name="session_id" value="<?php echo osc_esc_html((string) @$s['id']); ?>" />
                  <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
                  <button type="submit" class="pngm-ua-btn is-ghost pngm-sec-btn"><?php _e('Sign out', 'epsilon'); ?></button>
                </form>
              <?php } ?>
            </li>
          <?php } ?>
        </ul>
        <form method="post" action="<?php echo osc_esc_html($sec_url); ?>" class="pngm-sec-revoke-all">
          <input type="hidden" name="pngm_sec_action" value="revoke_other_sessions" />
          <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
          <button type="submit" class="pngm-ua-btn is-ghost is-block"><?php _e('Sign out of all other sessions', 'epsilon'); ?></button>
        </form>
      </section>

      <?php
        $pngm_limits = function_exists('pngm_antispam_public_status') ? pngm_antispam_public_status() : null;
        if (is_array($pngm_limits)) {
          $reg = $pngm_limits['registration'];
          $post = $pngm_limits['posting'];
          $msg = $pngm_limits['messaging'];
      ?>
      <section class="pngm-sec-card" id="pngm-sec-rate-limits">
        <div class="pngm-sec-card-top">
          <h2><?php _e('Rate limits & abuse protection', 'epsilon'); ?></h2>
          <span class="pngm-sec-badge is-ok"><?php _e('Active', 'epsilon'); ?></span>
        </div>
        <p class="pngm-sec-help"><?php _e('These protections run on registration, listing publish, and messaging.', 'epsilon'); ?></p>

        <div class="pngm-sec-limits">
          <article class="pngm-sec-limit<?php echo !empty($reg['active']) ? ' is-on' : ''; ?>">
            <span class="pngm-sec-limit-ico" aria-hidden="true"><i class="fas fa-user-plus"></i></span>
            <div class="pngm-sec-limit-body">
              <div class="pngm-sec-limit-top">
                <strong><?php _e('Registration', 'epsilon'); ?></strong>
                <span class="pngm-sec-limit-pill"><?php echo (int) $reg['max_per_hour']; ?>/hr</span>
              </div>
              <em><?php _e('Per IP address', 'epsilon'); ?><?php echo !empty($reg['captcha']) ? ' · CAPTCHA' : ''; ?></em>
            </div>
          </article>

          <article class="pngm-sec-limit<?php echo !empty($post['active']) ? ' is-on' : ''; ?>">
            <span class="pngm-sec-limit-ico is-post" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
            <div class="pngm-sec-limit-body">
              <div class="pngm-sec-limit-top">
                <strong><?php _e('Posting', 'epsilon'); ?></strong>
                <span class="pngm-sec-limit-pill"><?php echo (int) $post['max_per_hour']; ?>/hr</span>
              </div>
              <em><?php echo osc_esc_html(sprintf(
                  __('Wait %d–%d seconds between listings', 'epsilon'),
                  (int) $post['items_wait'],
                  (int) $post['min_seconds']
              )); ?></em>
            </div>
          </article>

          <article class="pngm-sec-limit<?php echo !empty($msg['active']) ? ' is-on' : ''; ?>">
            <span class="pngm-sec-limit-ico is-msg" aria-hidden="true"><i class="fas fa-comments"></i></span>
            <div class="pngm-sec-limit-body">
              <div class="pngm-sec-limit-top">
                <strong><?php _e('Messaging', 'epsilon'); ?></strong>
                <span class="pngm-sec-limit-pill"><?php echo (int) $msg['period_hours']; ?>h</span>
              </div>
              <em><?php echo osc_esc_html(sprintf(
                  __('%d messages or %d contacts (new accounts)', 'epsilon'),
                  (int) $msg['max_messages'],
                  (int) $msg['max_users']
              )); ?></em>
            </div>
          </article>
        </div>
      </section>
      <?php } ?>

      <?php
        $pngm_pwa = function_exists('pngm_pwa_public_status') ? pngm_pwa_public_status() : null;
        if (is_array($pngm_pwa)) {
      ?>
      <section class="pngm-sec-card" id="pngm-sec-pwa">
        <div class="pngm-sec-card-top">
          <h2><?php _e('App mode (PWA)', 'epsilon'); ?></h2>
          <span class="pngm-sec-badge<?php echo !empty($pngm_pwa['standalone_configured']) ? ' is-ok' : ''; ?>">
            <?php echo !empty($pngm_pwa['standalone_configured']) ? __('Standalone ready', 'epsilon') : __('Not ready', 'epsilon'); ?>
          </span>
        </div>
        <p class="pngm-sec-help"><?php _e('This is a Progressive Web App — not only a responsive mobile site. Install it on iPhone or Android for a full-screen app.', 'epsilon'); ?></p>

        <div class="pngm-sec-limits">
          <article class="pngm-sec-limit<?php echo !empty($pngm_pwa['standalone_configured']) ? ' is-on' : ''; ?>">
            <span class="pngm-sec-limit-ico" aria-hidden="true"><i class="fas fa-mobile-alt"></i></span>
            <div class="pngm-sec-limit-body">
              <div class="pngm-sec-limit-top">
                <strong><?php _e('Configured display', 'epsilon'); ?></strong>
                <span class="pngm-sec-limit-pill"><?php echo osc_esc_html((string) $pngm_pwa['display']); ?></span>
              </div>
              <em><?php _e('From web app manifest', 'epsilon'); ?></em>
            </div>
          </article>

          <article class="pngm-sec-limit">
            <span class="pngm-sec-limit-ico is-post" aria-hidden="true"><i class="fas fa-desktop"></i></span>
            <div class="pngm-sec-limit-body">
              <div class="pngm-sec-limit-top">
                <strong><?php _e('This session', 'epsilon'); ?></strong>
                <span class="pngm-sec-limit-pill" data-pngm-display-mode-label>browser</span>
              </div>
              <em><?php _e('Updates to “standalone” after Add to Home Screen', 'epsilon'); ?></em>
            </div>
          </article>

          <article class="pngm-sec-limit is-on">
            <span class="pngm-sec-limit-ico is-msg" aria-hidden="true"><i class="fas fa-download"></i></span>
            <div class="pngm-sec-limit-body">
              <div class="pngm-sec-limit-top">
                <strong><?php _e('Install on this device', 'epsilon'); ?></strong>
              </div>
              <em><?php _e('Android: Install app prompt. iPhone: Share → Add to Home Screen.', 'epsilon'); ?></em>
              <div class="pngm-sec-actions" style="margin-top:10px">
                <button type="button" class="pngm-ua-btn" data-pngm-pwa-install-btn><?php _e('Install / Add to Home Screen', 'epsilon'); ?></button>
              </div>
            </div>
          </article>
        </div>
      </section>
      <?php } ?>

      <section class="pngm-sec-card" id="pngm-sec-activity">
        <h2><?php _e('Recent security activity', 'epsilon'); ?></h2>
        <?php if (empty($activity)) { ?>
          <p class="pngm-sec-help"><?php _e('No recent security events yet. Password changes and sign-in updates will show up here.', 'epsilon'); ?></p>
        <?php } else { ?>
          <ul class="pngm-sec-activity">
            <?php foreach (array_slice($activity, 0, 6) as $ev) {
                if (!is_array($ev)) {
                    continue;
                }
                $icon = 'fas fa-shield-alt';
                $type = (string) @$ev['type'];
                if ($type === 'password') {
                    $icon = 'fas fa-key';
                } elseif ($type === 'email') {
                    $icon = 'fas fa-envelope';
                } elseif ($type === 'session') {
                    $icon = 'fas fa-desktop';
                } elseif ($type === 'social') {
                    $icon = 'fas fa-link';
                } elseif ($type === 'twofa') {
                    $icon = 'fas fa-mobile-alt';
                }
                ?>
              <li>
                <span class="pngm-sec-act-ico" aria-hidden="true"><i class="<?php echo osc_esc_html($icon); ?>"></i></span>
                <span class="pngm-sec-act-copy">
                  <strong><?php echo osc_esc_html((string) @$ev['label']); ?></strong>
                  <em><?php echo osc_esc_html(pngm_sec_time_label((string) @$ev['at'])); ?><?php if (!empty($ev['location'])) { echo ' · ' . osc_esc_html((string) $ev['location']); } ?></em>
                </span>
              </li>
            <?php } ?>
          </ul>
        <?php } ?>
      </section>

      <section class="pngm-sec-card is-danger" id="pngm-sec-danger">
        <h2><?php _e('Danger zone', 'epsilon'); ?></h2>
        <div class="pngm-sec-row">
          <div class="pngm-sec-row-copy">
            <strong><?php _e('Delete account', 'epsilon'); ?></strong>
            <em><?php _e('Permanently remove your account and listings. This cannot be undone.', 'epsilon'); ?></em>
          </div>
          <?php if (!function_exists('eps_is_demo') || !eps_is_demo()) { ?>
            <a class="pngm-ua-btn is-danger pngm-sec-btn" href="<?php echo osc_esc_html($delete_url); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete your account? This action cannot be undone', 'epsilon')); ?>?');"><?php _e('Delete account', 'epsilon'); ?></a>
          <?php } else { ?>
            <span class="pngm-ua-btn is-danger is-disabled" title="<?php echo osc_esc_html(__('You cannot do this on demo site', 'epsilon')); ?>"><?php _e('Delete account', 'epsilon'); ?></span>
          <?php } ?>
        </div>
      </section>
    </div>
  </div>
</div>

<script>
(function () {
  var pass = document.getElementById('pngm-sec-new-pass');
  var rules = document.getElementById('pngm-sec-pass-rules');
  if (pass && rules) {
    function check() {
      var v = String(pass.value || '');
      var map = {
        len: v.length >= 8,
        case: /[a-z]/.test(v) && /[A-Z]/.test(v),
        num: /\d/.test(v),
        sym: /[^A-Za-z0-9]/.test(v)
      };
      var items = rules.querySelectorAll('[data-rule]');
      var i;
      for (i = 0; i < items.length; i += 1) {
        var key = items[i].getAttribute('data-rule');
        items[i].classList.toggle('is-ok', !!map[key]);
      }
    }
    pass.addEventListener('input', check);
    check();
  }

  function panelByKey(key) {
    return document.getElementById(key === 'email' ? 'pngm-sec-email' : 'pngm-sec-password');
  }

  function setPanelOpen(panel, open) {
    if (!panel) return;
    panel.classList.toggle('is-collapsed', !open);
    panel.classList.toggle('is-open', open);
    panel.setAttribute('aria-hidden', open ? 'false' : 'true');
  }

  function closePanels(exceptKey) {
    ['password', 'email'].forEach(function (key) {
      if (exceptKey && key === exceptKey) return;
      setPanelOpen(panelByKey(key), false);
    });
  }

  document.querySelectorAll('[data-pngm-sec-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var key = btn.getAttribute('data-pngm-sec-open');
      var panel = panelByKey(key);
      if (!panel) return;
      closePanels(key);
      setPanelOpen(panel, true);
      panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      var first = panel.querySelector('input:not([disabled])');
      if (first) first.focus();
    });
  });

  document.querySelectorAll('[data-pngm-sec-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var key = btn.getAttribute('data-pngm-sec-close');
      setPanelOpen(panelByKey(key), false);
    });
  });

  var menuBtn = document.querySelector('[data-pngm-ua-menu="1"]');
  if (menuBtn) {
    menuBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var hamburger = document.querySelector('header .menu.btn');
      if (hamburger) {
        hamburger.click();
      } else {
        document.body.classList.add('pngm-ua-nav-open');
        var cover = document.getElementById('menu-cover');
        if (cover && window.jQuery) {
          window.jQuery(cover).stop(true, true).fadeIn(200);
        }
      }
    });
  }
})();
</script>
