<?php
/**
 * Keep Publish fast: skip unreachable Akismet / remote SMTP hangs,
 * and never leave new public listings inactive when moderation is off.
 *
 * Local (127.0.0.1 / localhost): skip outbound mail + Akismet entirely.
 * Everywhere: PHPMailer connect timeout capped (default 300s blocks publish).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'publish_speed.php'
) {
    exit;
}

/**
 * @return bool
 */
function pngm_is_local_host()
{
    static $local = null;
    if ($local !== null) {
        return $local;
    }
    $host = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = strtolower((string) $_SERVER['HTTP_HOST']);
        $host = preg_replace('/:\d+$/', '', $host);
    }
    if ($host === '' && defined('WEB_PATH')) {
        $host = (string) parse_url(WEB_PATH, PHP_URL_HOST);
        $host = strtolower($host);
    }
    $local = (
        $host === 'localhost'
        || $host === '127.0.0.1'
        || $host === '::1'
        || substr($host, -6) === '.local'
        || substr($host, -5) === '.test'
    );
    return $local;
}

if (!defined('PNGM_DISABLE_AKISMET')) {
    // Local machine usually cannot reach Akismet; waiting on TCP wastes ~20–60s.
    define('PNGM_DISABLE_AKISMET', pngm_is_local_host() ? 1 : 0);
}

/**
 * Skip activation / admin mails on local — Hostinger SMTP from a PC often hangs ~1 min.
 *
 * @param array $aItem
 * @return array
 */
function pngm_publish_skip_emails_local($aItem)
{
    if (!is_array($aItem)) {
        return $aItem;
    }
    if (pngm_is_local_host()) {
        $aItem['send_no_emails'] = 1;
    }
    return $aItem;
}
osc_add_filter('item_post_email_data', 'pngm_publish_skip_emails_local', 8);

/**
 * Hard-cap SMTP connect/read so a dead mail host cannot freeze Publish.
 *
 * @param object $mail PHPMailer
 * @param array  $params
 * @return object
 */
function pngm_publish_mail_timeout($mail, $params = null)
{
    if (is_object($mail) && property_exists($mail, 'Timeout')) {
        $mail->Timeout = pngm_is_local_host() ? 3 : 12;
    }
    return $mail;
}
osc_add_filter('init_send_mail', 'pngm_publish_mail_timeout', 8);

/**
 * When Osclass moderation is off (moderate_items < 0), keep every public post active.
 *
 * @param array $aInsert
 * @return array
 */
function pngm_publish_force_active($aInsert)
{
    if (!is_array($aInsert)) {
        return $aInsert;
    }
    if (defined('OC_ADMIN') && OC_ADMIN) {
        return $aInsert;
    }
    if (!function_exists('osc_moderate_items') || (int) osc_moderate_items() >= 0) {
        return $aInsert;
    }
    // Spam flag may still apply; do not force-enable spam rows.
    if (isset($aInsert['b_spam']) && (int) $aInsert['b_spam'] === 1) {
        return $aInsert;
    }
    $aInsert['b_active'] = 1;
    return $aInsert;
}
osc_add_filter('item_post_data', 'pngm_publish_force_active', 10);
