<?php
/**
 * P2-004 / QA-007 — Duplicate listing prevention.
 *
 * - Score near-identical titles (same seller / email / IP)
 * - Block exact titles site-wide
 * - Throttle rapid posting
 * - Queue softer same-seller matches for moderation (b_active = 0)
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
if (!defined('PNGM_DUP_BLOCK_SCORE')) {
    define('PNGM_DUP_BLOCK_SCORE', 90);
}
if (!defined('PNGM_DUP_MODERATE_SCORE')) {
    define('PNGM_DUP_MODERATE_SCORE', 75);
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
 * Normalize title for comparison.
 *
 * @param string $title
 * @return string
 */
function pngm_dup_normalize_title($title)
{
    $title = strtolower(trim(strip_tags((string) $title)));
    if (function_exists('mb_strtolower')) {
        $title = mb_strtolower(trim(strip_tags((string) $title)), 'UTF-8');
    }
    $title = preg_replace('/\s+/u', ' ', $title);
    $title = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $title);
    return trim((string) $title);
}

/**
 * Similarity 0–100 between two titles.
 *
 * @param string $a
 * @param string $b
 * @return int
 */
function pngm_dup_title_score($a, $b)
{
    $a = pngm_dup_normalize_title($a);
    $b = pngm_dup_normalize_title($b);
    if ($a === '' || $b === '') {
        return 0;
    }
    if ($a === $b) {
        return 100;
    }
    $percent = 0.0;
    similar_text($a, $b, $percent);
    $score = (int) round($percent);

    if (function_exists('levenshtein') && strlen($a) <= 255 && strlen($b) <= 255) {
        $max = max(strlen($a), strlen($b));
        if ($max > 0) {
            $lev = 100 - (int) round((levenshtein($a, $b) / $max) * 100);
            $score = max($score, $lev);
        }
    }

    return max(0, min(100, $score));
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
 * Recent listings by the same seller (user id and/or email and/or IP).
 *
 * @param int    $user_id
 * @param string $email
 * @param string $ip
 * @param int    $exclude_item_id
 * @return array
 */
function pngm_dup_find_seller_items($user_id, $email, $ip, $exclude_item_id = 0)
{
    $user_id = (int) $user_id;
    $email = strtolower(trim((string) $email));
    $ip = trim((string) $ip);
    $exclude_item_id = (int) $exclude_item_id;
    $days = (int) PNGM_DUP_WINDOW_DAYS;
    $prefix = DB_TABLE_PREFIX;
    $out = array();

    $seller_bits = array();
    if ($user_id > 0) {
        $seller_bits[] = 'i.fk_i_user_id = ' . $user_id;
    }
    if ($email !== '') {
        $seller_bits[] = 'LOWER(i.s_contact_email) = "' . addslashes($email) . '"';
    }
    if ($ip !== '' && $ip !== '127.0.0.1' && $ip !== '::1') {
        $seller_bits[] = 'i.s_ip = "' . addslashes($ip) . '"';
    }
    if (empty($seller_bits)) {
        return $out;
    }

    $comm = pngm_dup_db();
    if (!$comm) {
        return $out;
    }

    try {
        $where = array();
        $where[] = 'i.b_spam = 0';
        $where[] = sprintf('i.dt_pub_date >= DATE_SUB(NOW(), INTERVAL %d DAY)', $days);
        $where[] = '(' . implode(' OR ', $seller_bits) . ')';
        if ($exclude_item_id > 0) {
            $where[] = 'i.pk_i_id <> ' . $exclude_item_id;
        }

        $sql = sprintf(
            'SELECT i.pk_i_id, i.fk_i_category_id, i.i_price, i.dt_pub_date, d.s_title, d.s_description
             FROM %st_item i
             INNER JOIN %st_item_description d ON d.fk_i_item_id = i.pk_i_id
             WHERE %s
             ORDER BY i.dt_pub_date DESC
             LIMIT 50',
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
            $out[$id] = $row;
        }
    } catch (Exception $e) {
        return array();
    }

    return array_values($out);
}

/**
 * Site-wide exact normalized title match (any seller).
 *
 * @param string $title
 * @param int    $exclude_item_id
 * @return array|null
 */
function pngm_dup_find_exact_title_any($title, $exclude_item_id = 0)
{
    $norm = pngm_dup_normalize_title($title);
    if ($norm === '') {
        return null;
    }

    $exclude_item_id = (int) $exclude_item_id;
    $days = (int) PNGM_DUP_WINDOW_DAYS;
    $prefix = DB_TABLE_PREFIX;
    $comm = pngm_dup_db();
    if (!$comm) {
        return null;
    }

    // Pull recent non-spam titles and compare normalized in PHP (handles punctuation / case).
    try {
        $where = array();
        $where[] = 'i.b_spam = 0';
        $where[] = 'i.b_enabled = 1';
        $where[] = sprintf('i.dt_pub_date >= DATE_SUB(NOW(), INTERVAL %d DAY)', $days);
        if ($exclude_item_id > 0) {
            $where[] = 'i.pk_i_id <> ' . $exclude_item_id;
        }
        // Cheap SQL prefilter: same length ±2 or LIKE first word.
        $raw = addslashes(trim(strip_tags((string) $title)));
        $where[] = '(LOWER(TRIM(d.s_title)) = "' . addslashes($norm) . '" OR LOWER(TRIM(d.s_title)) = "' . strtolower($raw) . '" OR d.s_title = "' . $raw . '")';

        $sql = sprintf(
            'SELECT i.pk_i_id, i.fk_i_user_id, d.s_title, i.dt_pub_date
             FROM %st_item i
             INNER JOIN %st_item_description d ON d.fk_i_item_id = i.pk_i_id
             WHERE %s
             ORDER BY i.dt_pub_date DESC
             LIMIT 20',
            $prefix,
            $prefix,
            implode(' AND ', $where)
        );
        $rs = $comm->query($sql);
        if (!$rs) {
            return null;
        }
        $rows = $rs->result();
        if (!is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            $other = isset($row['s_title']) ? (string) $row['s_title'] : '';
            if (pngm_dup_normalize_title($other) === $norm) {
                return $row;
            }
        }
    } catch (Exception $e) {
        return null;
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
    $desc = pngm_dup_primary_description(isset($aItem['description']) ? $aItem['description'] : array());
    $cat_id = isset($aItem['catId']) ? (int) $aItem['catId'] : 0;
    $price = isset($aItem['price']) ? (int) $aItem['price'] : 0;

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

    if ($title === '') {
        return $result;
    }

    // --- Site-wide exact title ---
    $exact = pngm_dup_find_exact_title_any($title, $exclude_item_id);
    if (is_array($exact) && !empty($exact['pk_i_id'])) {
        $result['action'] = 'block';
        $result['score'] = 100;
        $result['match_id'] = (int) $exact['pk_i_id'];
        $match_title = isset($exact['s_title']) ? (string) $exact['s_title'] : $title;
        $result['message'] = sprintf(
            __('A listing with the same title (“%s”) already exists. Please choose a more specific title or edit the existing listing.', 'epsilon'),
            $match_title
        );
        return $result;
    }

    // --- Same seller near-duplicates ---
    $recent = pngm_dup_find_seller_items($user_id, $email, $ip, $exclude_item_id);
    $best_score = 0;
    $best_id = 0;
    $best_title = '';

    foreach ($recent as $row) {
        $other_title = isset($row['s_title']) ? (string) $row['s_title'] : '';
        $score = pngm_dup_title_score($title, $other_title);

        $same_cat = ($cat_id > 0 && isset($row['fk_i_category_id']) && (int) $row['fk_i_category_id'] === $cat_id);
        $same_price = (isset($row['i_price']) && (int) $row['i_price'] === $price && $price > 0);
        if ($same_cat && $score >= 60) {
            $score = min(100, $score + 5);
        }
        if ($same_price && $score >= 60) {
            $score = min(100, $score + 5);
        }

        $other_desc = isset($row['s_description']) ? trim(strip_tags((string) $row['s_description'])) : '';
        if ($desc !== '' && $other_desc !== '' && $score >= 50) {
            $dscore = 0.0;
            similar_text(
                pngm_dup_normalize_title(substr($desc, 0, 400)),
                pngm_dup_normalize_title(substr($other_desc, 0, 400)),
                $dscore
            );
            if ($dscore >= 80) {
                $score = min(100, $score + 10);
            }
        }

        if ($score > $best_score) {
            $best_score = $score;
            $best_id = isset($row['pk_i_id']) ? (int) $row['pk_i_id'] : 0;
            $best_title = $other_title;
        }
    }

    $result['score'] = $best_score;
    $result['match_id'] = $best_id;

    if ($best_score >= (int) PNGM_DUP_BLOCK_SCORE) {
        $result['action'] = 'block';
        $result['message'] = sprintf(
            __('This listing looks like a duplicate of one you already posted (“%s”). Please edit that listing instead of creating another.', 'epsilon'),
            $best_title !== '' ? $best_title : __('your earlier ad', 'epsilon')
        );
        return $result;
    }

    if ($best_score >= (int) PNGM_DUP_MODERATE_SCORE) {
        $result['action'] = 'moderate';
        $result['message'] = sprintf(
            __('This listing is very similar to one you already posted (“%s”). It was submitted for moderation review and is not public yet.', 'epsilon'),
            $best_title !== '' ? $best_title : __('your earlier ad', 'epsilon')
        );
        return $result;
    }

    return $result;
}

/**
 * Soft-duplicates: force inactive before insert so success=1 and stats stay correct.
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
    $eval = pngm_dup_evaluate($aItem, 0);
    $GLOBALS['pngm_dup_eval'] = $eval;
    if ($eval['action'] === 'moderate') {
        $aItem['active'] = 'INACTIVE';
        $GLOBALS['pngm_dup_moderate'] = $eval;
    }
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
        unset($GLOBALS['pngm_dup_moderate']);
        $flash_error .= ($flash_error !== '' ? PHP_EOL : '') . $eval['message'];
        return $flash_error;
    }
    if ($eval['action'] === 'moderate') {
        $GLOBALS['pngm_dup_moderate'] = $eval;
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
 * Safety net: keep soft duplicates inactive in DB.
 *
 * @param array $aInsert
 * @return array
 */
function pngm_dup_item_post_data($aInsert)
{
    if (!empty($GLOBALS['pngm_dup_moderate']) && is_array($aInsert)) {
        $aInsert['b_active'] = 0;
    }
    return $aInsert;
}
osc_add_filter('item_post_data', 'pngm_dup_item_post_data', 8);

/**
 * Flash moderation notice after a soft-duplicate insert.
 *
 * @param array $item
 */
function pngm_dup_posted_item_notice($item)
{
    if (empty($GLOBALS['pngm_dup_moderate']) || !is_array($GLOBALS['pngm_dup_moderate'])) {
        return;
    }
    $msg = isset($GLOBALS['pngm_dup_moderate']['message'])
        ? (string) $GLOBALS['pngm_dup_moderate']['message']
        : __('Your listing was submitted for moderation because it looks similar to one you already posted.', 'epsilon');
    unset($GLOBALS['pngm_dup_moderate'], $GLOBALS['pngm_dup_eval']);
    if ($msg !== '' && function_exists('osc_add_flash_warning_message')) {
        osc_add_flash_warning_message($msg);
    } elseif ($msg !== '' && function_exists('osc_add_flash_ok_message')) {
        osc_add_flash_ok_message($msg);
    }
}
osc_add_hook('posted_item', 'pngm_dup_posted_item_notice', 8);

/**
 * AJAX: early exact-title check from the post wizard Details step.
 * Skips throttle so sellers are not blocked mid-wizard for rate limits
 * (those still apply on publish).
 */
function pngm_ajax_check_duplicate_title()
{
    header('Content-Type: application/json; charset=utf-8');

    $title = trim((string) Params::getParam('title'));
    $exclude = (int) Params::getParam('itemId');

    if (strlen($title) < 3) {
        echo json_encode(array('ok' => true, 'action' => 'ok'));
        exit;
    }

    $exact = pngm_dup_find_exact_title_any($title, $exclude);
    if (is_array($exact) && !empty($exact['pk_i_id'])) {
        $match_title = isset($exact['s_title']) ? (string) $exact['s_title'] : $title;
        echo json_encode(array(
            'ok' => false,
            'action' => 'block',
            'match_id' => (int) $exact['pk_i_id'],
            'message' => sprintf(
                __('A listing with the same title (“%s”) already exists. Please choose a more specific title or edit the existing listing.', 'epsilon'),
                $match_title
            ),
        ));
        exit;
    }

    // Same-seller near-duplicate (title only — no description/price yet).
    $user_id = 0;
    $email = '';
    if (function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in()) {
        $user_id = (int) osc_logged_user_id();
        if (function_exists('osc_logged_user_email')) {
            $email = strtolower(trim((string) osc_logged_user_email()));
        }
    }
    $ip = function_exists('osc_get_ip') ? (string) osc_get_ip() : '';
    $recent = pngm_dup_find_seller_items($user_id, $email, $ip, $exclude);
    foreach ($recent as $row) {
        $other_title = isset($row['s_title']) ? (string) $row['s_title'] : '';
        $score = pngm_dup_title_score($title, $other_title);
        if ($score >= (int) PNGM_DUP_BLOCK_SCORE) {
            echo json_encode(array(
                'ok' => false,
                'action' => 'block',
                'match_id' => isset($row['pk_i_id']) ? (int) $row['pk_i_id'] : 0,
                'message' => sprintf(
                    __('This listing looks like a duplicate of one you already posted (“%s”). Please edit that listing instead of creating another.', 'epsilon'),
                    $other_title !== '' ? $other_title : $title
                ),
            ));
            exit;
        }
    }

    echo json_encode(array('ok' => true, 'action' => 'ok'));
    exit;
}
osc_add_hook('ajax_pngm_check_duplicate_title', 'pngm_ajax_check_duplicate_title');
