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
 * Photographic theme cover by subcategory name (unique product photo, not parent duplicate).
 *
 * @param string $category_name
 * @return string|false URL
 */
function pngm_category_theme_photo_url($category_name)
{
    $key = '';
    $n = function_exists('mb_strtolower')
        ? mb_strtolower(trim((string) $category_name), 'UTF-8')
        : strtolower(trim((string) $category_name));

    $map = array(
        'motorcycle parts' => 'moto-parts',
        'car parts' => 'car-parts',
        'motorcycl' => 'motorcycles',
        'truck' => 'trucks',
        'bus' => 'buses',
        'van' => 'buses',
        'boat' => 'boats',
        'marine' => 'boats',
        'heavy' => 'heavy',
        'machinery' => 'heavy',
        'rv' => 'rvs',
        'caravan' => 'rvs',
        'car' => 'cars',
        'mobile phone' => 'phones',
        'phone accessor' => 'phone-acc',
        'computer accessor' => 'pc-acc',
        'computer' => 'laptops',
        'laptop' => 'laptops',
        'tv' => 'tvs',
        'entertainment' => 'tvs',
        'audio' => 'audio',
        'speaker' => 'audio',
        'camera' => 'cameras',
        'gaming' => 'gaming',
        'console' => 'gaming',
        'home appliance' => 'appliances',
        'furniture' => 'furniture',
        'bag' => 'bags',
        'dog' => 'dogs',
        'house' => 'houses',
        'bicycle' => 'bicycles',
        'bike' => 'bicycles',
        'admin' => 'jobs',
        'job' => 'jobs',
        'tool' => 'tools',
        'farm machin' => 'tractor',
        'tractor' => 'tractor',
        'other vehicle' => 'other-vehicles',
        'other electronic' => 'other-electronics',
    );

    foreach ($map as $needle => $slug) {
        if (strpos($n, $needle) !== false) {
            $key = $slug;
            break;
        }
    }

    if ($key === '') {
        return false;
    }

    // Prefer dedicated theme file, else a known subcategory id that already has this photo.
    $themePath = ABS_PATH . 'oc-content/themes/epsilon_child/images/small_cat/themes/' . $key . '.png';
    $ver = defined('PNGM_CHILD_VERSION') ? ('?v=' . PNGM_CHILD_VERSION) : '';
    if (is_file($themePath) && filesize($themePath) >= 4000) {
        return osc_base_url() . 'oc-content/themes/epsilon_child/images/small_cat/themes/' . $key . '.png' . $ver;
    }

    $idBySlug = array(
        'cars' => 18,
        'motorcycles' => 9,
        'trucks' => 10,
        'buses' => 11,
        'boats' => 14,
        'car-parts' => 12,
        'moto-parts' => 13,
        'heavy' => 15,
        'rvs' => 16,
        'other-vehicles' => 17,
        'phones' => 31,
        'phone-acc' => 32,
        'laptops' => 33,
        'pc-acc' => 34,
        'tvs' => 35,
        'audio' => 36,
        'cameras' => 37,
        'gaming' => 101,
        'appliances' => 102,
        'other-electronics' => 100,
        'furniture' => 38,
        'bags' => 47,
        'dogs' => 69,
        'houses' => 75,
        'bicycles' => 64,
        'jobs' => 83,
        'tools' => 103,
        'tractor' => 125,
    );

    if (isset($idBySlug[$key])) {
        $file = pngm_category_cover_file((int) $idBySlug[$key]);
        if ($file !== false) {
            return $file['url'];
        }
    }

    return false;
}

/**
 * Absolute path + URL for a category cover PNG if it exists.
 *
 * @param int $category_id
 * @return array|false {path,url}
 */
function pngm_category_cover_file($category_id)
{
    $id = (int) $category_id;
    if ($id <= 0 || !defined('ABS_PATH')) {
        return false;
    }

    $path = ABS_PATH . 'oc-content/themes/epsilon_child/images/small_cat/' . $id . '.png';
    // Ignore tiny silhouette placeholders (< ~4KB); keep photo covers only.
    if (!is_file($path) || filesize($path) < 4000) {
        return false;
    }

    $ver = defined('PNGM_CHILD_VERSION') ? ('?v=' . PNGM_CHILD_VERSION) : '';

    return array(
        'path' => $path,
        'url'  => osc_base_url() . 'oc-content/themes/epsilon_child/images/small_cat/' . $id . '.png' . $ver,
    );
}

/**
 * Latest listing thumbnail for a category (meaningful subcategory image).
 *
 * @param int $category_id
 * @return string|false
 */
function pngm_category_listing_cover_url($category_id)
{
    $category_id = (int) $category_id;
    if ($category_id <= 0 || !class_exists('Search') || !class_exists('ItemResource')) {
        return false;
    }

    static $memo = array();
    if (array_key_exists($category_id, $memo)) {
        return $memo[$category_id];
    }

    try {
        $mSearch = new Search();
        $mSearch->addCategory($category_id);
        if (method_exists($mSearch, 'withPicture')) {
            $mSearch->withPicture(true);
        }
        $mSearch->order('dt_pub_date', 'DESC');
        $mSearch->limit(0, 8);
        $items = $mSearch->doSearch();
    } catch (Exception $e) {
        return $memo[$category_id] = false;
    }

    if (!is_array($items) || count($items) === 0) {
        return $memo[$category_id] = false;
    }

    foreach ($items as $item) {
        $item_id = isset($item['pk_i_id']) ? (int) $item['pk_i_id'] : 0;
        if ($item_id <= 0) {
            continue;
        }

        $resources = ItemResource::newInstance()->getAllResourcesFromItem($item_id);
        if (!is_array($resources) || count($resources) === 0) {
            continue;
        }

        $res = $resources[0];
        $path = isset($res['s_path']) ? $res['s_path'] : '';
        $name = isset($res['s_name']) ? $res['s_name'] : '';
        $ext = isset($res['s_extension']) ? $res['s_extension'] : 'jpg';

        if ($path === '' || $name === '') {
            continue;
        }

        $thumb = osc_base_url() . $path . $name . '_thumbnail.' . $ext;
        $preview = osc_base_url() . $path . $name . '_preview.' . $ext;

        // Prefer preview when thumbnail is tiny / missing on disk.
        $abs_thumb = ABS_PATH . $path . $name . '_thumbnail.' . $ext;
        $abs_preview = ABS_PATH . $path . $name . '_preview.' . $ext;
        if (is_file($abs_preview) && filesize($abs_preview) > 800) {
            return $memo[$category_id] = $preview;
        }
        if (is_file($abs_thumb) && filesize($abs_thumb) > 400) {
            return $memo[$category_id] = $thumb;
        }
        if (is_file(ABS_PATH . $path . $name . '.' . $ext)) {
            return $memo[$category_id] = osc_base_url() . $path . $name . '.' . $ext;
        }
    }

    return $memo[$category_id] = false;
}

/**
 * Image URL for a category / subcategory icon.
 * Prefer unique per-id covers (small_cat/{id}.png), then a listing photo from
 * that category, then parent cover / SVG / defaults.
 *
 * @param int         $category_id
 * @param string      $category_name
 * @param int         $parent_id
 * @return string
 */
function pngm_get_cat_image($category_id, $category_name = '', $parent_id = 0)
{
    $id = (int) $category_id;
    $parent_id = (int) $parent_id;

    // Unique cover file for this exact category / subcategory id.
    $own = pngm_category_cover_file($id);
    if ($own !== false) {
        return $own['url'];
    }

    // Listing photo from this category only (not parent).
    $listing = pngm_category_listing_cover_url($id);
    if ($listing !== false) {
        return $listing;
    }

    if ($category_name === '' && $id > 0 && class_exists('Category')) {
        $row = Category::newInstance()->findByPrimaryKey($id);
        if (is_array($row)) {
            $category_name = isset($row['s_name']) ? $row['s_name'] : '';
            if ($parent_id <= 0 && !empty($row['fk_i_parent_id'])) {
                $parent_id = (int) $row['fk_i_parent_id'];
            }
        }
    }

    // Unique product photo by subcategory name theme (cars vs boats vs phones…).
    $themePhoto = pngm_category_theme_photo_url($category_name);
    if ($themePhoto !== false) {
        return $themePhoto;
    }

    // Last resort: parent photographic cover (roots only).
    if ($parent_id > 0) {
        $parent_file = pngm_category_cover_file($parent_id);
        if ($parent_file !== false) {
            return $parent_file['url'];
        }
    }

    $svg = pngm_category_svg_url($id);
    if ($svg !== false) {
        return $svg;
    }
    if ($parent_id > 0) {
        $psvg = pngm_category_svg_url($parent_id);
        if ($psvg !== false) {
            return $psvg;
        }
    }

    if (function_exists('eps_get_cat_image') && (string) eps_param('sample_images') !== '1') {
        return eps_get_cat_image($id);
    }

    return osc_current_web_theme_url() . 'images/small_cat/default.png';
}

/**
 * Render a photographic category / subcategory visual (home + search strip).
 *
 * @param int   $category_id
 * @param array $category
 * @param int   $parent_id
 * @return string
 */
function pngm_render_category_visual($category_id, $category = array(), $parent_id = 0)
{
    $name = '';
    if (is_array($category) && !empty($category['s_name'])) {
        $name = $category['s_name'];
    } elseif (is_array($category) && !empty($category['name'])) {
        $name = $category['name'];
    }

    if ($parent_id <= 0 && is_array($category) && !empty($category['fk_i_parent_id'])) {
        $parent_id = (int) $category['fk_i_parent_id'];
    }

    $url = pngm_get_cat_image((int) $category_id, $name, (int) $parent_id);
    $is_svg = (stripos($url, '.svg') !== false);
    $class = $is_svg ? 'pngm-cat-svg' : 'pngm-cat-cover';
    $lazy = (function_exists('eps_is_lazy') && eps_is_lazy()) ? ' lazy' : '';
    $alt = osc_esc_html($name !== '' ? $name : __('Category', 'epsilon'));

    return '<img src="' . osc_esc_html($url) . '" alt="' . $alt . '" class="' . $class . $lazy . '" loading="lazy" decoding="async" />';
}
