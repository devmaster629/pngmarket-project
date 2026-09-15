<?php
/**
 * PNG Market — in-app Activity feed (bell destination).
 *
 * Messages stay on the Messages badge only. The bell lists listing / account
 * events with a dismiss control (Upwork-style).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'activity.php'
) {
    exit;
}

/**
 * @return string
 */
function pngm_activity_url()
{
    return osc_route_url('pngm-activity');
}

/**
 * @param int $user_id
 * @return array
 */
function pngm_activity_get($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return array();
    }

    $raw = osc_get_preference('feed_' . $user_id, 'pngm_activity');
    if (!is_string($raw) || $raw === '') {
        return array();
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : array();
}

/**
 * @param int   $user_id
 * @param array $items
 */
function pngm_activity_save($user_id, $items)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return;
    }
    if (!is_array($items)) {
        $items = array();
    }
    // Newest first, cap size.
    usort($items, function ($a, $b) {
        return (int) @$b['ts'] - (int) @$a['ts'];
    });
    if (count($items) > 80) {
        $items = array_slice($items, 0, 80);
    }

    $key = 'feed_' . $user_id;
    $json = json_encode(array_values($items));
    osc_set_preference($key, $json, 'pngm_activity', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->set($key, $json, 'pngm_activity');
    }
}

/**
 * Pref keys that belong in Messages, not the Activity bell.
 *
 * @param string $pref_key
 * @return bool
 */
function pngm_activity_is_message_pref($pref_key)
{
    $pref_key = (string) $pref_key;
    return ($pref_key === 'msg_new' || $pref_key === 'msg_reply');
}

/**
 * Icon class for a preference / activity type.
 *
 * @param string $type
 * @return string
 */
function pngm_activity_icon($type)
{
    $map = array(
        'listing_approved' => 'fas fa-check-circle',
        'listing_rejected' => 'fas fa-times-circle',
        'listing_expiring' => 'fas fa-clock',
        'listing_expired' => 'fas fa-hourglass-end',
        'listing_renewed' => 'fas fa-redo',
        'saved_search' => 'fas fa-bookmark',
        'security' => 'fas fa-shield-alt',
        'subscription' => 'fas fa-credit-card',
    );
    $type = (string) $type;
    return isset($map[$type]) ? $map[$type] : 'fas fa-bell';
}

/**
 * Append an activity row (never for chat messages).
 *
 * @param int    $user_id
 * @param string $type
 * @param string $title
 * @param string $body
 * @param string $url
 * @return string|false  new item id
 */
function pngm_activity_add($user_id, $type, $title, $body, $url = '')
{
    $user_id = (int) $user_id;
    $type = (string) $type;
    if ($user_id <= 0 || pngm_activity_is_message_pref($type)) {
        return false;
    }

    $title = trim(strip_tags((string) $title));
    $body = trim(strip_tags((string) $body));
    if ($title === '') {
        return false;
    }

    $items = pngm_activity_get($user_id);
    $id = 'a' . dechex(time()) . substr(md5($user_id . $type . $title . mt_rand()), 0, 8);
    array_unshift($items, array(
        'id' => $id,
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'url' => (string) $url,
        'ts' => time(),
        'read' => 0,
    ));
    pngm_activity_save($user_id, $items);

    return $id;
}

/**
 * @param int $user_id
 * @return int
 */
function pngm_activity_unread_count($user_id)
{
    $n = 0;
    foreach (pngm_activity_get($user_id) as $row) {
        if (empty($row['read'])) {
            $n++;
        }
    }
    return $n;
}

/**
 * @param int    $user_id
 * @param string $id
 * @return bool
 */
function pngm_activity_delete($user_id, $id)
{
    $id = (string) $id;
    if ($id === '') {
        return false;
    }
    $items = pngm_activity_get($user_id);
    $next = array();
    $found = false;
    foreach ($items as $row) {
        if (isset($row['id']) && (string) $row['id'] === $id) {
            $found = true;
            continue;
        }
        $next[] = $row;
    }
    if ($found) {
        pngm_activity_save($user_id, $next);
    }
    return $found;
}

/**
 * @param int $user_id
 */
function pngm_activity_mark_all_read($user_id)
{
    $items = pngm_activity_get($user_id);
    $changed = false;
    foreach ($items as &$row) {
        if (empty($row['read'])) {
            $row['read'] = 1;
            $changed = true;
        }
    }
    unset($row);
    if ($changed) {
        pngm_activity_save($user_id, $items);
    }
}

/**
 * @param int $user_id
 */
function pngm_activity_clear_all($user_id)
{
    pngm_activity_save($user_id, array());
}

/**
 * Human time label.
 *
 * @param int $ts
 * @return string
 */
function pngm_activity_time_label($ts)
{
    $ts = (int) $ts;
    if ($ts <= 0) {
        return '';
    }
    if (function_exists('im_get_time_diff')) {
        return im_get_time_diff(date('Y-m-d H:i:s', $ts));
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return __('Just now', 'epsilon');
    }
    if ($diff < 3600) {
        return sprintf(__('%d min ago', 'epsilon'), (int) floor($diff / 60));
    }
    if ($diff < 86400) {
        return sprintf(__('%d h ago', 'epsilon'), (int) floor($diff / 3600));
    }
    return date('j M Y, g:i A', $ts);
}

/**
 * Register front route.
 */
function pngm_activity_register_route()
{
    if (!function_exists('osc_add_route')) {
        return;
    }
    osc_add_route(
        'pngm-activity',
        'user/activity/?',
        'user/activity',
        'custom/activity.php',
        true,
        'custom',
        'pngm-activity',
        __('Activity', 'epsilon')
    );
}
osc_add_hook('init', 'pngm_activity_register_route', 5);
