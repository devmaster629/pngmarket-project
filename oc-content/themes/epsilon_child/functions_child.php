<?php
/**
 * PNG Market child theme.
 *
 * Everything here layers on top of the Epsilon parent theme so that Epsilon
 * updates never overwrite our design work.
 */

if (!defined('PNGM_CHILD_VERSION')) {
    define('PNGM_CHILD_VERSION', '1.2.3');
}

require_once dirname(__FILE__) . '/includes/vehicle_makes.php';
require_once dirname(__FILE__) . '/includes/locations.php';
require_once dirname(__FILE__) . '/includes/item_page.php';
require_once dirname(__FILE__) . '/includes/footer_helpers.php';
require_once dirname(__FILE__) . '/includes/plugins_integration.php';
require_once dirname(__FILE__) . '/includes/category_icons.php';
require_once dirname(__FILE__) . '/includes/listing_helpers.php';


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
    }

    osc_enqueue_style('pngm-custom', osc_current_web_theme_url('css/custom.css' . $version));

    osc_register_script('pngm-custom', osc_current_web_theme_url('js/custom.js' . $version), array('jquery', 'global'));
    osc_enqueue_script('pngm-custom');
}

osc_add_hook('header', 'pngm_enqueue_assets', 8);


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

        $ids = array('make', 'make_other', 'accessories', 'body', 'fuel', 'seats', 'transmission', 'condition');
        $escaped = array();
        foreach ($ids as $id) {
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
 * Newest listings for the active location (URL params, then cookie).
 * Used by SEARCH-04 when the keyword search returns nothing.
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

    $city = Params::getParam('sCity');
    $region = Params::getParam('sRegion');
    $country = Params::getParam('sCountry');

    if ($city === '' && $region === '' && $country === '' && function_exists('eps_location_from_cookies')) {
        $cookie = eps_location_from_cookies();

        if (@$cookie['success'] === true) {
            if (@$cookie['fk_i_city_id'] > 0) {
                $city = $cookie['fk_i_city_id'];
            } elseif (@$cookie['fk_i_region_id'] > 0) {
                $region = $cookie['fk_i_region_id'];
            } elseif (@$cookie['fk_c_country_code'] !== '') {
                $country = $cookie['fk_c_country_code'];
            }
        }
    }

    if ($city !== '' && $city !== null) {
        $mSearch->addCity($city);
    } elseif ($region !== '' && $region !== null) {
        $mSearch->addRegion($region);
    } elseif ($country !== '' && $country !== null) {
        $mSearch->addCountry($country);
    }

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
