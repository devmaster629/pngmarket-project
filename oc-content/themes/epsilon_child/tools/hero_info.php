<?php
function info($path, $label)
{
    if (!is_file($path)) {
        echo "$label: missing\n";
        return;
    }
    $im = imagecreatefrompng($path);
    $w = imagesx($im);
    $h = imagesy($im);
    $rgb = imagecolorat($im, 2, 2);
    $r = ($rgb >> 16) & 255;
    $g = ($rgb >> 8) & 255;
    $b = $rgb & 255;
    echo sprintf("%s: %dx%d bytes=%d corner=#%02x%02x%02x\n", $label, $w, $h, filesize($path), $r, $g, $b);
    imagedestroy($im);
}

$base = 'f:/freelancer/php/oc-content/themes/epsilon_child/images';
info($base . '/home-hero.png', 'current (HEAD restored)');
info($base . '/home-hero-before-key.png', 'before-key');
info('C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/home-hero.png', 'assets white');
info($base . '/design/home-hero-zoomed.png', 'zoomed');
info($base . '/design/home-hero-tall.png', 'tall');
