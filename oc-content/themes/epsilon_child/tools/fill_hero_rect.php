<?php
/**
 * Zoom-crop home-hero so the collage fills the homepage hero rect
 * without clipping the floating objects.
 */
$src = dirname(__DIR__) . '/images/home-hero.png';
$bak = dirname(__DIR__) . '/images/home-hero-before-fill.png';

if (is_file($bak)) {
    copy($bak, $src);
}

$im = imagecreatefrompng($src);
if (!$im) {
    fwrite(STDERR, "Cannot load hero\n");
    exit(1);
}

$w = imagesx($im);
$h = imagesy($im);

// Asymmetric crop: keep truck/top intact, trim sides + bottom more.
$cutL = (int) round($w * 0.04);
$cutR = (int) round($w * 0.04);
$cutT = (int) round($h * 0.02); // keep truck
$cutB = (int) round($h * 0.06);

$x = $cutL;
$y = $cutT;
$cw = $w - $cutL - $cutR;
$ch = $h - $cutT - $cutB;

// Match hero slot aspect (~0.88) by trimming extra height from bottom first.
$target = 0.88;
$needH = (int) round($cw / $target);
if ($needH < $ch) {
    $trim = $ch - $needH;
    // Take most trim from bottom, a little from top only if needed.
    $fromTop = (int) floor($trim * 0.15);
    $fromBottom = $trim - $fromTop;
    $y += $fromTop;
    $ch = $needH;
}

echo "Source {$w}x{$h}\n";
echo "Crop x={$x} y={$y} w={$cw} h={$ch} aspect=" . round($cw / $ch, 3) . "\n";

$out = imagecreatetruecolor($cw, $ch);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $cw, $ch, $transparent);
imagecopy($out, $im, 0, 0, $x, $y, $cw, $ch);

imagepng($out, $src, 6);
imagedestroy($im);
imagedestroy($out);

echo 'Saved home-hero.png (' . filesize($src) . " bytes) {$cw}x{$ch}\n";
