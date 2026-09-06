<?php
// Sample hero region colors from user's stage screenshot vs restored file
$shot = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_image-cc16ab18-b9fc-4647-8a76-f4d1e0dd2e13.png';
$local = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_image-056f1a7c-a7cb-42c9-b5e6-d4a1868fdd44.png';
$hero = 'f:/freelancer/php/oc-content/themes/epsilon_child/images/home-hero.png';
$white = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/home-hero.png';

function sample($path, $label)
{
    $im = @imagecreatefromstring(file_get_contents($path));
    if (!$im) {
        echo "$label FAIL\n";
        return;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    echo "$label {$w}x{$h}\n";
    // sample a few points in right-half mid area (hero zone for full-page screenshots)
    $pts = array(
        array(0.78, 0.28),
        array(0.85, 0.35),
        array(0.90, 0.45),
        array(0.82, 0.55),
        array(0.02, 0.02),
    );
    foreach ($pts as $p) {
        $x = (int) ($w * $p[0]);
        $y = (int) ($h * $p[1]);
        $rgb = imagecolorat($im, min($w - 1, $x), min($h - 1, $y));
        $r = ($rgb >> 16) & 255;
        $g = ($rgb >> 8) & 255;
        $b = $rgb & 255;
        echo sprintf("  (%.2f,%.2f)=#%02x%02x%02x\n", $p[0], $p[1], $r, $g, $b);
    }
    imagedestroy($im);
}

sample($shot, 'stage-screenshot');
sample($local, 'local-X-screenshot');
sample($hero, 'restored-HEAD-hero');
sample($white, 'assets-white-hero');
