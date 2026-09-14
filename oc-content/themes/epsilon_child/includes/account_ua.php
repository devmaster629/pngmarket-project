<?php
/**
 * PNG Market — User account sidebar + dashboard helpers.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'account_ua.php'
) {
    exit;
}

/**
 * Detect active account section for sidebar highlighting.
 *
 * @return string
 */
function pngm_ua_active_key()
{
    $loc = osc_get_osclass_location();
    $sec = osc_get_osclass_section();
    $route = Params::getParam('route');

    if ($loc === 'user' && $sec === 'dashboard') {
        return 'dashboard';
    }
    if ($loc === 'user' && $sec === 'items') {
        $param = (osc_version() >= 830 ? 'sItemType' : 'itemType');
        $type = Params::getParam($param);
        if ($type === 'active') {
            return 'active';
        }
        return 'listings';
    }
    if ($loc === 'user' && $sec === 'alerts') {
        return 'alerts';
    }
    if ($route === 'pngm-subscriptions' || $sec === 'pngm-sub') {
        return 'subscriptions';
    }
    if ($loc === 'user' && $sec === 'profile') {
        return 'profile';
    }
    if ($loc === 'user' && ($sec === 'change_password' || $sec === 'change_email' || $sec === 'change_username')) {
        return 'security';
    }
    if ($route === 'im-threads' || $route === 'im-messages' || strpos((string) $route, 'im-') === 0) {
        return 'messages';
    }
    if ($route === 'pngm-notif-prefs' || $sec === 'pngm-notif') {
        return 'notifications';
    }
    if ($route === 'pngm-account-security' || $sec === 'pngm-sec') {
        return 'security';
    }
    if ($route === 'bpr-profile') {
        return 'business';
    }
    if ($route === 'favorite-lists' || strpos((string) $route, 'favorite') === 0) {
        return 'favorites';
    }

    return '';
}

/**
 * Profile completion percent + checklist.
 *
 * @param array|null $user
 * @return array{percent:int,done:int,total:int,gaps:array}
 */
function pngm_ua_profile_completion($user = null)
{
    if (!is_array($user)) {
        $user = User::newInstance()->findByPrimaryKey(osc_logged_user_id());
    }
    if (!is_array($user)) {
        return array('percent' => 0, 'done' => 0, 'total' => 1, 'gaps' => array());
    }

    $checks = array(
        'name'     => trim((string) @$user['s_name']) !== '',
        'email'    => trim((string) @$user['s_email']) !== '',
        'phone'    => (trim((string) @$user['s_phone_mobile']) !== '' || trim((string) @$user['s_phone_land']) !== ''),
        'location' => (trim((string) @$user['s_city']) !== '' || trim((string) @$user['s_region']) !== '' || trim((string) @$user['fk_c_country_code']) !== ''),
        'address'  => (trim((string) @$user['s_address']) !== '' || trim((string) @$user['s_zip']) !== ''),
        'info'     => trim(strip_tags((string) @$user['s_info'])) !== '',
    );

    $total = count($checks);
    $done = count(array_filter($checks));
    $gaps = array();
    $labels = array(
        'name' => __('Full name', 'epsilon'),
        'email' => __('Email', 'epsilon'),
        'phone' => __('Phone number', 'epsilon'),
        'location' => __('Location', 'epsilon'),
        'address' => __('Address', 'epsilon'),
        'info' => __('About you', 'epsilon'),
    );
    foreach ($checks as $k => $ok) {
        if (!$ok && isset($labels[$k])) {
            $gaps[] = $labels[$k];
        }
    }

    $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

    return array(
        'percent' => $percent,
        'done'    => $done,
        'total'   => $total,
        'gaps'    => $gaps,
    );
}

/**
 * Sum listing views for the logged-in user (approx. profile/listing visibility).
 *
 * @param int $user_id
 * @return int
 */
function pngm_ua_profile_views($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return 0;
    }

    try {
        $conn = DBConnectionClass::newInstance();
        $data = $conn->getOsclassDb();
        $comm = new DBCommandClass($data);
        $prefix = DB_TABLE_PREFIX;
        $sql = "SELECT COALESCE(SUM(s.i_num_views), 0) AS views
                FROM {$prefix}t_item_stats s
                INNER JOIN {$prefix}t_item i ON i.pk_i_id = s.fk_i_item_id
                WHERE i.fk_i_user_id = " . $user_id;
        $rs = $comm->query($sql);
        if ($rs) {
            $rows = $rs->result();
            if (isset($rows[0]['views'])) {
                return (int) $rows[0]['views'];
            }
        }
    } catch (Exception $e) {
        return 0;
    }

    return 0;
}

/**
 * Recent messenger threads for dashboard.
 *
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function pngm_ua_recent_messages($user_id, $limit = 4)
{
    $user_id = (int) $user_id;
    $out = array();
    if ($user_id <= 0 || !class_exists('ModelIM')) {
        return $out;
    }

    try {
        $threads = ModelIM::newInstance()->getThreadsByUserId($user_id, (int) $limit, 0);
        if (!is_array($threads)) {
            return $out;
        }
        foreach ($threads as $t) {
            $thread_id = (int) @$t['i_thread_id'];
            $last = ModelIM::newInstance()->getLastMessageByThreadId($thread_id);
            $is_read = 1;
            if (method_exists(ModelIM::newInstance(), 'getThreadIsRead')) {
                $is_read = (int) ModelIM::newInstance()->getThreadIsRead($thread_id, $user_id);
            }
            $other_name = '';
            if ((int) @$t['i_from_user_id'] === $user_id) {
                $other_name = (string) @$t['s_to_user_name'];
            } else {
                $other_name = (string) @$t['s_from_user_name'];
            }
            if ($other_name === '') {
                $other_name = __('User', 'epsilon');
            }
            $initials = '';
            $parts = preg_split('/\s+/', trim($other_name));
            foreach (array_slice($parts, 0, 2) as $p) {
                $initials .= strtoupper(substr($p, 0, 1));
            }
            $snippet = '';
            $time = '';
            if (is_array($last)) {
                $snippet = strip_tags((string) @$last['s_message']);
                if (function_exists('mb_substr')) {
                    $snippet = mb_substr($snippet, 0, 72);
                } else {
                    $snippet = substr($snippet, 0, 72);
                }
                $dt = (string) @$last['dt_datetime'];
                if ($dt !== '' && function_exists('eps_smart_date')) {
                    $time = eps_smart_date($dt);
                } elseif ($dt !== '') {
                    $time = date('g:i A', strtotime($dt));
                }
            }
            $out[] = array(
                'id'       => $thread_id,
                'name'     => $other_name,
                'initials' => $initials !== '' ? $initials : 'U',
                'snippet'  => $snippet,
                'time'     => $time,
                'unread'   => $is_read === 0 ? 1 : 0,
                'url'      => osc_route_url('im-messages', array('thread-id' => $thread_id, 'secret' => 'n')),
            );
        }
    } catch (Exception $e) {
        return array();
    }

    return $out;
}

/**
 * Recent activity from user's latest listings.
 *
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function pngm_ua_recent_activity($user_id, $limit = 4)
{
    $user_id = (int) $user_id;
    $out = array();
    if ($user_id <= 0) {
        return $out;
    }

    try {
        $items = Item::newInstance()->findByUserID($user_id, 0, (int) $limit);
        if (!is_array($items)) {
            return $out;
        }
        foreach ($items as $item) {
            View::newInstance()->_erase('resources');
            View::newInstance()->_exportVariableToView('item', $item);

            $thumb = '';
            $item_id = (int) (isset($item['pk_i_id']) ? $item['pk_i_id'] : osc_item_id());
            if ($item_id > 0) {
                $resources = ItemResource::newInstance()->getAllResourcesFromItem($item_id);
                if (is_array($resources) && !empty($resources)) {
                    View::newInstance()->_exportVariableToView('resources', $resources);
                    osc_reset_resources();
                    if (osc_has_item_resources()) {
                        $thumb = osc_resource_thumbnail_url();
                        if ($thumb === '') {
                            $thumb = osc_resource_preview_url();
                        }
                        if ($thumb === '') {
                            $thumb = osc_resource_url();
                        }
                    }
                }
            }

            $title = osc_item_title();
            $time = function_exists('eps_smart_date') ? eps_smart_date(osc_item_pub_date()) : osc_format_date(osc_item_pub_date());
            $out[] = array(
                'type'  => 'listing',
                'icon'  => 'plus',
                'label' => __('New listing posted', 'epsilon'),
                'title' => $title,
                'time'  => $time,
                'thumb' => $thumb,
                'url'   => osc_item_url(),
            );
        }
        View::newInstance()->_erase('resources');
        View::newInstance()->_erase('item');
    } catch (Exception $e) {
        return array();
    }

    return $out;
}

/**
 * Initials from a display name (e.g. Valves Solutions → VS).
 *
 * @param string $name
 * @return string
 */
function pngm_ua_user_initials($name)
{
    $name = trim(preg_replace('/\s+/', ' ', (string) $name));
    if ($name === '') {
        return '?';
    }
    $parts = explode(' ', $name);
    if (count($parts) === 1) {
        if (function_exists('mb_substr')) {
            return strtoupper(mb_substr($parts[0], 0, 2));
        }
        return strtoupper(substr($parts[0], 0, 2));
    }
    $a = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1) : substr($parts[0], 0, 1);
    $b = function_exists('mb_substr') ? mb_substr($parts[count($parts) - 1], 0, 1) : substr($parts[count($parts) - 1], 0, 1);
    return strtoupper($a . $b);
}

/**
 * Shared account page header: title + optional subtitle + Hi userchip.
 *
 * @param string $title
 * @param string $subtitle
 */
function pngm_ua_render_page_header($title, $subtitle = '')
{
    $user_id = osc_logged_user_id();
    $name = osc_logged_user_name();
    echo '<div class="pngm-ua-top pngm-ua-page-head">';
    echo '<div class="pngm-ua-welcome">';
    echo '<h1>' . osc_esc_html($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p>' . osc_esc_html($subtitle) . '</p>';
    }
    echo '</div>';
    echo '<div class="pngm-ua-userchip">';
    echo '<a href="' . osc_esc_html(osc_user_profile_url()) . '" class="pngm-ua-userchip-link">';
    echo '<img src="' . osc_esc_html(function_exists('eps_profile_picture') ? eps_profile_picture($user_id, 'medium') : osc_user_profile_img_url($user_id)) . '" alt="" width="48" height="48" />';
    echo '<span>';
    echo '<strong>' . sprintf(__('Hi, %s', 'epsilon'), osc_esc_html($name)) . '</strong>';
    echo '<small>' . osc_esc_html(__('My Account', 'epsilon')) . ' <i class="fas fa-chevron-down" aria-hidden="true"></i></small>';
    echo '</span></a></div></div>';
}

/**
 * Dial-code list for profile phone selector (ISO + dial + flag).
 *
 * @return array
 */
function pngm_ua_phone_dial_codes()
{
    return array(
        array('iso' => 'PG', 'dial' => '675', 'flag' => '🇵🇬', 'label' => 'Papua New Guinea'),
        array('iso' => 'AU', 'dial' => '61', 'flag' => '🇦🇺', 'label' => 'Australia'),
        array('iso' => 'NZ', 'dial' => '64', 'flag' => '🇳🇿', 'label' => 'New Zealand'),
        array('iso' => 'ID', 'dial' => '62', 'flag' => '🇮🇩', 'label' => 'Indonesia'),
        array('iso' => 'FJ', 'dial' => '679', 'flag' => '🇫🇯', 'label' => 'Fiji'),
        array('iso' => 'SB', 'dial' => '677', 'flag' => '🇸🇧', 'label' => 'Solomon Islands'),
        array('iso' => 'VU', 'dial' => '678', 'flag' => '🇻🇺', 'label' => 'Vanuatu'),
        array('iso' => 'US', 'dial' => '1', 'flag' => '🇺🇸', 'label' => 'United States'),
        array('iso' => 'GB', 'dial' => '44', 'flag' => '🇬🇧', 'label' => 'United Kingdom'),
        array('iso' => 'IN', 'dial' => '91', 'flag' => '🇮🇳', 'label' => 'India'),
        array('iso' => 'CN', 'dial' => '86', 'flag' => '🇨🇳', 'label' => 'China'),
        array('iso' => 'PH', 'dial' => '63', 'flag' => '🇵🇭', 'label' => 'Philippines'),
        array('iso' => 'MY', 'dial' => '60', 'flag' => '🇲🇾', 'label' => 'Malaysia'),
        array('iso' => 'SG', 'dial' => '65', 'flag' => '🇸🇬', 'label' => 'Singapore'),
    );
}

/**
 * Split stored phone into dial code + local number.
 *
 * @param string $phone
 * @return array{dial:string,local:string,iso:string}
 */
function pngm_ua_phone_split($phone)
{
    $raw = trim((string) $phone);
    $digits = preg_replace('/\D+/', '', $raw);
    $codes = pngm_ua_phone_dial_codes();
    usort($codes, function ($a, $b) {
        return strlen($b['dial']) - strlen($a['dial']);
    });

    foreach ($codes as $row) {
        $d = $row['dial'];
        if ($digits !== '' && strpos($digits, $d) === 0 && strlen($digits) > strlen($d)) {
            return array(
                'dial' => $d,
                'local' => substr($digits, strlen($d)),
                'iso' => $row['iso'],
            );
        }
    }

    return array(
        'dial' => '675',
        'local' => $digits,
        'iso' => 'PG',
    );
}

/**
 * Merge dial + local into s_phone_mobile before profile save.
 */
function pngm_ua_profile_phone_merge()
{
    if (Params::getParam('page') !== 'user' || Params::getParam('action') !== 'profile_post') {
        return;
    }
    if (!Params::existParam('pngm_phone_dial') && !Params::existParam('pngm_phone_local')) {
        return;
    }
    $dial = preg_replace('/\D+/', '', (string) Params::getParam('pngm_phone_dial'));
    $local = preg_replace('/\D+/', '', (string) Params::getParam('pngm_phone_local'));
    if ($dial === '') {
        $dial = '675';
    }
    if ($local === '') {
        Params::setParam('s_phone_mobile', '');
        return;
    }
    Params::setParam('s_phone_mobile', '+' . $dial . $local);
}
osc_add_hook('init', 'pngm_ua_profile_phone_merge', 4);

/**
 * Render design-matching account sidebar.
 *
 * @param string $active
 */
function pngm_ua_render_sidebar($active = '')
{
    if ($active === '') {
        $active = pngm_ua_active_key();
    }

    $user_id = osc_logged_user_id();
    $count_active = function_exists('eps_count_user_items') ? eps_count_user_items($user_id, 'active') : 0;
    $count_all_active = $count_active;
    $count_pending = function_exists('eps_count_user_items') ? eps_count_user_items($user_id, 'pending_validate') : 0;
    $count_expired = function_exists('eps_count_user_items') ? eps_count_user_items($user_id, 'expired') : 0;
    $count_listings = $count_active + $count_pending + $count_expired;
    $count_messages = function_exists('eps_count_messages') ? eps_count_messages($user_id) : 0;

    $has_business = function_exists('bpr_call_after_install');
    $user = User::newInstance()->findByPrimaryKey($user_id);
    $is_company = is_array($user) && (int) @$user['b_company'] === 1;

    $item = function ($key, $url, $label, $icon, $counter = null) use ($active) {
        $cls = 'pngm-ua-nav-link' . ($active === $key ? ' is-active' : '');
        echo '<a class="' . $cls . '" href="' . osc_esc_html($url) . '">';
        echo '<i class="' . osc_esc_html($icon) . '" aria-hidden="true"></i>';
        echo '<span>' . osc_esc_html($label) . '</span>';
        if ($counter !== null && (int) $counter > 0) {
            echo '<em class="pngm-ua-nav-count">' . (int) $counter . '</em>';
        }
        echo '</a>';
    };

    echo '<aside id="user-menu" class="pngm-ua-sidebar" aria-label="' . osc_esc_html(__('Account menu', 'epsilon')) . '">';
    echo '<button type="button" class="pngm-ua-nav-close" aria-label="' . osc_esc_html(__('Close menu', 'epsilon')) . '">&times;</button>';
    echo '<div class="pngm-ua-brand">';
    echo '<a href="' . osc_esc_html(osc_base_url()) . '" class="pngm-ua-brand-link">';
    echo '<span class="pngm-ua-brand-mark" aria-hidden="true"><i class="fas fa-check"></i></span>';
    echo '<span class="pngm-ua-brand-text"><strong>PNG Market</strong><small>' . osc_esc_html(__('Buy · Sell · Find Anything', 'epsilon')) . '</small></span>';
    echo '</a></div>';

    echo '<nav class="pngm-ua-nav">';
    echo '<div class="pngm-ua-nav-group"><div class="pngm-ua-nav-label">' . osc_esc_html(__('Account', 'epsilon')) . '</div>';
    $item('dashboard', osc_user_dashboard_url(), __('Dashboard', 'epsilon'), 'fas fa-th-large');
    $item('active', eps_user_items_url('active'), __('Active Listings', 'epsilon'), 'fas fa-check-circle', $count_all_active);
    $item('listings', eps_user_items_url('all'), __('My Listings', 'epsilon'), 'fas fa-list', $count_listings);
    if (function_exists('im_messages')) {
        $item('messages', osc_route_url('im-threads'), __('Messages', 'epsilon'), 'fas fa-comment-dots', $count_messages);
    }
    $item('subscriptions', function_exists('pngm_sub_url') ? pngm_sub_url() : osc_route_url('pngm-subscriptions'), __('Subscriptions', 'epsilon'), 'fas fa-credit-card');
    echo '</div>';

    echo '<div class="pngm-ua-nav-group"><div class="pngm-ua-nav-label">' . osc_esc_html(__('Profile', 'epsilon')) . '</div>';
    $item('profile', osc_user_profile_url(), __('My Profile', 'epsilon'), 'fas fa-user');
    $item('public', osc_user_public_profile_url($user_id), __('Public Seller Profile', 'epsilon'), 'fas fa-id-card');
    if ($has_business && (function_exists('bpr_param') ? (bpr_param('only_company_users') == 0 || $is_company) : true)) {
        $item('business', osc_route_url('bpr-profile'), __('Business Profile', 'epsilon'), 'fas fa-briefcase');
    }
    echo '</div>';

    echo '<div class="pngm-ua-nav-group"><div class="pngm-ua-nav-label">' . osc_esc_html(__('Settings', 'epsilon')) . '</div>';
    $item('notifications', function_exists('pngm_notif_prefs_url') ? pngm_notif_prefs_url() : osc_route_url('pngm-notif-prefs'), __('Notification Preferences', 'epsilon'), 'fas fa-sliders-h');
    $item('security', function_exists('pngm_sec_url') ? pngm_sec_url() : osc_route_url('pngm-account-security'), __('Account & Security', 'epsilon'), 'fas fa-shield-alt');
    echo '</div>';

    echo '<div class="pngm-ua-nav-hooks menu-hooks">';
    osc_run_hook('user_menu_items');
    osc_run_hook('user_menu');
    echo '</div>';

    echo '</nav>';

    echo '<a class="pngm-ua-logout" href="' . osc_esc_html(osc_user_logout_url()) . '"><i class="fas fa-sign-out-alt" aria-hidden="true"></i><span>' . osc_esc_html(__('Logout', 'epsilon')) . '</span></a>';
    echo '</aside>';
}

/**
 * Listing status counts for My Listings tabs.
 *
 * @param int $user_id
 * @return array{all:int,active:int,pending_validate:int,blocked:int,expired:int}
 */
function pngm_ua_listing_counts($user_id)
{
    $user_id = (int) $user_id;
    $empty = array(
        'all' => 0,
        'active' => 0,
        'pending_validate' => 0,
        'blocked' => 0,
        'expired' => 0,
    );
    if ($user_id <= 0) {
        return $empty;
    }

    $m = Item::newInstance();
    return array(
        'all' => (int) $m->countItemTypesByUserID($user_id, 'all'),
        'active' => (int) $m->countItemTypesByUserID($user_id, 'active'),
        'pending_validate' => (int) $m->countItemTypesByUserID($user_id, 'pending_validate'),
        'blocked' => (int) $m->countItemTypesByUserID($user_id, 'blocked'),
        'expired' => (int) $m->countItemTypesByUserID($user_id, 'expired'),
    );
}

/**
 * Current listing status for badge styling.
 *
 * @return array{key:string,label:string}
 */
function pngm_ua_item_status()
{
    if (function_exists('osc_item_is_expired') && osc_item_is_expired()) {
        return array('key' => 'expired', 'label' => __('Expired', 'epsilon'));
    }
    if (function_exists('osc_item_is_enabled') && !osc_item_is_enabled()) {
        return array('key' => 'inactive', 'label' => __('Inactive', 'epsilon'));
    }
    if (function_exists('osc_item_is_inactive') && osc_item_is_inactive()) {
        return array('key' => 'pending', 'label' => __('Pending validation', 'epsilon'));
    }
    return array('key' => 'active', 'label' => __('Active', 'epsilon'));
}

/**
 * Category breadcrumb for current item (Root > Child).
 *
 * @return string
 */
function pngm_ua_item_category_path()
{
    $cat_id = (int) osc_item_category_id();
    if ($cat_id <= 0) {
        return (string) osc_item_category();
    }

    $tree = Category::newInstance()->hierarchy($cat_id);
    if (!is_array($tree) || empty($tree)) {
        return (string) osc_item_category();
    }

    $parts = array();
    $locale = osc_current_user_locale();
    foreach ($tree as $cat) {
        $name = '';
        if (isset($cat['locale'][$locale]['s_name'])) {
            $name = $cat['locale'][$locale]['s_name'];
        } elseif (isset($cat['s_name'])) {
            $name = $cat['s_name'];
        }
        if ($name !== '') {
            $parts[] = $name;
        }
    }

    return !empty($parts) ? implode(' > ', $parts) : (string) osc_item_category();
}

/**
 * Compact location for listing cards.
 *
 * @return string
 */
function pngm_ua_item_location_short()
{
    $parts = array_filter(array(osc_item_city(), osc_item_region()));
    if (empty($parts) && function_exists('eps_user_item_location')) {
        return (string) eps_user_item_location();
    }
    return implode(', ', $parts);
}

/**
 * Build My Listings filter URL preserving search params.
 *
 * @param string $type
 * @param array  $extra
 * @return string
 */
function pngm_ua_items_url($type = 'all', $extra = array())
{
    $param = (osc_version() >= 830 ? 'sItemType' : 'itemType');
    $base = osc_user_list_items_url();
    $q = array();

    if ($type !== '' && $type !== 'all') {
        $q[$param] = $type;
    }

    $keep = array('sPattern', 'sCategory', 'sOrder', 'sOrderType');
    foreach ($keep as $k) {
        $v = Params::getParam($k);
        if ($v !== '' && $v !== null) {
            $q[$k] = $v;
        }
    }

    if (is_array($extra)) {
        foreach ($extra as $k => $v) {
            if ($v === null || $v === '') {
                unset($q[$k]);
            } else {
                $q[$k] = $v;
            }
        }
    }

    if (empty($q)) {
        return $base;
    }

    $sep = (strpos($base, '?') !== false) ? '&' : '?';
    return $base . $sep . http_build_query($q);
}

/**
 * Allow Inactive (blocked) in user item type search.
 */
function pngm_ua_search_item_types($types)
{
    if (!is_array($types)) {
        $types = array();
    }
    $has_blocked = false;
    foreach ($types as $t) {
        if (isset($t['pk_i_id']) && $t['pk_i_id'] === 'blocked') {
            $has_blocked = true;
            break;
        }
    }
    if (!$has_blocked) {
        $types[] = array('pk_i_id' => 'blocked', 's_name' => __('Inactive', 'epsilon'));
    }
    return $types;
}
osc_add_filter('search_item_types', 'pngm_ua_search_item_types');
