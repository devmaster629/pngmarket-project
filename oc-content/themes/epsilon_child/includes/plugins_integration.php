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
            'detect'  => array('wac_item_chat_button', 'wac_web_contact_button', 'wac_call_after_install'),
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
 * True when Facebook Instant Login plugin is enabled with an App ID.
 * SDK init only needs App ID; App Secret is required for server-side login.
 *
 * @return bool
 */
function pngm_facebook_login_available()
{
    if (!function_exists('fjl_param')) {
        return false;
    }

    return (int) fjl_param('enabled') === 1
        && trim((string) fjl_param('app_id')) !== '';
}

/**
 * True when Facebook login can complete (App ID + Secret configured).
 *
 * @return bool
 */
function pngm_facebook_login_ready()
{
    if (!pngm_facebook_login_available()) {
        return false;
    }

    return trim((string) fjl_param('app_secret')) !== '';
}

/**
 * True when at least one social-login plugin API is available.
 *
 * @return bool
 */
function pngm_has_social_login()
{
    $google = function_exists('ggl_login_link') || function_exists('gc_login_button');
    $facebook = pngm_facebook_login_available()
        || (function_exists('fl_call_after_install') && function_exists('facebook_login_link') && facebook_login_link() !== false && facebook_login_link() !== '#');

    return $google || $facebook;
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

    $google_label = __('Continue with Google', 'epsilon');
    $fb_label = __('Continue with Facebook', 'epsilon');
    $google_icon = '<svg class="pngm-soc-svg pngm-soc-google" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
    $fb_icon = '<svg class="pngm-soc-svg pngm-soc-facebook" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="#fff"/><path fill="#1877F2" d="M13.28 20v-6.18h2.07l.31-2.4h-2.38V9.9c0-.7.19-1.17 1.2-1.17h1.28V6.58c-.22-.03-.98-.1-1.86-.1-1.84 0-3.1 1.12-3.1 3.18v1.76H8.72v2.4h2.08V20h2.48z"/></svg>';
    $chevron = '<i class="fas fa-chevron-right pngm-soc-chevron" aria-hidden="true"></i>';

    echo '<div class="social pngm-social-login">';

    $google = pngm_google_login_url();
    if ($google !== false) {
        echo '<a class="google pngm-btn-google" href="' . osc_esc_html($google) . '" title="' . osc_esc_html($google_label) . '">';
        echo $google_icon . '<span class="pngm-soc-label">' . osc_esc_html($google_label) . '</span>' . $chevron . '</a>';
    } elseif (function_exists('gc_login_button')) {
        echo '<div class="pngm-btn-google-wrap">';
        gc_login_button();
        echo '</div>';
    }

    $fb_printed = false;
    if (pngm_facebook_login_available()) {
        echo '<a target="_top" href="#" role="button" class="facebook pngm-btn-facebook" title="' . osc_esc_html($fb_label) . '">';
        echo $fb_icon . '<span class="pngm-soc-label">' . osc_esc_html($fb_label) . '</span>' . $chevron . '</a>';
        $fb_printed = true;
    }

    if (!$fb_printed) {
        $fb = pngm_facebook_login_url();
        if ($fb !== false) {
            echo '<a class="facebook pngm-btn-facebook" href="' . osc_esc_html($fb) . '" title="' . osc_esc_html($fb_label) . '">';
            echo $fb_icon . '<span class="pngm-soc-label">' . osc_esc_html($fb_label) . '</span>' . $chevron . '</a>';
            $fb_printed = true;
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
    $candidates = array('wac_item_chat_button', 'wac_web_contact_button');

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

    // WhatsApp Chat → PNG defaults + fix broken listing hooks.
    if (function_exists('wac_param') && function_exists('osc_set_preference')) {
        $cc = trim((string) wac_param('default_country_code'));
        if ($cc === '') {
            osc_set_preference('default_country_code', '675', 'plugin-wa_chat');
        }

        $hooks = trim((string) wac_param('hooks'));
        $parts = array_filter(array_map('trim', explode(',', $hooks)));
        $known = array('item_detail', 'item_sidebar_user', 'item_sidebar_top', 'item_contact', 'item_sidebar');
        $ok = false;
        foreach ($parts as $hook) {
            if (in_array($hook, $known, true)) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            osc_set_preference('hooks', 'item_detail', 'plugin-wa_chat');
        }
    }

    // Facebook Instant Login — optional .env credentials + theme button hook.
    if (function_exists('fjl_param') && function_exists('osc_set_preference')) {
        $fb_app_id = trim((string) (getenv('FB_APP_ID') ?: (isset($_ENV['FB_APP_ID']) ? $_ENV['FB_APP_ID'] : '')));
        $fb_app_secret = trim((string) (getenv('FB_APP_SECRET') ?: (isset($_ENV['FB_APP_SECRET']) ? $_ENV['FB_APP_SECRET'] : '')));

        if ($fb_app_id !== '' && trim((string) fjl_param('app_id')) === '') {
            osc_set_preference('app_id', $fb_app_id, 'plugin-facebook_js_login');
        }

        if ($fb_app_secret !== '' && trim((string) fjl_param('app_secret')) === '') {
            osc_set_preference('app_secret', $fb_app_secret, 'plugin-facebook_js_login');
        }

        if ($fb_app_id !== '' && $fb_app_secret !== '' && (int) fjl_param('enabled') !== 1) {
            osc_set_preference('enabled', '1', 'plugin-facebook_js_login');
        }

        $selector = trim((string) fjl_param('custom_selector'));
        $wanted = '.pngm-btn-facebook, .social a.facebook';
        if ($selector === '') {
            osc_set_preference('custom_selector', $wanted, 'plugin-facebook_js_login');
        } elseif (strpos($selector, '.pngm-btn-facebook') === false) {
            osc_set_preference('custom_selector', $selector . ', .pngm-btn-facebook', 'plugin-facebook_js_login');
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
        echo '<p>Facebook login needs the Instant Login plugin enabled with App ID + App Secret (Oc-Admin → Plugins → Facebook Instant Login), or set <code>FB_APP_ID</code> / <code>FB_APP_SECRET</code> in <code>.env</code>. The login button stays hidden until configured.</p>';
        echo '<p>Theme login/register already show Google &amp; Facebook buttons when those plugins are active. Style WhatsApp Chat float via child CSS — do not rebuild OAuth or chat.</p>';
    } else {
        echo '<p>All expected phase plugins are present and loaded. Configure OAuth / WhatsApp options inside each plugin — theme UI is ready.</p>';
    }

    echo '<p><em>Out of scope (Phase 3+):</em> Business Stores packages, store subscriptions, paid promotions, online payments, delivery, AI, mobile apps, Next.js/Laravel rebuild.</p>';
    echo '</div>';
}

osc_add_hook('admin_page_header', 'pngm_admin_plugins_notice', 9);
