<?php
$src = dirname(__DIR__) . '/images/home-hero.png';
$im = imagecreatefrompng($src);
imagealphablending($im, false);
imagesavealpha($im, true);

$w = imagesx($im);
$h = imagesy($im);
$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
$cleared = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 255;
        $g = ($rgb >> 8) & 255;
        $b = $rgb & 255;

        // Drop light canvas / near-white backdrop so page white shows through.
        $nearWhite = ($r > 235 && $g > 235 && $b > 235);
        $lightGray = ($r > 220 && $g > 220 && $b > 220
            && abs($r - $g) < 10 && abs($g - $b) < 10 && abs($r - $b) < 10);

        if ($nearWhite || $lightGray) {
            imagesetpixel($im, $x, $y, $transparent);
            $cleared++;
        }
    }
}

imagepng($im, $src, 6);
imagedestroy($im);
echo "Cleared {$cleared} backdrop pixels from home-hero.png\n";
