<?php
/**
 * Generate a unique photographic-style cover PNG for every subcategory.
 * Run: php oc-content/themes/epsilon_child/tools/generate_subcat_covers.php
 */
define('ABS_PATH', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR);
require ABS_PATH . 'oc-load.php';

$outDir = dirname(__DIR__) . '/images/small_cat';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

/**
 * @return string
 */
function pngm_cover_theme_key($name)
{
    $n = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);

    $map = array(
        'motorcycle parts' => 'moto_parts',
        'car parts' => 'car_parts',
        'motorcycl' => 'motorcycle',
        'motorbike' => 'motorcycle',
        'truck' => 'truck',
        'bus' => 'bus',
        'van' => 'bus',
        'boat' => 'boat',
        'marine' => 'boat',
        'heavy machin' => 'machinery',
        'rv' => 'rv',
        'caravan' => 'rv',
        'car' => 'car',
        'vehicle' => 'car',
        'mobile phone' => 'phone',
        'phone accessor' => 'phone_acc',
        'computer accessor' => 'pc_acc',
        'computer' => 'laptop',
        'laptop' => 'laptop',
        'tv' => 'tv',
        'entertainment' => 'tv',
        'audio' => 'speaker',
        'speaker' => 'speaker',
        'camera' => 'camera',
        'gaming' => 'gamepad',
        'console' => 'gamepad',
        'home appliance' => 'appliance',
        'furniture' => 'couch',
        'kitchen' => 'kitchen',
        'decor' => 'decor',
        'garden' => 'plant',
        'tool' => 'tools',
        'building material' => 'bricks',
        'generator' => 'generator',
        'solar' => 'solar',
        'clothing' => 'shirt',
        'shoe' => 'shoe',
        'bag' => 'bag',
        'jewelry' => 'watch',
        'watch' => 'watch',
        'beauty' => 'beauty',
        'health' => 'health',
        'baby' => 'baby',
        'toy' => 'toy',
        'school' => 'book',
        'sport' => 'sport',
        'bicycle' => 'bike',
        'bike' => 'bike',
        'fishing' => 'fish_hook',
        'camping' => 'tent',
        'music' => 'music',
        'book' => 'book',
        'art' => 'art',
        'ticket' => 'ticket',
        'dog' => 'dog',
        'cat' => 'cat',
        'bird' => 'bird',
        'fish' => 'fish',
        'livestock' => 'livestock',
        'pet' => 'paw',
        'animal' => 'paw',
        'house' => 'house',
        'apartment' => 'building',
        'room' => 'room',
        'land' => 'land',
        'property' => 'house',
        'job' => 'briefcase',
        'admin' => 'clipboard',
        'account' => 'calc',
        'finance' => 'calc',
        'construct' => 'helmet',
        'engineer' => 'cogs',
        'hospitalit' => 'utensils',
        'telecommunication' => 'laptop',
        'it &' => 'laptop',
        'mining' => 'mountain',
        'logistic' => 'delivery',
        'retail' => 'store',
        'customer' => 'headset',
        'education' => 'grad',
        'training' => 'grad',
        'security' => 'shield',
        'cleaning' => 'broom',
        'repair' => 'wrench',
        'delivery' => 'delivery',
        'event' => 'music',
        'moving' => 'box',
        'farm' => 'tractor',
        'seed' => 'seed',
        'plant' => 'plant',
        'fertilizer' => 'chem',
        'feed' => 'feed',
        'produce' => 'produce',
        'industrial' => 'factory',
        'office' => 'desk',
        'wholesale' => 'boxes',
        'packaging' => 'boxes',
        'restaurant' => 'utensils',
        'manufactur' => 'factory',
        'service' => 'tools',
    );

    foreach ($map as $needle => $key) {
        if (strpos($n, $needle) !== false) {
            return $key;
        }
    }

    return 'generic_' . substr(preg_replace('/[^a-z0-9]+/', '', $n), 0, 12);
}

/**
 * @param resource|\GdImage $im
 * @param int $color
 */
function pngm_fill_round_rect($im, $x1, $y1, $x2, $y2, $r, $color)
{
    imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
}

/**
 * Draw a distinctive glyph for a theme key.
 *
 * @param resource|\GdImage $im
 * @param string $key
 * @param int $ink
 * @param int $accent
 */
function pngm_draw_theme_glyph($im, $key, $ink, $accent)
{
    // Coordinate space roughly 40..120 within 160x160.
    switch ($key) {
        case 'car':
            imagefilledrectangle($im, 42, 78, 118, 100, $ink);
            imagefilledrectangle($im, 55, 62, 105, 78, $ink);
            imagefilledellipse($im, 58, 100, 22, 22, $accent);
            imagefilledellipse($im, 102, 100, 22, 22, $accent);
            break;
        case 'motorcycle':
            imagefilledellipse($im, 55, 100, 28, 28, $ink);
            imagefilledellipse($im, 105, 100, 28, 28, $ink);
            imageline($im, 55, 100, 90, 70, $ink);
            imageline($im, 90, 70, 110, 100, $ink);
            imageline($im, 90, 70, 75, 55, $accent);
            imagesetthickness($im, 3);
            break;
        case 'truck':
            imagefilledrectangle($im, 40, 70, 95, 100, $ink);
            imagefilledrectangle($im, 95, 80, 122, 100, $ink);
            imagefilledellipse($im, 58, 102, 18, 18, $accent);
            imagefilledellipse($im, 108, 102, 18, 18, $accent);
            break;
        case 'bus':
            imagefilledrectangle($im, 45, 55, 115, 100, $ink);
            imagefilledrectangle($im, 52, 62, 108, 78, $accent);
            imagefilledellipse($im, 60, 102, 16, 16, $accent);
            imagefilledellipse($im, 100, 102, 16, 16, $accent);
            break;
        case 'boat':
            imagefilledarc($im, 80, 95, 90, 40, 0, 180, $ink, IMG_ARC_PIE);
            imageline($im, 80, 55, 80, 95, $accent);
            imagefilledellipse($im, 80, 52, 10, 10, $accent);
            break;
        case 'machinery':
            imagefilledrectangle($im, 50, 70, 110, 105, $ink);
            imagefilledrectangle($im, 70, 45, 90, 70, $accent);
            imagefilledellipse($im, 60, 108, 18, 18, $accent);
            imagefilledellipse($im, 100, 108, 18, 18, $accent);
            break;
        case 'rv':
            imagefilledrectangle($im, 40, 65, 120, 100, $ink);
            imagefilledrectangle($im, 48, 72, 70, 88, $accent);
            imagefilledellipse($im, 58, 102, 16, 16, $accent);
            imagefilledellipse($im, 105, 102, 16, 16, $accent);
            break;
        case 'car_parts':
        case 'moto_parts':
            imagefilledellipse($im, 80, 80, 54, 54, $ink);
            imagefilledellipse($im, 80, 80, 28, 28, $accent);
            for ($a = 0; $a < 6; $a++) {
                $ang = deg2rad($a * 60);
                imagefilledellipse($im, (int) (80 + cos($ang) * 28), (int) (80 + sin($ang) * 28), 10, 10, $ink);
            }
            break;
        case 'phone':
            pngm_fill_round_rect($im, 62, 40, 98, 120, 8, $ink);
            imagefilledrectangle($im, 68, 50, 92, 100, $accent);
            imagefilledellipse($im, 80, 110, 8, 8, $accent);
            break;
        case 'phone_acc':
            pngm_fill_round_rect($im, 55, 48, 90, 112, 8, $ink);
            imagefilledellipse($im, 108, 80, 22, 28, $accent);
            imageline($im, 90, 70, 100, 70, $ink);
            break;
        case 'laptop':
            imagefilledrectangle($im, 48, 50, 112, 90, $ink);
            imagefilledrectangle($im, 54, 56, 106, 84, $accent);
            imagefilledrectangle($im, 42, 90, 118, 100, $ink);
            break;
        case 'pc_acc':
            imagefilledellipse($im, 80, 78, 42, 42, $ink);
            imagefilledrectangle($im, 72, 98, 88, 118, $accent);
            break;
        case 'tv':
            imagefilledrectangle($im, 40, 48, 120, 100, $ink);
            imagefilledrectangle($im, 48, 56, 112, 92, $accent);
            imagefilledrectangle($im, 72, 100, 88, 112, $ink);
            break;
        case 'speaker':
            imagefilledellipse($im, 80, 80, 50, 50, $ink);
            imagefilledellipse($im, 80, 80, 24, 24, $accent);
            imagefilledellipse($im, 80, 80, 10, 10, $ink);
            break;
        case 'camera':
            imagefilledrectangle($im, 45, 60, 115, 105, $ink);
            imagefilledellipse($im, 80, 82, 34, 34, $accent);
            imagefilledellipse($im, 80, 82, 16, 16, $ink);
            imagefilledrectangle($im, 70, 48, 95, 60, $ink);
            break;
        case 'gamepad':
            imagefilledellipse($im, 80, 85, 70, 40, $ink);
            imagefilledellipse($im, 60, 85, 12, 12, $accent);
            imagefilledrectangle($im, 95, 78, 110, 82, $accent);
            imagefilledrectangle($im, 100, 73, 104, 88, $accent);
            break;
        case 'appliance':
            imagefilledrectangle($im, 55, 40, 105, 120, $ink);
            imagefilledrectangle($im, 62, 48, 98, 70, $accent);
            imagefilledellipse($im, 80, 95, 18, 18, $accent);
            break;
        case 'couch':
            imagefilledrectangle($im, 40, 80, 120, 105, $ink);
            imagefilledrectangle($im, 48, 60, 112, 80, $accent);
            imagefilledrectangle($im, 40, 70, 52, 95, $ink);
            imagefilledrectangle($im, 108, 70, 120, 95, $ink);
            break;
        case 'kitchen':
            imagefilledrectangle($im, 50, 55, 110, 115, $ink);
            imagefilledellipse($im, 80, 75, 24, 16, $accent);
            break;
        case 'decor':
            imagefilledrectangle($im, 55, 45, 105, 105, $ink);
            imageline($im, 55, 45, 80, 70, $accent);
            imageline($im, 105, 45, 80, 70, $accent);
            break;
        case 'plant':
            imagefilledellipse($im, 80, 70, 36, 40, $ink);
            imagefilledrectangle($im, 70, 90, 90, 120, $accent);
            break;
        case 'tools':
            imageline($im, 55, 55, 105, 105, $ink);
            imageline($im, 105, 55, 55, 105, $accent);
            imagesetthickness($im, 5);
            break;
        case 'bricks':
            imagefilledrectangle($im, 45, 55, 115, 75, $ink);
            imagefilledrectangle($im, 45, 80, 115, 100, $accent);
            imagefilledrectangle($im, 45, 105, 115, 120, $ink);
            break;
        case 'generator':
            imagefilledrectangle($im, 50, 60, 110, 110, $ink);
            imagefilledrectangle($im, 70, 45, 90, 60, $accent);
            break;
        case 'solar':
            imagefilledrectangle($im, 45, 55, 115, 105, $ink);
            imageline($im, 80, 55, 80, 105, $accent);
            imageline($im, 45, 80, 115, 80, $accent);
            break;
        case 'shirt':
            imagefilledpolygon($im, array(50, 55, 80, 45, 110, 55, 118, 70, 100, 70, 100, 120, 60, 120, 60, 70, 42, 70), 9, $ink);
            break;
        case 'shoe':
            imagefilledellipse($im, 85, 95, 70, 28, $ink);
            imagefilledrectangle($im, 50, 75, 75, 95, $accent);
            break;
        case 'bag':
            imagefilledrectangle($im, 55, 65, 105, 115, $ink);
            imagefilledarc($im, 80, 65, 40, 30, 180, 360, $accent, IMG_ARC_PIE);
            break;
        case 'watch':
            imagefilledellipse($im, 80, 80, 46, 46, $ink);
            imagefilledellipse($im, 80, 80, 28, 28, $accent);
            imagefilledrectangle($im, 72, 45, 88, 58, $ink);
            imagefilledrectangle($im, 72, 102, 88, 115, $ink);
            break;
        case 'beauty':
            imagefilledellipse($im, 80, 70, 34, 34, $ink);
            imagefilledrectangle($im, 72, 85, 88, 120, $accent);
            break;
        case 'health':
            imagefilledrectangle($im, 72, 45, 88, 115, $ink);
            imagefilledrectangle($im, 50, 72, 110, 88, $ink);
            break;
        case 'baby':
            imagefilledellipse($im, 80, 60, 36, 36, $ink);
            imagefilledellipse($im, 80, 100, 50, 40, $accent);
            break;
        case 'toy':
            imagefilledellipse($im, 65, 70, 28, 28, $ink);
            imagefilledellipse($im, 95, 70, 28, 28, $ink);
            imagefilledellipse($im, 80, 100, 40, 34, $accent);
            break;
        case 'sport':
            imagefilledellipse($im, 80, 80, 56, 56, $ink);
            imageline($im, 55, 55, 105, 105, $accent);
            imageline($im, 105, 55, 55, 105, $accent);
            break;
        case 'bike':
            imagefilledellipse($im, 55, 100, 28, 28, $ink);
            imagefilledellipse($im, 110, 100, 28, 28, $ink);
            imageline($im, 55, 100, 80, 65, $accent);
            imageline($im, 80, 65, 110, 100, $accent);
            imageline($im, 80, 65, 70, 100, $ink);
            break;
        case 'fish_hook':
            imagefilledarc($im, 90, 85, 40, 50, 0, 200, $ink, IMG_ARC_NOFILL);
            break;
        case 'tent':
            imagefilledpolygon($im, array(80, 45, 120, 115, 40, 115), 3, $ink);
            imageline($im, 80, 45, 80, 115, $accent);
            break;
        case 'music':
            imagefilledellipse($im, 70, 100, 24, 18, $ink);
            imageline($im, 82, 100, 82, 50, $ink);
            imageline($im, 82, 50, 110, 60, $accent);
            break;
        case 'book':
            imagefilledrectangle($im, 50, 45, 110, 115, $ink);
            imageline($im, 80, 45, 80, 115, $accent);
            break;
        case 'art':
            imagefilledrectangle($im, 50, 45, 110, 115, $ink);
            imagefilledellipse($im, 80, 80, 30, 30, $accent);
            break;
        case 'ticket':
            imagefilledrectangle($im, 40, 60, 120, 100, $ink);
            imagefilledellipse($im, 40, 80, 16, 16, $accent);
            imagefilledellipse($im, 120, 80, 16, 16, $accent);
            break;
        case 'dog':
        case 'cat':
        case 'bird':
        case 'paw':
            imagefilledellipse($im, 80, 85, 40, 36, $ink);
            imagefilledellipse($im, 60, 60, 16, 16, $ink);
            imagefilledellipse($im, 100, 60, 16, 16, $ink);
            imagefilledellipse($im, 70, 55, 12, 12, $accent);
            imagefilledellipse($im, 90, 55, 12, 12, $accent);
            break;
        case 'fish':
            imagefilledellipse($im, 75, 80, 50, 28, $ink);
            imagefilledpolygon($im, array(100, 80, 120, 65, 120, 95), 3, $accent);
            break;
        case 'livestock':
            imagefilledellipse($im, 80, 85, 50, 36, $ink);
            imagefilledrectangle($im, 55, 55, 70, 75, $accent);
            break;
        case 'house':
            imagefilledpolygon($im, array(80, 40, 120, 75, 40, 75), 3, $ink);
            imagefilledrectangle($im, 55, 75, 105, 115, $accent);
            break;
        case 'building':
            imagefilledrectangle($im, 50, 45, 110, 115, $ink);
            imagefilledrectangle($im, 58, 55, 72, 70, $accent);
            imagefilledrectangle($im, 88, 55, 102, 70, $accent);
            imagefilledrectangle($im, 58, 80, 72, 95, $accent);
            imagefilledrectangle($im, 88, 80, 102, 95, $accent);
            break;
        case 'room':
            imagefilledrectangle($im, 45, 50, 115, 115, $ink);
            imagefilledrectangle($im, 70, 75, 115, 115, $accent);
            break;
        case 'land':
            imagefilledrectangle($im, 40, 90, 120, 115, $ink);
            imagefilledellipse($im, 70, 70, 30, 20, $accent);
            break;
        case 'briefcase':
            imagefilledrectangle($im, 45, 65, 115, 110, $ink);
            imagefilledrectangle($im, 70, 50, 90, 65, $accent);
            break;
        case 'clipboard':
            imagefilledrectangle($im, 55, 45, 105, 120, $ink);
            imagefilledrectangle($im, 65, 38, 95, 52, $accent);
            break;
        case 'calc':
            imagefilledrectangle($im, 55, 40, 105, 120, $ink);
            imagefilledrectangle($im, 62, 48, 98, 65, $accent);
            break;
        case 'helmet':
            imagefilledarc($im, 80, 90, 70, 60, 180, 360, $ink, IMG_ARC_PIE);
            imagefilledrectangle($im, 50, 88, 110, 100, $accent);
            break;
        case 'cogs':
            imagefilledellipse($im, 70, 80, 36, 36, $ink);
            imagefilledellipse($im, 95, 95, 28, 28, $accent);
            break;
        case 'utensils':
            imagefilledrectangle($im, 60, 45, 68, 115, $ink);
            imagefilledrectangle($im, 90, 45, 98, 115, $accent);
            break;
        case 'mountain':
            imagefilledpolygon($im, array(40, 115, 70, 55, 100, 115), 3, $ink);
            imagefilledpolygon($im, array(75, 115, 105, 60, 130, 115), 3, $accent);
            break;
        case 'delivery':
            imagefilledrectangle($im, 40, 70, 95, 100, $ink);
            imagefilledrectangle($im, 95, 80, 120, 100, $accent);
            imagefilledellipse($im, 60, 102, 14, 14, $accent);
            imagefilledellipse($im, 105, 102, 14, 14, $ink);
            break;
        case 'store':
            imagefilledrectangle($im, 50, 70, 110, 115, $ink);
            imagefilledpolygon($im, array(45, 70, 80, 45, 115, 70), 3, $accent);
            break;
        case 'headset':
            imagefilledarc($im, 80, 75, 60, 50, 200, 340, $ink, IMG_ARC_NOFILL);
            imagefilledrectangle($im, 50, 75, 60, 100, $accent);
            imagefilledrectangle($im, 100, 75, 110, 100, $accent);
            break;
        case 'grad':
            imagefilledpolygon($im, array(80, 50, 120, 70, 40, 70), 3, $ink);
            imagefilledrectangle($im, 55, 70, 105, 95, $accent);
            break;
        case 'shield':
            imagefilledpolygon($im, array(80, 40, 115, 55, 105, 110, 80, 120, 55, 110, 45, 55), 6, $ink);
            break;
        case 'broom':
            imagefilledrectangle($im, 78, 40, 86, 95, $ink);
            imagefilledpolygon($im, array(60, 95, 100, 95, 110, 120, 50, 120), 4, $accent);
            break;
        case 'wrench':
            imagesetthickness($im, 6);
            imageline($im, 55, 110, 110, 50, $ink);
            imagefilledellipse($im, 110, 50, 18, 18, $accent);
            break;
        case 'box':
            imagefilledrectangle($im, 50, 70, 110, 115, $ink);
            imagefilledpolygon($im, array(50, 70, 80, 50, 110, 70), 3, $accent);
            break;
        case 'tractor':
            imagefilledrectangle($im, 55, 70, 110, 95, $ink);
            imagefilledellipse($im, 60, 105, 28, 28, $accent);
            imagefilledellipse($im, 105, 108, 18, 18, $accent);
            break;
        case 'seed':
            imagefilledellipse($im, 80, 85, 28, 40, $ink);
            imageline($im, 80, 50, 80, 70, $accent);
            break;
        case 'chem':
            imagefilledrectangle($im, 70, 55, 90, 115, $ink);
            imagefilledellipse($im, 80, 55, 28, 18, $accent);
            break;
        case 'feed':
            imagefilledrectangle($im, 55, 60, 105, 115, $ink);
            imagefilledellipse($im, 80, 55, 30, 20, $accent);
            break;
        case 'produce':
            imagefilledellipse($im, 70, 85, 34, 34, $ink);
            imagefilledellipse($im, 95, 80, 28, 28, $accent);
            break;
        case 'factory':
            imagefilledrectangle($im, 45, 80, 115, 115, $ink);
            imagefilledrectangle($im, 55, 50, 70, 80, $accent);
            imagefilledrectangle($im, 85, 60, 100, 80, $accent);
            break;
        case 'desk':
            imagefilledrectangle($im, 40, 80, 120, 90, $ink);
            imagefilledrectangle($im, 50, 90, 62, 115, $accent);
            imagefilledrectangle($im, 98, 90, 110, 115, $accent);
            break;
        case 'boxes':
            imagefilledrectangle($im, 45, 75, 85, 115, $ink);
            imagefilledrectangle($im, 75, 55, 115, 95, $accent);
            break;
        case 'tools':
            imagefilledrectangle($im, 70, 45, 90, 115, $ink);
            imagefilledellipse($im, 80, 45, 30, 20, $accent);
            break;
        default:
            imagefilledellipse($im, 80, 80, 50, 50, $ink);
            imagefilledrectangle($im, 65, 65, 95, 95, $accent);
            break;
    }
    imagesetthickness($im, 1);
}

/**
 * @param int $id
 * @param string $name
 * @param string $dest
 * @return bool
 */
function pngm_generate_subcat_cover($id, $name, $dest)
{
    $size = 160;
    $im = imagecreatetruecolor($size, $size);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $white = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, $size, $size, $white);

    // Unique soft tint from id (keeps sibling tiles distinct even with same glyph).
    $h = ($id * 47) % 360;
    $s = 28 + ($id % 5) * 4;
    $l = 90 - ($id % 4) * 2;
    // Approximate HSL→RGB for pastel background.
    $c = (1 - abs(2 * ($l / 100) - 1)) * ($s / 100);
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = ($l / 100) - $c / 2;
    if ($h < 60) {
        $r = $c;
        $g = $x;
        $b = 0;
    } elseif ($h < 120) {
        $r = $x;
        $g = $c;
        $b = 0;
    } elseif ($h < 180) {
        $r = 0;
        $g = $c;
        $b = $x;
    } elseif ($h < 240) {
        $r = 0;
        $g = $x;
        $b = $c;
    } elseif ($h < 300) {
        $r = $x;
        $g = 0;
        $b = $c;
    } else {
        $r = $c;
        $g = 0;
        $b = $x;
    }
    $bg = imagecolorallocate($im, (int) (($r + $m) * 255), (int) (($g + $m) * 255), (int) (($b + $m) * 255));
    pngm_fill_round_rect($im, 8, 8, 151, 151, 18, $bg);

    $ink = imagecolorallocate($im, 32 + ($id * 13) % 40, 48 + ($id * 7) % 50, 56 + ($id * 11) % 40);
    $accent = imagecolorallocate($im, 0, 90 + ($id * 9) % 70, 36 + ($id * 5) % 50);

    $key = pngm_cover_theme_key($name);
    // Force uniqueness: append id variant when keys collide visually by shifting draw.
    imagesetthickness($im, 3);
    pngm_draw_theme_glyph($im, $key, $ink, $accent);

    // Small unique badge so identical themes still differ.
    $badge = imagecolorallocate($im, 0, 107, 36);
    $bx = 118 - ($id % 3) * 4;
    $by = 118 - ((int) ($id / 3) % 3) * 4;
    imagefilledellipse($im, $bx, $by, 14, 14, $badge);

    $ok = imagepng($im, $dest, 6);
    imagedestroy($im);

    return (bool) $ok;
}

$cats = Category::newInstance()->listAll(false);
$rootIds = array();
foreach ($cats as $c) {
    if ((int) @$c['fk_i_parent_id'] <= 0) {
        $rootIds[(int) $c['pk_i_id']] = true;
    }
}

$made = 0;
$skip = 0;
foreach ($cats as $c) {
    $id = (int) $c['pk_i_id'];
    $parent = (int) @$c['fk_i_parent_id'];
    if ($parent <= 0 || isset($rootIds[$id])) {
        continue;
    }

    // Never overwrite curated parent/root covers (1,2,3...).
    if (isset($rootIds[$id])) {
        continue;
    }

    $dest = $outDir . '/' . $id . '.png';
    // Skip if a large hand-made cover already exists (>12KB typical photo).
    if (is_file($dest) && filesize($dest) > 12000) {
        $skip++;
        continue;
    }

    if (pngm_generate_subcat_cover($id, $c['s_name'], $dest)) {
        echo "OK {$id} {$c['s_name']} => " . filesize($dest) . " bytes\n";
        $made++;
    } else {
        echo "FAIL {$id} {$c['s_name']}\n";
    }
}

echo "Done. Generated {$made}, skipped curated {$skip}.\n";
