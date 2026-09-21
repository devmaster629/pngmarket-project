<?php
/**
 * PNG Market — Notification preferences (storage + schema).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'notification_prefs.php'
) {
    exit;
}

/**
 * @return string
 */
function pngm_notif_prefs_url()
{
    return osc_route_url('pngm-notif-prefs');
}

/**
 * Default preference map matching the UI design.
 *
 * @return array
 */
function pngm_notif_prefs_defaults()
{
    $rows = pngm_notif_prefs_schema();
    $items = array();
    foreach ($rows as $section) {
        foreach ($section['items'] as $item) {
            $on = !empty($item['mandatory']) || !empty($item['default_on']);
            $items[$item['id']] = array(
                'email' => $on ? 1 : 0,
                'push' => $on ? 1 : 0,
            );
        }
    }

    return array(
        'allow' => 1,
        'digest' => 'immediate',
        'items' => $items,
    );
}

/**
 * UI schema: sections + rows.
 *
 * @return array
 */
function pngm_notif_prefs_schema()
{
    return array(
        array(
            'title' => __('Messages', 'epsilon'),
            'items' => array(
                array(
                    'id' => 'msg_new',
                    'label' => __('New message', 'epsilon'),
                    'icon' => 'fas fa-comment-dots',
                    'default_on' => true,
                ),
                array(
                    'id' => 'msg_reply',
                    'label' => __('Message reply', 'epsilon'),
                    'icon' => 'fas fa-reply',
                    'default_on' => true,
                ),
            ),
        ),
        array(
            'title' => __('Listings', 'epsilon'),
            'items' => array(
                array(
                    'id' => 'listing_approved',
                    'label' => __('Listing approved', 'epsilon'),
                    'icon' => 'fas fa-check-circle',
                ),
                array(
                    'id' => 'listing_rejected',
                    'label' => __('Listing rejected', 'epsilon'),
                    'icon' => 'fas fa-times-circle',
                ),
                array(
                    'id' => 'listing_expiring',
                    'label' => __('Listing expiring', 'epsilon'),
                    'icon' => 'fas fa-clock',
                    'default_on' => true,
                ),
                array(
                    'id' => 'listing_expired',
                    'label' => __('Listing expired', 'epsilon'),
                    'icon' => 'fas fa-hourglass-end',
                    'default_on' => true,
                ),
                array(
                    'id' => 'listing_renewed',
                    'label' => __('Listing renewed', 'epsilon'),
                    'icon' => 'fas fa-sync-alt',
                    'default_on' => true,
                ),
            ),
        ),
        array(
            'title' => __('Marketplace activity', 'epsilon'),
            'items' => array(
                array(
                    'id' => 'saved_search',
                    'label' => __('Saved search matches', 'epsilon'),
                    'icon' => 'fas fa-search',
                    'default_on' => true,
                ),
            ),
        ),
        array(
            'title' => __('Account and security', 'epsilon'),
            'items' => array(
                array(
                    'id' => 'signin',
                    'label' => __('New sign-in', 'epsilon'),
                    'icon' => 'fas fa-shield-alt',
                    'mandatory' => true,
                ),
                array(
                    'id' => 'password_changed',
                    'label' => __('Password changed', 'epsilon'),
                    'icon' => 'fas fa-key',
                    'mandatory' => true,
                ),
                array(
                    'id' => 'account_updates',
                    'label' => __('Account updates', 'epsilon'),
                    'icon' => 'fas fa-user-shield',
                    'mandatory' => true,
                ),
            ),
        ),
    );
}

/**
 * @param int $user_id
 * @return array
 */
function pngm_notif_prefs_get($user_id)
{
    $user_id = (int) $user_id;
    $defaults = pngm_notif_prefs_defaults();
    if ($user_id <= 0) {
        return $defaults;
    }

    $raw = osc_get_preference('user_' . $user_id, 'pngm_notif_prefs');
    if ($raw === '' || $raw === null || $raw === false) {
        return $defaults;
    }

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    $prefs = $defaults;
    if (isset($decoded['allow'])) {
        $prefs['allow'] = (int) $decoded['allow'] ? 1 : 0;
    }
    if (isset($decoded['digest']) && in_array($decoded['digest'], array('immediate', 'daily', 'weekly'), true)) {
        $prefs['digest'] = $decoded['digest'];
    }
    if (isset($decoded['items']) && is_array($decoded['items'])) {
        foreach ($prefs['items'] as $id => $vals) {
            if (isset($decoded['items'][$id]) && is_array($decoded['items'][$id])) {
                $prefs['items'][$id]['email'] = !empty($decoded['items'][$id]['email']) ? 1 : 0;
                $prefs['items'][$id]['push'] = !empty($decoded['items'][$id]['push']) ? 1 : 0;
            }
        }
    }

    // Enforce mandatory rows always on
    foreach (pngm_notif_prefs_schema() as $section) {
        foreach ($section['items'] as $item) {
            if (!empty($item['mandatory'])) {
                $prefs['items'][$item['id']]['email'] = 1;
                $prefs['items'][$item['id']]['push'] = 1;
            }
        }
    }

    return $prefs;
}

/**
 * @param int   $user_id
 * @param array $input
 * @return array
 */
function pngm_notif_prefs_save($user_id, $input)
{
    $user_id = (int) $user_id;
    $prefs = pngm_notif_prefs_defaults();
    if ($user_id <= 0) {
        return $prefs;
    }

    $prefs['allow'] = !empty($input['allow']) ? 1 : 0;
    $digest = isset($input['digest']) ? (string) $input['digest'] : 'immediate';
    if (!in_array($digest, array('immediate', 'daily', 'weekly'), true)) {
        $digest = 'immediate';
    }
    $prefs['digest'] = $digest;

    $posted = isset($input['items']) && is_array($input['items']) ? $input['items'] : array();
    foreach ($prefs['items'] as $id => $vals) {
        $prefs['items'][$id]['email'] = !empty($posted[$id]['email']) ? 1 : 0;
        $prefs['items'][$id]['push'] = !empty($posted[$id]['push']) ? 1 : 0;
    }

    foreach (pngm_notif_prefs_schema() as $section) {
        foreach ($section['items'] as $item) {
            if (!empty($item['mandatory'])) {
                $prefs['items'][$item['id']]['email'] = 1;
                $prefs['items'][$item['id']]['push'] = 1;
            }
        }
    }

    if (!(int) $prefs['allow']) {
        foreach ($prefs['items'] as $id => $vals) {
            $is_mandatory = false;
            foreach (pngm_notif_prefs_schema() as $section) {
                foreach ($section['items'] as $item) {
                    if ($item['id'] === $id && !empty($item['mandatory'])) {
                        $is_mandatory = true;
                        break 2;
                    }
                }
            }
            if (!$is_mandatory) {
                $prefs['items'][$id]['email'] = 0;
                $prefs['items'][$id]['push'] = 0;
            }
        }
    }

    osc_set_preference('user_' . $user_id, json_encode($prefs), 'pngm_notif_prefs', 'STRING');
    return $prefs;
}

/**
 * Register front route (login required → user-custom shell).
 */
function pngm_notif_prefs_register_route()
{
    if (!function_exists('osc_add_route')) {
        return;
    }
    osc_add_route(
        'pngm-notif-prefs',
        'user/notification-preferences/?',
        'user/notification-preferences',
        'custom/notification-preferences.php',
        true,
        'custom',
        'pngm-notif',
        __('Notification Preferences', 'epsilon')
    );
}
osc_add_hook('init', 'pngm_notif_prefs_register_route', 5);

require_once dirname(__FILE__) . '/notification_prefs_enforce.php';
