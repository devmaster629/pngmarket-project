<?php
/**
 * PNG Market — production Osclass settings that were still at installer defaults.
 *
 * Does not touch SMTP credentials, currency, timezone, or image sizes that are
 * already configured. Email verification, 30-day soft expiry, and image
 * compression prefs that are already correct are left alone.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'production_config.php'
) {
    exit;
}

if (!defined('PNGM_PRODUCTION_POLICY_VER')) {
    define('PNGM_PRODUCTION_POLICY_VER', 'v3');
}

/** First N listings from an account stay inactive until an admin activates one. */
if (!defined('PNGM_MODERATE_FIRST_ITEMS')) {
    define('PNGM_MODERATE_FIRST_ITEMS', 1);
}

/** Preferred listing photo extensions (Uppy + AjaxUploader + ItemActions).
 * Stored in Osclass preference allowedExt.
 * Skips svg/psd/ico (security / not useful as listing photos).
 */
if (!defined('PNGM_ALLOWED_IMAGE_EXT')) {
    define(
        'PNGM_ALLOWED_IMAGE_EXT',
        'png,gif,jpg,jpeg,jpe,jfif,pjp,pjpeg,webp,bmp,dib,avif,heic,heif,tif,tiff,jxl,jp2,j2k'
    );
}

/**
 * Preferred max upload size for listing photos (kilobytes).
 */
if (!defined('PNGM_MAX_IMAGE_KB')) {
    define('PNGM_MAX_IMAGE_KB', 20480); // 20MB
}

/**
 * @param string $name
 * @param mixed  $value
 * @param string $type
 */
function pngm_production_set($name, $value, $type = 'STRING')
{
    if (!function_exists('osc_set_preference') || !function_exists('osc_get_preference')) {
        return;
    }
    if ((string) osc_get_preference($name) === (string) $value) {
        return;
    }
    osc_set_preference($name, (string) $value, 'osclass', $type);
}

/**
 * Registration, contact gate, first-listing moderation, log cleanup, WebP.
 */
function pngm_production_enforce_prefs()
{
    if (!function_exists('osc_get_preference') || !function_exists('osc_set_preference')) {
        return;
    }

    // Accounts stay open, but the activation email must be completed.
    pngm_production_set('enabled_users', '1', 'BOOLEAN');
    pngm_production_set('enabled_user_registration', '1', 'BOOLEAN');
    pngm_production_set('enabled_user_validation', '1', 'BOOLEAN');
    pngm_production_set('notify_new_user', '1', 'BOOLEAN');
    pngm_production_set('notify_new_item', '1', 'BOOLEAN');

    // Core contact form matches the theme: guests cannot message sellers.
    pngm_production_set('reg_user_can_contact', '1', 'BOOLEAN');
    pngm_production_set('reg_user_can_see_phone', '1', 'BOOLEAN');

    // First listing needs admin approval. Later listings publish immediately.
    // logged_user_item_validation=1 would skip this for every logged-in user.
    pngm_production_set('moderate_items', (string) PNGM_MODERATE_FIRST_ITEMS, 'INTEGER');
    pngm_production_set('logged_user_item_validation', '0', 'BOOLEAN');

    if ((string) osc_get_preference('logging_auto_cleanup') !== '1') {
        pngm_production_set('logging_auto_cleanup', '1', 'BOOLEAN');
    }
    if ((int) osc_get_preference('logging_months') < 1) {
        pngm_production_set('logging_months', '12', 'INTEGER');
    }

    $desired = array_filter(array_map('trim', explode(',', strtolower((string) PNGM_ALLOWED_IMAGE_EXT))));
    $ext = strtolower((string) osc_get_preference('allowedExt'));
    $parts = array_filter(array_map('trim', explode(',', $ext)));
    $merged = $parts;
    foreach ($desired as $want) {
        if ($want !== '' && !in_array($want, $merged, true)) {
            $merged[] = $want;
        }
    }
    // Prefer a stable, broad order when we had to grow the list.
    if ($merged !== $parts) {
        $ordered = array();
        foreach ($desired as $want) {
            if (in_array($want, $merged, true)) {
                $ordered[] = $want;
            }
        }
        foreach ($merged as $extra) {
            if (!in_array($extra, $ordered, true)) {
                $ordered[] = $extra;
            }
        }
        pngm_production_set('allowedExt', implode(',', $ordered), 'STRING');
    }

    // Listing photo max size (20MB) — keep at least this large.
    $want_kb = (int) PNGM_MAX_IMAGE_KB;
    if ($want_kb > 0 && (int) osc_get_preference('maxSizeKb') < $want_kb) {
        pngm_production_set('maxSizeKb', (string) $want_kb, 'INTEGER');
    }
}

/**
 * Close the truncated "about to expire" mail so clients render it.
 * The 30-day / renew copy is still appended by listing_expiry.php.
 */
function pngm_production_repair_warn_email()
{
    if (!function_exists('osc_get_preference') || !class_exists('Page')) {
        return;
    }
    if ((string) osc_get_preference('pngm_warn_email_repaired', 'epsilon_child') === PNGM_PRODUCTION_POLICY_VER) {
        return;
    }

    $page = Page::newInstance()->findByInternalName('email_warn_expiration');
    if (!is_array($page) || empty($page['pk_i_id']) || !defined('DB_TABLE_PREFIX')) {
        return;
    }

    $body = '<p>Hi {USER_NAME},</p>'
        . '<p>Your listing <a href="{ITEM_URL}">{ITEM_TITLE}</a> is about to expire at {WEB_LINK}.</p>'
        . '<p>Regards,</p><p>{WEB_LINK}</p>';
    $title = '{WEB_TITLE} - Your ad is about to expire';

    try {
        $conn = DBConnectionClass::newInstance();
        $data = $conn->getOsclassDb();
        $comm = new DBCommandClass($data);
        $prefix = DB_TABLE_PREFIX;
        $rs = $comm->query(sprintf(
            'SELECT fk_c_locale_code, s_text FROM %st_pages_description WHERE fk_i_pages_id = %d',
            $prefix,
            (int) $page['pk_i_id']
        ));
        $rows = $rs ? $rs->result() : array();
    } catch (Exception $e) {
        return;
    }

    if (!is_array($rows)) {
        $rows = array();
    }

    foreach ($rows as $row) {
        $locale = isset($row['fk_c_locale_code']) ? (string) $row['fk_c_locale_code'] : '';
        $text = isset($row['s_text']) ? (string) $row['s_text'] : '';
        if ($locale === '') {
            continue;
        }
        $broken = (stripos($text, 'Regards') === false) || (substr(rtrim($text), -4) !== '</p>');
        if (!$broken) {
            continue;
        }
        Page::newInstance()->updateDescription((int) $page['pk_i_id'], $locale, $title, $body);
    }

    osc_set_preference('pngm_warn_email_repaired', PNGM_PRODUCTION_POLICY_VER, 'epsilon_child', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->toArray();
    }
}

/**
 * Apply production prefs on normal requests and cron.
 */
function pngm_production_apply()
{
    static $done = false;
    if ($done) {
        return;
    }
    pngm_production_enforce_prefs();
    pngm_production_repair_warn_email();
    $done = true;
}
osc_add_hook('init', 'pngm_production_apply', 5);
osc_add_hook('cron', 'pngm_production_apply', 2);
