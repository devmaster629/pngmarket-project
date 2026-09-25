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
    define('PNGM_ANTISPAM_POLICY_VER', 'v2');
}

/** Minimum seconds between listing publishes (Osclass core). 0 = disabled. */
if (!defined('PNGM_ANTISPAM_ITEMS_WAIT')) {
    define('PNGM_ANTISPAM_ITEMS_WAIT', 0);
}

/** IM: max messages per window for new / low-trust users. Unused when limits off. */
if (!defined('PNGM_ANTISPAM_IM_MAX_MESSAGES')) {
    define('PNGM_ANTISPAM_IM_MAX_MESSAGES', 20);
}

/** IM: max distinct recipients per window. Unused when limits off. */
if (!defined('PNGM_ANTISPAM_IM_MAX_USERS')) {
    define('PNGM_ANTISPAM_IM_MAX_USERS', 8);
}

/** IM: window length in hours. Unused when limits off. */
if (!defined('PNGM_ANTISPAM_IM_PERIOD_HOURS')) {
    define('PNGM_ANTISPAM_IM_PERIOD_HOURS', 12);
}

/** Max account registrations per IP per hour. */
if (!defined('PNGM_ANTISPAM_REG_MAX_PER_HOUR')) {
    define('PNGM_ANTISPAM_REG_MAX_PER_HOUR', 5);
}

/** Whether Instant Messenger flood limits are enforced. */
if (!defined('PNGM_ANTISPAM_IM_LIMITS_ENABLED')) {
    define('PNGM_ANTISPAM_IM_LIMITS_ENABLED', false);
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

    // Do not force CAPTCHA on local — production keys reject localhost domains.
    if (function_exists('pngm_is_local_dev_host') && pngm_is_local_dev_host()) {
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

    // Guests can see seller phone numbers, the same as signed-in users.
    if ((string) osc_get_preference('reg_user_can_see_phone') !== '0') {
        pngm_antispam_set_osclass('reg_user_can_see_phone', '0', 'BOOLEAN');
    }

    // Listing wait between publishes (0 = no timing limit).
    $desired_wait = (int) PNGM_ANTISPAM_ITEMS_WAIT;
    $applied = (string) osc_get_preference('pngm_antispam_posting', 'epsilon_child');
    if ($applied !== PNGM_ANTISPAM_POLICY_VER || (int) osc_get_preference('items_wait_time') !== $desired_wait) {
        pngm_antispam_set_osclass('items_wait_time', (string) $desired_wait, 'INTEGER');
        osc_set_preference('pngm_antispam_posting', PNGM_ANTISPAM_POLICY_VER, 'epsilon_child', 'STRING');
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

    // Guests cannot open or send IM threads.
    if ((string) osc_get_preference('only_logged', 'plugin-instant_messenger') !== '1') {
        pngm_antispam_set_im('only_logged', 1);
    }

    $applied = (string) osc_get_preference('pngm_antispam_im_limits', 'epsilon_child');
    if ($applied === PNGM_ANTISPAM_POLICY_VER) {
        return;
    }

    $im_on = PNGM_ANTISPAM_IM_LIMITS_ENABLED ? 1 : 0;
    pngm_antispam_set_im('limit_enabled', $im_on);

    if ($im_on) {
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
    }

    // Fewer notification emails when chatting quickly.
    if ((string) osc_get_preference('notify_only_unread', 'plugin-instant_messenger') !== '1') {
        pngm_antispam_set_im('notify_only_unread', 1);
    }

    osc_set_preference('pngm_antispam_im_limits', PNGM_ANTISPAM_POLICY_VER, 'epsilon_child', 'STRING');
}

/**
 * Count users registered from this IP in the last hour.
 *
 * @param string $ip
 * @return int
 */
function pngm_antispam_reg_count_ip_hour($ip)
{
    $ip = trim((string) $ip);
    if ($ip === '' || !defined('DB_TABLE_PREFIX')) {
        return 0;
    }
    try {
        $conn = DBConnectionClass::newInstance();
        $data = $conn->getOsclassDb();
        $dao = new DBCommandClass($data);
        $since = date('Y-m-d H:i:s', time() - 3600);
        $dao->select('COUNT(*) as total');
        $dao->from(DB_TABLE_PREFIX . 't_user');
        $dao->where('s_access_ip', $ip);
        $dao->where(sprintf("dt_reg_date >= '%s'", $since));
        $res = $dao->get();
        if ($res) {
            $row = $res->row();
            return isset($row['total']) ? (int) $row['total'] : 0;
        }
    } catch (Throwable $e) {
        return 0;
    }
    return 0;
}

/**
 * Block rapid registration spam from the same IP.
 */
function pngm_antispam_require_register_rate_limit()
{
    $ip = function_exists('osc_get_ip') ? (string) osc_get_ip() : '';
    if ($ip === '') {
        return;
    }
    $count = pngm_antispam_reg_count_ip_hour($ip);
    $max = (int) PNGM_ANTISPAM_REG_MAX_PER_HOUR;
    if ($count >= $max) {
        osc_add_flash_error_message(
            sprintf(
                __('Too many accounts were created from this network recently. Please wait before registering again (limit: %d per hour).', 'epsilon'),
                $max
            )
        );
        osc_redirect_to(osc_register_account_url());
    }
}

/**
 * Public snapshot of active rate limits (for Account & Security / auditors).
 *
 * @return array
 */
function pngm_antispam_public_status()
{
    $items_wait = function_exists('osc_items_wait_time') ? (int) osc_items_wait_time() : (int) osc_get_preference('items_wait_time');
    $dup_min = defined('PNGM_DUP_MIN_SECONDS') ? (int) PNGM_DUP_MIN_SECONDS : 0;
    $dup_hour = defined('PNGM_DUP_MAX_PER_HOUR') ? (int) PNGM_DUP_MAX_PER_HOUR : 0;
    $im_on = ((string) osc_get_preference('limit_enabled', 'plugin-instant_messenger') === '1');
    $im_msgs = (int) osc_get_preference('limit_max_messages', 'plugin-instant_messenger');
    $im_users = (int) osc_get_preference('limit_max_users', 'plugin-instant_messenger');
    $im_hours = (int) osc_get_preference('limit_period_hours', 'plugin-instant_messenger');
    $reg_max = (int) PNGM_ANTISPAM_REG_MAX_PER_HOUR;
    $captcha = function_exists('pngm_recaptcha_is_required') && pngm_recaptcha_is_required();
    $posting_on = ($items_wait > 0 || $dup_min > 0 || $dup_hour > 0);

    return array(
        'active' => true,
        'registration' => array(
            'active' => true,
            'label' => sprintf(__('Max %d new accounts per IP per hour + CAPTCHA', 'epsilon'), $reg_max),
            'max_per_hour' => $reg_max,
            'captcha' => $captcha,
        ),
        'posting' => array(
            'active' => $posting_on,
            'label' => $posting_on
                ? sprintf(
                    __('Min %d–%d seconds between listings; max %d listings per hour', 'epsilon'),
                    max(1, $items_wait),
                    max($items_wait, $dup_min),
                    max(1, $dup_hour)
                )
                : __('Listing timing limits off', 'epsilon'),
            'items_wait' => $items_wait,
            'min_seconds' => $dup_min,
            'max_per_hour' => $dup_hour,
        ),
        'messaging' => array(
            'active' => $im_on,
            'label' => $im_on
                ? sprintf(
                    __('Max %d messages or %d contacts per %d hours (new accounts)', 'epsilon'),
                    $im_msgs,
                    $im_users,
                    $im_hours
                )
                : __('Messaging limits off', 'epsilon'),
            'max_messages' => $im_msgs,
            'max_users' => $im_users,
            'period_hours' => $im_hours,
        ),
    );
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
    osc_add_hook('before_user_register', 'pngm_antispam_require_register_rate_limit', 1);
}
