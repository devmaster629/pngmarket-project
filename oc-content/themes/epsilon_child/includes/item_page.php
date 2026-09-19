<?php
/**
 * ITEM-01..04 — Listing gallery helpers, seller contacts, similar listings.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'item_page.php'
) {
    exit;
}

/**
 * Whether the current viewer may see seller contact channels (phone, WhatsApp, chat).
 * Guests must log in first — no contact digits or messaging for logged-out users.
 *
 * @return bool
 */
function pngm_viewer_can_contact_seller()
{
    return function_exists('osc_is_web_user_logged_in') && osc_is_web_user_logged_in();
}

/**
 * Digits-only phone for WhatsApp (PNG defaults to country code 675).
 *
 * @param string $phone
 * @return string empty if unusable
 */
function pngm_whatsapp_digits($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);

    if ($digits === '' || strlen($digits) < 7) {
        return '';
    }

    // Already international.
    if (strpos($digits, '675') === 0 && strlen($digits) >= 10) {
        return $digits;
    }

    // Local PNG mobile often starts with 7.
    if ($digits[0] === '0') {
        $digits = substr($digits, 1);
    }

    if (strpos($digits, '675') !== 0) {
        $digits = '675' . $digits;
    }

    return $digits;
}

/**
 * Preference key for listing WhatsApp opt-in (QD-004).
 *
 * @param int $item_id
 * @return string
 */
function pngm_item_whatsapp_pref_key($item_id)
{
    return 'wa_' . (int) $item_id;
}

/**
 * Whether the seller opted in to show WhatsApp on this listing.
 * Default is off — no public wa.me link without consent.
 *
 * @param int $item_id
 * @return bool
 */
function pngm_item_whatsapp_enabled($item_id = 0)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 && function_exists('osc_item_id')) {
        $item_id = (int) osc_item_id();
    }
    if ($item_id <= 0) {
        return false;
    }

    // Theme preference (source of truth for epsilon_child UI).
    if (function_exists('osc_get_preference')) {
        $pref = osc_get_preference(pngm_item_whatsapp_pref_key($item_id), 'pngm_whatsapp');
        if ($pref !== '' && $pref !== null && $pref !== false) {
            return ((string) $pref === '1' || (int) $pref === 1);
        }
    }

    // Fallback: wa_chat plugin row when present.
    if (class_exists('ModelWAC')) {
        try {
            $data = ModelWAC::newInstance()->getData($item_id);
            if (is_array($data) && isset($data['b_enable'])) {
                return ((int) $data['b_enable'] === 1);
            }
        } catch (Throwable $e) {
            // Plugin table may be missing.
        }
    }

    return false;
}

/**
 * Persist WhatsApp opt-in from the post/edit form checkbox.
 *
 * @param array $item
 */
function pngm_item_whatsapp_save($item)
{
    $item_id = 0;
    if (is_array($item) && isset($item['pk_i_id'])) {
        $item_id = (int) $item['pk_i_id'];
    }
    if ($item_id <= 0) {
        return;
    }

    $enabled = 0;
    if (isset($_POST['pngm_whatsapp']) && (string) $_POST['pngm_whatsapp'] !== '') {
        $enabled = 1;
    } elseif (class_exists('Params') && Params::getParam('pngm_whatsapp') !== '') {
        $enabled = 1;
    }

    if (function_exists('osc_set_preference')) {
        osc_set_preference(pngm_item_whatsapp_pref_key($item_id), (string) $enabled, 'pngm_whatsapp', 'BOOLEAN');
        if (class_exists('Preference')) {
            Preference::newInstance()->set(pngm_item_whatsapp_pref_key($item_id), (string) $enabled, 'pngm_whatsapp');
        }
    }

    // Keep wa_chat plugin row in sync when available.
    if (class_exists('ModelWAC')) {
        try {
            $model = ModelWAC::newInstance();
            $data = $model->getData($item_id);
            if (is_array($data) && isset($data['fk_i_item_id'])) {
                $model->updateData($item_id, array('b_enable' => $enabled));
            } else {
                $model->insertData(array(
                    'fk_i_item_id' => $item_id,
                    'b_enable' => $enabled,
                ));
            }
        } catch (Throwable $e) {
            // Ignore plugin sync failures.
        }
    }
}

if (function_exists('osc_add_hook')) {
    // Run after wa_chat's posted_item/edited_item (default priority 5) so ModelWAC stays in sync
    // with the contact-section checkbox, not a hidden plugin field.
    osc_add_hook('posted_item', 'pngm_item_whatsapp_save', 9);
    osc_add_hook('edited_item', 'pngm_item_whatsapp_save', 9);
    osc_add_hook('posted_item', 'pngm_item_contact_pref_save', 9);
    osc_add_hook('edited_item', 'pngm_item_contact_pref_save', 9);
}

/**
 * Preference key for preferred contact method.
 *
 * @param int $item_id
 * @return string
 */
function pngm_item_contact_pref_key($item_id)
{
    return 'pref_' . (int) $item_id;
}

/**
 * Allowed preferred contact values.
 *
 * @return array
 */
function pngm_item_contact_pref_allowed()
{
    return array('call', 'whatsapp', 'message');
}

/**
 * Persist preferred contact method from the post/edit pills.
 *
 * @param array $item
 */
function pngm_item_contact_pref_save($item)
{
    $item_id = 0;
    if (is_array($item) && isset($item['pk_i_id'])) {
        $item_id = (int) $item['pk_i_id'];
    }
    if ($item_id <= 0) {
        return;
    }

    $pref = '';
    if (isset($_POST['pngm_contact_pref'])) {
        $pref = strtolower(trim((string) $_POST['pngm_contact_pref']));
    } elseif (class_exists('Params')) {
        $pref = strtolower(trim((string) Params::getParam('pngm_contact_pref')));
    }

    if (!in_array($pref, pngm_item_contact_pref_allowed(), true)) {
        $pref = 'message';
    }

    // WhatsApp as preferred only makes sense with WhatsApp opt-in.
    if ($pref === 'whatsapp' && !pngm_item_whatsapp_enabled($item_id)) {
        $pref = 'message';
    }

    if (function_exists('osc_set_preference')) {
        osc_set_preference(pngm_item_contact_pref_key($item_id), $pref, 'pngm_contact', 'STRING');
        if (class_exists('Preference')) {
            Preference::newInstance()->set(pngm_item_contact_pref_key($item_id), $pref, 'pngm_contact');
        }
    }
}

/**
 * Seller preferred contact method for a listing.
 *
 * @param int $item_id
 * @return string call|whatsapp|message
 */
function pngm_item_contact_pref($item_id = 0)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 && function_exists('osc_item_id')) {
        $item_id = (int) osc_item_id();
    }
    if ($item_id <= 0) {
        return 'message';
    }

    $pref = '';
    if (function_exists('osc_get_preference')) {
        $pref = strtolower(trim((string) osc_get_preference(pngm_item_contact_pref_key($item_id), 'pngm_contact')));
    }

    if (!in_array($pref, pngm_item_contact_pref_allowed(), true)) {
        $pref = 'message';
    }

    if ($pref === 'whatsapp' && !pngm_item_whatsapp_enabled($item_id)) {
        return 'message';
    }

    return $pref;
}

/**
 * Buyer-facing label for preferred contact method.
 *
 * @param string $pref
 * @return string
 */
function pngm_item_contact_pref_label($pref)
{
    $map = array(
        'call' => __('Call', 'epsilon'),
        'whatsapp' => __('WhatsApp', 'epsilon'),
        'message' => __('Message', 'epsilon'),
    );
    $pref = (string) $pref;
    return isset($map[$pref]) ? $map[$pref] : $map['message'];
}

/**
 * Collect seller contact channels available for the current item.
 *
 * @return array
 */
function pngm_seller_contact_channels()
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $channels = array(
        'message'   => null,
        'whatsapp'  => null,
        'facebook'  => null,
        'messenger' => null,
        'profile'   => null,
        'listings'  => null,
    );

    if (!function_exists('osc_item_id') || (int) osc_item_id() <= 0) {
        $cache = $channels;
        return $cache;
    }

    $item_id = (int) osc_item_id();
    $user_id = (int) osc_item_user_id();
    $item_count = 0;

    if ($user_id > 0 && class_exists('User')) {
        $user = User::newInstance()->findByPrimaryKey($user_id);
        if (is_array($user) && isset($user['i_items'])) {
            $item_count = (int) $user['i_items'];
        }
    }

    // Instant Messenger owns chat. Do not duplicate the standard Message button.

    // WhatsApp only when seller opted in (QD-004) and viewer is logged in.
    if (pngm_viewer_can_contact_seller() && pngm_item_whatsapp_enabled($item_id)) {
        $phones = array();

        if (function_exists('eps_get_item_phone')) {
            $p = eps_get_item_phone();
            if (!empty($p['found']) && empty($p['login_required']) && !empty($p['phone'])) {
                $phones[] = $p['phone'];
            }
        }

        // Listing contact phone may still exist when show_phone is off for Call UI.
        if (function_exists('osc_item_contact_phone') && osc_item_contact_phone() !== '') {
            $phones[] = osc_item_contact_phone();
        }
        if (empty($phones) && function_exists('eps_item_extra')) {
            $extra = eps_item_extra($item_id);
            if (is_array($extra) && !empty($extra['s_phone'])) {
                $phones[] = $extra['s_phone'];
            }
        }

        foreach ($phones as $phone) {
            $digits = pngm_whatsapp_digits($phone);
            if ($digits !== '') {
                $text = rawurlencode(sprintf(
                    __('Hi, I am interested in your listing: %s', 'epsilon'),
                    osc_item_url()
                ));
                $channels['whatsapp'] = array(
                    'url'   => 'https://wa.me/' . $digits . '?text=' . $text,
                    'label' => __('WhatsApp', 'epsilon'),
                    'class' => 'pngm-contact-whatsapp',
                );
                break;
            }
        }
    }

    // Facebook / Messenger from website, user info, or Business Profile socials.
    $candidates = array();

    if ($user_id > 0 && function_exists('osc_user_website') && osc_user_website() !== '') {
        $candidates[] = osc_user_website();
    }

    if ($user_id > 0 && function_exists('osc_user_info') && osc_user_info() !== '') {
        if (preg_match_all('#https?://[^\s<>"\']+#i', osc_user_info(), $m)) {
            foreach ($m[0] as $u) {
                $candidates[] = $u;
            }
        }
    }

    if ($user_id > 0 && class_exists('ModelBPR')) {
        try {
            $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);
            if (is_array($seller) && !empty($seller['s_socials'])) {
                $rows = array_filter(explode('[y]', $seller['s_socials']));
                foreach ($rows as $row) {
                    $parts = explode('[x]', $row);
                    if (count($parts) >= 2 && trim($parts[1]) !== '') {
                        $type = trim($parts[0]);
                        $url = trim($parts[1]);
                        if ($type === 'fb' || stripos($url, 'facebook.com') !== false || stripos($url, 'fb.com') !== false || stripos($url, 'm.me/') !== false) {
                            $candidates[] = $url;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Plugin table may be missing.
        }
    }

    foreach ($candidates as $url) {
        $url = trim($url);
        if ($url === '') {
            continue;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $is_fb = (bool) preg_match('#(facebook\.com|fb\.com|fb\.me|m\.me)/#i', $url);
        if (!$is_fb) {
            continue;
        }

        if ($channels['facebook'] === null) {
            $channels['facebook'] = array(
                'url'   => $url,
                'label' => __('Facebook', 'epsilon'),
                'class' => 'pngm-contact-facebook',
            );
        }

        // Prefer m.me for Messenger; otherwise derive from profile username when possible.
        if ($channels['messenger'] === null) {
            if (preg_match('#m\.me/([^/?#]+)#i', $url, $mm)) {
                $channels['messenger'] = array(
                    'url'   => 'https://m.me/' . rawurlencode($mm[1]),
                    'label' => __('Messenger', 'epsilon'),
                    'class' => 'pngm-contact-messenger',
                );
            } elseif (preg_match('#facebook\.com/(?:profile\.php\?id=(\d+)|([^/?#]+))#i', $url, $fm)) {
                $handle = !empty($fm[1]) ? $fm[1] : $fm[2];
                if ($handle !== '' && !preg_match('/^(pages|groups|events|watch|share|sharer)$/i', $handle)) {
                    $channels['messenger'] = array(
                        'url'   => 'https://m.me/' . rawurlencode($handle),
                        'label' => __('Messenger', 'epsilon'),
                        'class' => 'pngm-contact-messenger',
                    );
                }
            }
        }
    }

    if ($user_id > 0 && function_exists('eps_user_public_profile_url')) {
        $channels['profile'] = array(
            'url'   => eps_user_public_profile_url($user_id),
            'label' => __('Seller profile', 'epsilon'),
            'class' => 'pngm-contact-profile',
        );

        $channels['listings'] = array(
            'url'   => osc_search_url(array('page' => 'search', 'userId' => $user_id)),
            'label' => $item_count > 0
                ? sprintf(__('Other listings (%d)', 'epsilon'), $item_count)
                : __('Other listings', 'epsilon'),
            'class' => 'pngm-contact-listings',
        );
    }

    $cache = $channels;
    return $cache;
}

/**
 * Render listing contact actions: Call | WhatsApp | Chat (mockup row).
 * Guests see nothing — no contact details and no login CTA in this block.
 */
function pngm_render_seller_contact_buttons()
{
    if (!function_exists('osc_is_ad_page') || !osc_is_ad_page()) {
        return;
    }

    if (!pngm_viewer_can_contact_seller()) {
        return;
    }

    $channels = pngm_seller_contact_channels();
    $item_id = (int) osc_item_id();
    $pref = function_exists('pngm_item_contact_pref') ? pngm_item_contact_pref($item_id) : 'message';

    $call = null;
    if (function_exists('eps_get_item_phone')) {
        $phone_data = eps_get_item_phone();
        if (!empty($phone_data['found']) && empty($phone_data['login_required'])) {
            $phone_class = !empty($phone_data['class']) ? trim((string) $phone_data['class']) : 'masked';
            $call = array(
                'key'   => 'call',
                'url'   => !empty($phone_data['url']) ? $phone_data['url'] : '#',
                'label' => __('Call', 'epsilon'),
                'class' => trim('pngm-action-call phone ' . $phone_class),
                'icon'  => 'fas fa-phone-alt',
                'attrs' => array(
                    'data-prefix' => 'tel',
                    'data-part1'  => isset($phone_data['part1']) ? $phone_data['part1'] : '',
                    'data-part2'  => isset($phone_data['part2']) ? $phone_data['part2'] : '',
                    'title'       => isset($phone_data['title']) ? $phone_data['title'] : __('Call', 'epsilon'),
                ),
            );
        }
    }

    $whatsapp = null;
    if (!empty($channels['whatsapp']['url'])) {
        $whatsapp = array(
            'key'   => 'whatsapp',
            'url'   => $channels['whatsapp']['url'],
            'label' => __('WhatsApp', 'epsilon'),
            'class' => 'pngm-action-whatsapp',
            'icon'  => 'fab fa-whatsapp',
            'attrs' => array(
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
                'title'  => __('WhatsApp', 'epsilon'),
            ),
        );
    }

    $chat = null;
    $item_row = function_exists('osc_item') ? osc_item() : array();
    $seller_id = is_array($item_row) && isset($item_row['fk_i_user_id']) ? (int) $item_row['fk_i_user_id'] : (int) osc_item_user_id();
    $is_own_listing = function_exists('osc_is_web_user_logged_in')
        && osc_is_web_user_logged_in()
        && $seller_id > 0
        && $seller_id === (int) osc_logged_user_id();

    if (function_exists('im_contact_button')) {
        if ($is_own_listing) {
            $chat = array(
                'key'   => 'message',
                'url'   => '#',
                'label' => __('Chat', 'epsilon'),
                'class' => 'pngm-action-chat',
                'icon'  => 'fas fa-comment-dots',
                'attrs' => array(
                    'title' => __('This is your listing. You cannot message yourself.', 'epsilon'),
                    'role' => 'button',
                    'data-pngm-chat-own' => '1',
                ),
            );
        } else {
            $im_url = im_contact_button($item_row, true);
            if ($im_url !== false && $im_url !== null && trim((string) $im_url) !== '' && trim((string) $im_url) !== '#') {
                $chat = array(
                    'key'   => 'message',
                    'url'   => $im_url,
                    'label' => __('Chat', 'epsilon'),
                    'class' => 'pngm-action-chat',
                    'icon'  => 'fas fa-comment-dots',
                    'attrs' => array(
                        'title' => __('Chat with seller', 'epsilon'),
                    ),
                );
            }
        }
    }

    $by_key = array();
    if ($call) {
        $by_key['call'] = $call;
    }
    if ($whatsapp) {
        $by_key['whatsapp'] = $whatsapp;
    }
    if ($chat) {
        $by_key['message'] = $chat;
    }

    if (empty($by_key)) {
        return;
    }

    // Put preferred channel first when available.
    $actions = array();
    if (isset($by_key[$pref])) {
        $actions[] = $by_key[$pref];
        unset($by_key[$pref]);
    }
    foreach (array('call', 'whatsapp', 'message') as $k) {
        if (isset($by_key[$k])) {
            $actions[] = $by_key[$k];
        }
    }

    $count = count($actions);

    echo '<div class="pngm-contact-panel pngm-item-detail-block">';
    echo '<h2 class="pngm-contact-title">' . osc_esc_html(__('Contact Seller', 'epsilon')) . '</h2>';
    echo '<div class="pngm-contact-actions pngm-contact-count-' . (int) $count . '">';

    foreach ($actions as $action) {
        $is_pref = (!empty($action['key']) && $action['key'] === $pref);
        $attr_html = '';
        if (!empty($action['attrs']) && is_array($action['attrs'])) {
            foreach ($action['attrs'] as $ak => $av) {
                $attr_html .= ' ' . $ak . '="' . osc_esc_html($av) . '"';
            }
        }
        $cls = 'pngm-contact-action ' . $action['class'] . ($is_pref ? ' is-preferred' : '');
        echo '<a class="' . osc_esc_html($cls) . '" href="' . osc_esc_html($action['url']) . '"' . $attr_html . '>';
        echo '<i class="' . osc_esc_html($action['icon']) . '" aria-hidden="true"></i>';
        echo '<span>' . osc_esc_html($action['label']) . '</span>';
        if ($is_pref) {
            echo '<em class="pngm-contact-action-badge">' . osc_esc_html(__('Preferred', 'epsilon')) . '</em>';
        }
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Extract simple keywords from listing title for similar search.
 *
 * @param string $title
 * @return string
 */
function pngm_similar_keywords($title)
{
    $title = function_exists('mb_strtolower')
        ? mb_strtolower(trim((string) $title), 'UTF-8')
        : strtolower(trim((string) $title));

    $title = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $title);
    $parts = preg_split('/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY);
    $stop = array(
        'the', 'and', 'for', 'with', 'from', 'this', 'that', 'your', 'you',
        'are', 'was', 'new', 'used', 'sale', 'sell', 'buy', 'png', 'only',
        'good', 'best', 'free', 'price', 'urgent',
    );

    $words = array();

    foreach ($parts as $w) {
        if (strlen($w) < 3 || in_array($w, $stop, true)) {
            continue;
        }
        $words[] = $w;
        if (count($words) >= 4) {
            break;
        }
    }

    return implode(' ', $words);
}

/**
 * Run a scoped item search and return rows.
 *
 * @param array $args
 * @return array
 */
function pngm_run_item_search($args)
{
    if (!class_exists('Search')) {
        return array();
    }

    $limit = isset($args['limit']) ? (int) $args['limit'] : 12;
    $exclude = isset($args['exclude']) ? (int) $args['exclude'] : 0;
    $mSearch = new Search();

    if (!empty($args['category'])) {
        $mSearch->addCategory((int) $args['category']);
    }

    if (!empty($args['city'])) {
        $mSearch->addCity((int) $args['city']);
    } elseif (!empty($args['region'])) {
        $mSearch->addRegion((int) $args['region']);
    }

    if (!empty($args['pattern'])) {
        $mSearch->addPattern($args['pattern']);
    }

    if (!empty($args['user'])) {
        $mSearch->fromUser((int) $args['user']);
    }

    if ($exclude > 0) {
        $mSearch->addItemConditions(sprintf('%st_item.pk_i_id <> %d', DB_TABLE_PREFIX, $exclude));
    }

    $mSearch->limit(0, max(1, $limit));
    $rows = $mSearch->doSearch();

    return is_array($rows) ? $rows : array();
}

/**
 * Merge unique item rows up to $limit.
 *
 * @param array $dest
 * @param array $src
 * @param int   $limit
 * @param int   $exclude_id
 * @return array
 */
function pngm_merge_items($dest, $src, $limit, $exclude_id)
{
    $seen = array();

    foreach ($dest as $row) {
        if (isset($row['pk_i_id'])) {
            $seen[(int) $row['pk_i_id']] = true;
        }
    }

    foreach ($src as $row) {
        if (!isset($row['pk_i_id'])) {
            continue;
        }

        $id = (int) $row['pk_i_id'];

        if ($id === (int) $exclude_id || isset($seen[$id])) {
            continue;
        }

        $seen[$id] = true;
        $dest[] = $row;

        if (count($dest) >= $limit) {
            break;
        }
    }

    return $dest;
}

/**
 * Similar listings: subcategory → category+city/region → keywords (ITEM-04).
 *
 * @param int $limit
 * @return array
 */
function pngm_get_similar_listings($limit = 12)
{
    $limit = max(1, (int) $limit);
    $item_id = (int) osc_item_id();
    $cat_id = (int) osc_item_category_id();
    $city_id = (int) osc_item_city_id();
    $region_id = (int) osc_item_region_id();
    $parent_id = 0;
    $keywords = pngm_similar_keywords(osc_item_title());

    if ($cat_id > 0 && class_exists('Category')) {
        $cat = Category::newInstance()->findByPrimaryKey($cat_id);
        if (is_array($cat) && !empty($cat['fk_i_parent_id'])) {
            $parent_id = (int) $cat['fk_i_parent_id'];
        }
    }

    $items = array();

    // 1) Same subcategory + same city
    if ($cat_id > 0 && $city_id > 0) {
        $items = pngm_merge_items($items, pngm_run_item_search(array(
            'category' => $cat_id,
            'city'     => $city_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        )), $limit, $item_id);
    }

    // 2) Same subcategory + same region
    if (count($items) < $limit && $cat_id > 0 && $region_id > 0) {
        $items = pngm_merge_items($items, pngm_run_item_search(array(
            'category' => $cat_id,
            'region'   => $region_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        )), $limit, $item_id);
    }

    // 3) Same subcategory only
    if (count($items) < $limit && $cat_id > 0) {
        $items = pngm_merge_items($items, pngm_run_item_search(array(
            'category' => $cat_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        )), $limit, $item_id);
    }

    // 4) Parent category + location
    if (count($items) < $limit && $parent_id > 0) {
        $args = array(
            'category' => $parent_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        );
        if ($city_id > 0) {
            $args['city'] = $city_id;
        } elseif ($region_id > 0) {
            $args['region'] = $region_id;
        }
        $items = pngm_merge_items($items, pngm_run_item_search($args), $limit, $item_id);
    }

    // 5) Keyword / pattern fallback within parent or category
    if (count($items) < $limit && $keywords !== '') {
        $args = array(
            'pattern' => $keywords,
            'exclude' => $item_id,
            'limit'   => $limit,
        );
        if ($parent_id > 0) {
            $args['category'] = $parent_id;
        } elseif ($cat_id > 0) {
            $args['category'] = $cat_id;
        }
        $items = pngm_merge_items($items, pngm_run_item_search($args), $limit, $item_id);
    }

    return array_slice($items, 0, $limit);
}

/**
 * Render similar listings block (ITEM-04).
 *
 * @param string $card_type
 * @param int    $limit
 */
function pngm_similar_ads($card_type = 'normal', $limit = 0)
{
    if ($limit <= 0) {
        $limit = (function_exists('eps_param') && eps_param('related_count') > 0)
            ? (int) eps_param('related_count')
            : 12;
    }

    if ($card_type === '' && function_exists('eps_param')) {
        $card_type = eps_param('related_design') !== '' ? eps_param('related_design') : 'tall';
    }

    $aItems = pngm_get_similar_listings($limit);
    $default_items = View::newInstance()->_get('items');
    View::newInstance()->_exportVariableToView('items', $aItems);

    if (osc_count_items() > 0 && function_exists('eps_draw_item')) {
        ?>
        <div id="rel-block" class="related type-category pngm-similar">
          <h2><?php _e('Similar listings', 'epsilon'); ?></h2>
          <div class="nice-scroll-wrap nice-scroll-have-overflow">
            <div class="nice-scroll-prev"><span class="mover"><i class="fas fa-caret-left"></i></span></div>
            <div class="products grid nice-scroll no-visible-scroll">
              <?php
                $c = 1;
                while (osc_has_items()) {
                    eps_draw_item($c, false, $card_type);
                    $c++;
                }
              ?>
            </div>
            <div class="nice-scroll-next"><span class="mover"><i class="fas fa-caret-right"></i></span></div>
          </div>
        </div>
        <?php
    }

    View::newInstance()->_exportVariableToView('items', $default_items);
}

/**
 * Other listings from the same seller (ITEM-03).
 *
 * @param string $card_type
 * @param int    $limit
 */
function pngm_seller_other_ads($card_type = 'normal', $limit = 8)
{
    $user_id = (int) osc_item_user_id();

    if ($user_id <= 0 || !function_exists('eps_related_ads')) {
        return;
    }

    eps_related_ads('user', $card_type, $limit, 'pngm-seller-other');
}

/* Contact row is rendered directly in item.php after Location (mockup order). */
