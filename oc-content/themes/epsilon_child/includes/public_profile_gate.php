<?php
/**
 * A-022 — Public profile enumeration hardening.
 *
 * - Public profiles only for sellers (active listing count) or the account owner.
 * - Sequential Previous/Next browsing removed in header.php.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'public_profile_gate.php'
) {
    exit;
}

/**
 * Gate who may be viewed via pub_profile / public profile URLs.
 *
 * @param bool       $enabled
 * @param array|false $user
 * @return bool
 */
function pngm_public_profile_is_enabled_gate($enabled, $user)
{
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return false;
    }

    $uid = (int) $user['pk_i_id'];

    // Owner may always open their own Public Seller Profile (account preview).
    if (function_exists('osc_is_web_user_logged_in')
        && osc_is_web_user_logged_in()
        && (int) osc_logged_user_id() === $uid
    ) {
        return true;
    }

    if ($enabled !== true) {
        return false;
    }

    // Re-read from DB — core used to mutate b_enabled/b_active via assignment typo.
    $fresh = function_exists('osc_get_user_row') ? osc_get_user_row($uid) : $user;
    if (!is_array($fresh) || empty($fresh['pk_i_id'])) {
        return false;
    }

    // Inactive / disabled accounts stay private.
    if (isset($fresh['b_enabled']) && (int) $fresh['b_enabled'] !== 1) {
        return false;
    }
    if (isset($fresh['b_active']) && (int) $fresh['b_active'] !== 1) {
        return false;
    }

    // Only accounts with at least one listing are public sellers (not ID-browseable buyers).
    $items = isset($fresh['i_items']) ? (int) $fresh['i_items'] : 0;
    if ($items < 1) {
        return false;
    }

    return true;
}
osc_add_filter('user_public_profile_is_enabled', 'pngm_public_profile_is_enabled_gate', 8);

/**
 * Never return a public-profile URL for gated (non-seller) users — stops empty/eps fallbacks.
 *
 * @param string     $url
 * @param int        $id
 * @param array|false $user
 * @param array      $params
 * @return string
 */
function pngm_public_profile_url_gate($url, $id, $user = false, $params = array())
{
    $id = (int) $id;
    if ($id <= 0) {
        return '';
    }

    if ($user === false || !is_array($user) || empty($user['pk_i_id'])) {
        $user = function_exists('osc_get_user_row') ? osc_get_user_row($id) : false;
    }

    if (function_exists('osc_user_public_profile_is_enabled')
        && osc_user_public_profile_is_enabled($user) !== true
    ) {
        return '';
    }

    return (string) $url;
}
osc_add_filter('user_public_profile_url', 'pngm_public_profile_url_gate', 8);
