<?php
/**
 * PNG Market — Enforce notification preferences on real email/push paths.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'notification_prefs_enforce.php'
) {
    exit;
}

/**
 * Whether a user allows a preference on a channel (email|push).
 *
 * @param int    $user_id
 * @param string $pref_key
 * @param string $channel
 * @return bool
 */
function pngm_notif_user_allows($user_id, $pref_key, $channel = 'email')
{
    $user_id = (int) $user_id;
    $pref_key = (string) $pref_key;
    $channel = ($channel === 'push') ? 'push' : 'email';

    if ($pref_key === '' || $user_id <= 0) {
        return true;
    }

    $mandatory = array('signin', 'password_changed', 'account_updates');
    if (in_array($pref_key, $mandatory, true)) {
        return true;
    }

    $prefs = pngm_notif_prefs_get($user_id);
    if (empty($prefs['allow'])) {
        return false;
    }

    if (!isset($prefs['items'][$pref_key]) || !is_array($prefs['items'][$pref_key])) {
        return false;
    }

    return !empty($prefs['items'][$pref_key][$channel]);
}

/**
 * Resolve registered user id from email address.
 *
 * @param string $email
 * @return int
 */
function pngm_notif_user_id_by_email($email)
{
    $email = trim((string) $email);
    if ($email === '' || !class_exists('User')) {
        return 0;
    }
    $user = User::newInstance()->findByEmail($email);
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return 0;
    }
    return (int) $user['pk_i_id'];
}

/**
 * Map Osclass osc_sendMail $type → preference key (or null = do not gate).
 *
 * @param string $type
 * @return string|null
 */
function pngm_notif_pref_key_for_mail_type($type)
{
    $type = (string) $type;
    $map = array(
        'alert_email_hourly' => 'saved_search',
        'alert_email_daily' => 'saved_search',
        'alert_email_weekly' => 'saved_search',
        'alert_email_instant' => 'saved_search',
        'warn_expiration' => 'listing_expiring',
        'user_forgot_password' => 'password_changed',
        'new_email' => 'account_updates',
        'user_validation' => 'account_updates',
        'user_registration' => 'account_updates',
        // Theme-owned mails (already checked by caller)
        'pngm_notif' => null,
        'pngm_listing_approved' => null,
        'pngm_listing_rejected' => null,
        'pngm_listing_renewed' => null,
        'pngm_listing_expired' => null,
    );

    return array_key_exists($type, $map) ? $map[$type] : null;
}

/**
 * Digest preference vs alert mail type.
 *
 * @param array  $prefs
 * @param string $type
 * @return bool
 */
function pngm_notif_digest_allows_alert_type($prefs, $type)
{
    $digest = isset($prefs['digest']) ? (string) $prefs['digest'] : 'immediate';
    $type = (string) $type;

    // Digest is a maximum frequency preference, not an exclusive cron-type lock.
    if ($digest === 'weekly') {
        return $type === 'alert_email_weekly';
    }
    if ($digest === 'daily') {
        return in_array($type, array('alert_email_daily', 'alert_email_weekly'), true);
    }

    // immediate: deliver whatever frequency the saved search is set to.
    return in_array($type, array(
        'alert_email_instant',
        'alert_email_hourly',
        'alert_email_daily',
        'alert_email_weekly',
    ), true);
}

/**
 * Detect IM notify call and return [thread_id, to_email] or null.
 *
 * @return array|null
 */
function pngm_notif_im_mail_context()
{
    if (!function_exists('im_email_message_notify')) {
        return null;
    }
    $frames = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 16);
    foreach ($frames as $frame) {
        if (empty($frame['function']) || $frame['function'] !== 'im_email_message_notify') {
            continue;
        }
        $args = isset($frame['args']) ? $frame['args'] : array();
        $to_email = isset($args[1]) ? (string) $args[1] : '';
        $thread_id = isset($args[4]) ? (int) $args[4] : 0;
        if ($to_email === '' || $thread_id <= 0) {
            return null;
        }
        return array($thread_id, $to_email);
    }
    return null;
}

/**
 * msg_new vs msg_reply from thread message count.
 *
 * @param int $thread_id
 * @return string
 */
function pngm_notif_im_pref_key($thread_id)
{
    $thread_id = (int) $thread_id;
    $count = 0;
    if ($thread_id > 0 && class_exists('ModelIM')) {
        $rows = ModelIM::newInstance()->getMessagesByThreadId($thread_id);
        $count = is_array($rows) ? count($rows) : 0;
    }
    // Immediate notify runs before insert (count = prior messages).
    // Deferred notify runs after insert (count includes current message).
    $from_deferred = false;
    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 16) as $frame) {
        if (!empty($frame['function']) && $frame['function'] === 'im_email_message_notify_from_row') {
            $from_deferred = true;
            break;
        }
    }
    if ($from_deferred) {
        return ($count <= 1) ? 'msg_new' : 'msg_reply';
    }
    return ($count === 0) ? 'msg_new' : 'msg_reply';
}

/**
 * Keep original mail params — other filters may return false and wipe $params.
 *
 * @param array  $params
 * @param string $type
 * @return array
 */
function pngm_notif_stash_mail_params($params, $type = '')
{
    if (is_array($params)) {
        $GLOBALS['pngm_notif_mail_params'] = $params;
        $GLOBALS['pngm_notif_mail_type'] = (string) $type;
    }
    return $params;
}
osc_add_filter('pre_send_mail_filter', 'pngm_notif_stash_mail_params', 0);

/**
 * Gate outbound mail according to saved prefs.
 *
 * @param array|false $params
 * @param string      $type
 * @return array|false
 */
function pngm_notif_pre_send_mail_filter($params, $type = '')
{
    if (is_array($params) && isset($params['stop']) && $params['stop'] === true) {
        return $params;
    }

    if (!is_array($params) && isset($GLOBALS['pngm_notif_mail_params']) && is_array($GLOBALS['pngm_notif_mail_params'])) {
        $params = $GLOBALS['pngm_notif_mail_params'];
    }
    if (!is_array($params)) {
        return $params;
    }

    $to = '';
    if (isset($params['to'])) {
        $to = is_array($params['to']) ? (string) reset($params['to']) : (string) $params['to'];
    }
    $user_id = pngm_notif_user_id_by_email($to);
    if ($user_id <= 0) {
        return $params;
    }

    $type = (string) $type;
    if ($type === '' && isset($GLOBALS['pngm_notif_mail_type'])) {
        $type = (string) $GLOBALS['pngm_notif_mail_type'];
    }

    $pref_key = null;

    if ($type === '') {
        $im = pngm_notif_im_mail_context();
        if ($im !== null) {
            $pref_key = pngm_notif_im_pref_key($im[0]);
        }
    } elseif ($type === 'im_message') {
        $im = pngm_notif_im_mail_context();
        if ($im !== null) {
            $pref_key = pngm_notif_im_pref_key($im[0]);
        } else {
            $pref_key = 'msg_new';
        }
    } else {
        $pref_key = pngm_notif_pref_key_for_mail_type($type);
    }

    if ($pref_key === null) {
        return $params;
    }

    $prefs = pngm_notif_prefs_get($user_id);

    $subject = isset($params['subject']) ? (string) $params['subject'] : '';
    $body_txt = isset($params['body']) ? strip_tags((string) $params['body']) : '';
    if ($body_txt === '' && isset($params['alt_body'])) {
        $body_txt = strip_tags((string) $params['alt_body']);
    }

    if (strpos($type, 'alert_email_') === 0) {
        $email_ok = pngm_notif_user_allows($user_id, 'saved_search', 'email')
            && pngm_notif_digest_allows_alert_type($prefs, $type);
        if (pngm_notif_user_allows($user_id, 'saved_search', 'push')) {
            pngm_notif_queue_push($user_id, 'saved_search', $subject, $body_txt, osc_user_alerts_url());
        }
        if (!$email_ok) {
            return array('stop' => true);
        }
        return $params;
    }

    // IM mails: email gated here; browser push is queued on im_insert_message
    // so it does not wait for deferred SMTP cron.
    if ($pref_key === 'msg_new' || $pref_key === 'msg_reply') {
        if (!pngm_notif_user_allows($user_id, $pref_key, 'email')) {
            return array('stop' => true);
        }
        return $params;
    }

    if (!pngm_notif_user_allows($user_id, $pref_key, 'email')) {
        return array('stop' => true);
    }

    return $params;
}
osc_add_filter('pre_send_mail_filter', 'pngm_notif_pre_send_mail_filter', 10);

/**
 * Deliver a browser push: VAPID Web Push when subscribed, else queue for next visit.
 *
 * @param int    $user_id
 * @param string $pref_key
 * @param string $title
 * @param string $body
 * @param string $url
 */
function pngm_notif_queue_push($user_id, $pref_key, $title, $body, $url = '')
{
    $user_id = (int) $user_id;
    $pref_key = (string) $pref_key;
    if ($user_id <= 0 || !pngm_notif_user_allows($user_id, $pref_key, 'push')) {
        return;
    }

    // Prefer real Web Push (works while the site is closed).
    if (function_exists('pngm_webpush_send_to_user')) {
        $sent = (int) pngm_webpush_send_to_user($user_id, (string) $title, (string) $body, (string) $url);
        if ($sent > 0) {
            return;
        }
    }

    // Fallback: show when the user next loads any page (Service Worker / Notification API).
    $raw = osc_get_preference('pushq_' . $user_id, 'pngm_notif_prefs');
    $queue = array();
    if ($raw !== '' && $raw !== null && $raw !== false) {
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            $queue = $decoded;
        }
    }

    $queue[] = array(
        'title' => (string) $title,
        'body' => (string) $body,
        'url' => (string) $url,
        'ts' => time(),
    );
    if (count($queue) > 25) {
        $queue = array_slice($queue, -25);
    }

    osc_set_preference('pushq_' . $user_id, json_encode($queue), 'pngm_notif_prefs', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->toArray();
    }
}

/**
 * @param int $user_id
 * @return array
 */
function pngm_notif_push_queue_take($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return array();
    }
    $raw = osc_get_preference('pushq_' . $user_id, 'pngm_notif_prefs');
    osc_set_preference('pushq_' . $user_id, '[]', 'pngm_notif_prefs', 'STRING');
    if ($raw === '' || $raw === null || $raw === false) {
        return array();
    }
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : array();
}

/**
 * Item owner user id (registered) or 0.
 *
 * @param array $item
 * @return int
 */
function pngm_notif_item_owner_id($item)
{
    if (!is_array($item)) {
        return 0;
    }
    if (!empty($item['fk_i_user_id'])) {
        return (int) $item['fk_i_user_id'];
    }
    if (!empty($item['s_contact_email'])) {
        return pngm_notif_user_id_by_email($item['s_contact_email']);
    }
    return 0;
}

/**
 * @param array $item
 * @return string
 */
function pngm_notif_item_title($item)
{
    if (!is_array($item)) {
        return __('Listing', 'epsilon');
    }
    if (!empty($item['s_title'])) {
        return strip_tags((string) $item['s_title']);
    }
    if (isset($item['locale']) && is_array($item['locale'])) {
        foreach ($item['locale'] as $loc) {
            if (!empty($loc['s_title'])) {
                return strip_tags((string) $loc['s_title']);
            }
        }
    }
    return __('Listing', 'epsilon');
}

/**
 * Send a preference-gated mail (+ optional push queue).
 *
 * @param int    $user_id
 * @param string $pref_key
 * @param string $to_email
 * @param string $to_name
 * @param string $subject
 * @param string $body
 * @param string $url
 * @param string $mail_type
 */
function pngm_notif_notify_user($user_id, $pref_key, $to_email, $to_name, $subject, $body, $url = '', $mail_type = 'pngm_notif')
{
    $user_id = (int) $user_id;
    $to_email = trim((string) $to_email);
    if ($user_id <= 0 || $to_email === '') {
        return;
    }

    $email_ok = pngm_notif_user_allows($user_id, $pref_key, 'email');
    $push_ok = pngm_notif_user_allows($user_id, $pref_key, 'push');

    if ($email_ok) {
        $emailParams = array(
            'from' => function_exists('_osc_from_email_aux') ? _osc_from_email_aux() : osc_contact_email(),
            'to' => $to_email,
            'to_name' => $to_name !== '' ? $to_name : $to_email,
            'subject' => $subject,
            'body' => $body,
            'alt_body' => strip_tags($body),
        );
        osc_sendMail($emailParams, $mail_type);
    }

    // Activity bell when the user opted into email and/or push for this event.
    if (($email_ok || $push_ok) && function_exists('pngm_activity_add')) {
        pngm_activity_add($user_id, $pref_key, $subject, strip_tags($body), $url);
    }

    if ($push_ok) {
        pngm_notif_queue_push($user_id, $pref_key, $subject, strip_tags($body), $url);
    }
}

/**
 * @param int    $item_id
 * @param string $pref_key
 * @param string $subject_tpl
 * @param string $body_tpl
 * @param string $mail_type
 */
function pngm_notif_notify_item_owner($item_id, $pref_key, $subject_tpl, $body_tpl, $mail_type)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0) {
        return;
    }
    $item = Item::newInstance()->findByPrimaryKey($item_id);
    if (!is_array($item) || empty($item['s_contact_email'])) {
        return;
    }

    $user_id = pngm_notif_item_owner_id($item);
    if ($user_id <= 0) {
        return;
    }

    $title = pngm_notif_item_title($item);
    View::newInstance()->_exportVariableToView('item', $item);
    $item_url = function_exists('osc_item_url') ? osc_item_url() : osc_base_url();

    $subject = sprintf($subject_tpl, $title);
    $body = sprintf(
        $body_tpl,
        osc_esc_html($item['s_contact_name'] !== '' ? $item['s_contact_name'] : __('there', 'epsilon')),
        osc_esc_html($title),
        '<a href="' . osc_esc_html($item_url) . '">' . osc_esc_html($item_url) . '</a>',
        osc_esc_html(osc_page_title())
    );

    pngm_notif_notify_user(
        $user_id,
        $pref_key,
        $item['s_contact_email'],
        isset($item['s_contact_name']) ? $item['s_contact_name'] : '',
        $subject,
        $body,
        $item_url,
        $mail_type
    );
}

/** @var array */
$GLOBALS['pngm_notif_approved_sent'] = array();

/**
 * Listing became publicly active.
 *
 * @param int $item_id
 */
function pngm_notif_on_listing_approved($item_id)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0) {
        return;
    }
    if (!empty($GLOBALS['pngm_notif_approved_sent'][$item_id])) {
        return;
    }
    $item = Item::newInstance()->findByPrimaryKey($item_id);
    if (!is_array($item)) {
        return;
    }
    if ((int) $item['b_active'] !== 1 || (int) $item['b_enabled'] !== 1 || (int) $item['b_spam'] === 1) {
        return;
    }
    if (function_exists('osc_isExpired') && osc_isExpired($item['dt_expiration'])) {
        return;
    }

    $GLOBALS['pngm_notif_approved_sent'][$item_id] = 1;
    pngm_notif_notify_item_owner(
        $item_id,
        'listing_approved',
        __('Listing approved: %s', 'epsilon'),
        '<p>' . __('Hi %1$s,', 'epsilon') . '</p><p>' . __('Your listing “%2$s” is now live on %4$s.', 'epsilon') . '</p><p>%3$s</p>',
        'pngm_listing_approved'
    );
}
osc_add_hook('activate_item', 'pngm_notif_on_listing_approved');
osc_add_hook('enable_item', 'pngm_notif_on_listing_approved');

/** @var array */
$GLOBALS['pngm_notif_rejected_sent'] = array();

/**
 * @param int $item_id
 */
function pngm_notif_on_listing_rejected($item_id)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0) {
        return;
    }
    if (!empty($GLOBALS['pngm_notif_rejected_sent'][$item_id])) {
        return;
    }
    $GLOBALS['pngm_notif_rejected_sent'][$item_id] = 1;

    pngm_notif_notify_item_owner(
        $item_id,
        'listing_rejected',
        __('Listing rejected: %s', 'epsilon'),
        '<p>' . __('Hi %1$s,', 'epsilon') . '</p><p>' . __('Your listing “%2$s” was disabled or marked as not allowed on %4$s.', 'epsilon') . '</p><p>%3$s</p>',
        'pngm_listing_rejected'
    );
}
osc_add_hook('disable_item', 'pngm_notif_on_listing_rejected');
osc_add_hook('item_spam_on', 'pngm_notif_on_listing_rejected');

/**
 * @param int $item_id
 */
function pngm_notif_on_listing_renewed($item_id)
{
    pngm_notif_notify_item_owner(
        (int) $item_id,
        'listing_renewed',
        __('Listing renewed: %s', 'epsilon'),
        '<p>' . __('Hi %1$s,', 'epsilon') . '</p><p>' . __('Your listing “%2$s” was renewed successfully on %4$s.', 'epsilon') . '</p><p>%3$s</p>',
        'pngm_listing_renewed'
    );
}
osc_add_hook('renew_item', 'pngm_notif_on_listing_renewed');

/**
 * Hourly expired-listing mail is handled by includes/listing_expiry.php
 * (pngm_listing_expiry_cron_expired) with Renew CTA.
 */
/**
 * After a normal (non-AJAX) IM send, keep the original filename for display.
 *
 * @param int $message_id
 */
function pngm_im_on_insert_save_file_label($message_id)
{
    $message_id = (int) $message_id;
    if ($message_id <= 0 || !class_exists('ModelIM')) {
        return;
    }
    $msg = ModelIM::newInstance()->getMessageById($message_id);
    if (!is_array($msg) || empty($msg['s_file'])) {
        return;
    }

    $original = '';
    if (!empty($_FILES['im-file']['name'])) {
        $name = $_FILES['im-file']['name'];
        $original = is_array($name) ? (string) reset($name) : (string) $name;
    } elseif (!empty($_FILES['im-file']['name'][0])) {
        $original = (string) $_FILES['im-file']['name'][0];
    }
    if ($original === '') {
        return;
    }

    $ui = dirname(__FILE__) . '/im_ui.php';
    if (file_exists($ui)) {
        require_once $ui;
    }
    if (function_exists('pngm_im_set_file_label')) {
        pngm_im_set_file_label($message_id, $original);
    }
}
osc_add_hook('im_insert_message', 'pngm_im_on_insert_save_file_label', 9);

/**
 * Queue browser push as soon as a message is stored (does not wait for deferred email).
 *
 * @param int $message_id
 */
function pngm_im_on_insert_queue_push($message_id)
{
    $message_id = (int) $message_id;
    if ($message_id <= 0 || !class_exists('ModelIM') || !function_exists('pngm_notif_queue_push')) {
        return;
    }

    $msg = ModelIM::newInstance()->getMessageById($message_id);
    if (!is_array($msg) || empty($msg['fk_i_thread_id'])) {
        return;
    }

    $thread = ModelIM::newInstance()->getThreadById((int) $msg['fk_i_thread_id']);
    if (!is_array($thread)) {
        return;
    }

    $type = isset($msg['i_type']) ? (int) $msg['i_type'] : 0;
    // type 0 = from-user wrote → notify to-user; type 1 = reverse.
    if ($type === 0) {
        if ((int) @$thread['i_to_user_notify'] !== 1) {
            return;
        }
        $user_id = (int) @$thread['i_to_user_id'];
        $from_name = isset($thread['s_from_user_name']) ? (string) $thread['s_from_user_name'] : __('Someone', 'epsilon');
    } else {
        if ((int) @$thread['i_from_user_notify'] !== 1) {
            return;
        }
        $user_id = (int) @$thread['i_from_user_id'];
        $from_name = isset($thread['s_to_user_name']) ? (string) $thread['s_to_user_name'] : __('Someone', 'epsilon');
    }

    if ($user_id <= 0) {
        // Guest recipient — try email lookup.
        $email = ($type === 0)
            ? (isset($thread['s_to_user_email']) ? (string) $thread['s_to_user_email'] : '')
            : (isset($thread['s_from_user_email']) ? (string) $thread['s_from_user_email'] : '');
        if ($email !== '' && function_exists('pngm_notif_user_id_by_email')) {
            $user_id = pngm_notif_user_id_by_email($email);
        }
    }
    if ($user_id <= 0) {
        return;
    }

    $thread_id = (int) $thread['i_thread_id'];
    $rows = ModelIM::newInstance()->getMessagesByThreadId($thread_id);
    $count = is_array($rows) ? count($rows) : 0;
    $pref_key = ($count <= 1) ? 'msg_new' : 'msg_reply';

    if (!function_exists('pngm_notif_user_allows') || !pngm_notif_user_allows($user_id, $pref_key, 'push')) {
        return;
    }

    $title = ($pref_key === 'msg_new')
        ? sprintf(__('New message from %s', 'epsilon'), $from_name)
        : sprintf(__('Reply from %s', 'epsilon'), $from_name);
    $body = isset($msg['s_message']) ? trim(strip_tags((string) $msg['s_message'])) : '';
    if ($body === '' && !empty($msg['s_file'])) {
        $body = __('Sent an attachment', 'epsilon');
    }
    if (function_exists('mb_substr')) {
        $body = mb_substr($body, 0, 140, 'UTF-8');
    } else {
        $body = substr($body, 0, 140);
    }

    $url = osc_base_url() . 'index.php?page=custom&file=instant_messenger/user/threads.php';
    if (function_exists('osc_route_url')) {
        $secret = ($type === 0)
            ? (isset($thread['s_to_secret']) ? (string) $thread['s_to_secret'] : '')
            : (isset($thread['s_from_secret']) ? (string) $thread['s_from_secret'] : '');
        if ($secret === '') {
            $secret = 'n';
        }
        $try = osc_route_url('im-messages', array('thread-id' => $thread_id, 'secret' => $secret));
        if (is_string($try) && $try !== '') {
            $url = $try;
        } else {
            $try = osc_route_url('im-threads');
            if (is_string($try) && $try !== '') {
                $url = $try;
            }
        }
    }

    pngm_notif_queue_push($user_id, $pref_key, $title, $body, $url);
}
osc_add_hook('im_insert_message', 'pngm_im_on_insert_queue_push', 8);

/**
 * Send IM notification email for a stored message (respects thread notify + prefs).
 * Used when deferred mail is enabled so chat stays fast but email still delivers
 * without waiting for minutely cron.
 *
 * @param int $message_id
 * @return bool
 */
function pngm_im_deliver_email_for_message($message_id)
{
    $message_id = (int) $message_id;
    if ($message_id <= 0 || !class_exists('ModelIM') || !function_exists('im_email_message_notify_from_row')) {
        return false;
    }

    $msg = ModelIM::newInstance()->getMessageById($message_id);
    if (!is_array($msg) || empty($msg['fk_i_thread_id'])) {
        return false;
    }
    // Already emailed or already read — nothing to do.
    if (!empty($msg['i_email_sent']) || !empty($msg['i_read'])) {
        return false;
    }

    $thread_id = (int) $msg['fk_i_thread_id'];
    $type = isset($msg['i_type']) ? (int) $msg['i_type'] : 0;
    $thread = ModelIM::newInstance()->getThreadById($thread_id);
    if (!is_array($thread) || (function_exists('im_is_valid_thread') && !im_is_valid_thread($thread))) {
        return false;
    }

    if ($type === 0 && (int) @$thread['i_to_user_notify'] !== 1) {
        return false;
    }
    if ($type === 1 && (int) @$thread['i_from_user_notify'] !== 1) {
        return false;
    }

    // Same rule as plugin: notify_once skips if an earlier unread was already emailed.
    if (function_exists('im_param') && (int) im_param('notify_once') === 1
        && method_exists(ModelIM::newInstance(), 'hasUnreadNotifiedEmail')
        && ModelIM::newInstance()->hasUnreadNotifiedEmail($thread_id, $type)
    ) {
        // Mark this row emailed so deferred cron does not keep retrying forever.
        if (method_exists(ModelIM::newInstance(), 'updateEmailSent')) {
            $dt = isset($msg['d_datetime']) ? (string) $msg['d_datetime'] : date('Y-m-d H:i:s');
            ModelIM::newInstance()->updateEmailSent($thread_id, $type, $dt);
        }
        return false;
    }

    // Pref gate (email channel) — push is handled separately on insert.
    $to_email = ($type === 0)
        ? (isset($thread['s_to_user_email']) ? (string) $thread['s_to_user_email'] : '')
        : (isset($thread['s_from_user_email']) ? (string) $thread['s_from_user_email'] : '');
    $user_id = ($type === 0)
        ? (int) @$thread['i_to_user_id']
        : (int) @$thread['i_from_user_id'];
    if ($user_id <= 0 && $to_email !== '' && function_exists('pngm_notif_user_id_by_email')) {
        $user_id = pngm_notif_user_id_by_email($to_email);
    }
    if ($user_id > 0 && function_exists('pngm_notif_user_allows')) {
        $rows = ModelIM::newInstance()->getMessagesByThreadId($thread_id);
        $count = is_array($rows) ? count($rows) : 0;
        $pref_key = ($count <= 1) ? 'msg_new' : 'msg_reply';
        if (!pngm_notif_user_allows($user_id, $pref_key, 'email')) {
            return false;
        }
    }

    if (!im_email_message_notify_from_row($thread, $msg, $type)) {
        return false;
    }

    $dt = isset($msg['d_datetime']) ? (string) $msg['d_datetime'] : date('Y-m-d H:i:s');
    if (method_exists(ModelIM::newInstance(), 'updateEmailSent')) {
        ModelIM::newInstance()->updateEmailSent($thread_id, $type, $dt);
    }
    return true;
}

/**
 * After IM insert: deliver email in shutdown so AJAX/chat responses are not blocked by SMTP.
 *
 * @param int $message_id
 */
function pngm_im_on_insert_deliver_email($message_id)
{
    $message_id = (int) $message_id;
    if ($message_id <= 0) {
        return;
    }
    // Immediate path already set i_email_sent=1 when deferred is off.
    if (function_exists('im_param') && (int) im_param('email_deferred') !== 1) {
        return;
    }

    if (!empty($GLOBALS['pngm_im_email_shutdown_registered'][$message_id])) {
        return;
    }
    if (!isset($GLOBALS['pngm_im_email_shutdown_registered'])) {
        $GLOBALS['pngm_im_email_shutdown_registered'] = array();
    }
    $GLOBALS['pngm_im_email_shutdown_registered'][$message_id] = 1;

    register_shutdown_function(function () use ($message_id) {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        try {
            pngm_im_deliver_email_for_message($message_id);
        } catch (Exception $e) {
            // Never break the page for mail failures.
        }
    });
}
osc_add_hook('im_insert_message', 'pngm_im_on_insert_deliver_email', 10);

/**
 * Flush any leftover deferred IM emails (cron miss / process killed before shutdown).
 * Uses delay=0 so pending rows send immediately (plugin treats 0 as 5 minutes).
 */
function pngm_im_flush_pending_emails()
{
    if (!function_exists('im_param') || (int) im_param('email_deferred') !== 1) {
        return;
    }
    if (!class_exists('ModelIM') || !function_exists('im_email_message_notify_from_row')) {
        return;
    }
    if (!method_exists(ModelIM::newInstance(), 'getPendingEmailNotifications')) {
        return;
    }

    // Anything still pending (no artificial delay) — chat already returned to the sender.
    $date = date('Y-m-d H:i:s');
    $list = ModelIM::newInstance()->getPendingEmailNotifications($date);
    if (!is_array($list) || empty($list)) {
        return;
    }

    foreach ($list as $l) {
        $thread_id = isset($l['fk_i_thread_id']) ? (int) $l['fk_i_thread_id'] : 0;
        $type = isset($l['i_type']) ? (int) $l['i_type'] : 0;
        $dt_datetime = isset($l['dt_datetime']) ? (string) $l['dt_datetime'] : '';
        if ($thread_id <= 0 || $dt_datetime === '') {
            continue;
        }

        $thread = ModelIM::newInstance()->getThreadById($thread_id);
        if (!is_array($thread) || (function_exists('im_is_valid_thread') && !im_is_valid_thread($thread))) {
            continue;
        }
        if ($type === 0 && (int) @$thread['i_to_user_notify'] !== 1) {
            continue;
        }
        if ($type === 1 && (int) @$thread['i_from_user_notify'] !== 1) {
            continue;
        }
        if ((int) im_param('notify_once') === 1
            && method_exists(ModelIM::newInstance(), 'hasUnreadNotifiedEmail')
            && ModelIM::newInstance()->hasUnreadNotifiedEmail($thread_id, $type)
        ) {
            continue;
        }

        $message = ModelIM::newInstance()->getNotificationMessage($thread_id, $type, $dt_datetime);
        if (!is_array($message) || empty($message['pk_i_id'])) {
            continue;
        }
        if (pngm_im_deliver_email_for_message((int) $message['pk_i_id'])) {
            // already marked inside deliver
        }
    }
}

/**
 * Throttled catch-up for deferred IM mail when minutely cron is missing.
 */
function pngm_im_email_catchup_on_init()
{
    if (!function_exists('osc_get_preference') || !function_exists('osc_set_preference')) {
        return;
    }
    if (defined('OC_ADMIN') && OC_ADMIN) {
        return;
    }
    if (class_exists('Params') && (string) Params::getParam('page') === 'cron') {
        return;
    }
    if (!function_exists('im_param') || (int) im_param('email_deferred') !== 1) {
        return;
    }

    $now = time();
    $last = (int) osc_get_preference('pngm_im_email_catchup_ts', 'epsilon_child');
    if ($last > 0 && ($now - $last) < 90) {
        return;
    }
    osc_set_preference('pngm_im_email_catchup_ts', (string) $now, 'epsilon_child', 'STRING');
    pngm_im_flush_pending_emails();
}
osc_add_hook('init', 'pngm_im_email_catchup_on_init', 26);

/**
 * Deliver queued browser notifications for the logged-in user.
 */
function pngm_notif_push_footer()
{
    if (!function_exists('osc_is_web_user_logged_in') || !osc_is_web_user_logged_in()) {
        return;
    }
    $queue = pngm_notif_push_queue_take((int) osc_logged_user_id());
    if (empty($queue)) {
        return;
    }
    $payload = array();
    foreach ($queue as $row) {
        if (!is_array($row)) {
            continue;
        }
        $payload[] = array(
            'title' => isset($row['title']) ? (string) $row['title'] : '',
            'body' => isset($row['body']) ? (string) $row['body'] : '',
            'url' => isset($row['url']) ? (string) $row['url'] : '',
        );
    }
    if (empty($payload)) {
        return;
    }
    ?>
<script>
(function () {
  var items = <?php echo json_encode($payload); ?>;
  var icon = <?php echo json_encode(rtrim(osc_base_url(), '/') . '/pwa/icon-192.png'); ?>;
  if (!items || !items.length || typeof Notification === 'undefined') return;
  if (Notification.permission !== 'granted') return;
  items.forEach(function (n, i) {
    setTimeout(function () {
      try {
        var note = new Notification(n.title || 'PNG Market', {
          body: n.body || '',
          icon: icon
        });
        if (n.url) {
          note.onclick = function () {
            window.focus();
            window.location.href = n.url;
          };
        }
      } catch (e) {}
    }, i * 400);
  });
})();
</script>
    <?php
}
/**
 * Activity bell entry when a saved-search alert email is sent.
 *
 * @param array $user
 * @param string $ads
 * @param array $s_search
 * @param array $items
 * @param int $totalItems
 */
function pngm_notif_on_saved_search_alert($user, $ads = '', $s_search = array(), $items = array(), $totalItems = 0)
{
    if (!function_exists('pngm_activity_add') || !is_array($user)) {
        return;
    }
    $user_id = isset($user['pk_i_id']) ? (int) $user['pk_i_id'] : 0;
    if ($user_id <= 0 && !empty($user['s_email']) && function_exists('pngm_notif_user_id_by_email')) {
        $user_id = pngm_notif_user_id_by_email($user['s_email']);
    }
    if ($user_id <= 0) {
        return;
    }

    $totalItems = (int) $totalItems;
    if ($totalItems <= 0 && is_array($items)) {
        $totalItems = count($items);
    }
    if ($totalItems <= 0) {
        return;
    }

    $email_ok = function_exists('pngm_notif_user_allows')
        && pngm_notif_user_allows($user_id, 'saved_search', 'email');
    $push_ok = function_exists('pngm_notif_user_allows')
        && pngm_notif_user_allows($user_id, 'saved_search', 'push');
    if (!$email_ok && !$push_ok) {
        return;
    }

    $subject = sprintf(
        _n('%d new listing matches your saved search', '%d new listings match your saved search', $totalItems, 'epsilon'),
        $totalItems
    );
    $body = __('Open Saved Searches to review matching listings.', 'epsilon');
    $url = function_exists('osc_user_alerts_url') ? osc_user_alerts_url() : osc_base_url();
    pngm_activity_add($user_id, 'saved_search', $subject, $body, $url);
}
osc_add_hook('hook_alert_email_instant', 'pngm_notif_on_saved_search_alert', 8);
osc_add_hook('hook_alert_email_hourly', 'pngm_notif_on_saved_search_alert', 8);
osc_add_hook('hook_alert_email_daily', 'pngm_notif_on_saved_search_alert', 8);
osc_add_hook('hook_alert_email_weekly', 'pngm_notif_on_saved_search_alert', 8);

// Prefer web_push.php footer (Service Worker). Keep this as fallback if web_push is absent.
if (!function_exists('pngm_webpush_footer')) {
    osc_add_hook('footer', 'pngm_notif_push_footer', 10);
}
