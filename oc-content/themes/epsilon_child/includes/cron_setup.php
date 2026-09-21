<?php
/**
 * PNG Market — Cron / automated tasks bootstrap.
 *
 * Ensures expiry reminders, alerts, and theme hourly jobs rely on a real
 * server cron (not only auto_cron), records health, and surfaces status
 * in Oc-Admin.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'cron_setup.php'
) {
    exit;
}

if (!defined('PNGM_CRON_POLICY_VER')) {
    define('PNGM_CRON_POLICY_VER', 'v1');
}

/** Hourly cron is stale after this many seconds (admin warning). */
if (!defined('PNGM_CRON_HOURLY_STALE_SEC')) {
    define('PNGM_CRON_HOURLY_STALE_SEC', 3 * 3600);
}

/** Minutely cron is stale after this many seconds. */
if (!defined('PNGM_CRON_MINUTELY_STALE_SEC')) {
    define('PNGM_CRON_MINUTELY_STALE_SEC', 20 * 60);
}

/**
 * @return bool
 */
function pngm_cron_is_local()
{
    return function_exists('pngm_is_local_dev_host') && pngm_is_local_dev_host();
}

/**
 * Prefer real crontab on production; keep auto_cron on local for convenience.
 */
function pngm_cron_enforce_prefs()
{
    if (!function_exists('osc_get_preference') || !function_exists('osc_set_preference')) {
        return;
    }

    $applied = (string) osc_get_preference('pngm_cron_policy', 'epsilon_child');
    if ($applied === PNGM_CRON_POLICY_VER) {
        return;
    }

    if (pngm_cron_is_local()) {
        if ((string) osc_get_preference('auto_cron') !== '1') {
            osc_set_preference('auto_cron', '1', 'osclass', 'BOOLEAN');
        }
    } else {
        // Production: built-in auto_cron is unreliable (Osclass admin warning).
        if ((string) osc_get_preference('auto_cron') !== '0') {
            osc_set_preference('auto_cron', '0', 'osclass', 'BOOLEAN');
        }
    }

    // Expiry reminder window (also enforced by listing_expiry.php).
    if ((int) osc_get_preference('warn_expiration') !== 7) {
        osc_set_preference('warn_expiration', '7', 'osclass', 'INTEGER');
    }

    if ((string) osc_get_preference('enabled_renewal_items') !== '1') {
        osc_set_preference('enabled_renewal_items', '1', 'osclass', 'BOOLEAN');
    }

    osc_set_preference('pngm_cron_policy', PNGM_CRON_POLICY_VER, 'epsilon_child', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->toArray();
    }
}
osc_add_hook('init', 'pngm_cron_enforce_prefs', 3);
osc_add_hook('cron', 'pngm_cron_enforce_prefs', 1);

/**
 * @param string $type minutely|hourly|daily|weekly|monthly|yearly|any
 */
function pngm_cron_stamp($type)
{
    if (!function_exists('osc_set_preference')) {
        return;
    }
    $now = date('Y-m-d H:i:s');
    $type = preg_replace('/[^a-z]/', '', strtolower((string) $type));
    if ($type === '') {
        $type = 'any';
    }
    osc_set_preference('last_' . $type, $now, 'pngm_cron', 'STRING');
    osc_set_preference('last_any', $now, 'pngm_cron', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->toArray();
    }
}

function pngm_cron_stamp_minutely()
{
    pngm_cron_stamp('minutely');
}
function pngm_cron_stamp_hourly()
{
    pngm_cron_stamp('hourly');
}
function pngm_cron_stamp_daily()
{
    pngm_cron_stamp('daily');
}
osc_add_hook('cron_minutely', 'pngm_cron_stamp_minutely', 1);
osc_add_hook('cron_hourly', 'pngm_cron_stamp_hourly', 1);
osc_add_hook('cron_daily', 'pngm_cron_stamp_daily', 1);

/**
 * @return array{ok:bool,auto_cron:bool,local:bool,rows:array,warnings:array,crontab:array}
 */
function pngm_cron_health()
{
    if (function_exists('pngm_cron_enforce_prefs')) {
        pngm_cron_enforce_prefs();
    }

    $out = array(
        'ok' => true,
        'auto_cron' => false,
        'local' => pngm_cron_is_local(),
        'rows' => array(),
        'warnings' => array(),
        'crontab' => array(),
    );

    if (!function_exists('osc_get_preference') || !class_exists('Cron')) {
        $out['ok'] = false;
        $out['warnings'][] = 'Cron helpers unavailable.';
        return $out;
    }

    $out['auto_cron'] = ((string) osc_get_preference('auto_cron') === '1');
    $now = time();

    $map = array(
        'MINUTELY' => PNGM_CRON_MINUTELY_STALE_SEC,
        'HOURLY' => PNGM_CRON_HOURLY_STALE_SEC,
        'DAILY' => 36 * 3600,
        'WEEKLY' => 9 * 86400,
        'MONTHLY' => 40 * 86400,
        'YEARLY' => 400 * 86400,
    );

    foreach ($map as $type => $stale) {
        $row = Cron::newInstance()->getCronByType($type);
        $last = is_array($row) ? (string) @$row['d_last_exec'] : '';
        $next = is_array($row) ? (string) @$row['d_next_exec'] : '';
        $last_ts = $last !== '' ? strtotime($last) : 0;
        $is_stale = ($last_ts <= 0) || (($now - $last_ts) > (int) $stale);
        $out['rows'][$type] = array(
            'last' => $last,
            'next' => $next,
            'stale' => $is_stale,
        );
        if ($is_stale && in_array($type, array('MINUTELY', 'HOURLY', 'DAILY'), true)) {
            $out['ok'] = false;
            $out['warnings'][] = sprintf(
                '%s cron last ran %s (expected within %s).',
                $type,
                $last !== '' ? $last : 'never',
                $type === 'MINUTELY' ? '20 minutes' : ($type === 'HOURLY' ? '3 hours' : '36 hours')
            );
        }
    }

    if ($out['auto_cron'] && !$out['local']) {
        $out['ok'] = false;
        $out['warnings'][] = 'Built-in auto_cron is still enabled on production. Use server crontab and disable auto_cron.';
    }

    $warn = (int) osc_get_preference('warn_expiration');
    if ($warn !== 7) {
        $out['warnings'][] = sprintf('warn_expiration is %d (expected 7).', $warn);
    }

    $root = defined('ABS_PATH') ? rtrim(str_replace('\\', '/', ABS_PATH), '/') : '';
    $php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $runner = $root !== '' ? $root . '/pngm-cron.php' : 'pngm-cron.php';
    $http = '';
    if (function_exists('osc_base_url')) {
        $http = rtrim((string) osc_base_url(false), '/') . '/index.php?page=cron';
    }
    $out['crontab'] = array(
        '# Linux / cPanel (every 5 minutes) — preferred',
        '*/5 * * * * ' . $php . ' ' . $runner . ' >/dev/null 2>&1',
        '',
        '# Or HTTP hit (every 5 minutes)',
        '*/5 * * * * curl -fsS "' . $http . '" >/dev/null 2>&1',
        '',
        '# Windows Task Scheduler — Program: ' . $php,
        '# Arguments: ' . $runner,
        '# Trigger: every 5 minutes',
    );

    return $out;
}

/**
 * Oc-Admin dashboard: cron health + crontab cheat-sheet.
 */
function pngm_cron_admin_dashboard()
{
    if (!function_exists('osc_is_admin_user_logged_in') || !osc_is_admin_user_logged_in()) {
        return;
    }

    $h = pngm_cron_health();
    $cls = $h['ok'] ? 'widget-ok' : 'widget-warning';
    echo '<div class="widget-box widget-message ' . osc_esc_html($cls) . '">';
    echo '<strong>' . osc_esc_html(__('PNG Market — Automated tasks (cron)', 'epsilon')) . '</strong>';
    echo '<ul style="margin:8px 0 0 18px;">';
    foreach ($h['rows'] as $type => $row) {
        $mark = !empty($row['stale']) ? '⚠' : '✓';
        echo '<li>' . osc_esc_html($mark . ' ' . $type . ' last=' . $row['last'] . ' next=' . $row['next']) . '</li>';
    }
    echo '<li>' . osc_esc_html(
        'auto_cron=' . ($h['auto_cron'] ? '1' : '0')
        . ' · warn_expiration=' . (int) osc_get_preference('warn_expiration')
        . ' · local=' . ($h['local'] ? 'yes' : 'no')
    ) . '</li>';
    echo '</ul>';

    if (!empty($h['warnings'])) {
        echo '<p style="margin-top:8px;"><strong>' . osc_esc_html(__('Action needed:', 'epsilon')) . '</strong></p><ul style="margin:0 0 0 18px;">';
        foreach ($h['warnings'] as $w) {
            echo '<li>' . osc_esc_html($w) . '</li>';
        }
        echo '</ul>';
    }

    echo '<p style="margin-top:8px;"><strong>' . osc_esc_html(__('Server crontab / Task Scheduler:', 'epsilon')) . '</strong></p>';
    echo '<pre style="white-space:pre-wrap;font-size:11px;background:#f6f6f6;padding:8px;border-radius:4px;">';
    echo osc_esc_html(implode("\n", $h['crontab']));
    echo '</pre>';
    echo '<p style="margin:6px 0 0;">' . osc_esc_html(
        __('Test (admin session): open /index.php?page=cron&force=1&type=hourly&print=1', 'epsilon')
    ) . '</p>';
    echo '</div>';
}
osc_add_hook('admin_dashboard_col1_top', 'pngm_cron_admin_dashboard', 4);
