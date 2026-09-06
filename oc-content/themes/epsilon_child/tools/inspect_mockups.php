<?php
$f = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_1.homepage-351d7e0d-f660-4c4e-85b4-140d4d93c8c3.png';
$im = imagecreatefromstring(file_get_contents($f));
$w = imagesx($im);
$h = imagesy($im);
echo "homepage: {$w}x{$h}\n";

$deskW = (int) round($w * 0.55);
$out = dirname(__DIR__) . '/images/design';
if (!is_dir($out)) {
    mkdir($out, 0755, true);
}

// Save left desktop strip for visual inspection of cat area
$stripH = min(400, $h);
$stripY = (int) round($h * 0.18);
$strip = imagecreatetruecolor($deskW, $stripH);
imagecopy($strip, $im, 0, 0, 0, $stripY, $deskW, $stripH);
imagepng($strip, $out . '/homepage-desk-cats-region.png');
echo "Wrote homepage-desk-cats-region.png y={$stripY}\n";

// Also save full left desktop column
$col = imagecreatetruecolor($deskW, $h);
imagecopy($col, $im, 0, 0, 0, 0, $deskW, $h);
imagepng($col, $out . '/homepage-desk-full.png');
echo "Wrote homepage-desk-full.png\n";

// Comparison image
$c = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_image-5510bc1d-fd88-492c-b2de-bc18834a17d7.png';
$cim = imagecreatefromstring(file_get_contents($c));
$cw = imagesx($cim);
$ch = imagesy($cim);
echo "comparison: {$cw}x{$ch}\n";
// Bottom half (design)
$hy = (int) round($ch * 0.42);
$bottom = imagecreatetruecolor($cw, $ch - $hy);
imagecopy($bottom, $cim, 0, 0, 0, $hy, $cw, $ch - $hy);
imagepng($bottom, $out . '/comparison-design-half.png');
echo "Wrote comparison-design-half.png from y={$hy}\n";

imagedestroy($im);
imagedestroy($strip);
imagedestroy($col);
imagedestroy($cim);
imagedestroy($bottom);
