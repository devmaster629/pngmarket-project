<?php
/**
 * PNG Market child theme.
 *
 * Everything here layers on top of the Epsilon parent theme so that Epsilon
 * updates never overwrite our design work.
 */

if (!defined('PNGM_CHILD_VERSION')) {
    define('PNGM_CHILD_VERSION', '2.7.0');
}

require_once dirname(__FILE__) . '/includes/vehicle_makes.php';
require_once dirname(__FILE__) . '/includes/attributes_post_form.php';
require_once dirname(__FILE__) . '/includes/locations.php';
require_once dirname(__FILE__) . '/includes/item_page.php';
require_once dirname(__FILE__) . '/includes/footer_helpers.php';
require_once dirname(__FILE__) . '/includes/plugins_integration.php';
require_once dirname(__FILE__) . '/includes/category_icons.php';
require_once dirname(__FILE__) . '/includes/listing_helpers.php';
require_once dirname(__FILE__) . '/includes/listing_expiry.php';
require_once dirname(__FILE__) . '/includes/cron_setup.php';
require_once dirname(__FILE__) . '/includes/antispam_config.php';
require_once dirname(__FILE__) . '/includes/production_config.php';
require_once dirname(__FILE__) . '/includes/duplicate_listings.php';
require_once dirname(__FILE__) . '/includes/public_profile_gate.php';
require_once dirname(__FILE__) . '/includes/post_wizard.php';
require_once dirname(__FILE__) . '/includes/account_ua.php';
require_once dirname(__FILE__) . '/includes/notification_prefs.php';
require_once dirname(__FILE__) . '/includes/web_push.php';
require_once dirname(__FILE__) . '/includes/pwa.php';
require_once dirname(__FILE__) . '/includes/activity.php';
require_once dirname(__FILE__) . '/includes/account_security.php';
require_once dirname(__FILE__) . '/includes/persistent_login.php';
require_once dirname(__FILE__) . '/includes/subscriptions.php';
require_once dirname(__FILE__) . '/includes/attributes_display.php';
require_once dirname(__FILE__) . '/includes/item_detail_templates.php';
require_once dirname(__FILE__) . '/includes/verification.php';

/**
 * Total active listings matching a default-location cookie (no result limit).
 *
 * @param array $location
 * @return int
 */
function pngm_location_items_total($location)
{
    if (!is_array($location) || empty($location['success'])) {
        return 0;
    }

    $mSearch = new Search();
    if (!empty($location['fk_c_country_code'])) {
        $mSearch->addCountry($location['fk_c_country_code']);
    }
    if (!empty($location['fk_i_region_id'])) {
        $mSearch->addRegion($location['fk_i_region_id']);
    }
    if (!empty($location['fk_i_city_id'])) {
        $mSearch->addCity($location['fk_i_city_id']);
    }
    $mSearch->limit(0, 1);
    $mSearch->addGroupBy(DB_TABLE_PREFIX . 't_item.pk_i_id');
    $mSearch->doSearch();

    return (int) $mSearch->count();
}

/**
 * Search URL for the current default location cookie.
 *
 * @param array $location
 * @return string
 */
function pngm_location_search_url($location)
{
    $params = array('page' => 'search');
    if (!empty($location['fk_i_city_id'])) {
        $params['sCity'] = $location['fk_i_city_id'];
    } elseif (!empty($location['fk_i_region_id'])) {
        $params['sRegion'] = $location['fk_i_region_id'];
    } elseif (!empty($location['fk_c_country_code'])) {
        $params['sCountry'] = $location['fk_c_country_code'];
    }
    return osc_search_url($params);
}

/**
 * Total active site listings (for Latest "See all").
 *
 * @return int
 */
function pngm_total_active_items()
{
    $mSearch = new Search();
    $mSearch->limit(0, 1);
    $mSearch->doSearch();
    return (int) $mSearch->count();
}

/**
 * Unread conversations / messages waiting for the logged user.
 *
 * @return int
 */
function pngm_unread_message_count()
{
    if (!osc_is_web_user_logged_in() || !function_exists('eps_count_messages')) {
        return 0;
    }

    return (int) eps_count_messages(osc_logged_user_id());
}

/**
 * Bell badge: unread Activity items (not chat — messages use their own badge).
 *
 * @return int
 */
function pngm_notification_count()
{
    if (!osc_is_web_user_logged_in()) {
        return 0;
    }

    if (function_exists('pngm_notif_prefs_get')) {
        $prefs = pngm_notif_prefs_get(osc_logged_user_id());
        if (empty($prefs['allow'])) {
            return 0;
        }
    }

    if (function_exists('pngm_activity_unread_count')) {
        return pngm_activity_unread_count(osc_logged_user_id());
    }

    return 0;
}

/**
 * Bell opens the Activity feed (listing / account updates).
 *
 * @return string
 */
function pngm_notification_url()
{
    if (function_exists('pngm_activity_url')) {
        return pngm_activity_url();
    }

    return osc_user_alerts_url();
}

/**
 * Badge counts for the header/sidebar, refreshed by the front-end poller.
 */
function pngm_ajax_badge_counts()
{
    header('Content-Type: application/json; charset=utf-8');

    $threads = array();
    if (osc_is_web_user_logged_in()) {
        $ui = dirname(__FILE__) . '/includes/im_ui.php';
        if (file_exists($ui)) {
            require_once $ui;
        }
        if (function_exists('pngm_im_unread_thread_map')) {
            $threads = pngm_im_unread_thread_map(osc_logged_user_id(), 50);
        }
    }

    echo json_encode(array(
        'messages' => pngm_unread_message_count(),
        'notifications' => pngm_notification_count(),
        'threads' => $threads,
    ));
}

osc_add_hook('ajax_pngm_badge_counts', 'pngm_ajax_badge_counts');


/**
 * Enqueue child assets.
 *
 * Priority 8 so this runs before osc_load_styles (9) / osc_load_scripts (10),
 * while still being registered after the parent theme's own enqueues in
 * head.php. That ordering is what makes custom.css win over style.css.
 */
function pngm_enqueue_assets()
{
    $version = function_exists('eps_asset_version') ? eps_asset_version() : '';

    if ($version === '') {
        $version = '?v=' . PNGM_CHILD_VERSION;
    } else {
        $version .= '-' . PNGM_CHILD_VERSION;
    }

    // Phase 2 design system first, then page overrides in custom.css.
    osc_enqueue_style('pngm-design-system', osc_current_web_theme_url('css/design-system.css' . $version));
    osc_enqueue_style('pngm-custom', osc_current_web_theme_url('css/custom.css' . $version));

    $loc = function_exists('osc_get_osclass_location') ? (string) osc_get_osclass_location() : '';
    if ($loc === 'item') {
        osc_enqueue_style('pngm-item-detail-tpl', osc_current_web_theme_url('css/item-detail-templates.css' . $version));
    }
    $page_param = (string) Params::getParam('page');
    if (in_array($loc, array('login', 'register'), true)
        || in_array($page_param, array('login', 'register'), true)
    ) {
        osc_enqueue_style('pngm-auth', osc_current_web_theme_url('css/auth.css' . $version));
    }

    $static_slug = '';
    if ($page_param === 'page') {
        $static_slug = (string) Params::getParam('slug');
        if ($static_slug === '' && function_exists('osc_static_page_slug')) {
            $static_slug = (string) osc_static_page_slug();
        }
        if ($static_slug === '' && (string) Params::getParam('s_internal_name') !== '') {
            $static_slug = (string) Params::getParam('s_internal_name');
        }
    }
    if ($static_slug === '' && function_exists('osc_is_static_page') && osc_is_static_page() && function_exists('osc_static_page_slug')) {
        $static_slug = (string) osc_static_page_slug();
    }
    $req = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ($static_slug === '' && preg_match('#/(about|privacy|terms)(/|\?|$)#i', $req, $m)) {
        $static_slug = strtolower($m[1]);
    }

    if ($static_slug === 'about') {
        osc_enqueue_style('pngm-about', osc_current_web_theme_url('css/about.css' . $version));
    }
    if ($static_slug === 'privacy') {
        osc_enqueue_style('pngm-privacy', osc_current_web_theme_url('css/privacy.css' . $version));
    }
    if ($static_slug === 'terms') {
        osc_enqueue_style('pngm-terms', osc_current_web_theme_url('css/terms.css' . $version));
    }

    $is_contact = ($loc === 'contact')
        || ($page_param === 'contact')
        || (function_exists('osc_is_contact_page') && osc_is_contact_page());
    if ($is_contact) {
        osc_enqueue_style('pngm-contact', osc_current_web_theme_url('css/contact.css' . $version));
    }

    // Location chooser modal (header / navi) — always available when feature is on.
    if (function_exists('eps_param') && (int) eps_param('default_location') === 1) {
        osc_enqueue_style('pngm-location-modal', osc_current_web_theme_url('css/location-modal.css' . $version));
    }

    $is_ua = false;
    $is_pub_profile = false;
    $is_ua = false;
    $is_pub_profile = false;
    $is_twofa = (Params::getParam('route') === 'pngm-twofa-verify');
    if (function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in()) {
        $loc = osc_get_osclass_location();
        $is_ua = ($loc === 'user') || (strpos((string) Params::getParam('route'), 'im-') === 0)
            || (strpos((string) Params::getParam('route'), 'bpr-') === 0)
            || (strpos((string) Params::getParam('route'), 'favorite') === 0)
            || (strpos((string) Params::getParam('route'), 'osp-') === 0)
            || (Params::getParam('route') === 'pngm-notif-prefs')
            || (Params::getParam('route') === 'pngm-account-security')
            || (Params::getParam('route') === 'pngm-subscriptions')
            || (Params::getParam('route') === 'pngm-activity');
    }
    if (Params::getParam('action') === 'pub_profile'
        || (function_exists('osc_get_osclass_section') && osc_get_osclass_section() === 'pub_profile')
    ) {
        $is_pub_profile = true;
    }
    if ($is_ua || $is_pub_profile || $is_twofa) {
        osc_enqueue_style('pngm-account-ua', osc_current_web_theme_url('css/account-ua.css' . $version));
    }

    osc_register_script('pngm-gallery', osc_current_web_theme_url('js/gallery.js' . $version), array('jquery'));
    osc_enqueue_script('pngm-gallery');

    osc_register_script('pngm-custom', osc_current_web_theme_url('js/custom.js' . $version), array('jquery', 'global', 'validate', 'pngm-gallery'));
    osc_enqueue_script('pngm-custom');

    if (function_exists('osc_is_publish_page') && (osc_is_publish_page() || (function_exists('osc_is_edit_page') && osc_is_edit_page()))) {
        osc_enqueue_style('pngm-post-wizard', osc_current_web_theme_url('css/post-wizard.css' . $version));
        osc_register_script('pngm-post-wizard', osc_current_web_theme_url('js/post-wizard.js' . $version), array('jquery', 'pngm-custom'));
        osc_enqueue_script('pngm-post-wizard');
    }
}

osc_add_hook('header', 'pngm_enqueue_assets', 8);

/**
 * jQuery Validate can be enqueued again after custom.js; re-apply our wrapper.
 */
function pngm_repatch_jquery_validate()
{
    if (!osc_is_publish_page() && !osc_is_edit_page()) {
        return;
    }
    ?>
<script>
(function () {
  if (window.pngmItemValidation && window.pngmItemValidation.repatchValidatePlugin) {
    window.pngmItemValidation.repatchValidatePlugin();
  }
})();
</script>
    <?php
}

osc_add_hook('scripts_loaded', 'pngm_repatch_jquery_validate', 10);

/**
 * Accessible viewport — allow native pinch-to-zoom (ITEM-01 / P1-001).
 * Listing photos still use gallery.js transform zoom; rewrite parent theme tags
 * that ship with maximum-scale=1 or user-scalable=no.
 */
function pngm_viewport_meta()
{
    $content = 'width=device-width, initial-scale=1.0, viewport-fit=cover';
    echo '<meta name="viewport" content="' . $content . '" />' . "\n";
    echo '<script>(function(){var c=' . json_encode($content) . ';var m=document.querySelectorAll(\'meta[name="viewport"]\');for(var i=0;i<m.length;i++){m[i].setAttribute("content",c);}})();</script>' . "\n";
}

osc_add_hook('header', 'pngm_viewport_meta', 1);


/**
 * Banner helper that skips empty slots.
 *
 * Parent eps_banner() returns '' (not false) when banners are enabled but the
 * slot has no code, so `!== false` still renders an empty .banner-box and leaves
 * a blank strip on the page.
 *
 * @param string $location Banner location key (e.g. home_top).
 * @return string|false
 */
function pngm_banner($location)
{
    if (!function_exists('eps_banner')) {
        return false;
    }

    $html = eps_banner($location);

    if ($html === false || $html === null) {
        return false;
    }

    $html = trim((string) $html);

    if ($html === '') {
        return false;
    }

    // Ignore wrapper-only markup with no real ad content.
    $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
    if ($text === '' && !preg_match('/<(img|iframe|ins|script)\b/i', $html)) {
        return false;
    }

    return $html;
}


/**
 * Subcategories of the category the category loop currently points at.
 *
 * Reads the tree array directly instead of osc_has_subcategories() so it can be
 * called inside the main category loop without disturbing the View pointer.
 *
 * @param int $limit Maximum number of subcategories to return.
 *
 * @return array
 */
function pngm_current_subcategories($limit = 8)
{
    $category = osc_category();

    if (!is_array($category) || empty($category['categories'])) {
        return array();
    }

    $subcategories = array();

    foreach ($category['categories'] as $subcategory) {
        if (empty($subcategory['pk_i_id']) || empty($subcategory['s_name'])) {
            continue;
        }

        if (isset($subcategory['b_enabled']) && $subcategory['b_enabled'] == 0) {
            continue;
        }

        $subcategories[] = array(
            'id'   => $subcategory['pk_i_id'],
            'name' => $subcategory['s_name'],
            'url'  => osc_search_url(array('page' => 'search', 'sCategory' => $subcategory['pk_i_id'])),
        );

        if (count($subcategories) >= $limit) {
            break;
        }
    }

    return $subcategories;
}


/**
 * Enabled subcategories for a given category id (search / browse).
 *
 * @param int $category_id
 * @param int $limit 0 = no limit
 *
 * @return array
 */
function pngm_subcategories_for($category_id, $limit = 0)
{
    $category_id = (int) $category_id;

    if ($category_id <= 0 || !class_exists('Category')) {
        return array();
    }

    $rows = Category::newInstance()->findSubcategoriesEnabled($category_id);

    if (!is_array($rows) || count($rows) === 0) {
        return array();
    }

    $out = array();

    foreach ($rows as $row) {
        if (empty($row['pk_i_id']) || empty($row['s_name'])) {
            continue;
        }

        $out[] = array(
            'id'    => (int) $row['pk_i_id'],
            'name'  => $row['s_name'],
            'count' => isset($row['i_num_items']) ? (int) $row['i_num_items'] : 0,
            'url'   => osc_search_url(array('page' => 'search', 'sCategory' => $row['pk_i_id'])),
        );

        if ($limit > 0 && count($out) >= $limit) {
            break;
        }
    }

    return $out;
}


/**
 * Walk up to the enabled root category id for a category row/id.
 *
 * @param int|array|null $category
 *
 * @return int
 */
function pngm_category_root_id($category)
{
    if (is_numeric($category)) {
        $category = function_exists('eps_get_category') ? eps_get_category((int) $category) : null;
    }

    if (!is_array($category) || (int) @$category['pk_i_id'] <= 0) {
        return 0;
    }

    $root = $category;
    $guard = 0;
    while (is_array($root) && (int) @$root['fk_i_parent_id'] > 0 && $guard < 12) {
        $parent = function_exists('eps_get_category') ? eps_get_category((int) $root['fk_i_parent_id']) : null;
        if (!is_array($parent) || (int) @$parent['pk_i_id'] <= 0) {
            break;
        }
        $root = $parent;
        $guard += 1;
    }

    return (int) @$root['pk_i_id'];
}


/**
 * Search filter Category dropdown: root categories only.
 * Nested browsing stays in the icon strips above results.
 *
 * @param string     $name
 * @param array|null $category Current category row
 * @param string     $default_str
 */
function pngm_search_root_category_select($name = 'sCategory', $category = null, $default_str = '')
{
    if ($default_str === '') {
        $default_str = __('Category...', 'epsilon');
    }

    $roots = class_exists('Category') ? Category::newInstance()->findRootCategoriesEnabled() : array();
    if (!is_array($roots)) {
        $roots = array();
    }

    $selected = pngm_category_root_id($category);

    echo '<select name="' . osc_esc_html($name) . '" id="' . osc_esc_html($name) . '">';
    echo '<option value="">' . osc_esc_html($default_str) . '</option>';
    foreach ($roots as $root) {
        $id = (int) @$root['pk_i_id'];
        if ($id <= 0 || empty($root['s_name'])) {
            continue;
        }
        echo '<option value="' . $id . '"' . ($selected === $id ? ' selected="selected"' : '') . '>';
        echo osc_esc_html($root['s_name']);
        echo '</option>';
    }
    echo '</select>';
}


/**
 * Second strip: children of the active level-1 subcategory (when they exist).
 *
 * @param int        $search_cat_id
 * @param int        $level1_id      Active item from the root children strip
 * @param array|null $category
 *
 * @return array{parent: ?array, subcats: array, active_id: int}
 */
function pngm_search_nested_subcat_strip($search_cat_id, $level1_id, $category = null)
{
    $search_cat_id = (int) $search_cat_id;
    $level1_id = (int) $level1_id;
    $empty = array(
        'parent'    => null,
        'subcats'   => array(),
        'active_id' => 0,
    );

    if ($level1_id <= 0) {
        return $empty;
    }

    $root_id = pngm_category_root_id($level1_id);
    // Browsing the root itself — no nested row.
    if ($level1_id === $root_id) {
        return $empty;
    }

    $parent = function_exists('eps_get_category') ? eps_get_category($level1_id) : null;
    if (!is_array($parent) || (int) @$parent['pk_i_id'] <= 0) {
        return $empty;
    }

    $subcats = pngm_subcategories_for($level1_id);
    if (count($subcats) === 0) {
        return $empty;
    }

    $current = is_array($category) ? $category : null;
    if (!is_array($current) || (int) @$current['pk_i_id'] !== $search_cat_id) {
        $current = function_exists('eps_get_category') ? eps_get_category($search_cat_id) : null;
    }

    $active_id = $level1_id;
    if ($search_cat_id !== $level1_id && is_array($current)) {
        $walk = $current;
        $guard = 0;
        while (is_array($walk) && $guard < 12) {
            $parent_id = (int) @$walk['fk_i_parent_id'];
            if ($parent_id === $level1_id) {
                $active_id = (int) @$walk['pk_i_id'];
                break;
            }
            if ($parent_id <= 0) {
                break;
            }
            $walk = function_exists('eps_get_category') ? eps_get_category($parent_id) : null;
            $guard += 1;
        }
    }

    return array(
        'parent'    => $parent,
        'subcats'   => $subcats,
        'active_id' => $active_id,
    );
}


/**
 * Search subcategory strip: always list children of the root category.
 *
 * Mid-level categories (e.g. Other Vehicles) keep the full Vehicles strip
 * instead of drilling into their own (often tiny) child set.
 *
 * @param int        $search_cat_id
 * @param array|null $category      Current category row when already loaded
 *
 * @return array{parent: ?array, subcats: array, active_id: int}
 */
function pngm_search_subcat_strip($search_cat_id, $category = null)
{
    $search_cat_id = (int) $search_cat_id;
    $empty = array(
        'parent'    => null,
        'subcats'   => array(),
        'active_id' => 0,
    );

    if ($search_cat_id <= 0) {
        return $empty;
    }

    $current = is_array($category) ? $category : null;
    if (!is_array($current) || (int) @$current['pk_i_id'] !== $search_cat_id) {
        $current = function_exists('eps_get_category') ? eps_get_category($search_cat_id) : null;
    }

    if (!is_array($current) || (int) @$current['pk_i_id'] <= 0) {
        return $empty;
    }

    $root = $current;
    $guard = 0;
    while (is_array($root) && (int) @$root['fk_i_parent_id'] > 0 && $guard < 12) {
        $parent = function_exists('eps_get_category') ? eps_get_category((int) $root['fk_i_parent_id']) : null;
        if (!is_array($parent) || (int) @$parent['pk_i_id'] <= 0) {
            break;
        }
        $root = $parent;
        $guard += 1;
    }

    $root_id = (int) @$root['pk_i_id'];
    $subcats = pngm_subcategories_for($root_id);

    // Highlight the direct child of root that contains the current category.
    $active_id = $search_cat_id;
    if ($search_cat_id !== $root_id) {
        $walk = $current;
        $guard = 0;
        while (is_array($walk) && $guard < 12) {
            $parent_id = (int) @$walk['fk_i_parent_id'];
            if ($parent_id === $root_id) {
                $active_id = (int) @$walk['pk_i_id'];
                break;
            }
            if ($parent_id <= 0) {
                break;
            }
            $walk = function_exists('eps_get_category') ? eps_get_category($parent_id) : null;
            $guard += 1;
        }
    }

    return array(
        'parent'    => $root,
        'subcats'   => $subcats,
        'active_id' => $active_id,
    );
}


/**
 * CATEGORY-02 — Keep vehicle Attributes fields on Vehicles only.
 *
 * Seed data ships Car Make / Fuel / etc. with empty s_category_id, which the
 * Attributes plugin treats as “all categories” and breaks Phones posting.
 * Idempotent: only updates rows that are still unmapped.
 */
function pngm_fix_vehicle_attribute_categories()
{
    if (!defined('DB_TABLE_PREFIX') || !defined('DB_HOST')) {
        return;
    }

    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    try {
        $m = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if (!$m || $m->connect_error) {
            return;
        }

        $prefix = DB_TABLE_PREFIX;

        // Cars subcategory id (not parent Vehicles) so Motorcycles/Boats/Parts stay clean.
        $cars_id = 18;
        $cars = $m->query(
            "SELECT c.pk_i_id FROM {$prefix}t_category c
             INNER JOIN {$prefix}t_category_description d ON d.fk_i_category_id = c.pk_i_id
             WHERE c.fk_i_parent_id = 1 AND d.s_name = 'Cars'
             LIMIT 1"
        );
        if ($cars && $row = $cars->fetch_assoc()) {
            $cars_id = (int) $row['pk_i_id'];
        }

        $vehicles_root = 1;
        $vehicles = $m->query(
            "SELECT c.pk_i_id FROM {$prefix}t_category c
             INNER JOIN {$prefix}t_category_description d ON d.fk_i_category_id = c.pk_i_id
             WHERE (c.fk_i_parent_id IS NULL OR c.fk_i_parent_id = 0 OR c.fk_i_parent_id = '')
               AND d.s_name = 'Vehicles'
             LIMIT 1"
        );
        if ($vehicles && $vrow = $vehicles->fetch_assoc()) {
            $vehicles_root = (int) $vrow['pk_i_id'];
        }

        // Make / Brand must filter under any Vehicles branch (Cars, Motorbikes, Parts…).
        $vehicle_ids = array($vehicles_root);
        $pending = array($vehicles_root);
        $guard = 0;
        while (count($pending) > 0 && $guard < 40) {
            $parent = (int) array_shift($pending);
            $kids = $m->query(
                "SELECT pk_i_id FROM {$prefix}t_category
                 WHERE fk_i_parent_id = {$parent} AND b_enabled = 1"
            );
            if ($kids) {
                while ($kid = $kids->fetch_assoc()) {
                    $id = (int) $kid['pk_i_id'];
                    if ($id > 0 && !in_array($id, $vehicle_ids, true)) {
                        $vehicle_ids[] = $id;
                        $pending[] = $id;
                    }
                }
            }
            $guard += 1;
        }
        $vehicle_cat_csv = implode(',', $vehicle_ids);

        $make_ids = array('make', 'make_other');
        $make_escaped = array();
        foreach ($make_ids as $id) {
            $make_escaped[] = "'" . $m->real_escape_string($id) . "'";
        }
        $m->query(sprintf(
            "UPDATE %st_attribute
             SET s_category_id = '%s'
             WHERE s_identifier IN (%s)",
            $prefix,
            $m->real_escape_string($vehicle_cat_csv),
            implode(',', $make_escaped)
        ));

        // Car-specific attributes stay on Cars only.
        $car_only = array('accessories', 'body', 'fuel', 'seats', 'transmission', 'condition');
        $escaped = array();
        foreach ($car_only as $id) {
            $escaped[] = "'" . $m->real_escape_string($id) . "'";
        }

        $m->query(sprintf(
            "UPDATE %st_attribute
             SET s_category_id = '%d'
             WHERE s_identifier IN (%s)",
            $prefix,
            $cars_id,
            implode(',', $escaped)
        ));

        // Seats is a number, not free text ("abc").
        $m->query("UPDATE {$prefix}t_attribute SET s_type = 'NUMBER' WHERE s_identifier = 'seats'");

        pngm_seed_car_body_values($m, $prefix);
        $m->close();
    } catch (Exception $e) {
        // Plugin may be disabled / table missing — ignore.
    }
}

/**
 * Cars Body field: Sedan / Hatchback / Wagon / SUV / 4WD / Pickup / Van & Minibus / Other.
 *
 * @param mysqli $m
 * @param string $prefix
 */
function pngm_seed_car_body_values($m, $prefix)
{
    $res = $m->query("SELECT pk_i_id FROM {$prefix}t_attribute WHERE s_identifier = 'body' LIMIT 1");
    if (!$res || !($row = $res->fetch_assoc())) {
        return;
    }

    $attr_id = (int) $row['pk_i_id'];
    $wanted = array('Sedan', 'Hatchback', 'Wagon', 'SUV', '4WD', 'Pickup', 'Van & Minibus', 'Other');
    $rename = array(
        'Combi' => 'Wagon',
        'Coupe' => 'SUV',
        'Estate' => 'Wagon',
        'Station Wagon' => 'Wagon',
    );

    $existing = $m->query(
        "SELECT v.pk_i_id, l.pk_i_id AS loc_id, l.s_name, l.fk_c_locale_code
         FROM {$prefix}t_attribute_value v
         LEFT JOIN {$prefix}t_attribute_value_locale l ON l.fk_i_attribute_value_id = v.pk_i_id
         WHERE v.fk_i_attribute_id = {$attr_id}"
    );

    $have = array();
    $locale = 'en_US';

    if ($existing) {
        while ($r = $existing->fetch_assoc()) {
            if (!empty($r['fk_c_locale_code'])) {
                $locale = $r['fk_c_locale_code'];
            }
            $name = trim((string) $r['s_name']);
            if ($name === '') {
                continue;
            }
            if (isset($rename[$name]) && !empty($r['loc_id'])) {
                $new = $rename[$name];
                $m->query(sprintf(
                    "UPDATE %st_attribute_value_locale SET s_name = '%s' WHERE pk_i_id = %d",
                    $prefix,
                    $m->real_escape_string($new),
                    (int) $r['loc_id']
                ));
                $name = $new;
            }
            $have[strtolower($name)] = (int) $r['pk_i_id'];
        }
    }

    $order = 1;
    foreach ($wanted as $label) {
        $key = strtolower($label);
        if (isset($have[$key])) {
            $m->query("UPDATE {$prefix}t_attribute_value SET i_order = {$order} WHERE pk_i_id = " . (int) $have[$key]);
            $order++;
            continue;
        }

        $m->query("INSERT INTO {$prefix}t_attribute_value (fk_i_attribute_id, fk_i_parent_id, s_image, i_order) VALUES ({$attr_id}, NULL, '', {$order})");
        $vid = (int) $m->insert_id;
        if ($vid > 0) {
            $m->query(sprintf(
                "INSERT INTO %st_attribute_value_locale (fk_i_attribute_value_id, fk_c_locale_code, s_name) VALUES (%d, '%s', '%s')",
                $prefix,
                $vid,
                $m->real_escape_string($locale),
                $m->real_escape_string($label)
            ));
            $have[$key] = $vid;
        }
        $order++;
    }
}

osc_add_hook('init', 'pngm_fix_vehicle_attribute_categories', 8);


/**
 * Short, clean teaser for a listing card.
 *
 * Returns an empty string when the description is too thin to be worth showing,
 * so cards without meaningful copy stay compact instead of rendering a stub.
 *
 * @param int $length
 *
 * @return string
 */
function pngm_item_teaser($length = 80)
{
    $description = strip_tags(osc_item_description());
    $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
    $description = trim(preg_replace('/\s+/u', ' ', $description));

    if (mb_strlen($description, 'UTF-8') < 25) {
        return '';
    }

    if (mb_strlen($description, 'UTF-8') > $length) {
        $description = mb_substr($description, 0, $length, 'UTF-8');
        $description = preg_replace('/\s+\S*$/u', '', $description) . '...';
    }

    return osc_esc_html($description);
}


/**
 * SEARCH-01 — Expand keyword matching.
 *
 * Core already can search title + description when search_pattern_method = like.
 * This filter also matches category and parent-category (subcategory tree) names,
 * and requires every typed word to appear somewhere (partial / substring match).
 *
 * @param string $cond
 * @param string $pattern  Already SQL-escaped by Search::addPattern().
 * @param string $type     '' for normal search, 'premium' for premium query.
 *
 * @return string
 */
function pngm_search_cond_pattern($cond, $pattern, $type = '')
{
    $pattern = trim((string) $pattern);

    if ($pattern === '') {
        return $cond;
    }

    $item = ($type === 'premium') ? 'ti' : (DB_TABLE_PREFIX . 't_item');
    $prefix = DB_TABLE_PREFIX;
    $words = preg_split('/\s+/u', function_exists('mb_strtolower') ? mb_strtolower($pattern, 'UTF-8') : strtolower($pattern), -1, PREG_SPLIT_NO_EMPTY);

    if (!is_array($words) || count($words) === 0) {
        return $cond;
    }

    $parts = array();

    foreach ($words as $word) {
        if ($word === '') {
            continue;
        }

        // Skip tiny fragments that would match almost everything.
        if ((function_exists('mb_strlen') ? mb_strlen($word, 'UTF-8') : strlen($word)) < 2) {
            continue;
        }

        // Match title + description (including HTML-stripped description text)
        // and category / parent category names.
        $parts[] = sprintf(
            "("
            . "lower(ifnull(d.s_title,'')) like '%%%1\$s%%'"
            . " OR lower(ifnull(d.s_description,'')) like '%%%1\$s%%'"
            . " OR lower(replace(replace(replace(ifnull(d.s_description,''), '&nbsp;', ' '), '<br>', ' '), '<br/>', ' ')) like '%%%1\$s%%'"
            . " OR EXISTS ("
            . "   SELECT 1 FROM %2\$st_category_description pngm_cd"
            . "   INNER JOIN %2\$st_category pngm_c ON pngm_c.pk_i_id = pngm_cd.fk_i_category_id"
            . "   WHERE lower(pngm_cd.s_name) like '%%%1\$s%%'"
            . "     AND ("
            . "       pngm_c.pk_i_id = %3\$s.fk_i_category_id"
            . "       OR pngm_c.pk_i_id = (SELECT fk_i_parent_id FROM %2\$st_category WHERE pk_i_id = %3\$s.fk_i_category_id LIMIT 1)"
            . "     )"
            . " )"
            . ")",
            $word,
            $prefix,
            $item
        );
    }

    if (count($parts) === 0) {
        return $cond;
    }

    return '(' . implode(' AND ', $parts) . ')';
}

osc_add_filter('search_cond_pattern', 'pngm_search_cond_pattern');


/**
 * Prefer LIKE search so title + description substring matching works reliably.
 */
function pngm_force_like_search_pattern()
{
    if (function_exists('osc_set_preference') && function_exists('osc_get_preference')) {
        $method = (string) osc_get_preference('search_pattern_method');

        if ($method !== '' && $method !== 'like') {
            // Do not override an intentional fulltext setup every request;
            // only nudge empty / missing values.
            return;
        }

        if ($method === '') {
            osc_set_preference('search_pattern_method', 'like');
        }
    }
}

osc_add_hook('init', 'pngm_force_like_search_pattern', 2);


/**
 * Clear location cookie but stay on the search results page (SEARCH-04).
 */
function pngm_clear_location_stay()
{
    if (Params::getParam('pngmClearLocation') != 1) {
        return;
    }

    if (function_exists('eps_location_to_cookies')) {
        eps_location_to_cookies('');
    }

    $params = array('page' => 'search');

    if (Params::getParam('sPattern') !== '') {
        $params['sPattern'] = Params::getParam('sPattern');
    }

    if (Params::getParam('sCategory') !== '') {
        $params['sCategory'] = Params::getParam('sCategory');
    }

    header('Location: ' . osc_search_url($params));
    exit;
}

osc_add_hook('init', 'pngm_clear_location_stay', 1);


/**
 * Newest listings nationwide (no location filter).
 * Used when a keyword search returns nothing.
 *
 * @param int $limit
 *
 * @return array
 */
function pngm_newest_listings_nearby($limit = 12)
{
    $mSearch = new Search();
    $mSearch->order('dt_pub_date', 'DESC');
    $mSearch->limit(0, (int) $limit);

    $items = $mSearch->doSearch();

    return is_array($items) ? $items : array();
}


/**
 * Human-readable label for the active search location.
 *
 * @return string
 */
function pngm_active_location_label()
{
    $label = trim(implode(', ', array_filter(array(osc_search_city(), osc_search_region(), osc_search_country()))));

    if ($label !== '') {
        return $label;
    }

    if (function_exists('eps_location_from_cookies')) {
        $cookie = eps_location_from_cookies();

        if (@$cookie['success'] === true) {
            if (function_exists('osc_location_native_name_selector') && is_array($cookie)) {
                $name = osc_location_native_name_selector($cookie, 's_name');

                if ($name !== '') {
                    return $name;
                }
            }

            if (@$cookie['s_location'] !== '') {
                return $cookie['s_location'];
            }
        }
    }

    return '';
}

/**
 * Publish/edit form: title min 3 characters; description has no minimum length.
 */
function pngm_item_post_minlength_script()
{
    if (!osc_is_publish_page() && !osc_is_edit_page()) {
        return;
    }

    $title_msg = osc_esc_js(__('Title: enter at least 3 characters.', 'epsilon'));
    ?>
<script>
(function ($) {
  $(function () {
    var form = $('form[name="item"]');
    if (!form.length || !form.data('validator')) {
      return;
    }
    form.find('input[name^="title["]').each(function () {
      $(this).rules('add', { minlength: 3, messages: { minlength: '<?php echo $title_msg; ?>' } });
    });
    form.find('textarea[name^="description["]').each(function () {
      $(this).rules('remove', 'minlength');
    });
  });
})(jQuery);
</script>
    <?php
}

// Osclass Plugins::runHook only executes priorities 0–10.
osc_add_hook('footer', 'pngm_item_post_minlength_script', 8);

/**
 * Post-ad validation UX — must run after parent item-post.php inline .validate() init.
 */
function pngm_is_item_post_form_page()
{
    if (osc_is_publish_page() || osc_is_edit_page()) {
        return true;
    }

    return osc_get_osclass_location() === 'item'
        && in_array(Params::getParam('action'), array('item_add', 'item_edit'), true);
}

function pngm_item_post_validation_script()
{
    if (!pngm_is_item_post_form_page()) {
        return;
    }
    ?>
<script>
(function ($) {
  function enhance() {
    if (!window.pngmItemValidation) {
      return false;
    }

    window.pngmItemValidation.repatchValidatePlugin();
    return window.pngmItemValidation.forceEnhanceValidator();
  }

  function scheduleEnhance() {
    var attempts = 0;

    (function tryEnhance() {
      attempts += 1;

      if (enhance() || attempts >= 80) {
        return;
      }

      window.setTimeout(tryEnhance, 100);
    })();
  }

  scheduleEnhance();
  $(scheduleEnhance);
  $(window).on('load', scheduleEnhance);
})(jQuery);
</script>
    <?php
}

osc_add_hook('footer_after', 'pngm_item_post_validation_script', 10);

/**
 * Server-side: title must be at least 3 letters; description has no minimum.
 *
 * @param string $flash_error
 * @param array  $aItem
 * @return string
 */
function pngm_item_title_desc_length_error($flash_error, $aItem)
{
    $flash_error = (string) $flash_error;

    // Drop core "Description too short" (core requires 3 letters).
    $flash_error = preg_replace('/^.*Description too short.*(\r\n|\n|\r)?/mi', '', $flash_error);

    $titles = (isset($aItem['title']) && is_array($aItem['title'])) ? $aItem['title'] : array();
    foreach ($titles as $key => $value) {
        $value = strip_tags(trim((string) $value));
        // Core already rejects empty titles (min 1); catch 1–2 character titles.
        if (osc_validate_text($value, 1) && !osc_validate_text($value, 3)) {
            if (is_string($key) && $key !== '') {
                $flash_error .= sprintf(_m('Title too short (%s).'), $key) . PHP_EOL;
            } else {
                $flash_error .= _m('Title too short.') . PHP_EOL;
            }
        }
    }

    return $flash_error;
}

osc_add_filter('pre_item_add_error', 'pngm_item_title_desc_length_error', 10);
osc_add_filter('pre_item_edit_error', 'pngm_item_title_desc_length_error', 10);

/**
 * Show reCAPTCHA on auth pages when a site key exists.
 * Always paint our own widget markup and load the API from recaptcha.net
 * (mobile Safari / Android WebViews often block or mishandle google.com).
 *
 * @param string $section
 */
function pngm_auth_show_recaptcha($section = '')
{
    // Production keys reject 127.0.0.1 — hide widget on local so login is usable.
    if (function_exists('pngm_is_local_dev_host') && pngm_is_local_dev_host()) {
        return;
    }

    if (function_exists('anr_get_option') && anr_get_option('site_key') !== '') {
        if (function_exists('eps_show_recaptcha')) {
            eps_show_recaptcha($section === 'register' ? 'registration' : $section);
        }
        return;
    }

    $key = '';
    if (function_exists('osc_recaptcha_public_key')) {
        $key = trim((string) osc_recaptcha_public_key(true));
    }

    if ($key === '') {
        if (function_exists('eps_show_recaptcha')) {
            eps_show_recaptcha($section);
        }
        return;
    }

    $label = __('Security check — confirm you are human', 'epsilon');
    // Accessible region so auditors/screen readers see CAPTCHA before the iframe loads.
    echo '<div class="pngm-auth-captcha-label" id="pngm-recaptcha-label">'
        . osc_esc_html($label)
        . '</div>';
    echo '<div class="g-recaptcha pngm-g-recaptcha" role="group" aria-labelledby="pngm-recaptcha-label" data-sitekey="'
        . osc_esc_html($key)
        . '" data-pngm-recaptcha="1"></div>';
}

/**
 * Local development hosts where Google reCAPTCHA site keys usually fail
 * ("localhost is not in the list of supported domains").
 *
 * @return bool
 */
function pngm_is_local_dev_host()
{
    $host = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = strtolower((string) $_SERVER['HTTP_HOST']);
    } elseif (function_exists('osc_base_url')) {
        $host = strtolower((string) parse_url(osc_base_url(), PHP_URL_HOST));
    }
    $host = preg_replace('/:\d+$/', '', $host);
    return in_array($host, array('localhost', '127.0.0.1', '::1'), true);
}

/**
 * Soft-disable reCAPTCHA for this request only on local hosts (memory preference).
 * Does not change the DB — staging/production keep CAPTCHA enabled.
 */
function pngm_local_disable_recaptcha_runtime()
{
    if (!pngm_is_local_dev_host() || !class_exists('Preference')) {
        return;
    }
    Preference::newInstance()->set('recaptchaEnabled', '0', 'osclass');
    Preference::newInstance()->set('enabled_recaptcha_items', '0', 'osclass');
}
osc_add_hook('init', 'pngm_local_disable_recaptcha_runtime', 0);

/**
 * Front-end login/register must verify reCAPTCHA when the widget is configured.
 * On local hosts we skip CAPTCHA so email/password login works without Google domain setup.
 * Staging keeps CAPTCHA (login already works there).
 */
function pngm_recaptcha_is_required()
{
    if (function_exists('pngm_is_local_dev_host') && pngm_is_local_dev_host()) {
        return false;
    }

    if (!function_exists('osc_recaptcha_enabled') || !osc_recaptcha_enabled()) {
        return false;
    }

    $public = '';
    if (function_exists('osc_recaptcha_public_key')) {
        $public = trim((string) osc_recaptcha_public_key(true));
    }

    return $public !== '';
}

function pngm_recaptcha_token_valid()
{
    $token = '';
    if (isset($_POST['g-recaptcha-response'])) {
        $token = trim((string) $_POST['g-recaptcha-response']);
    } else {
        $token = trim((string) Params::getParam('g-recaptcha-response', false, false));
    }

    if ($token === '') {
        return false;
    }

    if (function_exists('osc_check_recaptcha')) {
        return osc_check_recaptcha();
    }

    return false;
}

function pngm_require_recaptcha_on_login()
{
    if (!pngm_recaptcha_is_required()) {
        return;
    }

    $token = '';
    if (isset($_POST['g-recaptcha-response'])) {
        $token = trim((string) $_POST['g-recaptcha-response']);
    } else {
        $token = trim((string) Params::getParam('g-recaptcha-response', false, false));
    }

    if ($token === '') {
        osc_add_flash_error_message(_m('The reCAPTCHA was not entered correctly'));
        osc_redirect_to(osc_user_login_url());
        return;
    }

    // Google tokens are single-use. Core login.php already calls osc_check_recaptcha().
    // Only verify here when Oc-Admin is logged in (core skips the check in that case).
    if (function_exists('osc_is_admin_user_logged_in') && osc_is_admin_user_logged_in()) {
        if (!function_exists('osc_check_recaptcha') || !osc_check_recaptcha()) {
            osc_add_flash_error_message(_m('The reCAPTCHA was not entered correctly'));
            osc_redirect_to(osc_user_login_url());
        }
    }
}

function pngm_require_recaptcha_on_register()
{
    if (!pngm_recaptcha_is_required()) {
        return;
    }

    $token = '';
    if (isset($_POST['g-recaptcha-response'])) {
        $token = trim((string) $_POST['g-recaptcha-response']);
    } else {
        $token = trim((string) Params::getParam('g-recaptcha-response', false, false));
    }

    if ($token === '') {
        osc_add_flash_error_message(_m('The reCAPTCHA was not entered correctly'));
        osc_redirect_to(osc_register_account_url());
        return;
    }

    // Do not call osc_check_recaptcha() here. Google tokens are single-use and
    // UserActions::add() always verifies on front-end register (even with Oc-Admin
    // logged in). Verifying twice causes valid CAPTCHAs to fail (QD-001).
}

function pngm_require_recaptcha_on_contact()
{
    if (Params::getParam('action') !== 'contact_post') {
        return;
    }

    if (!pngm_recaptcha_is_required()) {
        return;
    }

    $token = '';
    if (isset($_POST['g-recaptcha-response'])) {
        $token = trim((string) $_POST['g-recaptcha-response']);
    } else {
        $token = trim((string) Params::getParam('g-recaptcha-response', false, false));
    }

    if ($token === '') {
        osc_add_flash_error_message(_m('Recaptcha validation has failed'));
        Session::newInstance()->_setForm('yourName', Params::getParam('yourName'));
        Session::newInstance()->_setForm('yourEmail', Params::getParam('yourEmail'));
        Session::newInstance()->_setForm('subject', Params::getParam('subject'));
        Session::newInstance()->_setForm('message_body', Params::getParam('message'));
        osc_redirect_to(osc_contact_url());
        return;
    }

    // Contact controller verifies once — do not consume the token here.
}

osc_add_hook('before_validating_login', 'pngm_require_recaptcha_on_login');
osc_add_hook('before_user_register', 'pngm_require_recaptcha_on_register');
osc_add_hook('init_contact', 'pngm_require_recaptcha_on_contact');

/**
 * Mobile-safe reCAPTCHA loader (Android + iPhone).
 * - Prefer recaptcha.net (works when google.com is treated as a tracker)
 * - Use compact widget under ~420px so taps hit the checkbox
 * - Undo empty widgets and guard auth form submit without a token
 */
function pngm_recaptcha_incognito_fix()
{
    static $printed = false;
    if ($printed) {
        return;
    }

    if (function_exists('pngm_is_local_dev_host') && pngm_is_local_dev_host()) {
        return;
    }

    if (!function_exists('osc_recaptcha_public_key')) {
        return;
    }

    $site_key = osc_recaptcha_public_key(true);
    if ($site_key === '' || $site_key === false || $site_key === null) {
        return;
    }

    $printed = true;

    $lang = substr((string) osc_current_user_locale(), 0, 2);
    if ($lang === '') {
        $lang = 'en';
    }

    $required = function_exists('pngm_recaptcha_is_required') && pngm_recaptcha_is_required();
    $msg = __('Please complete the reCAPTCHA before continuing.', 'epsilon');
    ?>
<script>
(function () {
  var siteKey = <?php echo json_encode((string) $site_key); ?>;
  var lang = <?php echo json_encode($lang); ?>;
  var authRequired = <?php echo $required ? 'true' : 'false'; ?>;
  var missingMsg = <?php echo json_encode($msg); ?>;
  var loading = false;
  var scriptReady = false;

  function widgets() {
    return Array.prototype.slice.call(
      document.querySelectorAll('.g-recaptcha, [id^="anr_captcha_field_"], [data-pngm-recaptcha]')
    ).filter(function (el) {
      return !isCaptchaDeferredHidden(el);
    });
  }

  // Publish wizard keeps Review (and captcha) in display:none until the last step.
  // Google throws "reCAPTCHA Timeout" if we render while hidden.
  function isCaptchaDeferredHidden(el) {
    if (!el) {
      return true;
    }
    var panel = el.closest ? el.closest('.pngm-post-step-panel') : null;
    if (panel) {
      if (panel.hasAttribute('hidden') || panel.hidden) {
        return true;
      }
      try {
        if (window.getComputedStyle(panel).display === 'none') {
          return true;
        }
      } catch (e) {}
    }
    return false;
  }

  function isRendered(el) {
    if (!el) {
      return true;
    }
    if (el.getAttribute('data-pngm-rendered') === '1') {
      return true;
    }
    return !!el.querySelector('iframe, textarea[name="g-recaptcha-response"]');
  }

  function widgetSize() {
    // Compact fits narrow Android/iPhone widths without parent CSS scale hacks.
    try {
      if (window.matchMedia && window.matchMedia('(max-width: 420px)').matches) {
        return 'compact';
      }
    } catch (e) {}
    return (window.innerWidth && window.innerWidth <= 420) ? 'compact' : 'normal';
  }

  function renderOne(el) {
    if (!el || isRendered(el)) {
      return;
    }
    if (typeof window.grecaptcha === 'undefined' || typeof window.grecaptcha.render !== 'function') {
      return;
    }
    try {
      if (!el.getAttribute('data-sitekey')) {
        el.setAttribute('data-sitekey', siteKey);
      }
      // Clear leftover empty nodes from a failed google.com auto-render.
      while (el.firstChild) {
        el.removeChild(el.firstChild);
      }
      window.grecaptcha.render(el, {
        sitekey: siteKey,
        size: widgetSize(),
        theme: 'light'
      });
      el.setAttribute('data-pngm-rendered', '1');
    } catch (err) {
      // Already rendered by another script — mark done if iframe appeared.
      if (el.querySelector('iframe')) {
        el.setAttribute('data-pngm-rendered', '1');
      }
    }
  }

  function renderAll() {
    widgets().forEach(renderOne);
  }

  function loadApi(cb) {
    if (typeof window.grecaptcha !== 'undefined' && typeof window.grecaptcha.render === 'function') {
      scriptReady = true;
      if (typeof window.grecaptcha.ready === 'function') {
        window.grecaptcha.ready(function () { cb(); });
      } else {
        cb();
      }
      return;
    }

    if (loading) {
      return;
    }
    loading = true;

    window.pngmRecaptchaOnload = function () {
      scriptReady = true;
      if (typeof window.grecaptcha !== 'undefined' && typeof window.grecaptcha.ready === 'function') {
        window.grecaptcha.ready(function () { cb(); });
      } else {
        cb();
      }
    };

    var script = document.createElement('script');
    // recaptcha.net mirrors google.com and survives tracker-blocking mobile browsers.
    script.src = 'https://www.recaptcha.net/recaptcha/api.js?hl='
      + encodeURIComponent(lang)
      + '&onload=pngmRecaptchaOnload&render=explicit';
    script.async = true;
    script.defer = true;
    script.onerror = function () {
      loading = false;
      // Fallback if recaptcha.net is blocked.
      var fallback = document.createElement('script');
      fallback.src = 'https://www.google.com/recaptcha/api.js?hl='
        + encodeURIComponent(lang)
        + '&onload=pngmRecaptchaOnload&render=explicit';
      fallback.async = true;
      fallback.defer = true;
      document.head.appendChild(fallback);
    };
    document.head.appendChild(script);
  }

  function ensure() {
    if (!widgets().length) {
      return;
    }
    loadApi(renderAll);
  }

  function tokenFor(form) {
    if (!form) {
      return '';
    }
    var field = form.querySelector('textarea[name="g-recaptcha-response"]');
    if (field && field.value) {
      return String(field.value).trim();
    }
    // Some WebViews keep the response on a sibling widget outside the form briefly.
    var any = document.querySelector('textarea[name="g-recaptcha-response"]');
    return any && any.value ? String(any.value).trim() : '';
  }

  function showAuthToast(message) {
    var host = document.getElementById('pngm-loc-toast-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'pngm-loc-toast-host';
      host.className = 'pngm-loc-toast-host';
      host.setAttribute('aria-live', 'assertive');
      document.body.appendChild(host);
    }

    // One captcha toast at a time.
    var existing = host.querySelector('.pngm-loc-toast.pngm-auth-recaptcha-toast');
    if (existing && existing.parentNode) {
      existing.parentNode.removeChild(existing);
    }

    var toast = document.createElement('div');
    toast.className = 'pngm-loc-toast is-error pngm-auth-recaptcha-toast';
    toast.setAttribute('role', 'alert');
    toast.innerHTML =
      '<span class="pngm-loc-toast-msg"></span>' +
      '<button type="button" class="pngm-loc-toast-close" aria-label="Dismiss">&times;</button>';
    toast.querySelector('.pngm-loc-toast-msg').textContent = message || '';
    host.appendChild(toast);

    function dismiss() {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }

    toast.querySelector('.pngm-loc-toast-close').addEventListener('click', dismiss);
    window.setTimeout(dismiss, 4800);
  }

  function guardAuthForms() {
    if (!authRequired) {
      return;
    }
    var forms = document.querySelectorAll(
      'form.pngm-auth-form, form#pngm-login-form, form#register, body.pngm-auth form[action]'
    );
    Array.prototype.forEach.call(forms, function (form) {
      if (form.getAttribute('data-pngm-recaptcha-guard') === '1') {
        return;
      }
      form.setAttribute('data-pngm-recaptcha-guard', '1');
      form.addEventListener('submit', function (e) {
        if (!widgets().length) {
          return;
        }
        // Give a late-rendered widget one more chance before blocking.
        renderAll();
        var token = tokenFor(form);
        if (token) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        ensure();

        // Remove any leftover inline captcha error from older builds.
        var oldNote = form.querySelector('.pngm-auth-captcha-error');
        if (oldNote && oldNote.parentNode) {
          oldNote.parentNode.removeChild(oldNote);
        }
        var wrap = form.querySelector('.pngm-auth-captcha');
        if (wrap && wrap.classList) {
          wrap.classList.remove('is-error');
        }

        showAuthToast(missingMsg);
        return false;
      }, true);
    });
  }

  function boot() {
    document.addEventListener('pngm:post-step', function () {
      // Review step just became visible — safe to render deferred captcha.
      ensure();
    });
    guardAuthForms();
    if (!widgets().length) {
      return;
    }
    ensure();
    setTimeout(ensure, 400);
    setTimeout(ensure, 1200);
    setTimeout(ensure, 3000);
    setTimeout(ensure, 6000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.addEventListener('pageshow', function () {
    // iOS Safari back-forward cache can leave an empty widget.
    widgets().forEach(function (el) {
      if (!el.querySelector('iframe')) {
        el.removeAttribute('data-pngm-rendered');
      }
    });
    ensure();
  });

  window.addEventListener('orientationchange', function () {
    setTimeout(ensure, 350);
  });
})();
</script>
    <?php
}

// Must be 0–10: Osclass ignores higher footer priorities.
osc_add_hook('footer', 'pngm_recaptcha_incognito_fix', 9);

/**
 * Messages tab should open the latest conversation on desktop only.
 * Mobile keeps the conversation list first, then opens a thread on tap.
 */
function pngm_im_redirect_threads_to_latest()
{
    if (!osc_is_web_user_logged_in()) {
        return;
    }

    $route = (string) Params::getParam('route');
    if ($route !== 'im-threads' && $route !== 'im-thread-page') {
        return;
    }

    // Let threads.php handle block / flag / notify / remove actions first
    if (Params::getParam('action') === 'block_email') {
        return;
    }
    if ((int) Params::getParam('remove-id') > 0
        || (int) Params::getParam('thread-flag-id') > 0
        || (int) Params::getParam('thread-notify-id') > 0
        || (int) Params::getParam('thread-remove-id') > 0
    ) {
        return;
    }

    $ui = WebThemes::newInstance()->getCurrentThemePath() . 'includes/im_ui.php';
    if (!file_exists($ui)) {
        return;
    }
    require_once $ui;

    if (function_exists('pngm_im_is_mobile_request') && pngm_im_is_mobile_request()) {
        return;
    }

    $rows = pngm_im_prepare_conversations((int) osc_logged_user_id(), 1, 0);
    if (!is_array($rows) || empty($rows[0]['url'])) {
        return;
    }

    header('Location: ' . $rows[0]['url']);
    exit;
}

osc_add_hook('init', 'pngm_im_redirect_threads_to_latest', 9);

/**
 * AJAX listing report — always records the mark (core skips some browsers).
 */
function pngm_ajax_report_item()
{
    header('Content-Type: application/json; charset=utf-8');

    if (function_exists('osc_item_mark_disable') && osc_item_mark_disable()) {
        echo json_encode(array(
            'ok' => 0,
            'error' => 'disabled',
            'message' => __('This feature is disabled, you cannot mark or report listing', 'epsilon'),
        ));
        return;
    }

    $id = (int) Params::getParam('id');
    $as = (string) Params::getParam('as');
    $allowed = array('spam', 'badcat', 'repeated', 'expired', 'offensive');

    if ($id <= 0 || !in_array($as, $allowed, true)) {
        echo json_encode(array('ok' => 0, 'error' => 'invalid'));
        return;
    }

    $item = function_exists('osc_get_item_row') ? osc_get_item_row($id) : false;
    if (!$item) {
        echo json_encode(array('ok' => 0, 'error' => 'missing'));
        return;
    }

    $mItem = new ItemActions(false);
    $mItem->mark($id, $as);

    echo json_encode(array(
        'ok' => 1,
        'message' => __('Thanks! Your report was sent.', 'epsilon'),
    ));
}

osc_add_hook('ajax_pngm_report_item', 'pngm_ajax_report_item');

/**
 * Prefer deferred IM emails so SMTP does not block the chat send request.
 * The plugin cron still delivers the mail a few minutes later.
 */
function pngm_im_enable_deferred_email()
{
    if (!function_exists('im_param')) {
        return;
    }
    if ((int) im_param('email_deferred') === 1) {
        return;
    }
    osc_set_preference('email_deferred', '1', 'plugin-instant_messenger', 'INTEGER');
    if (class_exists('Preference')) {
        Preference::newInstance()->set('email_deferred', '1', 'plugin-instant_messenger');
    }
}
osc_add_hook('init', 'pngm_im_enable_deferred_email', 8);

/**
 * Lightweight AJAX send — inserts the message without a full page redirect.
 */
function pngm_ajax_im_send()
{
    header('Content-Type: application/json; charset=utf-8');

    if (!osc_is_web_user_logged_in() || !class_exists('ModelIM') || !function_exists('im_insert_message')) {
        echo json_encode(array('ok' => 0, 'error' => 'auth'));
        return;
    }

    $thread_id = (int) Params::getParam('thread-id');
    $secret = (string) Params::getParam('secret');
    $message_raw = Params::getParam('im-message', false, false);

    $thread = ModelIM::newInstance()->getThreadById($thread_id);
    if (!function_exists('im_is_valid_thread') || !im_is_valid_thread($thread)) {
        echo json_encode(array('ok' => 0, 'error' => 'thread'));
        return;
    }

    $ctx = function_exists('im_thread_context') ? im_thread_context($thread, $secret) : null;
    if (!is_array($ctx) || !isset($ctx['send_type'])) {
        echo json_encode(array('ok' => 0, 'error' => 'denied'));
        return;
    }
    if (array_key_exists('can_view', $ctx) && empty($ctx['can_view'])) {
        echo json_encode(array('ok' => 0, 'error' => 'denied'));
        return;
    }

    $type = (int) $ctx['send_type'];
    $message_text = nl2br(htmlspecialchars(function_exists('im_str') ? im_str($message_raw) : (string) $message_raw, ENT_QUOTES, 'UTF-8'));

    $files = array();
    if (function_exists('im_uploaded_file_list')) {
        $files = im_uploaded_file_list(Params::getFiles('im-file'));
        if (count($files) === 0) {
            $files = im_uploaded_file_list(Params::getFiles('im-file[]'));
        }
    }

    if (trim(strip_tags($message_text)) === '' && count($files) === 0) {
        echo json_encode(array('ok' => 0, 'error' => 'empty'));
        return;
    }

    $id = 0;
    $last_file_name = '';
    if (count($files) === 0) {
        $id = (int) im_insert_message($thread_id, $message_text, $type, array(), true, false);
    } else {
        $n = count($files);
        foreach ($files as $i => $file) {
            $text = ($i === 0 ? $message_text : '');
            $id = (int) im_insert_message($thread_id, $text, $type, $file, $i === 0, false);
            if ($id > 0 && !empty($file['name']) && function_exists('pngm_im_set_file_label')) {
                $ui = dirname(__FILE__) . '/includes/im_ui.php';
                if (file_exists($ui)) {
                    require_once $ui;
                }
                pngm_im_set_file_label($id, $file['name']);
                $last_file_name = basename((string) $file['name']);
            }
        }
    }

    if ($id <= 0) {
        echo json_encode(array('ok' => 0, 'error' => 'insert'));
        return;
    }

    $payload = array(
        'ok' => 1,
        'id' => $id,
        'time' => __('Just now', 'epsilon'),
    );
    if ($last_file_name !== '') {
        $payload['file'] = $last_file_name;
    }
    echo json_encode($payload);
}
osc_add_hook('ajax_pngm_im_send', 'pngm_ajax_im_send');

