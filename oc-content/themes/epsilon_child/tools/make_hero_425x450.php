<?php
/**
 * Rebuild home-hero.png as 425×387.
 * Spreads collage horizontally (expand empty gaps, keep objects) so content
 * fills the full frame — no stretch, no empty side bars, no crop of objects.
 */
$srcPath = dirname(__DIR__) . '/images/home-hero-before-fill.png';
if (!is_file($srcPath)) {
    $srcPath = dirname(__DIR__) . '/images/home-hero.png';
}
$outPath = dirname(__DIR__) . '/images/home-hero.png';

$tw = 425;
$th = 387;
$targetAspect = $tw / $th;

$im = imagecreatefrompng($srcPath);
if (!$im) {
    fwrite(STDERR, "Cannot load source\n");
    exit(1);
}

$sw = imagesx($im);
$sh = imagesy($im);
imagealphablending($im, false);
imagesavealpha($im, true);

function hero_is_content_pixel($im, $x, $y)
{
    $rgba = imagecolorat($im, $x, $y);
    $a = ($rgba & 0x7F000000) >> 24;
    $r = ($rgba >> 16) & 0xFF;
    $g = ($rgba >> 8) & 0xFF;
    $b = $rgba & 0xFF;
    if ($a >= 120) {
        return false;
    }
    if ($r > 248 && $g > 248 && $b > 248) {
        return false;
    }
    // Near-black backdrop (solid or semi)
    if ($r < 18 && $g < 18 && $b < 18) {
        return false;
    }
    return true;
}

// Content bounds
$minX = $sw;
$minY = $sh;
$maxX = 0;
$maxY = 0;
for ($y = 0; $y < $sh; $y++) {
    for ($x = 0; $x < $sw; $x++) {
        if (!hero_is_content_pixel($im, $x, $y)) {
            continue;
        }
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

if ($maxX <= $minX || $maxY <= $minY) {
    $minX = 0;
    $minY = 0;
    $maxX = $sw - 1;
    $maxY = $sh - 1;
}

$pad = 4;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($sw - 1, $maxX + $pad);
$maxY = min($sh - 1, $maxY + $pad);

$cw = $maxX - $minX + 1;
$ch = $maxY - $minY + 1;
$contentAspect = $cw / $ch;

echo "Source content {$cw}x{$ch} @ ({$minX},{$minY}) aspect=" . round($contentAspect, 3) . "\n";

// Crop to content
$crop = imagecreatetruecolor($cw, $ch);
imagealphablending($crop, false);
imagesavealpha($crop, true);
$transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);
imagefilledrectangle($crop, 0, 0, $cw, $ch, $transparent);
imagecopy($crop, $im, 0, 0, $minX, $minY, $cw, $ch);
imagedestroy($im);

$work = $crop;
$ww = $cw;
$wh = $ch;

// If content is narrower than target slot, widen by expanding empty columns.
if ($contentAspect < $targetAspect - 0.01) {
    $needW = (int) max($cw, round($ch * $targetAspect));
    $extra = $needW - $cw;
    echo "Widening {$cw} → {$needW} (+{$extra}) by expanding sparse gaps\n";

    // Column density: how much real content in each source column
    $density = array();
    $maxDens = 1;
    for ($x = 0; $x < $cw; $x++) {
        $sum = 0;
        for ($y = 0; $y < $ch; $y++) {
            if (hero_is_content_pixel($crop, $x, $y)) {
                $sum++;
            }
        }
        $density[$x] = $sum;
        if ($sum > $maxDens) {
            $maxDens = $sum;
        }
    }

    // Soften density so tiny gaps inside objects aren't over-expanded.
    $smooth = array();
    $radius = 3;
    for ($x = 0; $x < $cw; $x++) {
        $acc = 0;
        $n = 0;
        for ($k = -$radius; $k <= $radius; $k++) {
            $i = $x + $k;
            if ($i < 0 || $i >= $cw) {
                continue;
            }
            $acc += $density[$i];
            $n++;
        }
        $smooth[$x] = $acc / max(1, $n);
    }

    // Expansion weight: empty columns expand more; dense object columns stay tight.
    // floor keeps objects from being crushed; empty gets the bulk of extra width.
    $weights = array();
    $weightSum = 0.0;
    for ($x = 0; $x < $cw; $x++) {
        $norm = $smooth[$x] / $maxDens; // 0 empty .. 1 dense
        $w = 0.08 + pow(1.0 - $norm, 2.2); // empty → high weight
        $weights[$x] = $w;
        $weightSum += $w;
    }

    // dest width of each source column (1 + share of extra)
    $colW = array();
    $assigned = 0;
    for ($x = 0; $x < $cw; $x++) {
        $share = (int) floor($extra * ($weights[$x] / $weightSum));
        $colW[$x] = 1 + $share;
        $assigned += $share;
    }
    // Distribute remainder to emptiest columns
    $remain = $extra - $assigned;
    if ($remain > 0) {
        $order = range(0, $cw - 1);
        usort($order, function ($a, $b) use ($smooth) {
            if ($smooth[$a] == $smooth[$b]) {
                return $a - $b;
            }
            return ($smooth[$a] < $smooth[$b]) ? -1 : 1;
        });
        $i = 0;
        while ($remain > 0) {
            $colW[$order[$i % $cw]]++;
            $remain--;
            $i++;
        }
    }

    $wide = imagecreatetruecolor($needW, $ch);
    imagealphablending($wide, false);
    imagesavealpha($wide, true);
    $transparent = imagecolorallocatealpha($wide, 0, 0, 0, 127);
    imagefilledrectangle($wide, 0, 0, $needW, $ch, $transparent);

    $dx = 0;
    for ($x = 0; $x < $cw; $x++) {
        $dw = $colW[$x];
        if ($dw <= 1) {
            imagecopy($wide, $crop, $dx, 0, $x, 0, 1, $ch);
        } else {
            // Stretch only this (preferably empty) column strip
            imagecopyresampled($wide, $crop, $dx, 0, $x, 0, $dw, $ch, 1, $ch);
        }
        $dx += $dw;
    }

    imagedestroy($crop);
    $work = $wide;
    $ww = $needW;
    $wh = $ch;
    echo "Wide canvas {$ww}x{$wh} aspect=" . round($ww / $wh, 3) . "\n";
}

// Uniform scale to fill 425×450 (aspect now matches → fills both axes).
$scale = min($tw / $ww, $th / $wh);
// Prefer cover-ish fill when extremely close; still keep all content if aspect matches.
$scale = max($tw / $ww, $th / $wh); // cover
$dw = (int) max(1, round($ww * $scale));
$dh = (int) max(1, round($wh * $scale));

// If cover would clip more than ~1px, fall back to contain (aspect should match).
$clipX = max(0, $dw - $tw);
$clipY = max(0, $dh - $th);
if ($clipX > 2 || $clipY > 2) {
    $scale = min($tw / $ww, $th / $wh);
    $dw = (int) max(1, floor($ww * $scale));
    $dh = (int) max(1, floor($wh * $scale));
}

$dx = (int) floor(($tw - $dw) / 2);
$dy = (int) floor(($th - $dh) / 2);

echo "Scaled to {$dw}x{$dh} placed at ({$dx},{$dy}) in {$tw}x{$th}\n";

$out = imagecreatetruecolor($tw, $th);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $tw, $th, $transparent);

imagealphablending($out, true);
imagecopyresampled($out, $work, $dx, $dy, 0, 0, $dw, $dh, $ww, $wh);
imagealphablending($out, false);
imagesavealpha($out, true);

// Clear residual near-black backdrop so page white shows through.
$cleared = 0;
for ($y = 0; $y < $th; $y++) {
    for ($x = 0; $x < $tw; $x++) {
        $rgb = imagecolorat($out, $x, $y);
        $a = ($rgb & 0x7F000000) >> 24;
        $r = ($rgb >> 16) & 255;
        $g = ($rgb >> 8) & 255;
        $b = $rgb & 255;
        if ($a >= 120) {
            continue;
        }
        if ($r < 18 && $g < 18 && $b < 18) {
            imagesetpixel($out, $x, $y, $transparent);
            $cleared++;
        }
    }
}

imagepng($out, $outPath, 6);
imagedestroy($work);
imagedestroy($out);

echo "Cleared {$cleared} black pixels\n";
echo 'Saved ' . basename($outPath) . ' (' . filesize($outPath) . " bytes)\n";
