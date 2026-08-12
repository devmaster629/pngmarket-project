<?php
/**
 * Category icons that match PNG Market category names/IDs.
 * Sample theme PNGs (1.png–8.png) are from the default Osclass demo and do not
 * match this site's remapped categories — always resolve by name/id here.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'category_icons.php'
) {
    exit;
}

/**
 * Font Awesome class (without fas/far prefix) for a category.
 *
 * @param int         $category_id
 * @param string|null $category_name
 * @return string
 */
function pngm_category_fa_icon($category_id, $category_name = null)
{
    $id = (int) $category_id;

    $by_id = array(
        1   => 'fa-car',
        2   => 'fa-mobile-alt',
        3   => 'fa-couch',
        4   => 'fa-tshirt',
        5   => 'fa-baby',
        6   => 'fa-football-ball',
        7   => 'fa-paw',
        8   => 'fa-building',
        96  => 'fa-briefcase',
        97  => 'fa-tools',
        124 => 'fa-seedling',
        133 => 'fa-industry',
    );

    if (isset($by_id[$id])) {
        return $by_id[$id];
    }

    $name = function_exists('mb_strtolower')
        ? mb_strtolower(trim((string) $category_name), 'UTF-8')
        : strtolower(trim((string) $category_name));

    if ($name === '') {
        return 'fa-th-large';
    }

    if (strpos($name, 'vehicle') !== false || strpos($name, 'car') !== false) {
        return 'fa-car';
    }
    if (strpos($name, 'phone') !== false || strpos($name, 'electronic') !== false) {
        return 'fa-mobile-alt';
    }
    if (strpos($name, 'home') !== false || strpos($name, 'furniture') !== false || strpos($name, 'garden') !== false) {
        return 'fa-couch';
    }
    if (strpos($name, 'fashion') !== false || strpos($name, 'beauty') !== false) {
        return 'fa-tshirt';
    }
    if (strpos($name, 'bab') !== false || strpos($name, 'kid') !== false || strpos($name, 'toy') !== false) {
        return 'fa-baby';
    }
    if (strpos($name, 'sport') !== false || strpos($name, 'hobb') !== false || strpos($name, 'leisure') !== false) {
        return 'fa-football-ball';
    }
    if (strpos($name, 'pet') !== false || strpos($name, 'animal') !== false) {
        return 'fa-paw';
    }
    if (strpos($name, 'propert') !== false || strpos($name, 'real estate') !== false) {
        return 'fa-building';
    }
    if (strpos($name, 'job') !== false || strpos($name, 'career') !== false) {
        return 'fa-briefcase';
    }
    if (strpos($name, 'service') !== false) {
        return 'fa-tools';
    }
    if (strpos($name, 'agricultur') !== false || strpos($name, 'farm') !== false) {
        return 'fa-seedling';
    }
    if (strpos($name, 'business') !== false || strpos($name, 'industrial') !== false) {
        return 'fa-industry';
    }

    return 'fa-th-large';
}

/**
 * Render home/search category icon HTML (Font Awesome, color-aware).
 *
 * @param int   $category_id
 * @param array $category
 * @return string
 */
function pngm_render_category_icon($category_id, $category = array())
{
    $name = '';
    if (is_array($category) && !empty($category['s_name'])) {
        $name = $category['s_name'];
    } elseif (function_exists('osc_category_name')) {
        $name = (string) osc_category_name();
    }

    $icon = pngm_category_fa_icon($category_id, $name);
    $color = function_exists('eps_get_cat_color') ? eps_get_cat_color($category_id, $category) : '';
    $style = ($color !== '' && $color !== false) ? ' style="color:' . osc_esc_html($color) . ';"' : '';

    return '<i class="fas ' . osc_esc_html($icon) . '" aria-hidden="true"' . $style . '></i>';
}

/**
 * Prefer matching SVG samples from Epsilon when available (by semantic name).
 *
 * @param int $category_id
 * @return string|false URL or false
 */
function pngm_category_svg_url($category_id)
{
    $map = array(
        1   => 'vehicles.svg',
        2   => 'phones-electronics.svg',
        3   => 'home-furniture-garden.svg',
        4   => 'fashion-beauty.svg',
        5   => 'babies-kids-toys.svg',
        6   => 'sports-hobbies-leisure.svg',
        7   => 'pets-animals.svg',
        8   => 'property.svg',
        96  => 'jobs.svg',
        97  => 'services.svg',
        124 => 'agriculture-farming.svg',
        133 => 'business-industrial.svg',
    );

    $id = (int) $category_id;
    if (!isset($map[$id])) {
        return false;
    }

    $file = $map[$id];
    $candidates = array(
        array(
            'path' => ABS_PATH . 'oc-content/themes/epsilon_child/images/small_cat/sample/' . $file,
            'url'  => osc_base_url() . 'oc-content/themes/epsilon_child/images/small_cat/sample/' . $file,
        ),
        array(
            'path' => ABS_PATH . 'oc-content/themes/epsilon/images.7225/small_cat/sample/' . $file,
            'url'  => osc_base_url() . 'oc-content/themes/epsilon/images.7225/small_cat/sample/' . $file,
        ),
        array(
            'path' => ABS_PATH . 'oc-content/themes/epsilon/images/small_cat/sample/' . $file,
            'url'  => osc_base_url() . 'oc-content/themes/epsilon/images/small_cat/sample/' . $file,
        ),
    );

    foreach ($candidates as $c) {
        if (is_file($c['path'])) {
            return $c['url'];
        }
    }

    return false;
}

/**
 * Image URL for a category icon (semantic SVG first, never wrong sample PNGs).
 *
 * @param int $category_id
 * @return string
 */
function pngm_get_cat_image($category_id)
{
    $svg = pngm_category_svg_url($category_id);
    if ($svg !== false) {
        return $svg;
    }

    // Child / parent custom PNG by id (correct uploads only).
    if (defined('ABS_PATH')) {
        $child = ABS_PATH . 'oc-content/themes/epsilon_child/images/small_cat/' . (int) $category_id . '.png';
        if (is_file($child)) {
            return osc_base_url() . 'oc-content/themes/epsilon_child/images/small_cat/' . (int) $category_id . '.png';
        }
    }

    if (function_exists('eps_get_cat_image') && (string) eps_param('sample_images') !== '1') {
        return eps_get_cat_image($category_id);
    }

    return osc_current_web_theme_url() . 'images/small_cat/default.png';
}
