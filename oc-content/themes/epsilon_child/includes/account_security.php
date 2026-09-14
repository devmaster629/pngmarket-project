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
        'password_changed_at' => '',
        'connected' => array(
            'google' => 0,
            'facebook' => 0,
        ),
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
    osc_set_preference('user_' . $user_id, json_encode($data), 'pngm_account_security', 'STRING');
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
 * Register front route (login required → user-custom shell).
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
}
osc_add_hook('init', 'pngm_sec_register_route', 5);
