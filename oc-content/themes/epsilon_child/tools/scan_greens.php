<?php
$f = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_1.homepage-351d7e0d-f660-4c4e-85b4-140d4d93c8c3.png';
$im = imagecreatefromstring(file_get_contents($f));
$w = imagesx($im);
$h = imagesy($im);
$greens = array();
for ($y = 0; $y < $h; $y += 4) {
    for ($x = 0; $x < (int) ($w * 0.65); $x += 4) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($g > 80 && $g > $r + 25 && $g > $b + 15 && $r < 120 && $b < 120) {
            // quantize
            $rq = (int) ($r / 8) * 8;
            $gq = (int) ($g / 8) * 8;
            $bq = (int) ($b / 8) * 8;
            $hex = sprintf('#%02x%02x%02x', $rq, $gq, $bq);
            if (!isset($greens[$hex])) {
                $greens[$hex] = 0;
            }
            $greens[$hex]++;
        }
    }
}
arsort($greens);
$i = 0;
foreach ($greens as $hex => $count) {
    echo "{$hex} x{$count}\n";
    if (++$i >= 15) {
        break;
    }
}

// Also sample listing-detail for button greens
$f2 = 'C:/Users/Administrator/.cursor/projects/f-freelancer-php/assets/c__Users_Administrator_AppData_Roaming_Cursor_User_workspaceStorage_70d89828c97983d6c77351d5175e120f_images_5.account.dashboard-ab7dc543-4cb2-4431-a30d-e8d1cd6222f1.png';
if (is_file($f2)) {
    $im2 = imagecreatefromstring(file_get_contents($f2));
    $w2 = imagesx($im2);
    $h2 = imagesy($im2);
    echo "dashboard {$w2}x{$h2}\n";
    $greens2 = array();
    for ($y = 0; $y < $h2; $y += 3) {
        for ($x = 0; $x < $w2; $x += 3) {
            $rgb = imagecolorat($im2, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            if ($g > 90 && $g > $r + 30 && $g > $b + 20 && $r < 100) {
                $rq = (int) ($r / 8) * 8;
                $gq = (int) ($g / 8) * 8;
                $bq = (int) ($b / 8) * 8;
                $hex = sprintf('#%02x%02x%02x', $rq, $gq, $bq);
                if (!isset($greens2[$hex])) {
                    $greens2[$hex] = 0;
                }
                $greens2[$hex]++;
            }
        }
    }
    arsort($greens2);
    echo "Dashboard greens:\n";
    $i = 0;
    foreach ($greens2 as $hex => $count) {
        echo "  {$hex} x{$count}\n";
        if (++$i >= 10) {
            break;
        }
    }
}
