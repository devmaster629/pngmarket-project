<?php
/**
 * PNG Market — Account & Security (route + helpers).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'account_security.php'
) {
    exit;
}

/**
 * @return string
 */
function pngm_sec_url()
{
    return osc_route_url('pngm-account-security');
}

/**
 * Preference bag for security extras (2FA flag, sessions, activity).
 *
 * @param int $user_id
 * @return array
 */
function pngm_sec_get($user_id)
{
    $user_id = (int) $user_id;
    $raw = osc_get_preference('user_' . $user_id, 'pngm_account_security');
    $data = array();
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    return array_merge(array(
        'twofa' => 0,
        'totp_secret' => '',
        'password_changed_at' => '',
        'connected' => array(
            'google' => 0,
            'facebook' => 0,
        ),
        'trusted_devices' => array(),
        'sessions' => array(),
        'activity' => array(),
    ), $data);
}

/**
 * @param int   $user_id
 * @param array $data
 */
function pngm_sec_save($user_id, $data)
{
    $user_id = (int) $user_id;
    $json = json_encode($data);
    $key = 'user_' . $user_id;
    osc_set_preference($key, $json, 'pngm_account_security', 'STRING');
    // Preference::replace() writes DB but does not refresh the in-memory cache.
    if (class_exists('Preference')) {
        Preference::newInstance()->set($key, $json, 'pngm_account_security');
    }
}

/**
 * Fingerprint for the current browser session.
 *
 * @return string
 */
function pngm_sec_session_fingerprint()
{
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    $ip = function_exists('osc_get_ip') ? (string) osc_get_ip() : '';
    return substr(hash('sha256', $ua . '|' . $ip . '|' . session_id()), 0, 16);
}

/**
 * Human label for current device.
 *
 * @return array
 */
function pngm_sec_current_device_meta()
{
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    $browser = __('Browser', 'epsilon');
    $os = __('Device', 'epsilon');

    if (stripos($ua, 'Edg/') !== false) {
        $browser = 'Edge';
    } elseif (stripos($ua, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) {
        $browser = 'Safari';
    } elseif (stripos($ua, 'Firefox') !== false) {
        $browser = 'Firefox';
    }

    if (stripos($ua, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (stripos($ua, 'Android') !== false) {
        $os = 'Android';
    } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
        $os = 'iOS';
    } elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) {
        $os = 'macOS';
    } elseif (stripos($ua, 'Linux') !== false) {
        $os = 'Linux';
    }

    $loc = '';
    if (function_exists('osc_get_ip')) {
        $loc = (string) osc_get_ip();
    }

    return array(
        'id' => pngm_sec_session_fingerprint(),
        'label' => sprintf('%s on %s', $browser, $os),
        'location' => $loc !== '' ? $loc : __('Unknown location', 'epsilon'),
        'last_seen' => date('Y-m-d H:i:s'),
        'current' => 1,
    );
}

/**
 * Ensure current session is tracked and returned.
 *
 * @param int $user_id
 * @return array
 */
function pngm_sec_touch_session($user_id)
{
    $data = pngm_sec_get($user_id);
    $current = pngm_sec_current_device_meta();
    $found = false;
    $sessions = array();

    foreach ((array) $data['sessions'] as $s) {
        if (!is_array($s) || empty($s['id'])) {
            continue;
        }
        if ($s['id'] === $current['id']) {
            $s = array_merge($s, $current);
            $found = true;
        } else {
            $s['current'] = 0;
        }
        $sessions[] = $s;
    }

    if (!$found) {
        array_unshift($sessions, $current);
    }

    // Cap stored sessions
    $sessions = array_slice($sessions, 0, 8);
    $data['sessions'] = $sessions;
    pngm_sec_save($user_id, $data);

    return $sessions;
}

/**
 * Append a security activity row.
 *
 * @param int    $user_id
 * @param string $type
 * @param string $label
 */
function pngm_sec_log_activity($user_id, $type, $label)
{
    $data = pngm_sec_get($user_id);
    $row = array(
        'type' => (string) $type,
        'label' => (string) $label,
        'at' => date('Y-m-d H:i:s'),
        'location' => function_exists('osc_get_ip') ? (string) osc_get_ip() : '',
    );
    $activity = isset($data['activity']) && is_array($data['activity']) ? $data['activity'] : array();
    array_unshift($activity, $row);
    $data['activity'] = array_slice($activity, 0, 20);
    pngm_sec_save($user_id, $data);
}

/**
 * Relative time label.
 *
 * @param string $datetime
 * @return string
 */
function pngm_sec_time_label($datetime)
{
    $datetime = (string) $datetime;
    if ($datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    if (!$ts) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return __('Just now', 'epsilon');
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return sprintf(_n('%d minute ago', '%d minutes ago', $m, 'epsilon'), $m);
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return sprintf(_n('%d hour ago', '%d hours ago', $h, 'epsilon'), $h);
    }
    if ($diff < 86400 * 7) {
        $d = (int) floor($diff / 86400);
        return sprintf(_n('%d day ago', '%d days ago', $d, 'epsilon'), $d);
    }
    return date('M j, Y', $ts);
}

/**
 * @return string
 */
function pngm_sec_2fa_url()
{
    return osc_route_url('pngm-twofa-verify');
}

/**
 * Cookie name for trusted browser token.
 *
 * @return string
 */
function pngm_sec_2fa_cookie_name()
{
    return 'pngm_2fa_td';
}

/**
 * Mask an email for the verify screen.
 *
 * @param string $email
 * @return string
 */
function pngm_sec_mask_email($email)
{
    $email = trim((string) $email);
    $at = strpos($email, '@');
    if ($at === false || $at < 1) {
        return $email;
    }
    $local = substr($email, 0, $at);
    $domain = substr($email, $at + 1);
    $keep = min(2, max(1, (int) floor(strlen($local) / 3)));
    return substr($local, 0, $keep) . str_repeat('*', max(3, strlen($local) - $keep)) . '@' . $domain;
}

/**
 * Soft-clear web login without destroying the whole PHP session.
 */
function pngm_sec_soft_logout()
{
    Session::newInstance()->_drop('userId');
    Session::newInstance()->_drop('userName');
    Session::newInstance()->_drop('userEmail');
    Session::newInstance()->_drop('userPhone');
    Cookie::newInstance()->pop('oc_userId');
    Cookie::newInstance()->pop('oc_userSecret');
    Cookie::newInstance()->set();
}

/**
 * Whether the current browser is a trusted device for this user.
 *
 * @param int $user_id
 * @return bool
 */
function pngm_sec_device_is_trusted($user_id)
{
    $user_id = (int) $user_id;
    $token = Cookie::newInstance()->_get(pngm_sec_2fa_cookie_name());
    if ($token === '' || $token === null) {
        return false;
    }
    $hash = hash('sha256', (string) $token);
    $data = pngm_sec_get($user_id);
    $devices = isset($data['trusted_devices']) && is_array($data['trusted_devices']) ? $data['trusted_devices'] : array();
    $now = time();
    $max_age = 180 * 86400;
    foreach ($devices as $d) {
        if (!is_array($d) || empty($d['hash'])) {
            continue;
        }
        $at = !empty($d['at']) ? strtotime((string) $d['at']) : 0;
        if ($at && ($now - $at) > $max_age) {
            continue;
        }
        if (hash_equals((string) $d['hash'], $hash)) {
            return true;
        }
    }
    return false;
}

/**
 * Mark the current browser as trusted (sets cookie + stores hash).
 *
 * @param int $user_id
 */
function pngm_sec_trust_current_device($user_id)
{
    $user_id = (int) $user_id;
    $token = osc_genRandomPassword(48);
    $meta = pngm_sec_current_device_meta();
    $data = pngm_sec_get($user_id);
    $devices = isset($data['trusted_devices']) && is_array($data['trusted_devices']) ? $data['trusted_devices'] : array();

    array_unshift($devices, array(
        'hash' => hash('sha256', $token),
        'label' => (string) $meta['label'],
        'at' => date('Y-m-d H:i:s'),
    ));
    $data['trusted_devices'] = array_slice($devices, 0, 10);
    pngm_sec_save($user_id, $data);

    Cookie::newInstance()->push(pngm_sec_2fa_cookie_name(), $token, true);
    Cookie::newInstance()->set();
}

/**
 * Clear all trusted devices and cookie.
 *
 * @param int $user_id
 */
function pngm_sec_clear_trusted_devices($user_id)
{
    $data = pngm_sec_get($user_id);
    $data['trusted_devices'] = array();
    pngm_sec_save($user_id, $data);
    Cookie::newInstance()->pop(pngm_sec_2fa_cookie_name());
    Cookie::newInstance()->set();
}

/**
 * Clear pending 2FA challenge session keys.
 */
function pngm_sec_2fa_clear_pending()
{
    $keys = array(
        'pngm_2fa_uid',
        'pngm_2fa_exp',
        'pngm_2fa_tries',
        'pngm_2fa_remember',
        'pngm_2fa_redirect',
        'pngm_2fa_method',
        'pngm_2fa_setup_secret',
    );
    foreach ($keys as $k) {
        Session::newInstance()->_drop($k);
    }
}

/**
 * @return bool
 */
function pngm_sec_twofa_is_enabled($user_id)
{
    $sec = pngm_sec_get((int) $user_id);
    return !empty($sec['twofa']) && !empty($sec['totp_secret']);
}

/**
 * RFC 4648 Base32 encode (no padding) for authenticator secrets.
 *
 * @param string $data
 * @return string
 */
function pngm_sec_base32_encode($data)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = (string) $data;
    $binary = '';
    $len = strlen($data);
    for ($i = 0; $i < $len; $i++) {
        $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($binary, 5) as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $out .= $alphabet[bindec($chunk)];
    }
    return $out;
}

/**
 * @param string $b32
 * @return string Binary secret or empty on failure
 */
function pngm_sec_base32_decode($b32)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string) $b32));
    if ($b32 === '') {
        return '';
    }
    $binary = '';
    $len = strlen($b32);
    for ($i = 0; $i < $len; $i++) {
        $val = strpos($alphabet, $b32[$i]);
        if ($val === false) {
            return '';
        }
        $binary .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $out .= chr(bindec($chunk));
        }
    }
    return $out;
}

/**
 * @return string Base32 secret
 */
function pngm_sec_totp_new_secret()
{
    return pngm_sec_base32_encode(random_bytes(20));
}

/**
 * @param string   $secret Base32
 * @param int|null $counter
 * @return string 6-digit code
 */
function pngm_sec_totp_code($secret, $counter = null)
{
    $key = pngm_sec_base32_decode($secret);
    if ($key === '') {
        return '';
    }
    if ($counter === null) {
        $counter = (int) floor(time() / 30);
    }
    $bin_counter = pack('N*', 0, $counter);
    $hash = hash_hmac('sha1', $bin_counter, $key, true);
    $offset = ord($hash[19]) & 0x0f;
    $truncated = (
        ((ord($hash[$offset]) & 0x7f) << 24)
        | ((ord($hash[$offset + 1]) & 0xff) << 16)
        | ((ord($hash[$offset + 2]) & 0xff) << 8)
        | (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    return str_pad((string) $truncated, 6, '0', STR_PAD_LEFT);
}

/**
 * @param string $secret
 * @param string $code
 * @param int    $window
 * @return bool
 */
function pngm_sec_totp_verify($secret, $code, $window = 1)
{
    $code = preg_replace('/\D+/', '', (string) $code);
    if (strlen($code) !== 6 || $secret === '') {
        return false;
    }
    $counter = (int) floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        $expect = pngm_sec_totp_code($secret, $counter + $i);
        if ($expect !== '' && hash_equals($expect, $code)) {
            return true;
        }
    }
    return false;
}

/**
 * otpauth:// URI for Google Authenticator / Authy / etc.
 *
 * @param string $secret
 * @param string $account
 * @return string
 */
function pngm_sec_totp_otpauth_uri($secret, $account)
{
    $issuer = osc_page_title();
    $label = rawurlencode($issuer . ':' . $account);
    return 'otpauth://totp/' . $label
        . '?secret=' . rawurlencode($secret)
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}

/**
 * QR image URL for the otpauth URI.
 *
 * @param string $otpauth
 * @return string
 */
function pngm_sec_totp_qr_url($otpauth)
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&ecc=M&data=' . rawurlencode($otpauth);
}

/**
 * @return string|null Pending setup secret
 */
function pngm_sec_2fa_setup_secret()
{
    $secret = (string) Session::newInstance()->_get('pngm_2fa_setup_secret');
    return $secret !== '' ? $secret : null;
}

/**
 * Start authenticator setup (does not enable until confirmed).
 *
 * @return string Secret
 */
function pngm_sec_2fa_begin_setup()
{
    $secret = pngm_sec_totp_new_secret();
    Session::newInstance()->_set('pngm_2fa_setup_secret', $secret);
    return $secret;
}

/**
 * @return array|null Pending login challenge
 */
function pngm_sec_2fa_pending()
{
    $uid = (int) Session::newInstance()->_get('pngm_2fa_uid');
    $exp = (int) Session::newInstance()->_get('pngm_2fa_exp');
    if ($uid <= 0 || $exp < time()) {
        return null;
    }
    if (!pngm_sec_twofa_is_enabled($uid)) {
        return null;
    }
    return array(
        'uid' => $uid,
        'exp' => $exp,
        'tries' => (int) Session::newInstance()->_get('pngm_2fa_tries'),
        'remember' => (int) Session::newInstance()->_get('pngm_2fa_remember'),
        'redirect' => (string) Session::newInstance()->_get('pngm_2fa_redirect'),
        'method' => (string) Session::newInstance()->_get('pngm_2fa_method'),
    );
}

/**
 * Create a login challenge that requires an authenticator code.
 *
 * @param array  $user
 * @param bool   $remember
 * @param string $redirect
 * @return bool
 */
function pngm_sec_2fa_start_challenge($user, $remember = false, $redirect = '', $method = '')
{
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return false;
    }
    if (!pngm_sec_twofa_is_enabled((int) $user['pk_i_id'])) {
        return false;
    }

    if ($redirect === '') {
        $redirect = (string) osc_get_http_referer();
    }
    if ($redirect === '' || stripos($redirect, 'login') !== false || stripos($redirect, 'two-step') !== false) {
        $redirect = osc_user_dashboard_url();
    }
    if ($method === '' && function_exists('pngm_persist_detect_login_method')) {
        $method = pngm_persist_detect_login_method();
    }
    if ($method === '' || $method === 'unknown') {
        $method = 'password';
    }

    Session::newInstance()->_set('pngm_2fa_uid', (int) $user['pk_i_id']);
    Session::newInstance()->_set('pngm_2fa_exp', time() + 900);
    Session::newInstance()->_set('pngm_2fa_tries', 0);
    Session::newInstance()->_set('pngm_2fa_remember', $remember ? 1 : 0);
    Session::newInstance()->_set('pngm_2fa_redirect', $redirect);
    Session::newInstance()->_set('pngm_2fa_method', $method);
    return true;
}

/**
 * Complete login after a valid authenticator code.
 *
 * @param array $pending
 * @return string Redirect URL
 */
function pngm_sec_2fa_complete_login($pending)
{
    $user_id = (int) $pending['uid'];
    $user = User::newInstance()->findByPrimaryKey($user_id);
    if (!is_array($user)) {
        pngm_sec_2fa_clear_pending();
        return osc_user_login_url();
    }

    require_once LIB_PATH . 'osclass/UserActions.php';
    $uActions = new UserActions(false);
    $logged = $uActions->bootstrap_login($user_id);

    if ($logged !== 3) {
        pngm_sec_2fa_clear_pending();
        return osc_user_login_url();
    }

    // Always persist on this device (constant login), including after social + 2FA.
    $ok = false;
    if (function_exists('pngm_persist_web_login')) {
        $ok = pngm_persist_web_login($user);
    } else {
        if ($user['s_secret'] == '') {
            require_once osc_lib_path() . 'osclass/helpers/hSecurity.php';
            $secret = osc_genRandomPassword();
            User::newInstance()->update(array('s_secret' => $secret), array('pk_i_id' => $user_id));
            $user['s_secret'] = $secret;
        }
        Cookie::newInstance()->push('oc_userId', $user['pk_i_id']);
        Cookie::newInstance()->push('oc_userSecret', $user['s_secret']);
        Cookie::newInstance()->set();
        $ok = true;
    }

    $method = !empty($pending['method']) ? (string) $pending['method'] : 'twofa';
    if (function_exists('pngm_persist_record_login')) {
        pngm_persist_record_login($user_id, $method, $ok);
    }
    Session::newInstance()->_set('pngm_persist_done', '1');

    pngm_sec_trust_current_device($user_id);
    pngm_sec_touch_session($user_id);
    pngm_sec_log_activity($user_id, 'twofa', __('Signed in with two-step verification', 'epsilon'));
    pngm_sec_2fa_clear_pending();

    $redirect = !empty($pending['redirect']) ? (string) $pending['redirect'] : osc_user_dashboard_url();
    Session::newInstance()->_set('pngm_2fa_just_done', '1');
    osc_run_hook('after_login', $user, $redirect);

    return osc_apply_filter('correct_login_url_redirect', $redirect);
}

/**
 * Intercept password login when 2FA is on and device is not trusted.
 */
function pngm_sec_before_login_gate()
{
    $email = trim((string) Params::getParam('email'));
    if ($email === '') {
        return;
    }

    $user = null;
    if (osc_validate_email($email)) {
        $user = User::newInstance()->findByEmail($email);
    }
    if (empty($user)) {
        $user = User::newInstance()->findByUsername($email);
    }
    if (empty($user) || empty($user['pk_i_id'])) {
        return;
    }

    $uid = (int) $user['pk_i_id'];
    if (!pngm_sec_twofa_is_enabled($uid)) {
        return;
    }
    if (pngm_sec_device_is_trusted($uid)) {
        return;
    }

    $remember = Params::getParam('remember') == 1;
    pngm_sec_2fa_start_challenge($user, $remember, (string) osc_get_http_referer());
    osc_add_flash_ok_message(__('Enter the 6-digit code from your authenticator app to finish signing in.', 'epsilon'));
    header('Location: ' . pngm_sec_2fa_url());
    exit;
}
osc_add_hook('before_login', 'pngm_sec_before_login_gate');

/**
 * Intercept social / plugin logins that already bootstrapped the session.
 *
 * @param array  $user
 * @param string $url_redirect
 */
function pngm_sec_after_login_gate($user, $url_redirect = '')
{
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return;
    }
    if (Session::newInstance()->_get('pngm_2fa_just_done') === '1') {
        Session::newInstance()->_drop('pngm_2fa_just_done');
        return;
    }

    $uid = (int) $user['pk_i_id'];
    if (!pngm_sec_twofa_is_enabled($uid)) {
        if (function_exists('pngm_sec_touch_session')) {
            pngm_sec_touch_session($uid);
        }
        return;
    }
    if (pngm_sec_device_is_trusted($uid)) {
        pngm_sec_touch_session($uid);
        return;
    }

    pngm_sec_soft_logout();
    // Social logins have no Remember checkbox — always persist after 2FA succeeds.
    $method = function_exists('pngm_persist_detect_login_method') ? pngm_persist_detect_login_method() : 'unknown';
    if ($method === 'unknown' || $method === 'password') {
        // Prefer social if this request looks like an OAuth callback.
        if (Params::getParam('fjlRedirect') == 1) {
            $method = 'facebook';
        } elseif (Params::getParam('gglLogin') == 1 || Params::getParam('route') === 'ggl-redirect') {
            $method = 'google';
        }
    }
    pngm_sec_2fa_start_challenge($user, true, (string) $url_redirect, $method);
    osc_add_flash_ok_message(__('Enter the 6-digit code from your authenticator app to finish signing in.', 'epsilon'));
    header('Location: ' . pngm_sec_2fa_url());
    exit;
}
osc_add_hook('after_login', 'pngm_sec_after_login_gate');

/**
 * Register front routes.
 */
function pngm_sec_register_route()
{
    if (!function_exists('osc_add_route')) {
        return;
    }
    osc_add_route(
        'pngm-account-security',
        'user/account-security/?',
        'user/account-security',
        'custom/account-security.php',
        true,
        'custom',
        'pngm-sec',
        __('Account & Security', 'epsilon')
    );
    osc_add_route(
        'pngm-twofa-verify',
        'user/two-step/?',
        'user/two-step',
        'custom/twofa-verify.php',
        false,
        'custom',
        'pngm-twofa',
        __('Two-step verification', 'epsilon')
    );
}
osc_add_hook('init', 'pngm_sec_register_route', 5);
