<?php
/**
 * PNG Market — account verification state.
 *
 * Single source of truth for the "Verified" badges. A badge may only appear
 * when the channel was actually confirmed:
 *   - email: Osclass marks b_active after the activation link is opened, so it
 *     only counts when the site actually requires that step.
 *   - phone: needs an explicit confirmation record, stored against the exact
 *     number that was confirmed so editing the number clears the badge.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'verification.php'
) {
    exit;
}

/**
 * Stored verification record for a user.
 *
 * @param int $user_id
 * @return array
 */
function pngm_verify_get($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return array('phone' => '', 'phone_at' => '');
    }

    $raw = osc_get_preference('user_' . $user_id, 'pngm_verification');
    $data = array();
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    return array_merge(array(
        'phone' => '',
        'phone_at' => '',
        'id_at' => '',
    ), $data);
}

/**
 * @param int   $user_id
 * @param array $data
 */
function pngm_verify_save($user_id, $data)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return;
    }

    $key = 'user_' . $user_id;
    $json = json_encode($data);
    osc_set_preference($key, $json, 'pngm_verification', 'STRING');
    // Preference::replace() writes the row but leaves the in-memory cache stale.
    if (class_exists('Preference')) {
        Preference::newInstance()->set($key, $json, 'pngm_verification');
    }
}

/**
 * Record a confirmed phone number for a user.
 *
 * @param int    $user_id
 * @param string $phone
 */
function pngm_verify_mark_phone($user_id, $phone)
{
    $data = pngm_verify_get($user_id);
    $data['phone'] = pngm_verify_phone_key($phone);
    $data['phone_at'] = date('Y-m-d H:i:s');
    pngm_verify_save($user_id, $data);
}

/**
 * Comparable form of a phone number (digits only).
 *
 * @param string $phone
 * @return string
 */
function pngm_verify_phone_key($phone)
{
    return preg_replace('/\D+/', '', (string) $phone);
}

/**
 * Has this user confirmed their email address?
 *
 * @param array $user
 * @return bool
 */
function pngm_email_is_verified($user)
{
    if (!is_array($user) || empty($user['b_active'])) {
        return false;
    }

    // Without the validation step there is nothing to confirm the address.
    return !function_exists('osc_user_validation_enabled') || osc_user_validation_enabled();
}

/**
 * Has this user confirmed the phone number currently on their profile?
 *
 * @param array $user
 * @return bool
 */
function pngm_phone_is_verified($user)
{
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return false;
    }

    $current = pngm_verify_phone_key(@$user['s_phone_mobile']);
    if ($current === '') {
        return false;
    }

    $record = pngm_verify_get($user['pk_i_id']);

    return $record['phone'] !== '' && $record['phone'] === $current;
}

/**
 * Has this user passed identity review?
 *
 * Being flagged as a company is a self-declared setting, not a check, so it
 * never counts on its own.
 *
 * @param array $user
 * @return bool
 */
function pngm_id_is_verified($user)
{
    if (!is_array($user) || empty($user['pk_i_id'])) {
        return false;
    }

    $record = pngm_verify_get($user['pk_i_id']);

    return $record['id_at'] !== '';
}
