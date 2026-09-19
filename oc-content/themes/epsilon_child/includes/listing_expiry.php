<?php
/**
 * Listing expiry policy: 30 days active, 7-day reminder, soft Expired (no delete), renew.
 *
 * Uses Osclass core: category i_expiration_days, warn_expiration cron mail, renew action.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'listing_expiry.php'
) {
    exit;
}

if (!defined('PNGM_LISTING_ACTIVE_DAYS')) {
    define('PNGM_LISTING_ACTIVE_DAYS', 30);
}
if (!defined('PNGM_LISTING_WARN_DAYS')) {
    define('PNGM_LISTING_WARN_DAYS', 7);
}
if (!defined('PNGM_LISTING_EXPIRY_POLICY_VER')) {
    define('PNGM_LISTING_EXPIRY_POLICY_VER', 'v1');
}

/**
 * Ensure Osclass prefs match the PNG Market expiry policy.
 */
function pngm_listing_expiry_enforce_prefs()
{
    if (!function_exists('osc_set_preference') || !function_exists('osc_get_preference')) {
        return;
    }

    if ((int) osc_get_preference('warn_expiration') !== PNGM_LISTING_WARN_DAYS) {
        osc_set_preference('warn_expiration', (string) PNGM_LISTING_WARN_DAYS, 'osclass', 'INTEGER');
    }

    if ((string) osc_get_preference('enabled_renewal_items') !== '1') {
        osc_set_preference('enabled_renewal_items', '1', 'osclass', 'BOOLEAN');
    }

    // Unlimited renewals.
    if ((int) osc_get_preference('renewal_limit') !== 0) {
        osc_set_preference('renewal_limit', '0', 'osclass', 'INTEGER');
    }

    // Renewed listings rise again in search / newest sort.
    if ((string) osc_get_preference('renewal_update_pub_date') !== '1') {
        osc_set_preference('renewal_update_pub_date', '1', 'osclass', 'BOOLEAN');
    }
}

/**
 * One-shot: set all categories to 30-day expiry and recalculate item dt_expiration.
 * Existing rows stay in the DB (soft Expired via date — never deleted by this policy).
 *
 * @param bool $force
 */
function pngm_listing_expiry_apply_policy($force = false)
{
    if (!function_exists('osc_get_preference') || !defined('DB_TABLE_PREFIX')) {
        return;
    }

    pngm_listing_expiry_enforce_prefs();

    $applied = (string) osc_get_preference('pngm_listing_expiry_policy', 'epsilon_child');
    if (!$force && $applied === PNGM_LISTING_EXPIRY_POLICY_VER) {
        return;
    }

    $days = (int) PNGM_LISTING_ACTIVE_DAYS;
    $prefix = DB_TABLE_PREFIX;

    try {
        $conn = DBConnectionClass::newInstance();
        $data = $conn->getOsclassDb();
        $comm = new DBCommandClass($data);

        $comm->query(sprintf(
            'UPDATE %st_category SET i_expiration_days = %d WHERE i_expiration_days IS NULL OR i_expiration_days <> %d',
            $prefix,
            $days,
            $days
        ));

        // Soft expiry from publish date + category days. Premium stays searchable via core helpers.
        $comm->query(sprintf(
            'UPDATE %st_item AS a
             INNER JOIN %st_category AS b ON b.pk_i_id = a.fk_i_category_id
             SET a.dt_expiration = DATE_ADD(a.dt_pub_date, INTERVAL b.i_expiration_days DAY)
             WHERE a.b_premium = 0 AND b.i_expiration_days > 0',
            $prefix,
            $prefix
        ));
    } catch (Exception $e) {
        return;
    }

    osc_set_preference('pngm_listing_expiry_policy', PNGM_LISTING_EXPIRY_POLICY_VER, 'epsilon_child', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->toArray();
    }
}

/**
 * Human expiry date for the current View item.
 *
 * @return string
 */
function pngm_listing_expiry_label()
{
    if (!function_exists('osc_item_field')) {
        return '';
    }
    $dt = (string) osc_item_field('dt_expiration');
    if ($dt === '' || strpos($dt, '9999') === 0) {
        return '';
    }
    if (function_exists('osc_format_date')) {
        return osc_format_date($dt);
    }
    $ts = strtotime($dt);
    return $ts ? date('M j, Y', $ts) : '';
}

/**
 * Renew URL for an item row (includes secret for email links).
 *
 * @param array $item
 * @return string
 */
function pngm_listing_renew_url($item)
{
    if (!is_array($item) || empty($item['pk_i_id']) || !function_exists('osc_item_renew_url')) {
        return '';
    }
    $secret = isset($item['s_secret']) ? (string) $item['s_secret'] : '';
    return osc_item_renew_url($secret, (int) $item['pk_i_id']);
}

/**
 * Append guidance + My Listings link to the 7-day expiry warning email.
 *
 * @param string $body
 * @param array  $aItem
 * @return string
 */
function pngm_listing_expiry_warn_email_body($body, $aItem)
{
    $body = (string) $body;
    if (strpos($body, 'pngm-expiry-warn-extra') !== false) {
        return $body;
    }

    $listings_url = function_exists('osc_user_items_url') ? osc_user_items_url() : osc_base_url();
    $extra  = '<div class="pngm-expiry-warn-extra">';
    $extra .= '<p>' . sprintf(
        __('Listings stay active for %1$d days. Yours expires in about %2$d days and will become Expired (not deleted). After it expires, renew it from My Listings.', 'epsilon'),
        (int) PNGM_LISTING_ACTIVE_DAYS,
        (int) PNGM_LISTING_WARN_DAYS
    ) . '</p>';
    $extra .= '<p><a href="' . osc_esc_html($listings_url) . '">' . osc_esc_html(__('Open My Listings', 'epsilon')) . '</a></p>';
    $extra .= '</div>';

    return $body . $extra;
}
osc_add_filter('email_warn_expiration_description_after', 'pngm_listing_expiry_warn_email_body', 8);

/**
 * In-app Activity + browser push for the 7-day expiry reminder (email is sent by core).
 *
 * @param array $aItem
 */
function pngm_listing_expiry_warn_notify_inapp($aItem)
{
    if (!is_array($aItem) || empty($aItem['pk_i_id'])) {
        return;
    }
    if (!function_exists('pngm_notif_item_owner_id') || !function_exists('pngm_activity_add')) {
        return;
    }

    $user_id = pngm_notif_item_owner_id($aItem);
    if ($user_id <= 0) {
        return;
    }

    $title_listing = function_exists('pngm_notif_item_title')
        ? pngm_notif_item_title($aItem)
        : (isset($aItem['s_title']) ? strip_tags((string) $aItem['s_title']) : __('Listing', 'epsilon'));

    View::newInstance()->_exportVariableToView('item', $aItem);
    $item_url = function_exists('osc_item_url') ? osc_item_url() : osc_base_url();
    $listings_url = function_exists('osc_user_items_url') ? osc_user_items_url() : $item_url;

    $exp_raw = isset($aItem['dt_expiration']) ? (string) $aItem['dt_expiration'] : '';
    $exp_label = '';
    if ($exp_raw !== '') {
        $exp_label = function_exists('osc_format_date') ? osc_format_date($exp_raw) : $exp_raw;
    }

    $subject = sprintf(__('Listing expiring soon: %s', 'epsilon'), $title_listing);
    $body = $exp_label !== ''
        ? sprintf(
            __('“%1$s” expires on %2$s (in about %3$d days). It will become Expired — not deleted. Renew it from My Listings after expiry.', 'epsilon'),
            $title_listing,
            $exp_label,
            (int) PNGM_LISTING_WARN_DAYS
        )
        : sprintf(
            __('“%1$s” expires in about %2$d days. It will become Expired — not deleted. Renew it from My Listings after expiry.', 'epsilon'),
            $title_listing,
            (int) PNGM_LISTING_WARN_DAYS
        );

    // Activity feed (bell) — same channel as other listing events.
    pngm_activity_add($user_id, 'listing_expiring', $subject, $body, $listings_url);

    // Browser push when the user allows “Listing expiring” push.
    if (function_exists('pngm_notif_queue_push')) {
        pngm_notif_queue_push($user_id, 'listing_expiring', $subject, $body, $listings_url);
    }
}
osc_add_hook('hook_email_warn_expiration', 'pngm_listing_expiry_warn_notify_inapp', 8);

/**
 * Hourly: notify owners of listings that expired in the last hour (with Renew CTA).
 * Replaces pngm_notif_cron_listing_expired.
 */
function pngm_listing_expiry_cron_expired()
{
    if (!function_exists('pngm_notif_notify_user') || !function_exists('pngm_notif_item_owner_id')) {
        return;
    }

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

    $listings_url = function_exists('osc_user_items_url') ? osc_user_items_url() : osc_base_url();
    $days_label = (string) PNGM_LISTING_ACTIVE_DAYS;

    foreach ($rows as $row) {
        $id = isset($row['pk_i_id']) ? (int) $row['pk_i_id'] : 0;
        if ($id <= 0) {
            continue;
        }
        $item = Item::newInstance()->findByPrimaryKey($id);
        if (!is_array($item) || empty($item['s_contact_email'])) {
            continue;
        }

        $user_id = pngm_notif_item_owner_id($item);
        if ($user_id <= 0) {
            continue;
        }

        $title = function_exists('pngm_notif_item_title') ? pngm_notif_item_title($item) : __('Listing', 'epsilon');
        View::newInstance()->_exportVariableToView('item', $item);
        $item_url = function_exists('osc_item_url') ? osc_item_url() : osc_base_url();
        $renew_url = pngm_listing_renew_url($item);
        $name = !empty($item['s_contact_name']) ? $item['s_contact_name'] : __('there', 'epsilon');

        $subject = sprintf(__('Listing expired: %s', 'epsilon'), $title);

        $body  = '<p>' . sprintf(__('Hi %s,', 'epsilon'), osc_esc_html($name)) . '</p>';
        $body .= '<p>' . sprintf(
            __('Your listing “%s” has expired. It is now Expired/Inactive and was not deleted.', 'epsilon'),
            osc_esc_html($title)
        ) . '</p>';
        $body .= '<p>' . sprintf(
            __('You can renew it for another %s days.', 'epsilon'),
            osc_esc_html($days_label)
        ) . '</p>';
        if ($renew_url !== '') {
            $body .= '<p><a href="' . osc_esc_html($renew_url) . '"><strong>'
                . osc_esc_html(__('Renew listing', 'epsilon')) . '</strong></a></p>';
        }
        $body .= '<p><a href="' . osc_esc_html($item_url) . '">' . osc_esc_html($item_url) . '</a></p>';
        $body .= '<p><a href="' . osc_esc_html($listings_url) . '">'
            . osc_esc_html(__('Open My Listings', 'epsilon')) . '</a></p>';

        pngm_notif_notify_user(
            $user_id,
            'listing_expired',
            $item['s_contact_email'],
            isset($item['s_contact_name']) ? $item['s_contact_name'] : '',
            $subject,
            $body,
            $renew_url !== '' ? $renew_url : $item_url,
            'pngm_listing_expired'
        );
    }
}

osc_add_hook('init', 'pngm_listing_expiry_apply_policy', 4);
osc_add_hook('cron_hourly', 'pngm_listing_expiry_cron_expired', 8);

/**
 * Renew must not bump listing stats again.
 * Expiry only hides ads by date — it never decreases user/category counters —
 * but core renew() always calls _increaseStats(), which inflated My Listings.
 *
 * @param int $item_id
 */
function pngm_listing_renew_flag_stat_undo($item_id)
{
    $GLOBALS['pngm_renew_undo_stat_id'] = (int) $item_id;
}
osc_add_hook('renew_item', 'pngm_listing_renew_flag_stat_undo', 1);

/**
 * Undo the stats bump that renew() applies immediately after renew_item.
 *
 * @param array $item
 */
function pngm_listing_renew_undo_stat_bump($item)
{
    if (empty($GLOBALS['pngm_renew_undo_stat_id']) || !is_array($item)) {
        return;
    }
    if ((int) @$item['pk_i_id'] !== (int) $GLOBALS['pngm_renew_undo_stat_id']) {
        return;
    }
    unset($GLOBALS['pngm_renew_undo_stat_id']);

    if (!empty($item['fk_i_user_id']) && class_exists('User')) {
        User::newInstance()->decreaseNumItems($item['fk_i_user_id']);
    }
    if (!empty($item['fk_i_category_id']) && class_exists('CategoryStats')) {
        CategoryStats::newInstance()->decreaseNumItems($item['fk_i_category_id']);
    }
    if (!empty($item['fk_c_country_code']) && class_exists('CountryStats')) {
        CountryStats::newInstance()->decreaseNumItems($item['fk_c_country_code']);
    }
    if (!empty($item['fk_i_region_id']) && class_exists('RegionStats')) {
        RegionStats::newInstance()->decreaseNumItems($item['fk_i_region_id']);
    }
    if (!empty($item['fk_i_city_id']) && class_exists('CityStats')) {
        CityStats::newInstance()->decreaseNumItems($item['fk_i_city_id']);
    }
}
osc_add_hook('item_increase_stat', 'pngm_listing_renew_undo_stat_bump', 8);

/**
 * One-shot: re-sync t_user.i_items with non-spam enabled+active listings
 * (includes expired — same rules as core publish counters).
 */
function pngm_listing_repair_user_item_counts()
{
    if (!function_exists('osc_get_preference') || !defined('DB_TABLE_PREFIX')) {
        return;
    }
    if ((string) osc_get_preference('pngm_user_items_repaired', 'epsilon_child') === 'v1') {
        return;
    }

    $prefix = DB_TABLE_PREFIX;
    try {
        $conn = DBConnectionClass::newInstance();
        $data = $conn->getOsclassDb();
        $comm = new DBCommandClass($data);
        $comm->query(sprintf(
            'UPDATE %st_user u
             SET u.i_items = (
               SELECT COUNT(*) FROM %st_item i
               WHERE i.fk_i_user_id = u.pk_i_id
                 AND i.b_enabled = 1
                 AND i.b_active = 1
                 AND i.b_spam = 0
             )',
            $prefix,
            $prefix
        ));
    } catch (Exception $e) {
        return;
    }

    osc_set_preference('pngm_user_items_repaired', 'v1', 'epsilon_child', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->toArray();
    }
}
osc_add_hook('init', 'pngm_listing_repair_user_item_counts', 5);
