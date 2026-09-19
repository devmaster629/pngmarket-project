<?php
/**
 * PNG Market — Persistent (“constant”) web login.
 *
 * Sets the same oc_userId / oc_userSecret cookies used by password “Remember me”
 * so Google/Facebook and other after_login paths stay signed in across visits.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'persistent_login.php'
) {
    exit;
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
        $samesite = 'Lax';
        if (PHP_VERSION_ID >= 70300) {
            @session_set_cookie_params(array(
                'lifetime' => $life,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ));
        } else {
            @session_set_cookie_params($life, $path, $domain, $secure, $httponly);
        }
    }
}

/**
 * Ensure the user has a secret and write persistent login cookies.
 *
 * @param array|int $user User row or user id
 * @return bool
 */
function pngm_persist_web_login($user)
{
    if (is_numeric($user)) {
        $user = User::newInstance()->findByPrimaryKey((int) $user);
    }
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return false;
    }

    $user_id = (int) $user['pk_i_id'];
    $secret = isset($user['s_secret']) ? trim((string) $user['s_secret']) : '';
    if ($secret === '') {
        if (!function_exists('osc_genRandomPassword')) {
            require_once osc_lib_path() . 'osclass/helpers/hSecurity.php';
        }
        $secret = osc_genRandomPassword();
        User::newInstance()->update(array('s_secret' => $secret), array('pk_i_id' => $user_id));
        $user['s_secret'] = $secret;
    }

    $life = function_exists('osc_time_cookie') ? (int) osc_time_cookie() : (86400 * 365 * 3);
    Cookie::newInstance()->set_expires($life);
    Cookie::newInstance()->push('oc_userId', $user_id);
    Cookie::newInstance()->push('oc_userSecret', $secret);
    Cookie::newInstance()->set();

    return true;
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
    // 2FA gate may soft-logout immediately after; skip until challenge completes.
    if (Session::newInstance()->_get('pngm_2fa_uid')) {
        return;
    }
    pngm_persist_web_login($user);
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

osc_add_hook('init', 'pngm_persist_session_lifetime', 1);
osc_add_hook('before_login', 'pngm_persist_force_remember_param', 1);
osc_add_hook('init', 'pngm_persist_force_remember_param', 2);
// After 2FA gate (priority 5): persist when login is fully accepted.
osc_add_hook('after_login', 'pngm_persist_after_login', 9);
