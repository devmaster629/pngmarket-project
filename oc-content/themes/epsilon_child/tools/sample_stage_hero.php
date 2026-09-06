<?php
$stage = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_image-cc16ab18-b9fc-4647-8a76-f4d1e0dd2e13.png';
$crop = 'f:/freelancer/php/oc-content/themes/epsilon_child/images/design/hero-from-stage-shot.png';
$im = imagecreatefrompng($crop);
$w = imagesx($im);
$h = imagesy($im);
// Grid sample
for ($y = 0.1; $y <= 0.9; $y += 0.2) {
    for ($x = 0.1; $x <= 0.9; $x += 0.2) {
        $rgb = imagecolorat($im, (int) ($w * $x), (int) ($h * $y));
        $r = ($rgb >> 16) & 255;
        $g = ($rgb >> 8) & 255;
        $b = $rgb & 255;
        $lum = (int) (($r + $g + $b) / 3);
        echo sprintf("(%.1f,%.1f) #%02x%02x%02x L=%d\n", $x, $y, $r, $g, $b, $lum);
    }
}
// Count dark vs light pixels
$dark = 0;
$light = 0;
for ($yy = 0; $yy < $h; $yy += 2) {
    for ($xx = 0; $xx < $w; $xx += 2) {
        $rgb = imagecolorat($im, $xx, $yy);
        $lum = ((($rgb >> 16) & 255) + (($rgb >> 8) & 255) + ($rgb & 255)) / 3;
        if ($lum < 40) {
            $dark++;
        }
        if ($lum > 200) {
            $light++;
        }
    }
}
echo "dark=$dark light=$light\n";
