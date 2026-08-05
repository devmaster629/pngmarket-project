<?php
/**
 * PNG Market child theme.
 *
 * Everything here layers on top of the Epsilon parent theme so that Epsilon
 * updates never overwrite our design work.
 */

if (!defined('PNGM_CHILD_VERSION')) {
    define('PNGM_CHILD_VERSION', '1.0.0');
}


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

    osc_register_script('pngm-custom', osc_current_web_theme_url('js/custom.js' . $version), array('jquery'));
    osc_enqueue_script('pngm-custom');
}

osc_add_hook('header', 'pngm_enqueue_assets', 8);


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
