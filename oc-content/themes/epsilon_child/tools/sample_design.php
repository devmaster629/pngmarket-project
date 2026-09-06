<?php
$f = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_1.homepage-351d7e0d-f660-4c4e-85b4-140d4d93c8c3.png';
$b = file_get_contents($f, false, null, 0, 16);
echo 'magic: ' . bin2hex($b) . PHP_EOL;
$data = file_get_contents($f);
$im = @imagecreatefromstring($data);
if (!$im) {
    echo "fail load\n";
    exit(1);
}
echo 'ok ' . imagesx($im) . 'x' . imagesy($im) . PHP_EOL;

function sample_hex($im, $x, $y)
{
    $rgb = imagecolorat($im, $x, $y);
    if (is_array($rgb)) {
        return sprintf('#%02x%02x%02x', $rgb['red'], $rgb['green'], $rgb['blue']);
    }
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

$w = imagesx($im);
$h = imagesy($im);
$deskW = (int) round($w * 0.58);

$points = array(
    'header_bg' => array(40, 30),
    'place_ad_btn' => array((int) ($deskW * 0.92), 42),
    'canvas' => array(40, 280),
    'price_area' => array(130, (int) ($h * 0.55)),
    'bottom_post' => array((int) ($w * 0.79), (int) ($h * 0.93)),
);

foreach ($points as $name => $xy) {
    $x = max(0, min($w - 1, $xy[0]));
    $y = max(0, min($h - 1, $xy[1]));
    echo str_pad($name, 18) . sample_hex($im, $x, $y) . " @{$x},{$y}\n";
}

// Scan for saturated greens on button row
$greens = array();
for ($y = 25; $y < 70; $y += 2) {
    for ($x = (int) ($deskW * 0.75); $x < $deskW - 10; $x += 3) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($g > 90 && $g > $r + 30 && $g > $b + 20) {
            $hex = sprintf('#%02x%02x%02x', $r, $g, $b);
            if (!isset($greens[$hex])) {
                $greens[$hex] = 0;
            }
            $greens[$hex]++;
        }
    }
}
arsort($greens);
echo "Top greens in header CTA area:\n";
$i = 0;
foreach ($greens as $hex => $count) {
    echo "  {$hex} x{$count}\n";
    if (++$i >= 8) {
        break;
    }
}

$outDir = dirname(__DIR__) . '/images';
$designDir = $outDir . '/design';
if (!is_dir($designDir)) {
    mkdir($designDir, 0755, true);
}

// Crop hero collage
$hx = (int) ($deskW * 0.60);
$hy = 70;
$hw = (int) ($deskW * 0.38);
$hh = (int) min($h * 0.48, $w);
$hx = max(0, min($w - 1, $hx));
$hy = max(0, min($h - 1, $hy));
$hw = min($hw, $w - $hx);
$hh = min($hh, $h - $hy);

$hero = imagecreatetruecolor($hw, $hh);
imagealphablending($hero, false);
imagesavealpha($hero, true);
$transparent = imagecolorallocatealpha($hero, 0, 0, 0, 127);
imagefilledrectangle($hero, 0, 0, $hw, $hh, $transparent);
imagealphablending($hero, true);
imagecopy($hero, $im, 0, 0, $hx, $hy, $hw, $hh);

imagepng($hero, $outDir . '/home-hero.png');
imagepng($hero, $designDir . '/home-hero-from-mockup.png');
echo "Wrote home-hero.png {$hw}x{$hh}\n";

// Save a JPEG of full mockup as design reference (smaller)
imagejpeg($im, $designDir . '/ref-homepage.jpg', 85);
echo "Wrote design/ref-homepage.jpg\n";

imagedestroy($hero);
imagedestroy($im);
echo "Done\n";
