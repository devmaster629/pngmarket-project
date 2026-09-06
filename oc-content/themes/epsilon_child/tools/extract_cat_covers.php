<?php
/**
 * Install studio-style category photo covers (generated assets, not mockup crops).
 * Run: php oc-content/themes/epsilon_child/tools/extract_cat_covers.php
 */
$outDir = dirname(__DIR__) . '/images/small_cat';
$assets = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets';

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

function pngm_install_cover($src, $dest)
{
    if (!is_file($src)) {
        fwrite(STDERR, "Missing: {$src}\n");
        return false;
    }
    $srcIm = @imagecreatefromstring(file_get_contents($src));
    if (!$srcIm) {
        fwrite(STDERR, "Cannot decode: {$src}\n");
        return false;
    }
    $sw = imagesx($srcIm);
    $sh = imagesy($srcIm);
    $side = min($sw, $sh);
    $sx = (int) (($sw - $side) / 2);
    $sy = (int) (($sh - $side) / 2);
    $trim = (int) ($side * 0.04);
    $tile = imagecreatetruecolor(160, 160);
    $white = imagecolorallocate($tile, 255, 255, 255);
    imagefilledrectangle($tile, 0, 0, 160, 160, $white);
    imagecopyresampled(
        $tile,
        $srcIm,
        0,
        0,
        $sx + $trim,
        $sy + $trim,
        160,
        160,
        $side - 2 * $trim,
        $side - 2 * $trim
    );
    imagepng($tile, $dest, 5);
    imagedestroy($tile);
    imagedestroy($srcIm);
    echo 'Installed ' . basename($dest) . ' (' . filesize($dest) . " bytes)\n";
    return true;
}

$map = array(
    1   => 'cat-vehicles.png',
    2   => 'cat-phones.png',
    3   => 'cat-home.png',
    4   => 'cat-fashion.png',
    5   => 'cat-babies.png',
    6   => 'cat-sports.png',
    7   => 'cat-pets.png',
    8   => 'cat-property.png',
    96  => 'cat-jobs.png',
    97  => 'cat-services.png',
    124 => 'cat-agriculture.png',
    133 => 'cat-business.png',
);

$ok = 0;
foreach ($map as $id => $file) {
    if (pngm_install_cover($assets . '/' . $file, $outDir . '/' . $id . '.png')) {
        $ok++;
    }
}

echo "Done. Installed {$ok}/" . count($map) . " covers.\n";
