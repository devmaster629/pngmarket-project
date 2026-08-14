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

    // Jobs
    if (strpos($name, 'administ') !== false || strpos($name, 'office') !== false) {
        return 'fa-clipboard';
    }
    if (strpos($name, 'account') !== false || strpos($name, 'finance') !== false) {
        return 'fa-calculator';
    }
    if (strpos($name, 'construct') !== false || strpos($name, 'trade') !== false) {
        return 'fa-hard-hat';
    }
    if (strpos($name, 'engineer') !== false) {
        return 'fa-cogs';
    }
    if (strpos($name, 'hospitalit') !== false || strpos($name, 'tourism') !== false) {
        return 'fa-utensils';
    }
    if (strpos($name, 'telecommunication') !== false || preg_match('/\bit\b/', $name)) {
        return 'fa-laptop';
    }
    if (strpos($name, 'mining') !== false || strpos($name, 'resource') !== false) {
        return 'fa-mountain';
    }
    if (strpos($name, 'logistic') !== false || strpos($name, 'transport') !== false || strpos($name, 'delivery') !== false) {
        return 'fa-truck';
    }
    if (strpos($name, 'retail') !== false || strpos($name, 'sales') !== false) {
        return 'fa-store';
    }
    if (strpos($name, 'customer') !== false) {
        return 'fa-headset';
    }
    if (strpos($name, 'education') !== false || strpos($name, 'training') !== false || strpos($name, 'tutor') !== false) {
        return 'fa-graduation-cap';
    }
    if (strpos($name, 'health') !== false || strpos($name, 'fitness') !== false) {
        return 'fa-heartbeat';
    }
    if (strpos($name, 'security') !== false) {
        return 'fa-shield-alt';
    }
    if (strpos($name, 'job') !== false || strpos($name, 'career') !== false) {
        return 'fa-briefcase';
    }

    // Vehicles
    if (strpos($name, 'motorcycl') !== false) {
        return 'fa-motorcycle';
    }
    if (strpos($name, 'boat') !== false || strpos($name, 'marine') !== false) {
        return 'fa-ship';
    }
    if (strpos($name, 'vehicle') !== false || strpos($name, 'car') !== false) {
        return 'fa-car';
    }

    // Electronics
    if (strpos($name, 'gaming') !== false || strpos($name, 'console') !== false) {
        return 'fa-gamepad';
    }
    if (strpos($name, 'computer') !== false) {
        return 'fa-desktop';
    }
    if (strpos($name, 'appliance') !== false) {
        return 'fa-blender';
    }
    if (strpos($name, 'phone') !== false || strpos($name, 'electronic') !== false) {
        return 'fa-mobile-alt';
    }

    // Property
    if (strpos($name, 'apartment') !== false) {
        return 'fa-building';
    }
    if (strpos($name, 'house') !== false || strpos($name, 'room') !== false) {
        return 'fa-home';
    }
    if (strpos($name, 'land') !== false) {
        return 'fa-map';
    }
    if (strpos($name, 'propert') !== false || strpos($name, 'real estate') !== false) {
        return 'fa-building';
    }

    // Home / fashion / family
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

    // Services / farming / business
    if (strpos($name, 'cleaning') !== false) {
        return 'fa-broom';
    }
    if (strpos($name, 'repair') !== false || strpos($name, 'maintenance') !== false) {
        return 'fa-wrench';
    }
    if (strpos($name, 'event') !== false || strpos($name, 'entertain') !== false) {
        return 'fa-music';
    }
    if (strpos($name, 'service') !== false) {
        return 'fa-tools';
    }
    if (strpos($name, 'agricultur') !== false || strpos($name, 'farm') !== false || strpos($name, 'seed') !== false || strpos($name, 'produce') !== false) {
        return 'fa-seedling';
    }
    if (strpos($name, 'business') !== false || strpos($name, 'industrial') !== false || strpos($name, 'wholesale') !== false) {
        return 'fa-industry';
    }

    return 'fa-th-large';
}

/**
 * Render home/search category icon HTML (Font Awesome, color-aware).
 *
 * @param int   $category_id
 * @param array $category
 * @param bool  $with_color Apply the category colour inline (home tiles). Off for chips.
 * @return string
 */
function pngm_render_category_icon($category_id, $category = array(), $with_color = true)
{
    $name = '';
    if (is_array($category) && !empty($category['s_name'])) {
        $name = $category['s_name'];
    } elseif (function_exists('osc_category_name')) {
        $name = (string) osc_category_name();
    }

    $icon = pngm_category_fa_icon($category_id, $name);
    $style = '';
    if ($with_color) {
        $color = function_exists('eps_get_cat_color') ? eps_get_cat_color($category_id, $category) : '';
        $style = ($color !== '' && $color !== false) ? ' style="color:' . osc_esc_html($color) . ';"' : '';
    }

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
