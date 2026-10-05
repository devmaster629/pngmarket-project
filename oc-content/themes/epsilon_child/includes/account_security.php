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
 * Real Google / Facebook link rows from the login plugins (not preference flags).
 *
 * @param int $user_id
 * @return array{google:array,facebook:array}
 */
function pngm_sec_social_status($user_id)
{
    $user_id = (int) $user_id;
    $out = array(
        'google' => array(
            'linked' => false,
            'email' => '',
            'name' => '',
            'available' => function_exists('ggl_login_link'),
        ),
        'facebook' => array(
            'linked' => false,
            'email' => '',
            'name' => '',
            // Connect needs App Secret for verification — not just App ID.
            'available' => function_exists('pngm_facebook_login_ready')
                ? pngm_facebook_login_ready()
                : (function_exists('fjl_param')
                    && (int) fjl_param('enabled') === 1
                    && trim((string) fjl_param('app_id')) !== ''
                    && trim((string) fjl_param('app_secret')) !== ''),
        ),
    );

    if ($user_id < 1) {
        return $out;
    }

    if (class_exists('ModelGGL')) {
        $row = ModelGGL::newInstance()->getUser($user_id);
        if (is_array($row) && !empty($row['s_oauth_uid'])) {
            $out['google']['linked'] = true;
            $out['google']['email'] = isset($row['s_email']) ? (string) $row['s_email'] : '';
            $first = isset($row['s_first_name']) ? (string) $row['s_first_name'] : '';
            $last = isset($row['s_last_name']) ? (string) $row['s_last_name'] : '';
            $out['google']['name'] = trim($first . ' ' . $last);
        }
    }

    if (class_exists('ModelFJL')) {
        $row = ModelFJL::newInstance()->getUserFBDataByUserId($user_id);
        if (is_array($row) && !empty($row['s_oauth_uid'])) {
            $out['facebook']['linked'] = true;
            $out['facebook']['email'] = isset($row['s_email']) ? (string) $row['s_email'] : '';
            $out['facebook']['name'] = isset($row['s_name']) ? (string) $row['s_name'] : '';
        }
    }

    return $out;
}

/**
 * Drop a provider link row for this Osclass user.
 *
 * @param int    $user_id
 * @param string $provider google|facebook
 * @return bool
 */
function pngm_sec_social_unlink($user_id, $provider)
{
    $user_id = (int) $user_id;
    if ($user_id < 1) {
        return false;
    }

    if ($provider === 'google' && class_exists('ModelGGL')) {
        $m = ModelGGL::newInstance();
        $m->dao->delete($m->getTable_user_ggl(), array('fk_i_user_id' => $user_id));
        return true;
    }

    if ($provider === 'facebook' && class_exists('ModelFJL')) {
        $m = ModelFJL::newInstance();
        $m->dao->delete($m->getTable_facebook(), array('fk_i_user_id' => $user_id));
        return true;
    }

    return false;
}

/**
 * Remember that the next OAuth success should return to Account & Security.
 *
 * @param string $provider
 * @param int    $user_id
 */
function pngm_sec_social_begin_link($provider, $user_id)
{
    Session::newInstance()->_set('pngm_sec_link_provider', $provider);
    Session::newInstance()->_set('pngm_sec_link_uid', (int) $user_id);
    Session::newInstance()->_set('pngm_sec_link_return', pngm_sec_url());
}

/**
 * @return bool
 */
function pngm_sec_social_has_link_intent()
{
    return Session::newInstance()->_get('pngm_sec_link_provider') !== ''
        && Session::newInstance()->_get('pngm_sec_link_provider') !== null
        && (int) Session::newInstance()->_get('pngm_sec_link_uid') > 0;
}

/**
 * @return string
 */
function pngm_sec_social_link_return_url()
{
    $url = (string) Session::newInstance()->_get('pngm_sec_link_return');
    return $url !== '' ? $url : pngm_sec_url();
}

/**
 * Clear link-intent session keys.
 */
function pngm_sec_social_clear_link_intent()
{
    Session::newInstance()->_drop('pngm_sec_link_provider');
    Session::newInstance()->_drop('pngm_sec_link_uid');
    Session::newInstance()->_drop('pngm_sec_link_return');
}

/**
 * Attach a Google OAuth row to $user_id (replaces any prior row for that user).
 *
 * @param int   $user_id
 * @param array $row
 * @return bool
 */
function pngm_sec_social_attach_google($user_id, $row)
{
    $user_id = (int) $user_id;
    if ($user_id < 1 || !is_array($row) || empty($row['s_oauth_uid']) || !class_exists('ModelGGL')) {
        return false;
    }
    $m = ModelGGL::newInstance();
    $existing = $m->getUser($user_id);
    $payload = array(
        'fk_i_user_id' => $user_id,
        's_oauth_provider' => isset($row['s_oauth_provider']) ? $row['s_oauth_provider'] : 'google',
        's_oauth_uid' => (string) $row['s_oauth_uid'],
        's_first_name' => isset($row['s_first_name']) ? $row['s_first_name'] : '',
        's_last_name' => isset($row['s_last_name']) ? $row['s_last_name'] : '',
        's_email' => isset($row['s_email']) ? $row['s_email'] : '',
        's_gender' => isset($row['s_gender']) ? $row['s_gender'] : '',
        's_locale' => isset($row['s_locale']) ? $row['s_locale'] : '',
        's_picture' => isset($row['s_picture']) ? $row['s_picture'] : '',
        's_link' => isset($row['s_link']) ? $row['s_link'] : '',
        'dt_modified' => date('Y-m-d H:i:s'),
        'dt_created' => (is_array($existing) && !empty($existing['dt_created']))
            ? $existing['dt_created']
            : date('Y-m-d H:i:s'),
    );
    $m->dao->replace($m->getTable_user_ggl(), $payload);
    return true;
}

/**
 * Attach a Facebook OAuth row to $user_id.
 *
 * @param int   $user_id
 * @param array $row
 * @return bool
 */
function pngm_sec_social_attach_facebook($user_id, $row)
{
    $user_id = (int) $user_id;
    if ($user_id < 1 || !is_array($row) || empty($row['s_oauth_uid']) || !class_exists('ModelFJL')) {
        return false;
    }
    $m = ModelFJL::newInstance();
    $existing = $m->getUserFBDataByUserId($user_id);
    $payload = array(
        'fk_i_user_id' => $user_id,
        's_oauth_provider' => isset($row['s_oauth_provider']) ? $row['s_oauth_provider'] : 'facebook',
        's_oauth_uid' => (string) $row['s_oauth_uid'],
        's_name' => isset($row['s_name']) ? $row['s_name'] : '',
        's_email' => isset($row['s_email']) ? $row['s_email'] : '',
        's_picture' => isset($row['s_picture']) ? $row['s_picture'] : '',
        'dt_modified' => date('Y-m-d H:i:s'),
        'dt_created' => (is_array($existing) && !empty($existing['dt_created']))
            ? $existing['dt_created']
            : date('Y-m-d H:i:s'),
    );
    $m->dao->replace($m->getTable_facebook(), $payload);
    return true;
}

/**
 * Restore an Osclass web session for $user_id after a mis-routed OAuth login.
 *
 * @param int $user_id
 * @return bool
 */
function pngm_sec_social_restore_session($user_id)
{
    $user_id = (int) $user_id;
    $user = User::newInstance()->findByPrimaryKey($user_id);
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return false;
    }
    Session::newInstance()->_set('userId', $user['pk_i_id']);
    Session::newInstance()->_set('userName', $user['s_name']);
    Session::newInstance()->_set('userEmail', $user['s_email']);
    Session::newInstance()->_set('userPhone', ($user['s_phone_mobile'] ? $user['s_phone_mobile'] : $user['s_phone_land']));
    if (function_exists('pngm_persist_web_login')) {
        pngm_persist_web_login($user);
    }
    return true;
}

/**
 * After Google OAuth, keep the link on the Account & Security user who started Connect.
 *
 * @param array  $user
 * @param string $url_redirect
 */
function pngm_sec_social_after_login_link($user, $url_redirect = '')
{
    if (!pngm_sec_social_has_link_intent() || !is_array($user) || empty($user['pk_i_id'])) {
        return;
    }

    $provider = (string) Session::newInstance()->_get('pngm_sec_link_provider');
    $intended = (int) Session::newInstance()->_get('pngm_sec_link_uid');
    $return = pngm_sec_social_link_return_url();
    pngm_sec_social_clear_link_intent();

    if ($intended < 1 || ($provider !== 'google' && $provider !== 'facebook')) {
        return;
    }

    $oauth_uid = (int) $user['pk_i_id'];
    $label = $provider === 'google' ? 'Google' : 'Facebook';

    if ($oauth_uid === $intended) {
        pngm_sec_log_activity($intended, 'social', sprintf(__('%s connected', 'epsilon'), $label));
        osc_add_flash_ok_message(sprintf(__('%s account connected', 'epsilon'), $label));
        header('Location: ' . $return);
        exit;
    }

    // OAuth resolved to a different Osclass user — move the provider row only when safe.
    $row = null;
    $owner_of_oauth = null;
    if ($provider === 'google' && class_exists('ModelGGL')) {
        $row = ModelGGL::newInstance()->getUser($oauth_uid);
        if (is_array($row) && !empty($row['s_oauth_uid'])) {
            $owner_of_oauth = ModelGGL::newInstance()->getUserByAuthId($row['s_oauth_uid']);
        }
    } elseif ($provider === 'facebook' && class_exists('ModelFJL')) {
        $row = ModelFJL::newInstance()->getUserFBDataByUserId($oauth_uid);
        if (is_array($row) && !empty($row['s_oauth_uid'])) {
            $owner_of_oauth = ModelFJL::newInstance()->getUserFBDataByAuthId($row['s_oauth_uid']);
        }
    }

    if (!is_array($row) || empty($row['s_oauth_uid'])) {
        pngm_sec_social_restore_session($intended);
        osc_add_flash_error_message(sprintf(__('Could not connect %s. Please try again.', 'epsilon'), $label));
        header('Location: ' . $return);
        exit;
    }

    $owner_id = is_array($owner_of_oauth) ? (int) @$owner_of_oauth['fk_i_user_id'] : 0;
    $other = User::newInstance()->findByPrimaryKey($oauth_uid);
    $just_created = is_array($other) && !empty($other['dt_reg_date'])
        && (time() - strtotime($other['dt_reg_date']) < 180);

    // Refuse to steal a provider identity that already belongs to another lasting account.
    if ($owner_id > 0 && $owner_id !== $intended && !$just_created) {
        pngm_sec_social_restore_session($intended);
        osc_add_flash_error_message(
            sprintf(__('This %s account is already linked to another PNGMarket user.', 'epsilon'), $label)
        );
        header('Location: ' . $return);
        exit;
    }

    if ($provider === 'google') {
        pngm_sec_social_attach_google($intended, $row);
        pngm_sec_social_unlink($oauth_uid, 'google');
    } else {
        pngm_sec_social_attach_facebook($intended, $row);
        pngm_sec_social_unlink($oauth_uid, 'facebook');
    }

    pngm_sec_social_restore_session($intended);
    pngm_sec_log_activity($intended, 'social', sprintf(__('%s connected', 'epsilon'), $label));
    osc_add_flash_ok_message(sprintf(__('%s account connected', 'epsilon'), $label));
    header('Location: ' . $return);
    exit;
}
// After 2FA gate (default 5) and persist (9): settle Account & Security linking.
osc_add_hook('after_login', 'pngm_sec_social_after_login_link', 15);

/**
 * When Connect started OAuth, land back on Account & Security instead of homepage.
 *
 * @param string $url
 * @return string
 */
function pngm_sec_social_login_redirect($url)
{
    if (pngm_sec_social_has_link_intent()) {
        return pngm_sec_social_link_return_url();
    }
    return $url;
}
osc_add_filter('correct_login_url_redirect', 'pngm_sec_social_login_redirect', 5);

/**
 * HTTP GET helper for Facebook Graph calls.
 *
 * @param string $url
 * @return string
 */
function pngm_sec_social_http_get($url)
{
    $url = (string) $url;
    if ($url === '') {
        return '';
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PNGMarket-FacebookLink/1.0',
        ));
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) {
            return '';
        }
        return (string) $body;
    }
    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 15,
            'header' => "User-Agent: PNGMarket-FacebookLink/1.0\r\n",
        ),
    ));
    $body = @file_get_contents($url, false, $ctx);
    return is_string($body) ? $body : '';
}

/**
 * Verify Facebook access token with Graph /me and return profile fields.
 *
 * @param string $access_token
 * @return array|string Profile array on success, error message on failure.
 */
function pngm_sec_social_facebook_graph_me($access_token)
{
    $access_token = trim((string) $access_token);
    if ($access_token === '') {
        return __('Facebook did not return an access token. Please try again.', 'epsilon');
    }
    $url = 'https://graph.facebook.com/me?' . http_build_query(array(
        'fields' => 'id,name,email,picture.type(large)',
        'access_token' => $access_token,
    ));
    $raw = pngm_sec_social_http_get($url);
    if ($raw === '') {
        return __('Could not reach Facebook to verify your login. Please try again.', 'epsilon');
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['id'])) {
        $msg = isset($data['error']['message']) ? (string) $data['error']['message'] : '';
        if ($msg !== '') {
            return sprintf(__('Facebook verification failed: %s', 'epsilon'), $msg);
        }
        return __('Facebook login could not be verified.', 'epsilon');
    }
    return $data;
}

/**
 * Verify Facebook signedRequest HMAC (optional secondary check).
 *
 * @param string $signed_request
 * @param string $app_secret
 * @return bool
 */
function pngm_sec_social_facebook_signed_ok($signed_request, $app_secret)
{
    $signed_request = (string) $signed_request;
    $app_secret = (string) $app_secret;
    if ($signed_request === '' || $app_secret === '') {
        return false;
    }
    $parts = explode('.', $signed_request, 2);
    if (count($parts) !== 2) {
        return false;
    }
    $sig = strtr($parts[0], '-_', '+/');
    $pad = strlen($sig) % 4;
    if ($pad > 0) {
        $sig .= str_repeat('=', 4 - $pad);
    }
    $expected = hash_hmac('sha256', $parts[1], $app_secret, true);
    $got = base64_decode($sig, true);
    return ($got !== false && hash_equals($expected, $got));
}

/**
 * Link Facebook to the currently logged-in user (no fake flag, no account switch).
 * Expects POSTed Instant Login payload fields from the security page SDK bridge.
 *
 * @param int $user_id
 * @return true|string True on success, or error message.
 */
function pngm_sec_social_link_facebook_logged_in($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id < 1 || !function_exists('fjl_param') || !class_exists('ModelFJL')) {
        return __('Facebook login is not available.', 'epsilon');
    }
    if ((int) fjl_param('enabled') !== 1
        || trim((string) fjl_param('app_id')) === ''
        || trim((string) fjl_param('app_secret')) === ''
    ) {
        return __('Facebook login is not configured yet.', 'epsilon');
    }

    $auth_raw = Params::getParam('pngm_fb_auth');
    // Prefer raw POST — Params may entity-encode JSON quotes.
    if (isset($_POST['pngm_fb_auth']) && is_string($_POST['pngm_fb_auth'])) {
        $auth_raw = $_POST['pngm_fb_auth'];
    }
    $auth = json_decode((string) $auth_raw, true);
    if (!is_array($auth) || empty($auth['authResponse']) || !is_array($auth['authResponse'])) {
        return __('Facebook did not return a valid login. Please try again.', 'epsilon');
    }

    $ar = $auth['authResponse'];
    $oauth_uid = isset($ar['userID']) ? (string) $ar['userID'] : '';
    $access_token = isset($ar['accessToken']) ? (string) $ar['accessToken'] : '';
    $signed_request = isset($ar['signedRequest']) ? (string) $ar['signedRequest'] : '';

    if ($oauth_uid === '' || $access_token === '') {
        return __('Facebook did not return a valid login. Please try again.', 'epsilon');
    }

    // Primary verification: ask Facebook Graph with the user access token.
    $me = pngm_sec_social_facebook_graph_me($access_token);
    if (!is_array($me)) {
        return is_string($me) ? $me : __('Facebook login could not be verified.', 'epsilon');
    }
    if ((string) $me['id'] !== $oauth_uid) {
        return __('Facebook login could not be verified.', 'epsilon');
    }

    // Secondary: signedRequest HMAC when present (ignore soft failures if Graph already OK).
    if ($signed_request !== '' && !pngm_sec_social_facebook_signed_ok($signed_request, (string) fjl_param('app_secret'))) {
        // Graph /me already proved the token — continue.
    }

    $owner = ModelFJL::newInstance()->getUserFBDataByAuthId($oauth_uid);
    if (is_array($owner) && (int) @$owner['fk_i_user_id'] > 0 && (int) $owner['fk_i_user_id'] !== $user_id) {
        return __('This Facebook account is already linked to another PNGMarket user.', 'epsilon');
    }

    $picture = '';
    if (isset($me['picture']['data']['url'])) {
        $picture = (string) $me['picture']['data']['url'];
    }

    $ok = pngm_sec_social_attach_facebook($user_id, array(
        's_oauth_provider' => 'facebook',
        's_oauth_uid' => $oauth_uid,
        's_name' => isset($me['name']) ? (string) $me['name'] : '',
        's_email' => isset($me['email']) ? (string) $me['email'] : '',
        's_picture' => $picture,
    ));

    return $ok ? true : __('Could not save Facebook connection.', 'epsilon');
}

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
