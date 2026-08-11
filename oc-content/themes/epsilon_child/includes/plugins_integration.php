<?php
/**
 * Requirement 9 — Prefer Osclass / Epsilon / licensed OsclassPoint plugins.
 *
 * Integrate existing plugins visually; do not rebuild Google/Facebook login,
 * Instant Messenger, WhatsApp Chat, Business Profile, or Attributes.
 * Phase 3 (Business Stores, payments, delivery, AI, apps, rebuilds) is out of scope.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'plugins_integration.php'
) {
    exit;
}

/**
 * Expected licensed plugins for the current phase.
 *
 * @return array
 */
function pngm_expected_plugins()
{
    return array(
        'instant_messenger' => array(
            'label'   => 'Instant Messenger',
            'folders' => array('instant_messenger'),
            'detect'  => array('im_contact_button', 'im_messages'),
            'required'=> true,
            'note'    => 'Theme uses im_contact_button / im_messages (listing contact + inbox).',
        ),
        'attributes' => array(
            'label'   => 'Attributes',
            'folders' => array('attributes'),
            'detect'  => array('atr_show_item', 'atr_search_form'),
            'required'=> true,
            'note'    => 'Vehicle fields via Attributes (category-scoped); not custom fields rebuild.',
        ),
        'business_profile' => array(
            'label'   => 'Business Profile',
            'folders' => array('business_profile'),
            'detect'  => array('bpr_companies_url', 'bpr_call_after_install'),
            'required'=> true,
            'note'    => 'Company pages / home block only. Full Business Stores = Phase 3.',
        ),
        'google_login' => array(
            'label'   => 'Google Login',
            'folders' => array('google_login'),
            'detect'  => array('ggl_login_link', 'gc_login_button'),
            'required'=> true,
            'note'    => 'Install folder google_login, set Client ID/Secret, redirect URIs /ggl/1.',
        ),
        'facebook_instant_login' => array(
            'label'   => 'Facebook Instant Login',
            'folders' => array('facebook_js_login', 'facebook_login'),
            'detect'  => array('fjl_login_button', 'fl_call_after_install', 'facebook_login_link'),
            'required'=> true,
            'note'    => 'Prefer facebook_js_login (Instant). Configure Facebook App ID/Secret in plugin.',
        ),
        'wa_chat' => array(
            'label'   => 'WhatsApp Chat',
            'folders' => array('wa_chat'),
            'detect'  => array('wach_button', 'wa_chat_button', 'wach_web_button', 'wach_call_after_install'),
            'required'=> true,
            'note'    => 'Install folder wa_chat; enable seller + site buttons; PNG country code +675.',
        ),
    );
}

/**
 * @param string $folder
 * @return bool
 */
function pngm_plugin_folder_exists($folder)
{
    $folder = trim((string) $folder);
    if ($folder === '' || !defined('ABS_PATH')) {
        return false;
    }

    $path = ABS_PATH . 'oc-content/plugins/' . $folder;
    return is_dir($path);
}

/**
 * @param array $names
 * @return bool
 */
function pngm_any_function_exists($names)
{
    foreach ((array) $names as $name) {
        if (is_string($name) && $name !== '' && function_exists($name)) {
            return true;
        }
    }
    return false;
}

/**
 * Runtime status for each expected plugin.
 *
 * @return array
 */
function pngm_plugin_integration_status()
{
    static $status = null;
    if ($status !== null) {
        return $status;
    }

    $status = array();

    foreach (pngm_expected_plugins() as $key => $meta) {
        $folder_ok = false;
        foreach ($meta['folders'] as $folder) {
            if (pngm_plugin_folder_exists($folder)) {
                $folder_ok = true;
                break;
            }
        }

        $loaded = pngm_any_function_exists($meta['detect']);
        $status[$key] = array(
            'label'   => $meta['label'],
            'note'    => $meta['note'],
            'required'=> !empty($meta['required']),
            'folder'  => $folder_ok,
            'loaded'  => $loaded,
            'ok'      => ($folder_ok && $loaded),
        );
    }

    return $status;
}

/**
 * True when at least one social-login plugin API is available.
 *
 * @return bool
 */
function pngm_has_social_login()
{
    return pngm_any_function_exists(array(
        'ggl_login_link',
        'gc_login_button',
        'fjl_login_button',
        'fl_call_after_install',
        'facebook_login_link',
    ));
}

/**
 * Google OAuth URL from Google Login plugin (or legacy Google Connect shim).
 *
 * @return string|false
 */
function pngm_google_login_url()
{
    if (function_exists('ggl_login_link')) {
        $url = ggl_login_link();
        if ($url !== false && $url !== null && $url !== '') {
            return $url;
        }
    }

    return false;
}

/**
 * Classic Facebook Login (PHP SDK) URL when Instant Login is not used.
 *
 * @return string|false
 */
function pngm_facebook_login_url()
{
    if (function_exists('fjl_login_button')) {
        // Instant Login uses JS onclick — no redirect URL.
        return false;
    }

    if (function_exists('fl_call_after_install') && function_exists('facebook_login_link')) {
        $url = facebook_login_link();
        if ($url !== false && $url !== null && $url !== '') {
            return $url;
        }
    }

    return false;
}

/**
 * Render social login buttons for login/register pages.
 *
 * @param string $context 'login'|'register'
 */
function pngm_render_social_login($context = 'login')
{
    if (osc_is_web_user_logged_in() || !pngm_has_social_login()) {
        return;
    }

    $is_register = ($context === 'register');
    $google_label = $is_register
        ? __('Continue with Google', 'epsilon')
        : __('Sign in with Google', 'epsilon');
    $fb_label = $is_register
        ? __('Continue with Facebook', 'epsilon')
        : __('Continue with Facebook', 'epsilon');

    echo '<div class="social pngm-social-login">';
    echo '<p class="pngm-social-login-label">' . osc_esc_html(__('Quick sign-in', 'epsilon')) . '</p>';

    $google = pngm_google_login_url();
    if ($google !== false) {
        echo '<a class="google pngm-btn-google" href="' . osc_esc_html($google) . '" title="' . osc_esc_html($google_label) . '">';
        echo '<i class="fab fa-google" aria-hidden="true"></i><span>' . osc_esc_html($google_label) . '</span></a>';
    } elseif (function_exists('gc_login_button')) {
        // Legacy Google Connect button renderer.
        echo '<div class="pngm-btn-google-wrap">';
        gc_login_button();
        echo '</div>';
    }

    if (function_exists('fjl_login_button')) {
        echo '<a target="_top" href="javascript:void(0);" class="facebook fl-button fjl-button pngm-btn-facebook" onclick="if(typeof fjlCheckLoginState===\'function\'){fjlCheckLoginState();}" title="' . osc_esc_html($fb_label) . '">';
        echo '<i class="fab fa-facebook-f" aria-hidden="true"></i><span>' . osc_esc_html($fb_label) . '</span></a>';
    } else {
        $fb = pngm_facebook_login_url();
        if ($fb !== false) {
            echo '<a class="facebook pngm-btn-facebook" href="' . osc_esc_html($fb) . '" title="' . osc_esc_html($fb_label) . '">';
            echo '<i class="fab fa-facebook-f" aria-hidden="true"></i><span>' . osc_esc_html($fb_label) . '</span></a>';
        }
    }

    echo '</div>';
    echo '<div class="pngm-social-divider"><span>' . osc_esc_html(__('or continue with email', 'epsilon')) . '</span></div>';
}

/**
 * Prefer WhatsApp Chat plugin output when available.
 *
 * @return bool true if plugin rendered something
 */
function pngm_try_render_wa_chat_item_button()
{
    $candidates = array('wach_button', 'wa_chat_button', 'wach_item_button');

    foreach ($candidates as $fn) {
        if (!function_exists($fn)) {
            continue;
        }

        ob_start();
        try {
            $result = call_user_func($fn);
        } catch (Throwable $e) {
            ob_end_clean();
            continue;
        }
        $html = trim((string) ob_get_clean());

        if ($html === '' && is_string($result)) {
            $html = trim($result);
        }

        if ($html !== '') {
            echo '<div class="pngm-wa-chat-plugin">' . $html . '</div>';
            return true;
        }
    }

    return false;
}

/**
 * Align Epsilon theme prefs with installed plugins (safe, idempotent).
 */
function pngm_align_theme_plugin_prefs()
{
    static $done = false;
    if ($done || !function_exists('osc_set_preference')) {
        return;
    }
    $done = true;

    // Instant Messenger → replace classic contact with IM when plugin is loaded.
    if (function_exists('im_contact_button') && function_exists('eps_param')) {
        if ((string) eps_param('messenger_replace_button') !== '1') {
            osc_set_preference('messenger_replace_button', '1', 'theme-epsilon');
        }
    }

    // Business Profile → home companies block (not full stores).
    if (function_exists('bpr_companies_block') && function_exists('eps_param')) {
        if ((string) eps_param('company_home') === '') {
            osc_set_preference('company_home', '1', 'theme-epsilon');
        }
        if ((string) eps_param('company_home_count') === '' || (int) eps_param('company_home_count') <= 0) {
            osc_set_preference('company_home_count', '5', 'theme-epsilon');
        }
    }
}

osc_add_hook('init', 'pngm_align_theme_plugin_prefs', 12);

/**
 * Admin dashboard notice: missing licensed plugins / Phase 3 exclusions.
 */
function pngm_admin_plugins_notice()
{
    if (!defined('OC_ADMIN') || OC_ADMIN != 1) {
        return;
    }

    $status = pngm_plugin_integration_status();
    $missing = array();

    foreach ($status as $row) {
        if (!empty($row['required']) && empty($row['ok'])) {
            $missing[] = $row['label'] . (empty($row['folder']) ? ' (not uploaded)' : ' (uploaded but inactive)');
        }
    }

    echo '<div class="flashmessage flashmessage-info pngm-admin-plugins" style="display:block;margin:12px 0;">';
    echo '<p><strong>PNG Market — plugins (current phase)</strong></p>';

    if (count($missing) > 0) {
        echo '<p>Upload &amp; enable these licensed OsclassPoint plugins, then configure API keys in each plugin settings page:</p><ul>';
        foreach ($missing as $label) {
            echo '<li>' . osc_esc_html($label) . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Folders:</strong> <code>google_login</code>, <code>facebook_js_login</code>, <code>wa_chat</code> under <code>oc-content/plugins/</code>.</p>';
        echo '<p>Theme login/register already show Google &amp; Facebook buttons when those plugins are active. Style WhatsApp Chat float via child CSS — do not rebuild OAuth or chat.</p>';
    } else {
        echo '<p>All expected phase plugins are present and loaded. Configure OAuth / WhatsApp options inside each plugin — theme UI is ready.</p>';
    }

    echo '<p><em>Out of scope (Phase 3+):</em> Business Stores packages, store subscriptions, paid promotions, online payments, delivery, AI, mobile apps, Next.js/Laravel rebuild.</p>';
    echo '</div>';
}

osc_add_hook('admin_page_header', 'pngm_admin_plugins_notice', 9);
