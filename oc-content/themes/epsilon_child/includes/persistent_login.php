<?php
/**
 * PNG Market — Persistent (“constant”) web login.
 *
 * Sets the same oc_userId / oc_userSecret cookies used by password “Remember me”
 * so Google/Facebook and other after_login paths stay signed in across visits.
 *
 * Osclass Cookie defaults to a 3-year expiry (set_expires() is a no-op since 8.3.1).
 * Session cookies are also long-lived (see Session::session_start). After a browser
 * restart, osc_is_web_user_logged_in() restores the session from those cookies.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'persistent_login.php'
) {
    exit;
}

// As early as the theme loads (session may already be active): keep session files alive.
$pngm_persist_life = function_exists('osc_time_cookie') ? (int) osc_time_cookie() : (86400 * 365 * 3);
if ($pngm_persist_life < 86400) {
    $pngm_persist_life = 86400 * 365 * 3;
}
$pngm_gc = (int) ini_get('session.gc_maxlifetime');
if ($pngm_gc < $pngm_persist_life) {
    @ini_set('session.gc_maxlifetime', (string) $pngm_persist_life);
}

/**
 * Long-lived PHP session cookie (matches Osclass cookie lifetime ~3 years).
 * Safe to call on every request; only sets when values are shorter/missing.
 */
function pngm_persist_session_lifetime()
{
    if (headers_sent()) {
        return;
    }
    $life = function_exists('osc_time_cookie') ? (int) osc_time_cookie() : (86400 * 365 * 3);
    if ($life < 86400) {
        $life = 86400 * 365 * 3;
    }

    $current_gc = (int) ini_get('session.gc_maxlifetime');
    if ($current_gc < $life) {
        @ini_set('session.gc_maxlifetime', (string) $life);
    }

    $current_cookie = (int) ini_get('session.cookie_lifetime');
    if ($current_cookie !== $life) {
        @ini_set('session.cookie_lifetime', (string) $life);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $params = session_get_cookie_params();
        $secure = !empty($params['secure']) || (function_exists('osc_is_ssl') && osc_is_ssl());
        $httponly = isset($params['httponly']) ? (bool) $params['httponly'] : true;
        $path = isset($params['path']) && $params['path'] !== '' ? $params['path'] : '/';
        $domain = isset($params['domain']) ? (string) $params['domain'] : '';
        if (PHP_VERSION_ID >= 70300) {
            @session_set_cookie_params(array(
                'lifetime' => $life,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax',
            ));
        } else {
            @session_set_cookie_params($life, $path, $domain, $secure, $httponly);
        }
    }
}

/**
 * Ensure the user has a secret and write persistent login cookies.
 *
 * Always reloads the user row so social logins that rotate s_secret stay in sync.
 *
 * @param array|int $user User row or user id
 * @return bool
 */
function pngm_persist_web_login($user)
{
    if (is_numeric($user)) {
        $user_id = (int) $user;
    } elseif (is_array($user) && !empty($user['pk_i_id'])) {
        $user_id = (int) $user['pk_i_id'];
    } else {
        return false;
    }

    $user = User::newInstance()->findByPrimaryKey($user_id);
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return false;
    }

    $secret = isset($user['s_secret']) ? trim((string) $user['s_secret']) : '';
    if ($secret === '') {
        if (!function_exists('osc_genRandomPassword')) {
            require_once osc_lib_path() . 'osclass/helpers/hSecurity.php';
        }
        $secret = osc_genRandomPassword();
        User::newInstance()->update(array('s_secret' => $secret), array('pk_i_id' => $user_id));
        $user['s_secret'] = $secret;
    }

    // Cookie::$expires defaults to time()+3y; set_expires() is intentionally a no-op in Osclass 8.3+.
    Cookie::newInstance()->push('oc_userId', $user_id);
    Cookie::newInstance()->push('oc_userSecret', $secret);
    Cookie::newInstance()->set();

    return true;
}

/**
 * Detect how the current login request authenticated.
 *
 * @return string password|google|facebook|twofa|unknown
 */
function pngm_persist_detect_login_method()
{
    if (!class_exists('Params')) {
        return 'unknown';
    }
    if (Params::getParam('gglLogin') == 1 || Params::getParam('route') === 'ggl-redirect') {
        return 'google';
    }
    if (Params::getParam('fjlRedirect') == 1) {
        return 'facebook';
    }
    if (Session::newInstance()->_get('pngm_2fa_just_done') === '1'
        || Params::getParam('pngm_2fa_action') === 'verify'
    ) {
        return 'twofa';
    }
    if (Params::getParam('page') === 'login') {
        return 'password';
    }
    return 'unknown';
}

/**
 * Record login method + mark social provider connected (Account & Security UI).
 *
 * @param int    $user_id
 * @param string $method
 * @param bool   $persisted
 */
function pngm_persist_record_login($user_id, $method, $persisted)
{
    $user_id = (int) $user_id;
    if ($user_id < 1 || !function_exists('pngm_sec_get') || !function_exists('pngm_sec_save')) {
        return;
    }
    $data = pngm_sec_get($user_id);
    $data['last_login_method'] = $method;
    $data['last_login_at'] = date('Y-m-d H:i:s');
    $data['last_login_persisted'] = $persisted ? 1 : 0;
    if ($method === 'google' || $method === 'facebook') {
        if (!isset($data['connected']) || !is_array($data['connected'])) {
            $data['connected'] = array('google' => 0, 'facebook' => 0);
        }
        $data['connected'][$method] = 1;
    }
    pngm_sec_save($user_id, $data);

    if (function_exists('pngm_sec_log_activity')) {
        $labels = array(
            'google' => __('Signed in with Google (stay signed in)', 'epsilon'),
            'facebook' => __('Signed in with Facebook (stay signed in)', 'epsilon'),
            'password' => __('Signed in with email (stay signed in)', 'epsilon'),
            'twofa' => __('Signed in with two-step verification (stay signed in)', 'epsilon'),
        );
        if (isset($labels[$method])) {
            pngm_sec_log_activity($user_id, 'login', $labels[$method]);
        }
    }
}

/**
 * Public status for Account & Security (auditor / user can verify persistence).
 *
 * @param int $user_id
 * @return array
 */
function pngm_persist_status($user_id = 0)
{
    $user_id = (int) ($user_id ?: (function_exists('osc_logged_user_id') ? osc_logged_user_id() : 0));
    $cid = Cookie::newInstance()->get_value('oc_userId');
    $sec = Cookie::newInstance()->get_value('oc_userSecret');
    $cookie_ok = ($cid !== '' && $sec !== '' && $user_id > 0 && (int) $cid === $user_id);
    $method = 'unknown';
    $at = '';
    $recorded = 0;
    if ($user_id > 0 && function_exists('pngm_sec_get')) {
        $data = pngm_sec_get($user_id);
        $method = !empty($data['last_login_method']) ? (string) $data['last_login_method'] : 'unknown';
        $at = !empty($data['last_login_at']) ? (string) $data['last_login_at'] : '';
        $recorded = !empty($data['last_login_persisted']) ? 1 : 0;
    }
    return array(
        'cookie_active' => $cookie_ok,
        'method' => $method,
        'at' => $at,
        'recorded_persisted' => $recorded,
        'ok' => $cookie_ok,
    );
}

/**
 * After any successful web login (password, Google, Facebook, 2FA), keep the device signed in.
 *
 * @param array  $user
 * @param string $url_redirect
 */
function pngm_persist_after_login($user, $url_redirect = '')
{
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return;
    }
    // Already persisted in 2FA complete for this request.
    if (Session::newInstance()->_get('pngm_persist_done') === '1') {
        Session::newInstance()->_drop('pngm_persist_done');
        return;
    }
    // 2FA gate may soft-logout immediately after; skip until challenge completes.
    if (Session::newInstance()->_get('pngm_2fa_uid')) {
        return;
    }
    $ok = pngm_persist_web_login($user);
    $method = pngm_persist_detect_login_method();
    pngm_persist_record_login((int) $user['pk_i_id'], $method, $ok);
}

/**
 * Password login without “Remember me” still gets a session-only cookie path in core.
 * Force remember=1 on credential login so users stay signed in on this device.
 */
function pngm_persist_force_remember_param()
{
    if (!class_exists('Params')) {
        return;
    }
    $page = Params::getParam('page');
    $action = Params::getParam('action');
    if ($page !== 'login') {
        return;
    }
    if ($action !== 'login_post' && $action !== 'login' && $action !== '') {
        return;
    }
    if (Params::getParam('email') === '' || Params::getParam('password') === '') {
        return;
    }
    Params::setParam('remember', '1');
}

/**
 * Re-assert remember cookies while logged in (survives session GC / browser restart).
 */
function pngm_persist_refresh_while_logged_in()
{
    if (!function_exists('osc_is_web_user_logged_in') || !osc_is_web_user_logged_in()) {
        return;
    }
    $uid = (int) osc_logged_user_id();
    if ($uid < 1) {
        return;
    }
    $cid = Cookie::newInstance()->get_value('oc_userId');
    $sec = Cookie::newInstance()->get_value('oc_userSecret');
    if ($cid === '' || $sec === '' || (int) $cid !== $uid) {
        pngm_persist_web_login($uid);
    }
}

osc_add_hook('init', 'pngm_persist_session_lifetime', 1);
osc_add_hook('before_login', 'pngm_persist_force_remember_param', 1);
osc_add_hook('init', 'pngm_persist_force_remember_param', 2);
osc_add_hook('init', 'pngm_persist_refresh_while_logged_in', 20);
// After 2FA gate (priority 5): persist when login is fully accepted.
osc_add_hook('after_login', 'pngm_persist_after_login', 9);
