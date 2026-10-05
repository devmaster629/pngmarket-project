<?php
/**
 * Profile photo uploads — larger limit + server-side compression.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'profile_photo.php'
) {
    exit;
}

/** Max raw upload before compression (bytes). */
function pngm_profile_photo_max_bytes()
{
    return 20 * 1024 * 1024;
}

/**
 * Target dimensions from Osclass preferences.
 *
 * @return array{width:int,height:int}
 */
function pngm_profile_photo_dimensions()
{
    $dim = function_exists('osc_profile_img_dimensions') ? osc_profile_img_dimensions() : '240x240';
    if ($dim === '') {
        $dim = '240x240';
    }
    $parts = explode('x', strtolower($dim));
    $w = isset($parts[0]) ? (int) $parts[0] : 240;
    $h = isset($parts[1]) ? (int) $parts[1] : 240;
    if ($w < 64) {
        $w = 240;
    }
    if ($h < 64) {
        $h = 240;
    }
    return array('width' => $w, 'height' => $h);
}

/**
 * @param resource $im
 * @return resource|null
 */
function pngm_profile_photo_orient($im, $path)
{
    if (!function_exists('exif_read_data') || !is_readable($path)) {
        return $im;
    }
    $exif = @exif_read_data($path);
    if (!is_array($exif) || empty($exif['Orientation'])) {
        return $im;
    }
    $orientation = (int) $exif['Orientation'];
    if ($orientation === 3) {
        $im = imagerotate($im, 180, 0);
    } elseif ($orientation === 6) {
        $im = imagerotate($im, -90, 0);
    } elseif ($orientation === 8) {
        $im = imagerotate($im, 90, 0);
    }
    return $im;
}

/**
 * Save GD image as JPEG profile photo for user.
 *
 * @param resource $im
 * @param int $user_id
 * @return string|false public URL
 */
function pngm_profile_photo_save_gd($im, $user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0 || !$im) {
        return false;
    }

    $dim = pngm_profile_photo_dimensions();
    $target_w = $dim['width'];
    $target_h = $dim['height'];

    $src_w = imagesx($im);
    $src_h = imagesy($im);
    if ($src_w < 1 || $src_h < 1) {
        return false;
    }

    $scale = min($target_w / $src_w, $target_h / $src_h, 1.0);
    $new_w = max(1, (int) round($src_w * $scale));
    $new_h = max(1, (int) round($src_h * $scale));

    $canvas = imagecreatetruecolor($target_w, $target_h);
    if (!$canvas) {
        return false;
    }
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $target_w, $target_h, $white);

    $dst_x = (int) floor(($target_w - $new_w) / 2);
    $dst_y = (int) floor(($target_h - $new_h) / 2);
    imagecopyresampled($canvas, $im, $dst_x, $dst_y, 0, 0, $new_w, $new_h, $src_w, $src_h);

    $dir = osc_content_path() . 'uploads/user-images/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $user = User::newInstance()->findByPrimaryKey($user_id);
    if (is_array($user) && !empty($user['s_profile_img'])) {
        @unlink($dir . $user['s_profile_img']);
    }

    $image_name = $user_id . '_' . osc_generate_rand_string(5) . '_' . date('Ymd') . '.jpg';
    $image_path = $dir . $image_name;
    $ok = imagejpeg($canvas, $image_path, 82);
    imagedestroy($canvas);

    if (!$ok || !is_readable($image_path)) {
        return false;
    }

    User::newInstance()->updateProfileImg($user_id, $image_name);
    return osc_content_url() . 'uploads/user-images/' . $image_name;
}

/**
 * @param string $tmp_path
 * @param int $user_id
 * @return string|false
 */
function pngm_profile_photo_save_file($tmp_path, $user_id)
{
    if (!is_readable($tmp_path) || !extension_loaded('gd')) {
        return false;
    }

    $info = @getimagesize($tmp_path);
    if (!is_array($info)) {
        return false;
    }

    $im = null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $im = @imagecreatefromjpeg($tmp_path);
            break;
        case IMAGETYPE_PNG:
            $im = @imagecreatefrompng($tmp_path);
            break;
        case IMAGETYPE_GIF:
            $im = @imagecreatefromgif($tmp_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $im = @imagecreatefromwebp($tmp_path);
            }
            break;
    }

    if (!$im) {
        return false;
    }

    $im = pngm_profile_photo_orient($im, $tmp_path);
    $url = pngm_profile_photo_save_gd($im, $user_id);
    imagedestroy($im);
    return $url;
}

/**
 * @param string $data data:image/... base64
 * @param int $user_id
 * @return string|false
 */
function pngm_profile_photo_save_blob($data, $user_id)
{
    $data = trim((string) $data);
    if ($data === '') {
        return false;
    }

    if (strpos($data, 'base64,') !== false) {
        $parts = explode('base64,', $data, 2);
        $data = isset($parts[1]) ? $parts[1] : '';
    }
    $binary = base64_decode($data, true);
    if ($binary === false || $binary === '') {
        return false;
    }
    if (strlen($binary) > pngm_profile_photo_max_bytes()) {
        return false;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'pngmpp');
    if ($tmp === false) {
        return false;
    }
    file_put_contents($tmp, $binary);
    $url = pngm_profile_photo_save_file($tmp, $user_id);
    @unlink($tmp);
    return $url;
}

/**
 * AJAX: save profile photo (cropper blob or multipart file).
 */
function pngm_ajax_profile_photo_upload()
{
    header('Content-Type: application/json; charset=utf-8');
    if (!function_exists('osc_is_web_user_logged_in') || !osc_is_web_user_logged_in()) {
        echo json_encode(array('ok' => false, 'error' => 'auth'));
        return;
    }

    $user_id = (int) osc_logged_user_id();
    $max = pngm_profile_photo_max_bytes();
    $url = false;

    $blob = Params::getParam('blob');
    if ($blob !== '') {
        $url = pngm_profile_photo_save_blob($blob, $user_id);
    } elseif (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
        $file = $_FILES['photo'];
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(array('ok' => false, 'error' => 'upload'));
            return;
        }
        if ((int) $file['size'] > $max) {
            echo json_encode(array('ok' => false, 'error' => 'size'));
            return;
        }
        $url = pngm_profile_photo_save_file($file['tmp_name'], $user_id);
    } else {
        echo json_encode(array('ok' => false, 'error' => 'empty'));
        return;
    }

    if ($url === false) {
        echo json_encode(array('ok' => false, 'error' => 'process'));
        return;
    }

    echo json_encode(array('ok' => true, 'url' => $url));
}

osc_add_hook('ajax_pngm_profile_photo_upload', 'pngm_ajax_profile_photo_upload');
