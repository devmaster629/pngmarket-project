<?php
/**
 * PNG Market — Anti-spam configuration bootstrap.
 *
 * Turns on existing Osclass / Instant Messenger protections with reasonable
 * defaults. Not a new fraud system: CAPTCHA, posting wait, IM flood limits.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'antispam_config.php'
) {
    exit;
}

if (!defined('PNGM_ANTISPAM_POLICY_VER')) {
    define('PNGM_ANTISPAM_POLICY_VER', 'v1');
}

/** Minimum seconds between listing publishes (Osclass core). */
if (!defined('PNGM_ANTISPAM_ITEMS_WAIT')) {
    define('PNGM_ANTISPAM_ITEMS_WAIT', 60);
}

/** IM: max messages per window for new / low-trust users. */
if (!defined('PNGM_ANTISPAM_IM_MAX_MESSAGES')) {
    define('PNGM_ANTISPAM_IM_MAX_MESSAGES', 20);
}

/** IM: max distinct recipients per window. */
if (!defined('PNGM_ANTISPAM_IM_MAX_USERS')) {
    define('PNGM_ANTISPAM_IM_MAX_USERS', 8);
}

/** IM: window length in hours. */
if (!defined('PNGM_ANTISPAM_IM_PERIOD_HOURS')) {
    define('PNGM_ANTISPAM_IM_PERIOD_HOURS', 12);
}

/**
 * @param string $name
 * @param mixed  $value
 * @param string $type
 */
function pngm_antispam_set_osclass($name, $value, $type = 'STRING')
{
    if (!function_exists('osc_set_preference')) {
        return;
    }
    osc_set_preference($name, (string) $value, 'osclass', $type);
}

/**
 * @param string $name
 * @param mixed  $value
 * @param string $type
 */
function pngm_antispam_set_im($name, $value, $type = 'INTEGER')
{
    if (!function_exists('osc_set_preference')) {
        return;
    }
    osc_set_preference($name, (string) $value, 'plugin-instant_messenger', $type);
}

/**
 * Enable reCAPTCHA when site/secret keys are already configured.
 */
function pngm_antispam_ensure_recaptcha()
{
    if (!function_exists('osc_get_preference')) {
        return;
    }

    $pub = trim((string) osc_get_preference('recaptchaPubKey'));
    $priv = trim((string) osc_get_preference('recaptchaPrivKey'));
    if ($pub === '' || $priv === '') {
        return;
    }

    if ((string) osc_get_preference('recaptchaEnabled') !== '1') {
        pngm_antispam_set_osclass('recaptchaEnabled', '1', 'BOOLEAN');
    }

    // Item publish form uses the same site-wide switch; keep items flag in sync when present.
    $items = osc_get_preference('enabled_recaptcha_items');
    if ($items !== null && $items !== false && (string) $items !== '1') {
        pngm_antispam_set_osclass('enabled_recaptcha_items', '1', 'BOOLEAN');
    }
}

/**
 * Core posting / registration-related spam prefs.
 */
function pngm_antispam_ensure_posting_prefs()
{
    if (!function_exists('osc_get_preference')) {
        return;
    }

    // Only registered users may publish.
    if ((string) osc_get_preference('reg_user_post') !== '1') {
        pngm_antispam_set_osclass('reg_user_post', '1', 'BOOLEAN');
    }

    $wait = (int) osc_get_preference('items_wait_time');
    if ($wait < (int) PNGM_ANTISPAM_ITEMS_WAIT) {
        pngm_antispam_set_osclass('items_wait_time', (string) PNGM_ANTISPAM_ITEMS_WAIT, 'INTEGER');
    }
}

/**
 * Instant Messenger flood limits (plugin prefs).
 */
function pngm_antispam_ensure_im_limits()
{
    if (!function_exists('osc_get_preference')) {
        return;
    }

    // Plugin may be inactive — still safe to write prefs.
    if ((string) osc_get_preference('limit_enabled', 'plugin-instant_messenger') !== '1') {
        pngm_antispam_set_im('limit_enabled', 1);
    }

    $applied = (string) osc_get_preference('pngm_antispam_im_limits', 'epsilon_child');
    if ($applied === PNGM_ANTISPAM_POLICY_VER) {
        return;
    }

    // Seed sensible numbers once (admin may tighten/loosen afterward).
    pngm_antispam_set_im('limit_max_messages', (int) PNGM_ANTISPAM_IM_MAX_MESSAGES);
    pngm_antispam_set_im('limit_max_users', (int) PNGM_ANTISPAM_IM_MAX_USERS);
    pngm_antispam_set_im('limit_period_hours', (int) PNGM_ANTISPAM_IM_PERIOD_HOURS);

    // Trust lift: after sustained normal use, stop applying the window.
    $disable_msgs = (int) osc_get_preference('limit_disable_after_messages', 'plugin-instant_messenger');
    if ($disable_msgs <= 0) {
        pngm_antispam_set_im('limit_disable_after_messages', 100);
    }
    $disable_users = (int) osc_get_preference('limit_disable_after_users', 'plugin-instant_messenger');
    if ($disable_users <= 0) {
        pngm_antispam_set_im('limit_disable_after_users', 20);
    }
    $disable_days = (int) osc_get_preference('limit_disable_after_days_from_reg', 'plugin-instant_messenger');
    if ($disable_days <= 0) {
        pngm_antispam_set_im('limit_disable_after_days_from_reg', 60);
    }

    // Fewer notification emails when chatting quickly.
    if ((string) osc_get_preference('notify_only_unread', 'plugin-instant_messenger') !== '1') {
        pngm_antispam_set_im('notify_only_unread', 1);
    }

    osc_set_preference('pngm_antispam_im_limits', PNGM_ANTISPAM_POLICY_VER, 'epsilon_child', 'STRING');
}

/**
 * Apply anti-spam policy on normal page loads.
 */
function pngm_antispam_apply_policy()
{
    static $done = false;
    if ($done) {
        return;
    }

    if (!function_exists('osc_set_preference') || !function_exists('osc_get_preference')) {
        return;
    }

    try {
        pngm_antispam_ensure_recaptcha();
        pngm_antispam_ensure_posting_prefs();
        pngm_antispam_ensure_im_limits();
        $done = true;
    } catch (Throwable $e) {
        // Prefs / DB unavailable — retry on next request.
    }
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init', 'pngm_antispam_apply_policy', 4);
}
