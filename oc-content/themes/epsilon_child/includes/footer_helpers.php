<?php
/**
 * FOOTER-01 — PNGMarket footer helpers (contact, pages, socials).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'footer_helpers.php'
) {
    exit;
}

/**
 * Canonical PNGMarket contact / branding defaults (used only as fallbacks).
 *
 * @return array
 */
function pngm_footer_defaults()
{
    return array(
        'site_name'    => 'PNGMarket',
        'site_email'   => 'info@pngmarket.online',
        'site_phone'   => '+675 7864 1186',
        'site_address' => 'Port Moresby, Papua New Guinea',
        'site_tagline' => 'Buy and sell across Papua New Guinea.',
    );
}

/**
 * Resolved footer contact fields (theme settings first, then defaults).
 *
 * @return array
 */
function pngm_footer_contact()
{
    $defaults = pngm_footer_defaults();
    $name = function_exists('eps_param') ? trim((string) eps_param('site_name')) : '';
    $email = function_exists('eps_param') ? trim((string) eps_param('site_email')) : '';
    $phone = function_exists('eps_param') ? trim((string) eps_param('site_phone')) : '';
    $address = function_exists('eps_param') ? trim((string) eps_param('site_address')) : '';

    // Prefer WhatsApp number as phone when site_phone is empty.
    if ($phone === '' && function_exists('eps_param') && (int) eps_param('footer_social_define') === 1) {
        $wa = trim((string) eps_param('footer_social_whatsapp'));
        if (preg_match('#wa\.me/(\d+)#', $wa, $m)) {
            $digits = $m[1];
            if (strpos($digits, '675') === 0 && strlen($digits) >= 10) {
                $rest = substr($digits, 3);
                $phone = '+675 ' . substr($rest, 0, 4) . ' ' . substr($rest, 4);
            } else {
                $phone = '+' . $digits;
            }
        }
    }

    if ($name === '' || preg_match('/website name|your site|osclass|example/i', $name)) {
        $name = $defaults['site_name'];
    }

    if ($email === '' || preg_match('/example\.|test@|placeholder/i', $email)) {
        $email = $defaults['site_email'];
    }

    if ($phone === '') {
        $phone = $defaults['site_phone'];
    }

    if ($address === '' || preg_match('/lorem|placeholder|example address/i', $address)) {
        $address = $defaults['site_address'];
    }

    $tel = preg_replace('/[^\d+]/', '', $phone);

    return array(
        'name'    => $name,
        'email'   => $email,
        'phone'   => $phone,
        'tel'     => $tel,
        'address' => $address,
        'tagline' => $defaults['site_tagline'],
    );
}

/**
 * Site WhatsApp chat URL (owner number — used on Contact us, not as a global FAB).
 *
 * @return string Empty if unknown.
 */
function pngm_site_whatsapp_url()
{
    $digits = '';

    if (function_exists('eps_param')) {
        $wa = trim((string) eps_param('footer_social_whatsapp'));
        if (preg_match('#wa\.me/(\d+)#', $wa, $m)) {
            $digits = $m[1];
        } elseif (preg_match('#whatsapp\.com/send\?phone=(\d+)#', $wa, $m)) {
            $digits = $m[1];
        }
    }

    if ($digits === '' && function_exists('wac_param')) {
        $raw = trim((string) wac_param('web_phone'));
        if ($raw !== '' && strtoupper($raw) !== 'OSCLASS' && function_exists('wac_sanitize_number')) {
            $digits = wac_sanitize_number($raw);
        }
    }

    if ($digits === '') {
        $contact = pngm_footer_contact();
        $digits = preg_replace('/\D/', '', isset($contact['tel']) ? $contact['tel'] : '');
    }

    if ($digits === '') {
        return '';
    }

    $text = '';
    if (function_exists('osc_page_title')) {
        $text = '?text=' . rawurlencode(sprintf(__('Hello! I have a question about %s', 'epsilon'), osc_page_title()));
    }

    return 'https://wa.me/' . $digits . $text;
}

/**
 * Active social profile links only (no empty / share-only placeholders).
 *
 * @return array type => url
 */
function pngm_footer_social_links()
{
    $out = array();

    $defaults = array(
        'facebook'  => 'https://www.facebook.com/pngmarket',
        'instagram' => 'https://www.instagram.com/pngmarket',
        'tiktok'    => 'https://www.tiktok.com/@pngmarket',
    );

    $map = array(
        'facebook'  => 'footer_social_facebook',
        'instagram' => 'footer_social_instagram',
        'tiktok'    => 'footer_social_tiktok',
    );

    foreach ($map as $type => $key) {
        $url = '';

        if (function_exists('eps_param')) {
            $url = trim((string) eps_param($key));
        }

        if ($url === '' && function_exists('osc_get_preference')) {
            $url = trim((string) osc_get_preference($key, 'pngmarket'));
        }

        if ($url === '' || preg_match('#(sharer\.php|shareArticle|twitter\.com/home\?status|pinterest\.com/pin/create)#i', $url)) {
            $url = $defaults[$type];
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $out[$type] = $url;
    }

    return $out;
}

/**
 * Information pages for footer: About, Terms, Privacy (+ any other linked pages).
 *
 * @return array list of [title, url, key]
 */
function pngm_footer_info_pages()
{
    if (function_exists('pngm_ensure_footer_pages')) {
        pngm_ensure_footer_pages();
    }

    $wanted = array(
        'about'   => __('About', 'epsilon'),
        'terms'   => __('Terms', 'epsilon'),
        'privacy' => __('Privacy', 'epsilon'),
    );

    $pages = array();
    $seen = array();

    foreach ($wanted as $slug => $label) {
        $page = null;

        if (class_exists('Page')) {
            $page = Page::newInstance()->findByInternalName($slug);
        }

        if (is_array($page) && !empty($page['pk_i_id'])) {
            $title = !empty($page['s_title']) ? $page['s_title'] : $label;
            $pages[] = array(
                'key'   => $slug,
                'title' => $label,
                'url'   => osc_static_page_url_from_page($page),
            );
            $seen[(int) $page['pk_i_id']] = true;
        }
    }

    // Append other admin-linked static pages (b_link=1, not indelible).
    if (function_exists('osc_get_pages_all')) {
        $extra = osc_get_pages_all(true, true, false, false);

        if (is_array($extra)) {
            foreach ($extra as $page) {
                $id = isset($page['pk_i_id']) ? (int) $page['pk_i_id'] : 0;
                $internal = isset($page['s_internal_name']) ? $page['s_internal_name'] : '';

                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }

                if (in_array($internal, array('about', 'terms', 'privacy', 'example_page'), true)) {
                    continue;
                }

                if (preg_match('/example/i', isset($page['s_title']) ? $page['s_title'] : '')) {
                    continue;
                }

                $pages[] = array(
                    'key'   => $internal,
                    'title' => $page['s_title'],
                    'url'   => osc_static_page_url_from_page($page),
                );
                $seen[$id] = true;
            }
        }
    }

    return $pages;
}

/**
 * Page bodies for required information pages.
 *
 * @return array
 */
function pngm_footer_page_seed()
{
    $contact = pngm_footer_contact();
    $brand = $contact['name'];

    return array(
        'about' => array(
            'title' => 'About ' . $brand,
            'text'  => '<p><strong>' . $brand . '</strong> is an online marketplace for buying and selling across Papua New Guinea.</p>'
                . '<p>Find cars, phones, electronics, furniture, jobs, services and more — or place your own ad to reach buyers nationwide.</p>'
                . '<p>Based in ' . osc_esc_html($contact['address']) . '. For support, email '
                . '<a href="mailto:' . osc_esc_html($contact['email']) . '">' . osc_esc_html($contact['email']) . '</a>'
                . ' or use our Contact Us form.</p>',
        ),
        'terms' => array(
            'title' => 'Terms of Use',
            'text'  => '<p>By using ' . $brand . ' you agree to post accurate listings, communicate respectfully, and comply with the laws of Papua New Guinea.</p>'
                . '<ul>'
                . '<li>You are responsible for the content of your listings and messages.</li>'
                . '<li>Do not post illegal, fraudulent, offensive, or misleading content.</li>'
                . '<li>We may remove listings or suspend accounts that break these terms.</li>'
                . '<li>Deals are between buyers and sellers; ' . $brand . ' is a platform, not a party to the transaction.</li>'
                . '</ul>'
                . '<p>Questions? Contact us at <a href="mailto:' . osc_esc_html($contact['email']) . '">' . osc_esc_html($contact['email']) . '</a>.</p>',
        ),
        'privacy' => array(
            'title' => 'Privacy Policy',
            'text'  => '<p>' . $brand . ' collects account and listing information you provide so we can operate the marketplace.</p>'
                . '<ul>'
                . '<li>We use your contact details to manage your account and enable buyer–seller communication.</li>'
                . '<li>We do not sell your personal information.</li>'
                . '<li>Listings you publish are visible to visitors of the site.</li>'
                . '<li>You can update or remove personal data from your account settings, or contact us for help.</li>'
                . '</ul>'
                . '<p>Contact: <a href="mailto:' . osc_esc_html($contact['email']) . '">' . osc_esc_html($contact['email']) . '</a>.</p>',
        ),
    );
}

/**
 * Ensure About / Terms / Privacy pages exist and are footer-linked.
 */
function pngm_ensure_footer_pages()
{
    if (!class_exists('Page') || !defined('DB_TABLE_PREFIX')) {
        return;
    }

    // Run at most once per request lifecycle flag in preference.
    $done = osc_get_preference('pngm_footer_pages_seeded', 'pngmarket');
    if ($done === '1') {
        // Still verify pages exist (in case DB was reset).
        $about = Page::newInstance()->findByInternalName('about');
        if (is_array($about) && !empty($about['pk_i_id'])) {
            return;
        }
    }

    $locale = 'en_US';
    if (function_exists('osc_current_user_locale') && osc_current_user_locale() !== '') {
        $locale = osc_current_user_locale();
    }

    $seed = pngm_footer_page_seed();
    $manager = Page::newInstance();

    foreach ($seed as $slug => $content) {
        $existing = $manager->findByInternalName($slug);

        if (is_array($existing) && !empty($existing['pk_i_id'])) {
            $manager->update(
                array('b_link' => 1, 'b_index' => 1, 'i_visibility' => 0),
                array('pk_i_id' => (int) $existing['pk_i_id'])
            );
            continue;
        }

        $manager->insert(
            array(
                's_internal_name' => $slug,
                'b_indelible'     => 0,
                'b_link'          => 1,
                'b_index'         => 1,
                'i_visibility'    => 0,
            ),
            array(
                $locale => array(
                    's_title' => $content['title'],
                    's_text'  => $content['text'],
                ),
            )
        );
    }

    // Hide / unlink the Osclass example page from the footer.
    $example = $manager->findByInternalName('example_page');
    if (is_array($example) && !empty($example['pk_i_id'])) {
        $manager->update(
            array('b_link' => 0),
            array('pk_i_id' => (int) $example['pk_i_id'])
        );
    }

    // Sync empty site phone into theme prefs when possible.
    if (function_exists('eps_param') && trim((string) eps_param('site_phone')) === '') {
        $contact = pngm_footer_contact();
        if ($contact['phone'] !== '') {
            osc_set_preference('site_phone', $contact['phone'], 'theme-epsilon');
        }
    }

    if (trim((string) eps_param('site_name')) === '') {
        osc_set_preference('site_name', 'PNGMarket', 'theme-epsilon');
    }

    if (trim((string) eps_param('site_email')) === '') {
        osc_set_preference('site_email', 'info@pngmarket.online', 'theme-epsilon');
    }

    osc_set_preference('pngm_footer_pages_seeded', '1', 'pngmarket');
    osc_set_preference('footer_link', '0', 'theme-epsilon');
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init', 'pngm_ensure_footer_pages', 20);
}
