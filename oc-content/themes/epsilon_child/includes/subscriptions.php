<?php
/**
 * PNG Market — Subscriptions (plans + billing UI helpers).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'subscriptions.php'
) {
    exit;
}

/**
 * @return string
 */
function pngm_sub_url()
{
    return osc_route_url('pngm-subscriptions');
}

/**
 * Catalog of listing plans (mockup Free / Basic / Premium).
 *
 * @return array
 */
function pngm_sub_plans()
{
    return array(
        'free' => array(
            'id' => 'free',
            'name' => __('Free', 'epsilon'),
            'label' => __('Free Plan', 'epsilon'),
            'price' => 0,
            'price_label' => 'K0',
            'period' => __('month', 'epsilon'),
            'tagline' => __('For getting started', 'epsilon'),
            'blurb' => __('Perfect for getting started. Upgrade anytime for more visibility and features.', 'epsilon'),
            'listings' => 5,
            'photos' => 5,
            'visibility' => __('Standard', 'epsilon'),
            'features' => array(
                __('Up to 5 active listings', 'epsilon'),
                __('5 photos per listing', 'epsilon'),
                __('Standard visibility', 'epsilon'),
                __('Basic profile', 'epsilon'),
                __('Community support', 'epsilon'),
            ),
        ),
        'basic' => array(
            'id' => 'basic',
            'name' => __('Basic', 'epsilon'),
            'label' => __('Basic Plan', 'epsilon'),
            'price' => 29,
            'price_label' => 'K29',
            'period' => __('month', 'epsilon'),
            'tagline' => __('More listings and reach', 'epsilon'),
            'blurb' => __('Grow your presence with more listings, photos, and priority placement in search.', 'epsilon'),
            'listings' => 20,
            'photos' => 10,
            'visibility' => __('Priority', 'epsilon'),
            'features' => array(
                __('Up to 20 active listings', 'epsilon'),
                __('10 photos per listing', 'epsilon'),
                __('Priority visibility', 'epsilon'),
                __('Featured in search', 'epsilon'),
                __('Seller insights', 'epsilon'),
                __('Email support', 'epsilon'),
            ),
        ),
        'premium' => array(
            'id' => 'premium',
            'name' => __('Premium', 'epsilon'),
            'label' => __('Premium Plan', 'epsilon'),
            'price' => 79,
            'price_label' => 'K79',
            'period' => __('month', 'epsilon'),
            'tagline' => __('Maximum visibility and tools', 'epsilon'),
            'blurb' => __('Unlimited listings, top visibility, and priority support for serious sellers.', 'epsilon'),
            'listings' => 0, // unlimited
            'photos' => 20,
            'visibility' => __('Top', 'epsilon'),
            'features' => array(
                __('Unlimited active listings', 'epsilon'),
                __('20 photos per listing', 'epsilon'),
                __('Top visibility', 'epsilon'),
                __('Featured badge', 'epsilon'),
                __('Advanced insights', 'epsilon'),
                __('Priority support', 'epsilon'),
            ),
        ),
    );
}

/**
 * @param int $user_id
 * @return array
 */
function pngm_sub_get($user_id)
{
    $user_id = (int) $user_id;
    $raw = osc_get_preference('user_' . $user_id, 'pngm_subscriptions');
    $data = array();
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    return array_merge(array(
        'plan' => 'free',
        'billing' => array(),
    ), $data);
}

/**
 * @param int   $user_id
 * @param array $data
 */
function pngm_sub_save($user_id, $data)
{
    $user_id = (int) $user_id;
    $json = json_encode($data);
    $key = 'user_' . $user_id;
    osc_set_preference($key, $json, 'pngm_subscriptions', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->set($key, $json, 'pngm_subscriptions');
    }
}

/**
 * @param int $user_id
 * @return array Plan row
 */
function pngm_sub_current_plan($user_id)
{
    $data = pngm_sub_get($user_id);
    $plans = pngm_sub_plans();
    $id = isset($data['plan']) ? (string) $data['plan'] : 'free';
    if (!isset($plans[$id])) {
        $id = 'free';
    }
    return $plans[$id];
}

/**
 * @param int $user_id
 * @return array
 */
function pngm_sub_billing($user_id)
{
    $data = pngm_sub_get($user_id);
    return isset($data['billing']) && is_array($data['billing']) ? $data['billing'] : array();
}

/**
 * Switch plan and append a billing row (UI layer until payment gateway is wired).
 *
 * @param int    $user_id
 * @param string $plan_id
 * @return bool
 */
function pngm_sub_set_plan($user_id, $plan_id)
{
    $plans = pngm_sub_plans();
    if (!isset($plans[$plan_id])) {
        return false;
    }
    $data = pngm_sub_get($user_id);
    $prev = isset($data['plan']) ? (string) $data['plan'] : 'free';
    if ($prev === $plan_id) {
        return true;
    }

    $plan = $plans[$plan_id];
    $data['plan'] = $plan_id;

    if ((int) $plan['price'] > 0) {
        $billing = isset($data['billing']) && is_array($data['billing']) ? $data['billing'] : array();
        array_unshift($billing, array(
            'id' => substr(hash('sha256', $user_id . '|' . $plan_id . '|' . microtime(true)), 0, 12),
            'date' => date('Y-m-d'),
            'description' => sprintf(__('%s subscription', 'epsilon'), $plan['label']),
            'amount' => $plan['price_label'],
            'status' => __('Paid', 'epsilon'),
            'receipt' => '',
        ));
        $data['billing'] = array_slice($billing, 0, 24);
    }

    pngm_sub_save($user_id, $data);
    return true;
}

/**
 * Active listing count for the user.
 *
 * @param int $user_id
 * @return int
 */
function pngm_sub_active_listings_count($user_id)
{
    if (function_exists('eps_count_user_items')) {
        return (int) eps_count_user_items((int) $user_id, 'active');
    }
    return 0;
}

/**
 * Register front route (login required → user-custom shell).
 */
function pngm_sub_register_route()
{
    if (!function_exists('osc_add_route')) {
        return;
    }
    osc_add_route(
        'pngm-subscriptions',
        'user/subscriptions/?',
        'user/subscriptions',
        'custom/subscriptions.php',
        true,
        'custom',
        'pngm-sub',
        __('Subscriptions', 'epsilon')
    );
}
osc_add_hook('init', 'pngm_sub_register_route', 5);
