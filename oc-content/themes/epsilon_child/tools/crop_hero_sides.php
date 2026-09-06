<?php
/**
 * Crop home-hero.png to content bounds (trim empty left/right, slight top/bottom).
 */
$src = dirname(__DIR__) . '/images/home-hero.png';
$im = imagecreatefrompng($src);
if (!$im) {
    fwrite(STDERR, "Cannot load hero\n");
    exit(1);
}

$w = imagesx($im);
$h = imagesy($im);
imagesavealpha($im, true);

function is_empty_pixel($im, $x, $y)
{
    $rgba = imagecolorsforindex($im, imagecolorat($im, $x, $y));
    // Fully transparent
    if ((int) $rgba['alpha'] >= 120) {
        return true;
    }
    // Near-white leftover
    if ($rgba['red'] > 245 && $rgba['green'] > 245 && $rgba['blue'] > 245 && $rgba['alpha'] > 60) {
        return true;
    }
    return false;
}

$minX = $w;
$minY = $h;
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < $h; $y += 1) {
    for ($x = 0; $x < $w; $x += 1) {
        if (!is_empty_pixel($im, $x, $y)) {
            if ($x < $minX) {
                $minX = $x;
            }
            if ($y < $minY) {
                $minY = $y;
            }
            if ($x > $maxX) {
                $maxX = $x;
            }
            if ($y > $maxY) {
                $maxY = $y;
            }
        }
    }
}

if ($maxX <= $minX || $maxY <= $minY) {
    fwrite(STDERR, "No content found\n");
    exit(1);
}

// Small padding so edges aren't clipped hard
$pad = 8;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($w - 1, $maxX + $pad);
$maxY = min($h - 1, $maxY + $pad);

$cw = $maxX - $minX + 1;
$ch = $maxY - $minY + 1;

echo "Original {$w}x{$h}\n";
echo "Crop box x={$minX} y={$minY} w={$cw} h={$ch}\n";

$out = imagecreatetruecolor($cw, $ch);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $cw, $ch, $transparent);
imagealphablending($out, true);
imagecopy($out, $im, 0, 0, $minX, $minY, $cw, $ch);
imagealphablending($out, false);
imagesavealpha($out, true);

// Backup then save
$bak = dirname(__DIR__) . '/images/home-hero-uncropped.png';
if (!is_file($bak)) {
    copy($src, $bak);
}
imagepng($out, $src, 6);
imagedestroy($im);
imagedestroy($out);

echo "Saved cropped hero (" . filesize($src) . " bytes)\n";
