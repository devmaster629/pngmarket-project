<?php
/**
 * Install photographic subcategory covers into images/small_cat/{id}.png
 * Run: php oc-content/themes/epsilon_child/tools/install_subcat_photo_covers.php
 */
$outDir = dirname(__DIR__) . '/images/small_cat';
$assets = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets';

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

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
    // Other popular trees
    38  => 'subcat-furniture.png',
    47  => 'subcat-bags.png',
    69  => 'subcat-dogs.png',
    75  => 'subcat-houses.png',
    64  => 'subcat-bicycles.png',
    83  => 'subcat-jobs.png',
    103 => 'subcat-tools.png',
    125 => 'subcat-tractor.png',
);

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

echo "Done. Installed {$ok}/" . count($map) . " photographic covers.\n";
