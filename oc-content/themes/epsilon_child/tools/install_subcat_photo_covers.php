<?php
/**
 * Install photographic subcategory covers into images/small_cat/{id}.png
 * and themes/{slug}.png from generated assets.
 *
 * Run: php oc-content/themes/epsilon_child/tools/install_subcat_photo_covers.php
 */
$outDir = dirname(__DIR__) . '/images/small_cat';
$themeDir = $outDir . '/themes';
$assets = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets';

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
if (!is_dir($themeDir)) {
    mkdir($themeDir, 0755, true);
}

// Category id => asset filename (subcat-*.png in assets/)
$map = array(
    // Vehicles
    18  => 'subcat-cars.png',
    9   => 'subcat-motorcycles.png',
    10  => 'subcat-trucks.png',
    11  => 'subcat-buses.png',
    14  => 'subcat-boats.png',
    12  => 'subcat-car-parts.png',
    13  => 'subcat-moto-parts.png',
    15  => 'subcat-heavy.png',
    16  => 'subcat-rvs.png',
    17  => 'subcat-other-vehicles.png',
    // Phones & Electronics
    31  => 'subcat-phones.png',
    32  => 'subcat-phone-acc.png',
    33  => 'subcat-laptops.png',
    34  => 'subcat-pc-acc.png',
    35  => 'subcat-tvs.png',
    36  => 'subcat-audio.png',
    37  => 'subcat-cameras.png',
    101 => 'subcat-gaming.png',
    102 => 'subcat-appliances.png',
    100 => 'subcat-other-electronics.png',
    // Home, Furniture & Garden
    38  => 'subcat-furniture.png',
    39  => 'subcat-kitchen-dining.png',
    40  => 'subcat-appliances.png',
    41  => 'subcat-home-decor.png',
    42  => 'subcat-garden.png',
    103 => 'subcat-tools.png',
    104 => 'subcat-building-materials.png',
    105 => 'subcat-generators-solar.png',
    106 => 'subcat-other-home.png',
    // Fashion & Beauty
    43  => 'subcat-mens-clothing.png',
    44  => 'subcat-womens-clothing.png',
    45  => 'subcat-childrens-clothing.png',
    46  => 'subcat-shoes.png',
    47  => 'subcat-bags.png',
    48  => 'subcat-jewelry.png',
    49  => 'subcat-beauty.png',
    50  => 'subcat-personal-care.png',
    51  => 'subcat-other-fashion.png',
    // Babies, Kids & Toys
    52  => 'subcat-baby-clothing.png',
    53  => 'subcat-baby-equipment.png',
    54  => 'subcat-toys.png',
    55  => 'subcat-school.png',
    56  => 'subcat-kids-furniture.png',
    57  => 'subcat-other-baby.png',
    // Sports, Hobbies & Leisure
    63  => 'subcat-sports.png',
    64  => 'subcat-bicycles.png',
    65  => 'subcat-fishing.png',
    66  => 'subcat-camping.png',
    67  => 'subcat-instruments.png',
    68  => 'subcat-books.png',
    107 => 'subcat-art.png',
    108 => 'subcat-tickets.png',
    109 => 'subcat-other-hobbies.png',
    // Pets & Animals
    69  => 'subcat-dogs.png',
    70  => 'subcat-cats.png',
    71  => 'subcat-birds.png',
    72  => 'subcat-fish.png',
    73  => 'subcat-livestock.png',
    74  => 'subcat-pet-supplies.png',
    110 => 'subcat-other-animals.png',
    // Property
    75  => 'subcat-houses.png',
    76  => 'subcat-apartments.png',
    77  => 'subcat-houses-rent.png',
    78  => 'subcat-apartments-rent.png',
    79  => 'subcat-rooms-rent.png',
    80  => 'subcat-land.png',
    81  => 'subcat-commercial-property.png',
    82  => 'subcat-other-property.png',
    // Jobs
    83  => 'subcat-jobs.png',
    85  => 'subcat-accounting.png',
    84  => 'subcat-construction-jobs.png',
    93  => 'subcat-customer-service.png',
    94  => 'subcat-education.png',
    92  => 'subcat-retail.png',
    86  => 'subcat-engineering.png',
    87  => 'subcat-hospitality.png',
    88  => 'subcat-it-jobs.png',
    91  => 'subcat-logistics.png',
    89  => 'subcat-mining.png',
    90  => 'subcat-retail.png',
    111 => 'subcat-security.png',
    95  => 'subcat-other-jobs.png',
    // Services
    118 => 'subcat-auto-services.png',
    123 => 'subcat-building-services.png',
    122 => 'subcat-cleaning.png',
    121 => 'subcat-it-services.png',
    119 => 'subcat-delivery.png',
    120 => 'subcat-events.png',
    113 => 'subcat-fitness.png',
    116 => 'subcat-moving.png',
    117 => 'subcat-repair.png',
    115 => 'subcat-professional.png',
    114 => 'subcat-tutoring.png',
    112 => 'subcat-other-services.png',
    // Agriculture
    125 => 'subcat-tractor.png',
    132 => 'subcat-farm-livestock.png',
    131 => 'subcat-seeds.png',
    130 => 'subcat-fertilizer.png',
    129 => 'subcat-animal-feed.png',
    128 => 'subcat-farm-supplies.png',
    127 => 'subcat-produce.png',
    126 => 'subcat-other-agriculture.png',
    // Business & Industrial
    134 => 'subcat-industrial.png',
    142 => 'subcat-office.png',
    141 => 'subcat-restaurant.png',
    140 => 'subcat-retail-equipment.png',
    139 => 'subcat-manufacturing.png',
    138 => 'subcat-construction-equip.png',
    137 => 'subcat-packaging.png',
    136 => 'subcat-wholesale.png',
    135 => 'subcat-other-business.png',
);

// Also copy each unique asset into themes/{slug}.png for name-based lookup.
$themeSlugs = array();
foreach ($map as $file) {
    $slug = preg_replace('/^subcat-|\.png$/i', '', $file);
    $themeSlugs[$slug] = $file;
}

function pngm_install_photo_cover($src, $dest)
{
    if (!is_file($src)) {
        fwrite(STDERR, "Missing: {$src}\n");
        return false;
    }

    $raw = @file_get_contents($src);
    $srcIm = $raw ? @imagecreatefromstring($raw) : false;
    if (!$srcIm) {
        fwrite(STDERR, "Cannot decode: {$src}\n");
        return false;
    }

    $sw = imagesx($srcIm);
    $sh = imagesy($srcIm);
    $side = min($sw, $sh);
    $sx = (int) (($sw - $side) / 2);
    $sy = (int) (($sh - $side) / 2);

    $tile = imagecreatetruecolor(160, 160);
    $white = imagecolorallocate($tile, 255, 255, 255);
    imagefilledrectangle($tile, 0, 0, 160, 160, $white);
    imagecopyresampled($tile, $srcIm, 0, 0, $sx, $sy, 160, 160, $side, $side);
    imagepng($tile, $dest, 5);
    imagedestroy($tile);
    imagedestroy($srcIm);

    echo 'Installed ' . basename($dest) . ' (' . filesize($dest) . " bytes)\n";
    return true;
}

$ok = 0;
foreach ($map as $id => $file) {
    if (pngm_install_photo_cover($assets . '/' . $file, $outDir . '/' . $id . '.png')) {
        $ok++;
    }
}

$themeOk = 0;
foreach ($themeSlugs as $slug => $file) {
    if (pngm_install_photo_cover($assets . '/' . $file, $themeDir . '/' . $slug . '.png')) {
        $themeOk++;
    }
}

echo "Done. Installed {$ok}/" . count($map) . " id covers and {$themeOk}/" . count($themeSlugs) . " theme covers.\n";
