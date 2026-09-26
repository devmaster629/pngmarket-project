<?php
/**
 * P2-004 / QA-007 — Duplicate listing prevention.
 *
 * Same seller cannot reuse an identical title on a non-expired listing
 * (case / spacing / punctuation-insensitive). Other users' titles never block.
 * Slight differences are allowed, e.g. "Genset 3.2kw" vs "Genset 3.8kw".
 * Expired listings are ignored so the title can be posted again.
 * Fuzzy near-match scoring is not used for titles.
 * Throttle rapid posting when limits are non-zero.
 *
 * Soft "moderate → pending" was removed: every successful publish stays active.
 *
 * NOTE: Osclass Plugins::applyFilter only runs priorities 0–10.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'duplicate_listings.php'
) {
    exit;
}

if (!defined('PNGM_DUP_WINDOW_DAYS')) {
    define('PNGM_DUP_WINDOW_DAYS', 30);
}
if (!defined('PNGM_DUP_MAX_PER_HOUR')) {
    define('PNGM_DUP_MAX_PER_HOUR', 0);
}
if (!defined('PNGM_DUP_MIN_SECONDS')) {
    define('PNGM_DUP_MIN_SECONDS', 0);
}

/**
 * @param mixed $titles
 * @return string
 */
function pngm_dup_primary_title($titles)
{
    if (is_string($titles)) {
        return trim($titles);
    }
    if (!is_array($titles)) {
        return '';
    }
    $locale = function_exists('osc_current_user_locale') ? (string) osc_current_user_locale() : '';
    if ($locale !== '' && isset($titles[$locale]) && trim((string) $titles[$locale]) !== '') {
        return trim((string) $titles[$locale]);
    }
    foreach ($titles as $t) {
        $t = trim((string) $t);
        if ($t !== '') {
            return $t;
        }
    }
    return '';
}

/**
 * @param mixed $descriptions
 * @return string
 */
function pngm_dup_primary_description($descriptions)
{
    if (is_string($descriptions)) {
        return trim(strip_tags($descriptions));
    }
    if (!is_array($descriptions)) {
        return '';
    }
    $locale = function_exists('osc_current_user_locale') ? (string) osc_current_user_locale() : '';
    if ($locale !== '' && isset($descriptions[$locale])) {
        return trim(strip_tags((string) $descriptions[$locale]));
    }
    foreach ($descriptions as $d) {
        $d = trim(strip_tags((string) $d));
        if ($d !== '') {
            return $d;
        }
    }
    return '';
}

/**
 * Normalize title for exact duplicate comparison.
 * Keeps letters, numbers, dots and hyphens so "3.2kw" ≠ "3.8kw".
 * Collapses case/spacing and strips other punctuation ("Test!" ≡ "test").
 *
 * @param string $title
 * @return string
 */
function pngm_dup_normalize_title($title)
{
    $title = trim(strip_tags((string) $title));
    if (function_exists('mb_strtolower')) {
        $title = mb_strtolower($title, 'UTF-8');
    } else {
        $title = strtolower($title);
    }
    // Keep word characters, digits, spaces, decimal points, hyphens.
    $title = preg_replace('/[^\p{L}\p{N}\s.\-]+/u', '', $title);
    $title = preg_replace('/\s+/u', ' ', (string) $title);
    return trim((string) $title);
}

/**
 * True when two titles are the same after normalization.
 *
 * @param string $a
 * @param string $b
 * @return bool
 */
function pngm_dup_titles_identical($a, $b)
{
    $a = pngm_dup_normalize_title($a);
    $b = pngm_dup_normalize_title($b);
    return ($a !== '' && $a === $b);
}

/**
 * @return DBCommandClass|null
 */
function pngm_dup_db()
{
    try {
        $conn = DBConnectionClass::newInstance();
        $data = $conn->getOsclassDb();
        return new DBCommandClass($data);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Non-expired listings owned by this seller only.
 *
 * Logged-in: match by user id only (never IP — shared networks were matching
 * other people's titles). Guest: match by contact email only.
 * Expired ads are ignored so the same title can be reused after expiry.
 *
 * @param int    $user_id
 * @param string $email
 * @param string $ip  unused (kept for callers); IP is intentionally not used
 * @param int    $exclude_item_id
 * @return array
 */
function pngm_dup_find_seller_items($user_id, $email, $ip, $exclude_item_id = 0)
{
    $user_id = (int) $user_id;
    $email = strtolower(trim((string) $email));
    $exclude_item_id = (int) $exclude_item_id;
    $days = (int) PNGM_DUP_WINDOW_DAYS;
    $prefix = DB_TABLE_PREFIX;
    $out = array();

    // Same account only — do not OR with IP (that linked other users on the same network).
    $seller_sql = '';
    if ($user_id > 0) {
        $seller_sql = 'i.fk_i_user_id = ' . $user_id;
    } elseif ($email !== '') {
        $seller_sql = 'LOWER(i.s_contact_email) = "' . addslashes($email) . '"';
    } else {
        return $out;
    }

    $comm = pngm_dup_db();
    if (!$comm) {
        return $out;
    }

    try {
        $where = array();
        $where[] = 'i.b_spam = 0';
        $where[] = 'i.b_enabled = 1';
        // Soft-expired listings do not block reusing the title.
        $where[] = '(i.dt_expiration >= NOW() OR i.dt_expiration LIKE "9999%")';
        $where[] = sprintf('i.dt_pub_date >= DATE_SUB(NOW(), INTERVAL %d DAY)', $days);
        $where[] = $seller_sql;
        if ($exclude_item_id > 0) {
            $where[] = 'i.pk_i_id <> ' . $exclude_item_id;
        }

        $sql = sprintf(
            'SELECT i.pk_i_id, i.fk_i_category_id, i.i_price, i.dt_pub_date, i.dt_expiration, d.s_title, d.s_description
             FROM %st_item i
             INNER JOIN %st_item_description d ON d.fk_i_item_id = i.pk_i_id
             WHERE %s
             ORDER BY i.dt_pub_date DESC
             LIMIT 80',
            $prefix,
            $prefix,
            implode(' AND ', $where)
        );
        $rs = $comm->query($sql);
        if (!$rs) {
            return $out;
        }
        $rows = $rs->result();
        if (!is_array($rows)) {
            return $out;
        }

        foreach ($rows as $row) {
            $id = isset($row['pk_i_id']) ? (int) $row['pk_i_id'] : 0;
            if ($id <= 0 || isset($out[$id])) {
                continue;
            }
            // Extra safety if MySQL NOW() edge-cases slip through.
            if (!empty($row['dt_expiration'])
                && strpos((string) $row['dt_expiration'], '9999') !== 0
                && function_exists('osc_isExpired')
                && osc_isExpired($row['dt_expiration'])
            ) {
                continue;
            }
            $out[$id] = $row;
        }
    } catch (Exception $e) {
        return array();
    }

    return array_values($out);
}

/**
 * Find same-seller listing with an identical normalized title.
 *
 * @param string $title
 * @param int    $user_id
 * @param string $email
 * @param string $ip
 * @param int    $exclude_item_id
 * @return array|null
 */
function pngm_dup_find_identical_title_for_seller($title, $user_id, $email, $ip, $exclude_item_id = 0)
{
    $norm = pngm_dup_normalize_title($title);
    if ($norm === '') {
        return null;
    }

    $recent = pngm_dup_find_seller_items($user_id, $email, $ip, $exclude_item_id);
    foreach ($recent as $row) {
        $other = isset($row['s_title']) ? (string) $row['s_title'] : '';
        if (pngm_dup_titles_identical($title, $other)) {
            return $row;
        }
    }
    return null;
}

/**
 * Count listings published by seller in the last hour.
 *
 * @param int    $user_id
 * @param string $email
 * @return int
 */
function pngm_dup_count_last_hour($user_id, $email)
{
    $user_id = (int) $user_id;
    $email = strtolower(trim((string) $email));
    $prefix = DB_TABLE_PREFIX;
    $comm = pngm_dup_db();
    if (!$comm) {
        return 0;
    }

    try {
        $bits = array();
        if ($user_id > 0) {
            $bits[] = 'fk_i_user_id = ' . $user_id;
        }
        if ($email !== '') {
            $bits[] = 'LOWER(s_contact_email) = "' . addslashes($email) . '"';
        }
        if (empty($bits)) {
            return 0;
        }
        $sql = sprintf(
            'SELECT COUNT(*) AS c FROM %st_item WHERE (%s) AND dt_pub_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            $prefix,
            implode(' OR ', $bits)
        );
        $rs = $comm->query($sql);
        if (!$rs) {
            return 0;
        }
        $row = $rs->row();
        return isset($row['c']) ? (int) $row['c'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Seconds since last listing by this seller.
 *
 * @param int    $user_id
 * @param string $email
 * @return int|null
 */
function pngm_dup_seconds_since_last($user_id, $email)
{
    $user_id = (int) $user_id;
    $email = strtolower(trim((string) $email));
    $prefix = DB_TABLE_PREFIX;
    $comm = pngm_dup_db();
    if (!$comm) {
        return null;
    }

    try {
        $bits = array();
        if ($user_id > 0) {
            $bits[] = 'fk_i_user_id = ' . $user_id;
        }
        if ($email !== '') {
            $bits[] = 'LOWER(s_contact_email) = "' . addslashes($email) . '"';
        }
        if (empty($bits)) {
            return null;
        }
        $sql = sprintf(
            'SELECT dt_pub_date FROM %st_item WHERE (%s) ORDER BY dt_pub_date DESC LIMIT 1',
            $prefix,
            implode(' OR ', $bits)
        );
        $rs = $comm->query($sql);
        if (!$rs) {
            return null;
        }
        $row = $rs->row();
        if (empty($row['dt_pub_date'])) {
            return null;
        }
        $ts = strtotime($row['dt_pub_date']);
        if (!$ts) {
            return null;
        }
        return max(0, time() - $ts);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Evaluate duplicate risk for a prepared item payload.
 *
 * @param array $aItem
 * @param int   $exclude_item_id
 * @return array{action:string,score:int,message:string,match_id:int}
 */
function pngm_dup_evaluate($aItem, $exclude_item_id = 0)
{
    $result = array(
        'action' => 'ok',
        'score' => 0,
        'message' => '',
        'match_id' => 0,
    );

    if (!is_array($aItem)) {
        return $result;
    }

    // Only skip when posting from oc-admin (ItemActions is_admin), not when an
    // admin cookie exists while using the public publish form.
    if (defined('OC_ADMIN') && OC_ADMIN) {
        return $result;
    }

    $user_id = !empty($aItem['userId']) ? (int) $aItem['userId'] : 0;
    if ($user_id <= 0 && function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in()) {
        $user_id = (int) osc_logged_user_id();
    }
    $email = isset($aItem['contactEmail']) ? strtolower(trim((string) $aItem['contactEmail'])) : '';
    if ($email === '' && function_exists('osc_logged_user_email')) {
        $email = strtolower(trim((string) osc_logged_user_email()));
    }
    $ip = isset($aItem['s_ip']) ? trim((string) $aItem['s_ip']) : '';
    if ($ip === '' && function_exists('osc_get_ip')) {
        $ip = (string) osc_get_ip();
    }

    $title = pngm_dup_primary_title(isset($aItem['title']) ? $aItem['title'] : array());

    // --- Throttle (0 = disabled) ---
    $max_per_hour = (int) PNGM_DUP_MAX_PER_HOUR;
    if ($max_per_hour > 0) {
        $hour_count = pngm_dup_count_last_hour($user_id, $email);
        if ($hour_count >= $max_per_hour) {
            $result['action'] = 'block';
            $result['message'] = sprintf(
                __('You have posted too many listings recently. Please wait before posting again (limit: %d per hour).', 'epsilon'),
                $max_per_hour
            );
            return $result;
        }
    }

    $min_seconds = (int) PNGM_DUP_MIN_SECONDS;
    if ($min_seconds > 0) {
        $since = pngm_dup_seconds_since_last($user_id, $email);
        if ($since !== null && $since < $min_seconds) {
            $wait = $min_seconds - (int) $since;
            $result['action'] = 'block';
            $result['message'] = sprintf(
                __('Please wait %d seconds before posting another listing.', 'epsilon'),
                max(1, $wait)
            );
            return $result;
        }
    }

    if ($title === '' || pngm_dup_normalize_title($title) === '') {
        return $result;
    }

    // Exact title only (same seller). Slight differences are allowed.
    $match = pngm_dup_find_identical_title_for_seller($title, $user_id, $email, $ip, $exclude_item_id);
    if (is_array($match)) {
        $best_title = isset($match['s_title']) ? (string) $match['s_title'] : $title;
        $result['action'] = 'block';
        $result['score'] = 100;
        $result['match_id'] = isset($match['pk_i_id']) ? (int) $match['pk_i_id'] : 0;
        $result['message'] = sprintf(
            __('You already have a listing with this exact title (“%s”). Please use a different title, or edit your existing listing.', 'epsilon'),
            $best_title !== '' ? $best_title : __('your earlier ad', 'epsilon')
        );
        return $result;
    }

    return $result;
}

/**
 * Evaluate duplicates for block-only checks (no soft pending).
 *
 * @param array $aItem
 * @return array
 */
function pngm_dup_item_add_prepare_data($aItem)
{
    unset($GLOBALS['pngm_dup_eval'], $GLOBALS['pngm_dup_moderate']);
    if (!is_array($aItem)) {
        return $aItem;
    }
    $GLOBALS['pngm_dup_eval'] = pngm_dup_evaluate($aItem, 0);
    return $aItem;
}
osc_add_filter('item_add_prepare_data', 'pngm_dup_item_add_prepare_data', 8);

/**
 * Block hard duplicates / throttle on publish.
 *
 * @param string $flash_error
 * @param array  $aItem
 * @return string
 */
function pngm_dup_pre_item_add_error($flash_error, $aItem)
{
    $flash_error = (string) $flash_error;
    $eval = !empty($GLOBALS['pngm_dup_eval']) && is_array($GLOBALS['pngm_dup_eval'])
        ? $GLOBALS['pngm_dup_eval']
        : pngm_dup_evaluate(is_array($aItem) ? $aItem : array(), 0);

    if ($eval['action'] === 'block' && $eval['message'] !== '') {
        $flash_error .= ($flash_error !== '' ? PHP_EOL : '') . $eval['message'];
    }
    return $flash_error;
}
osc_add_filter('pre_item_add_error', 'pngm_dup_pre_item_add_error', 8);

/**
 * On edit: block if renamed into a duplicate of another listing.
 *
 * @param string $flash_error
 * @param array  $aItem
 * @return string
 */
function pngm_dup_pre_item_edit_error($flash_error, $aItem)
{
    $flash_error = (string) $flash_error;
    $exclude = 0;
    if (is_array($aItem) && !empty($aItem['idItem'])) {
        $exclude = (int) $aItem['idItem'];
    } elseif (class_exists('Params') && Params::getParam('id') !== '') {
        $exclude = (int) Params::getParam('id');
    }
    $eval = pngm_dup_evaluate(is_array($aItem) ? $aItem : array(), $exclude);
    if ($eval['action'] === 'block' && $eval['message'] !== '') {
        $flash_error .= ($flash_error !== '' ? PHP_EOL : '') . $eval['message'];
    }
    return $flash_error;
}
osc_add_filter('pre_item_edit_error', 'pngm_dup_pre_item_edit_error', 8);

/**
 * Soft-pending path removed — listings stay active after a successful publish.
 *
 * @param array $aInsert
 * @return array
 */
function pngm_dup_item_post_data($aInsert)
{
    return $aInsert;
}
osc_add_filter('item_post_data', 'pngm_dup_item_post_data', 8);

/**
 * @param array $item
 */
function pngm_dup_posted_item_notice($item)
{
    unset($GLOBALS['pngm_dup_moderate'], $GLOBALS['pngm_dup_eval']);
}
osc_add_hook('posted_item', 'pngm_dup_posted_item_notice', 8);

/**
 * AJAX: exact duplicate title check for the logged-in seller (post wizard).
 */
function pngm_ajax_check_duplicate_title()
{
    header('Content-Type: application/json; charset=utf-8');

    $title = '';
    if (class_exists('Params')) {
        $title = trim((string) Params::getParam('title', false, false));
        if ($title === '' && isset($_POST['title'])) {
            $title = trim((string) $_POST['title']);
        }
    }
    $exclude = class_exists('Params') ? (int) Params::getParam('itemId') : 0;

    if ($title === '' || pngm_dup_normalize_title($title) === '') {
        echo json_encode(array('ok' => true, 'action' => 'ok'));
        exit;
    }

    $user_id = 0;
    $email = '';
    $ip = function_exists('osc_get_ip') ? (string) osc_get_ip() : '';
    if (function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in()) {
        $user_id = (int) osc_logged_user_id();
        if (function_exists('osc_logged_user_email')) {
            $email = strtolower(trim((string) osc_logged_user_email()));
        }
    }

    $aItem = array(
        'userId' => $user_id,
        'contactEmail' => $email,
        's_ip' => $ip,
        'title' => $title,
    );
    $eval = pngm_dup_evaluate($aItem, $exclude);

    if ($eval['action'] === 'block') {
        echo json_encode(array(
            'ok' => false,
            'action' => 'block',
            'message' => $eval['message'],
            'match_id' => (int) $eval['match_id'],
        ));
        exit;
    }

    echo json_encode(array('ok' => true, 'action' => 'ok'));
    exit;
}
osc_add_hook('ajax_pngm_check_duplicate_title', 'pngm_ajax_check_duplicate_title');

/* pngm:duplicate_listings-own-active-title-20260926 */
