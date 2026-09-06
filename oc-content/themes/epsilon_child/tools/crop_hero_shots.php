<?php
$out = 'f:/freelancer/php/oc-content/themes/epsilon_child/images/design';
$stage = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_image-cc16ab18-b9fc-4647-8a76-f4d1e0dd2e13.png';
$local = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_image-056f1a7c-a7cb-42c9-b5e6-d4a1868fdd44.png';

function crop_hero($src, $dest, $x0, $y0, $x1, $y1)
{
    $im = imagecreatefromstring(file_get_contents($src));
    $w = imagesx($im);
    $h = imagesy($im);
    $x = (int) ($w * $x0);
    $y = (int) ($h * $y0);
    $cw = (int) ($w * ($x1 - $x0));
    $ch = (int) ($h * ($y1 - $y0));
    $out = imagecreatetruecolor($cw, $ch);
    imagecopy($out, $im, 0, 0, $x, $y, $cw, $ch);
    imagepng($out, $dest);
    echo basename($dest) . " {$cw}x{$ch}\n";
    imagedestroy($im);
    imagedestroy($out);
}

// Hero is on the right side of both screenshots
crop_hero($stage, $out . '/hero-from-stage-shot.png', 0.62, 0.12, 0.98, 0.62);
crop_hero($local, $out . '/hero-from-local-x-shot.png', 0.62, 0.12, 0.98, 0.62);

// Also check tall asset
$tall = 'f:/freelancer/php/oc-content/themes/epsilon_child/images/design/home-hero-tall.png';
$im = imagecreatefrompng($tall);
echo 'tall ' . imagesx($im) . 'x' . imagesy($im) . "\n";
// save a preview scaled
$tw = 400;
$th = (int) (imagesy($im) * ($tw / imagesx($im)));
$p = imagecreatetruecolor($tw, $th);
imagecopyresampled($p, $im, 0, 0, 0, 0, $tw, $th, imagesx($im), imagesy($im));
imagepng($p, $out . '/hero-tall-preview.png');
imagedestroy($im);
imagedestroy($p);
