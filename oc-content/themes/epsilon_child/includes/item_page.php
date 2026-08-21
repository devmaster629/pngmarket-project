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

    $user_id = (int) osc_item_user_id();
    $item_count = 0;

    // Instant Messenger owns chat. Do not duplicate the standard Message button.

    // WhatsApp from listing / seller phones (plugin or fallback wa.me).
    $phones = array();
    if (function_exists('eps_get_item_phone')) {
        $p = eps_get_item_phone();
        if (!empty($p['found']) && empty($p['login_required']) && !empty($p['phone'])) {
            $phones[] = $p['phone'];
        }
    }

    if ($user_id > 0 && class_exists('User')) {
        $user = User::newInstance()->findByPrimaryKey($user_id);
        if (is_array($user)) {
            if (!empty($user['s_phone_mobile'])) {
                $phones[] = $user['s_phone_mobile'];
            }
            if (!empty($user['s_phone_land'])) {
                $phones[] = $user['s_phone_land'];
            }
            $item_count = isset($user['i_items']) ? (int) $user['i_items'] : 0;
        }
    }

    if (function_exists('wac_get_phone')) {
        $wac_phone = wac_get_phone(osc_item_id());
        if (!empty($wac_phone)) {
            array_unshift($phones, $wac_phone);
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
 */
function pngm_render_seller_contact_buttons()
{
    if (!function_exists('osc_is_ad_page') || !osc_is_ad_page()) {
        return;
    }

    $channels = pngm_seller_contact_channels();

    $call = null;
    if (function_exists('eps_get_item_phone')) {
        $phone_data = eps_get_item_phone();
        if (!empty($phone_data['found']) && empty($phone_data['login_required'])) {
            $call = array(
                'url'   => !empty($phone_data['url']) ? $phone_data['url'] : '#',
                'label' => __('Call', 'epsilon'),
                'class' => 'pngm-action-call',
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
    if (function_exists('im_contact_button')) {
        $im_url = im_contact_button(osc_item(), true);
        if ($im_url !== false && $im_url !== null && $im_url !== '') {
            $chat = array(
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

    $actions = array_filter(array($call, $whatsapp, $chat));
    if (empty($actions)) {
        return;
    }

    $count = count($actions);
    echo '<div class="pngm-contact-panel">';
    echo '<div class="pngm-contact-actions pngm-contact-count-' . (int) $count . '">';

    foreach ($actions as $action) {
        $attr_html = '';
        if (!empty($action['attrs']) && is_array($action['attrs'])) {
            foreach ($action['attrs'] as $ak => $av) {
                $attr_html .= ' ' . $ak . '="' . osc_esc_html($av) . '"';
            }
        }
        echo '<a class="pngm-contact-action ' . osc_esc_html($action['class']) . '" href="' . osc_esc_html($action['url']) . '"' . $attr_html . '>';
        echo '<i class="' . osc_esc_html($action['icon']) . '" aria-hidden="true"></i>';
        echo '<span>' . osc_esc_html($action['label']) . '</span>';
        echo '</a>';
    }

    echo '</div>';
    echo '<p class="pngm-contact-note">' . osc_esc_html(__('Your phone number is safe with us', 'epsilon')) . '</p>';
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

if (function_exists('osc_add_hook')) {
    osc_add_hook('item_contact', 'pngm_render_seller_contact_buttons', 8);
}
