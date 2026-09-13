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
    if ($digest === 'daily') {
        return $type === 'alert_email_daily';
    }
    if ($digest === 'weekly') {
        return $type === 'alert_email_weekly';
    }
    // immediate: allow instant + hourly (near-real-time); block daily/weekly digests
    return in_array($type, array('alert_email_instant', 'alert_email_hourly'), true);
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
    } else {
        $pref_key = pngm_notif_pref_key_for_mail_type($type);
    }

    if ($pref_key === null) {
        return $params;
    }

    $prefs = pngm_notif_prefs_get($user_id);

    if (strpos($type, 'alert_email_') === 0) {
        if (!pngm_notif_user_allows($user_id, 'saved_search', 'email')) {
            return array('stop' => true);
        }
        if (!pngm_notif_digest_allows_alert_type($prefs, $type)) {
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
 * Queue a browser push for next page load (requires Notification permission).
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
    if ($user_id <= 0 || !pngm_notif_user_allows($user_id, $pref_key, 'push')) {
        return;
    }

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

    if (pngm_notif_user_allows($user_id, $pref_key, 'email')) {
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

    pngm_notif_queue_push($user_id, $pref_key, $subject, strip_tags($body), $url);
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
 * Hourly: notify owners of listings that expired in the last hour.
 */
function pngm_notif_cron_listing_expired()
{
    $from = date('Y-m-d H:i:s', time() - 3600);
    $to = date('Y-m-d H:i:s');
    $prefix = DB_TABLE_PREFIX;

    try {
        $conn = DBConnectionClass::newInstance();
        $data = $conn->getOsclassDb();
        $comm = new DBCommandClass($data);
        $sql = sprintf(
            'SELECT pk_i_id FROM %st_item WHERE b_premium = 0 AND dt_expiration BETWEEN "%s" AND "%s"',
            $prefix,
            $from,
            $to
        );
        $rs = $comm->query($sql);
        if (!$rs) {
            return;
        }
        $rows = $rs->result();
    } catch (Exception $e) {
        return;
    }

    if (!is_array($rows)) {
        return;
    }

    foreach ($rows as $row) {
        $id = isset($row['pk_i_id']) ? (int) $row['pk_i_id'] : 0;
        if ($id <= 0) {
            continue;
        }
        pngm_notif_notify_item_owner(
            $id,
            'listing_expired',
            __('Listing expired: %s', 'epsilon'),
            '<p>' . __('Hi %1$s,', 'epsilon') . '</p><p>' . __('Your listing “%2$s” has expired on %4$s.', 'epsilon') . '</p><p>%3$s</p>',
            'pngm_listing_expired'
        );
    }
}
osc_add_hook('cron_hourly', 'pngm_notif_cron_listing_expired');

/**
 * After IM message insert — queue push even when email was skipped.
 *
 * @param int $message_id
 */
function pngm_notif_on_im_insert_message($message_id)
{
    $message_id = (int) $message_id;
    if ($message_id <= 0 || !class_exists('ModelIM')) {
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
    if ($type === 0) {
        $to_user_id = (int) $thread['i_to_user_id'];
        $from_name = (string) $thread['s_from_user_name'];
        $secret = (string) $thread['s_to_secret'];
    } else {
        $to_user_id = (int) $thread['i_from_user_id'];
        $from_name = (string) $thread['s_to_user_name'];
        $secret = (string) $thread['s_from_secret'];
    }

    if ($to_user_id <= 0) {
        return;
    }

    $msgs = ModelIM::newInstance()->getMessagesByThreadId((int) $thread['i_thread_id']);
    $count = is_array($msgs) ? count($msgs) : 0;
    $pref_key = ($count <= 1) ? 'msg_new' : 'msg_reply';

    $url = function_exists('osc_route_url')
        ? osc_route_url('im-messages', array('thread-id' => (int) $thread['i_thread_id'], 'secret' => $secret))
        : osc_base_url();

    $title = ($pref_key === 'msg_new')
        ? __('New message', 'epsilon')
        : __('Message reply', 'epsilon');
    $body = sprintf(__('From %s', 'epsilon'), $from_name);

    pngm_notif_queue_push($to_user_id, $pref_key, $title, $body, $url);
}
osc_add_hook('im_insert_message', 'pngm_notif_on_im_insert_message');

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
  if (!items || !items.length || typeof Notification === 'undefined') return;
  if (Notification.permission !== 'granted') return;
  items.forEach(function (n, i) {
    setTimeout(function () {
      try {
        var note = new Notification(n.title || 'PNG Market', {
          body: n.body || '',
                        icon: <?php echo json_encode(osc_base_url()); ?>
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
osc_add_hook('footer', 'pngm_notif_push_footer', 40);
